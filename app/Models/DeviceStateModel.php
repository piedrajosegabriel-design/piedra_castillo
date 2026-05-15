<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceStateModel extends Model
{
    // Configuracion base del modelo y tabla del estado actual del dispositivo.
    protected $table            = 'device_states';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'device_id',
        'operating_mode',
        'fan_state',
        'aromatizer_state',
        'alert_led_state',
        'last_reason',
        'updated_by',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
