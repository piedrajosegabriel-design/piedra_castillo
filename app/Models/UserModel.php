<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel — tabla `users`: las cuentas del sistema.
 *
 * Concentra TODO el acceso a datos de usuarios: búsqueda para login,
 * unicidad de email/usuario, creación (hasheando la contraseña),
 * edición de perfil y el ciclo completo del token de recuperación.
 * Regla de oro: la contraseña NUNCA se guarda en claro, siempre su hash.
 */
class UserModel extends Model
{
    // -------------------------------------------------------------------------
    // Configuración base del modelo
    // -------------------------------------------------------------------------
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'nombre',
        'apellido',
        'email',
        'usuario',
        'password_hash',
        'reset_token',
        'reset_expires_at',
    ];

    // -------------------------------------------------------------------------
    // Búsquedas
    // -------------------------------------------------------------------------

    /** Busca un usuario por id; null si el id no es válido o no existe. */
    public function obtenerPorId(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        return $this->find($userId);
    }

    /**
     * Busca por email O por nombre de usuario (permite loguear con cualquiera).
     * groupStart/groupEnd agrupan el OR entre paréntesis en el SQL.
     */
    public function buscarParaLogin(string $identificador): ?array
    {
        $identificador = trim($identificador);

        if ($identificador === '') {
            return null;
        }

        return $this->groupStart()
            ->where('email', strtolower($identificador))
            ->orWhere('usuario', $identificador)
            ->groupEnd()
            ->first();
    }

    // -------------------------------------------------------------------------
    // Unicidad (email y usuario no pueden repetirse)
    // -------------------------------------------------------------------------

    /** ¿Ya existe alguien con ese email o ese nombre de usuario? (registro) */
    public function existeCorreoOUsuario(string $email, string $usuario): bool
    {
        return $this->groupStart()
            ->where('email', strtolower(trim($email)))
            ->orWhere('usuario', trim($usuario))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    /** Igual que el anterior pero ignorando al propio usuario (editar perfil). */
    public function existeCorreoOUsuarioExcepto(int $userId, string $email, string $usuario): bool
    {
        return $this->where('id !=', $userId)
            ->groupStart()
            ->where('email', strtolower(trim($email)))
            ->orWhere('usuario', trim($usuario))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    // -------------------------------------------------------------------------
    // Escritura: crear cuenta y editar perfil/contraseña
    // -------------------------------------------------------------------------

    /** Crea la cuenta. La contraseña se hashea acá (bcrypt) antes de guardar. */
    public function crearUsuario(array $datos): int
    {
        $this->insert([
            'nombre'        => trim((string) ($datos['nombre'] ?? '')),
            'apellido'      => trim((string) ($datos['apellido'] ?? '')),
            'email'         => strtolower(trim((string) ($datos['email'] ?? ''))),
            'usuario'       => trim((string) ($datos['usuario'] ?? '')),
            'password_hash' => password_hash((string) ($datos['password'] ?? ''), PASSWORD_DEFAULT),
        ]);

        return (int) $this->getInsertID();
    }

    /** Actualiza los datos visibles del perfil (no toca la contraseña). */
    public function actualizarPerfil(int $userId, array $datos): bool
    {
        return $this->update($userId, [
            'nombre'   => trim((string) ($datos['nombre'] ?? '')),
            'apellido' => trim((string) ($datos['apellido'] ?? '')),
            'email'    => strtolower(trim((string) ($datos['email'] ?? ''))),
            'usuario'  => trim((string) ($datos['usuario'] ?? '')),
        ]);
    }

    /** Reemplaza el hash de contraseña (cambio de clave o rehash automático). */
    public function actualizarHashContrasena(int $userId, string $password): bool
    {
        return $this->update($userId, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * Anota que el usuario acaba de iniciar sesión.
     * Va por el builder y no por update() para no mover `updated_at`: esa
     * fecha sigue diciendo cuándo cambiaron los datos de la cuenta.
     */
    public function registrarIngreso(int $userId): void
    {
        $this->builder()
            ->where('id', $userId)
            ->update(['last_login_at' => date('Y-m-d H:i:s')]);
    }

    // -------------------------------------------------------------------------
    // Cuentas sin uso (las borra `php spark edenair:limpiar`)
    // -------------------------------------------------------------------------

    /**
     * Cuentas que nadie usa desde $limite (fecha 'Y-m-d H:i:s'):
     * - no iniciaron sesión desde entonces (si nunca se anotó el ingreso,
     *   cuenta la fecha de registro),
     * - ninguno de sus equipos reportó desde entonces (o no tienen equipos),
     * - y nunca iniciaron una compra: los registros de pagos no se tocan.
     *
     * Una cuenta sin fecha de registro ni de ingreso no entra: sin datos,
     * mejor no borrar.
     */
    public function sinUsoDesde(string $limite): array
    {
        // equipos y mediciones son solo para mostrar qué se va con la cuenta.
        $sql = 'SELECT u.id, u.usuario, u.email, u.created_at, u.last_login_at,
                       (SELECT COUNT(*) FROM devices d WHERE d.user_id = u.id) AS equipos,
                       (SELECT COUNT(*) FROM measurements m WHERE m.user_id = u.id) AS mediciones
                FROM users u
                WHERE COALESCE(u.last_login_at, u.created_at) < ?
                  AND NOT EXISTS (
                      SELECT 1 FROM devices d
                      WHERE d.user_id = u.id
                        AND COALESCE(d.last_seen_at, d.created_at) >= ?
                  )
                  AND NOT EXISTS (SELECT 1 FROM purchases p WHERE p.user_id = u.id)
                ORDER BY u.id';

        return $this->db->query($sql, [$limite, $limite])->getResultArray();
    }

    // -------------------------------------------------------------------------
    // Recuperación de contraseña (token por email)
    // El token viaja en claro en el enlace, pero en la base se guarda HASHEADO
    // (SHA-256): si alguien roba la base, no puede usar los tokens.
    // -------------------------------------------------------------------------

    /** Guarda el token (hasheado) con su fecha de vencimiento. */
    public function guardarToken(int $userId, string $token, string $expiresAt): bool
    {
        return $this->update($userId, [
            'reset_token'      => $this->hashToken($token),
            'reset_expires_at' => $expiresAt,
        ]);
    }

    /** Busca al usuario por token vigente (compara hashes y vencimiento). */
    public function buscarPorToken(string $token): ?array
    {
        if (trim($token) === '') {
            return null;
        }

        return $this->where('reset_token', $this->hashToken($token))
            ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
            ->first();
    }

    /** Guarda la nueva contraseña y anula el token (es de un solo uso). */
    public function actualizarPasswordConToken(int $userId, string $password): bool
    {
        return $this->update($userId, [
            'password_hash'    => password_hash($password, PASSWORD_DEFAULT),
            'reset_token'      => null,
            'reset_expires_at' => null,
        ]);
    }

    /** Borra el token de recuperación sin tocar la contraseña. */
    public function limpiarTokenRecuperacion(int $userId): bool
    {
        return $this->update($userId, [
            'reset_token'      => null,
            'reset_expires_at' => null,
        ]); 
    }

    /** Hash SHA-256 del token: lo que realmente se guarda y se compara. */
    private function hashToken(string $token): string
    {
        return hash('sha256', trim($token));
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Búsquedas:
   - obtenerPorId($id)            → fila del usuario o null
   - buscarParaLogin($id)         → busca por email O usuario (para el login)

   Unicidad:
   - existeCorreoOUsuario()       → true si email/usuario ya están tomados
   - existeCorreoOUsuarioExcepto()→ ídem pero excluyendo al propio usuario

   Escritura:
   - crearUsuario($datos)         → INSERT con password_hash(); devuelve el id
   - actualizarPerfil()           → UPDATE de nombre/apellido/email/usuario
   - actualizarHashContrasena()   → UPDATE solo del hash de contraseña
   - registrarIngreso()           → anota last_login_at (sin tocar updated_at)

   Cuentas sin uso:
   - sinUsoDesde($limite)         → cuentas sin ingresos ni equipos activos
                                    desde esa fecha y sin compras

   Recuperación de contraseña:
   - guardarToken()               → guarda hash del token + vencimiento
   - buscarPorToken()             → usuario con token vigente o null
   - actualizarPasswordConToken() → nueva contraseña + invalida el token
   - limpiarTokenRecuperacion()   → anula el token sin cambiar la contraseña
   - hashToken() (privado)        → SHA-256 del token

   Métodos de CI4 Model usados acá:
   - find($id)/first()/countAllResults() → ejecutar la consulta armada
   - where()/orWhere()/groupStart()/groupEnd() → query builder (el group
     agrupa condiciones entre paréntesis para que el OR no se "escape")
   - insert($datos)/update($id, $datos)  → INSERT/UPDATE respetando allowedFields
   - getInsertID()                → id autogenerado del último INSERT

   Funciones de PHP:
   - password_hash($p, PASSWORD_DEFAULT) → hash bcrypt de la contraseña
   - hash('sha256', $t)           → hash del token de recuperación
   ============================================================================ */
