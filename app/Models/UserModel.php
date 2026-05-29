<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
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

    public function obtenerPorId(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        return $this->find($userId);
    }

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

    public function existeCorreoOUsuario(string $email, string $usuario): bool
    {
        return $this->groupStart()
            ->where('email', strtolower(trim($email)))
            ->orWhere('usuario', trim($usuario))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    public function existeCorreoOUsuarioExcepto(int $userId, string $email, string $usuario): bool
    {
        return $this->where('id !=', $userId)
            ->groupStart()
            ->where('email', strtolower(trim($email)))
            ->orWhere('usuario', trim($usuario))
            ->groupEnd()
            ->countAllResults() > 0;
    }

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

    public function actualizarPerfil(int $userId, array $datos): bool
    {
        return $this->update($userId, [
            'nombre'   => trim((string) ($datos['nombre'] ?? '')),
            'apellido' => trim((string) ($datos['apellido'] ?? '')),
            'email'    => strtolower(trim((string) ($datos['email'] ?? ''))),
            'usuario'  => trim((string) ($datos['usuario'] ?? '')),
        ]);
    }

    public function actualizarHashContrasena(int $userId, string $password): bool
    {
        return $this->update($userId, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public function guardarToken(int $userId, string $token, string $expiresAt): bool
    {
        return $this->update($userId, [
            'reset_token'      => $this->hashToken($token),
            'reset_expires_at' => $expiresAt,
        ]);
    }

    public function buscarPorToken(string $token): ?array
    {
        if (trim($token) === '') {
            return null;
        }

        return $this->where('reset_token', $this->hashToken($token))
            ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
            ->first();
    }

    public function actualizarPasswordConToken(int $userId, string $password): bool
    {
        return $this->update($userId, [
            'password_hash'    => password_hash($password, PASSWORD_DEFAULT),
            'reset_token'      => null,
            'reset_expires_at' => null,
        ]);
    }

    public function limpiarTokenRecuperacion(int $userId): bool
    {
        return $this->update($userId, [
            'reset_token'      => null,
            'reset_expires_at' => null,
        ]);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', trim($token));
    }
}
