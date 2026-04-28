<?php

namespace App\Models;

use CodeIgniter\Model;

// Modelo encargado de leer y guardar usuarios en la tabla users.
class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = ['nombre', 'email', 'usuario', 'password_hash'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
