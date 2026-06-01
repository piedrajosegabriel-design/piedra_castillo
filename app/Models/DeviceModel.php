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
        'device_type',
        'device_uid',
        'api_token',
        'is_simulated',
        'is_active',
        'status',
        'mac_address',
        'activation_code',
        'notes',
        'last_seen_at',
        'last_command_sync_at',
    ];

    /** Todos los dispositivos del usuario con el nombre de su ambiente. */
    public function obtenerDeUsuario(int $userId): array
    {
        return $this->select('devices.*, spaces.environment_type, spaces.custom_name')
            ->join('spaces', 'spaces.id = devices.space_id', 'left')
            ->where('devices.user_id', $userId)
            ->orderBy('devices.created_at', 'ASC')
            ->findAll();
    }
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
