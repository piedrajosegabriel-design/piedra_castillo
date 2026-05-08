<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['nombre', 'email', 'usuario', 'password_hash'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function buscarParaLogin(string $login): ?array
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        return $this->groupStart()
            ->where('usuario', $login)
            ->orWhere('email', strtolower($login))
            ->groupEnd()
            ->first();
    }

    public function existeCorreoOUsuario(string $email, string $username): bool
    {
        return $this->groupStart()
            ->where('email', strtolower(trim($email)))
            ->orWhere('usuario', trim($username))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    public function crearUsuario(array $data): int
    {
        return (int) $this->insert([
            'nombre'        => trim((string) ($data['nombre'] ?? '')),
            'email'         => strtolower(trim((string) ($data['email'] ?? ''))),
            'usuario'       => trim((string) ($data['usuario'] ?? '')),
            'password_hash' => password_hash((string) ($data['password'] ?? ''), PASSWORD_DEFAULT),
        ]);
    }

    public function actualizarHashContrasena(int $userId, string $plainPassword): void
    {
        $this->update($userId, [
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
    }
}
