<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DevicePairingModel — tabla `device_pairings`: las ventanas de vinculación.
 *
 * Una fila = una vez que el usuario apretó "Conectar". Mientras la ventana
 * está abierta (`esperando` y sin vencer), el primer equipo que se presente a
 * la API queda dado de alta en esa cuenta. Después la fila queda como registro
 * histórico (`vinculado`, `expirado` o `cancelado`).
 */
class DevicePairingModel extends Model
{
    protected $table            = 'device_pairings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'token',
        'session_code',
        'ap_ssid',
        'ap_password',
        'status',
        'device_id',
        'client_ip',
        'expires_at',
        'completed_at',
    ];

    // -------------------------------------------------------------------------
    // Consultas propias
    // -------------------------------------------------------------------------

    /** Busca una ventana por su token (la usa el navegador para preguntar el estado). */
    public function buscarPorToken(string $token): ?array
    {
        return $this->where('token', $token)->first();
    }

    /**
     * Busca una ventana por el código de sesión que inventó la ESP32.
     *
     * Lo usa el celular al volver de configurar el WiFi: es lo único que
     * lleva encima, porque en ese teléfono nadie inició sesión.
     */
    public function buscarPorSesion(string $sesion): ?array
    {
        return $this->where('session_code', $sesion)->first();
    }

    /**
     * Ventanas abiertas ahora mismo: estado `esperando` y todavía sin vencer.
     * Vienen de la más nueva a la más vieja.
     */
    public function abiertas(): array
    {
        return $this->where('status', 'esperando')
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /** Cierra las ventanas que quedaron colgadas (nadie conectó el equipo a tiempo). */
    public function marcarVencidas(): int
    {
        $this->where('status', 'esperando')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->set(['status' => 'expirado', 'updated_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->db->affectedRows();
    }

    /** Cierra las ventanas que el usuario todavía tenía abiertas (una por vez). */
    public function cancelarAbiertasDe(int $userId): void
    {
        $this->where('user_id', $userId)
            ->where('status', 'esperando')
            ->set(['status' => 'cancelado', 'updated_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    // -------------------------------------------------------------------------
    // Limpieza (`php spark edenair:limpiar`)
    // La tabla no tiene clave foránea a `users`: al borrar una cuenta, sus
    // ventanas no se van solas.
    // -------------------------------------------------------------------------

    /** Borra todas las ventanas de un usuario (se usa al borrar su cuenta). */
    public function borrarDeUsuario(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }

    /** Cuántas ventanas vencieron antes de $limite (lo que borraría la limpieza). */
    public function contarVencidasAntesDe(string $limite): int
    {
        return $this->where('expires_at <', $limite)->countAllResults();
    }

    /**
     * Borra las ventanas que vencieron antes de $limite. Una ventana dura
     * minutos: pasado eso ya no la consulta nadie, y guarda la clave del WiFi
     * de configuración del equipo, que no tiene por qué quedar para siempre.
     */
    public function borrarVencidasAntesDe(string $limite): int
    {
        $this->where('expires_at <', $limite)->delete();

        return $this->db->affectedRows();
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - buscarPorToken($token)   → la ventana que abrió ese navegador
   - buscarPorSesion($cod)    → la ventana por el código que invento la ESP32
                                (lo usa el celular, que no tiene sesión)
   - abiertas()               → ventanas vigentes (esperando y sin vencer)
   - marcarVencidas()         → pasa a 'expirado' las que se pasaron de hora
   - cancelarAbiertasDe($id)  → cierra las anteriores del mismo usuario, para
                                que nunca tenga dos ventanas compitiendo
   - borrarDeUsuario($id)     → borra las ventanas de una cuenta que se borra
   - contarVencidasAntesDe()  → cuántas ventanas viejas hay (para avisar)
   - borrarVencidasAntesDe()  → borra las ventanas viejas (limpieza)
   - set()/update() sin id    → (CI4) UPDATE masivo con el where acumulado
   - affectedRows()           → (CI4) cuántas filas tocó el último UPDATE
   ============================================================================ */
