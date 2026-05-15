<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    // =========================================================================
    // CONFIGURACION DEL MODELO
    // =========================================================================
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['nombre', 'email', 'usuario', 'password_hash'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    // =========================================================================
    // CONSULTAS PARA ACCESO
    // =========================================================================
    public function buscarParaLogin(string $login): ?array
    {
        $login = $this->normalizarTexto($login);

        if ($login === '') {
            return null;
        }

        return $this->groupStart()
            ->where('usuario', $login)
            ->orWhere('email', $this->normalizarEmail($login))
            ->groupEnd()
            ->first();
    }

    public function existeCorreoOUsuario(string $email, string $username): bool
    {
        return $this->groupStart()
            ->where('email', $this->normalizarEmail($email))
            ->orWhere('usuario', $this->normalizarTexto($username))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    // =========================================================================
    // CREACION Y ACTUALIZACION
    // =========================================================================
    public function crearUsuario(array $data): int
    {
        return (int) $this->insert($this->crearDatosUsuario($data));
    }

    public function actualizarHashContrasena(int $userId, string $plainPassword): void
    {
        $this->update($userId, [
            'password_hash' => $this->crearHashContrasena($plainPassword),
        ]);
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================
    private function crearDatosUsuario(array $data): array
    {
        return [
            'nombre'        => $this->normalizarTexto((string) ($data['nombre'] ?? '')),
            'email'         => $this->normalizarEmail((string) ($data['email'] ?? '')),
            'usuario'       => $this->normalizarTexto((string) ($data['usuario'] ?? '')),
            'password_hash' => $this->crearHashContrasena((string) ($data['password'] ?? '')),
        ];
    }

    private function normalizarTexto(string $value): string
    {
        return trim($value);
    }

    private function normalizarEmail(string $value): string
    {
        return strtolower($this->normalizarTexto($value));
    }

    private function crearHashContrasena(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }
}
