<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceModel extends Model
{
    // Configuracion base del modelo y tabla de dispositivos asociados al usuario.
    protected $table            = 'devices';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'user_id',
        'space_id',
        'name',
        'device_uid',
        'api_token',
        'is_simulated',
        'is_active',
        'last_seen_at',
        'last_command_sync_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
