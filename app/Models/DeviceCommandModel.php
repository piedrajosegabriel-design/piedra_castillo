<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceCommandModel extends Model
{
    // Configuracion base del modelo y tabla de comandos enviados al dispositivo.
    protected $table            = 'device_commands';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'device_id',
        'issued_by_user_id',
        'source',
        'command_type',
        'target_value',
        'payload',
        'status',
        'executed_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
