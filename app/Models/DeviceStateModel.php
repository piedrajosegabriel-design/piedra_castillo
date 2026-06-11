<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DeviceStateModel — tabla `device_states`: el estado ACTUAL de cada dispositivo.
 *
 * Hay UNA fila por dispositivo (relación 1:1) y se va actualizando: en qué
 * modo está (automatic/manual), cómo está cada actuador (on/off), por qué
 * quedó así (last_reason) y quién lo cambió (updated_by).
 * No tiene métodos propios: los services la leen/escriben con el query builder.
 */
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

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO (solo configuración, sin métodos propios)
   - operating_mode       → 'automatic' (el sistema decide) o 'manual' (el usuario)
   - fan_state / aromatizer_state / alert_led_state → 'on'/'off' de cada actuador
   - last_reason          → texto con la última regla/causa del cambio
   - updated_by           → origen del cambio: web / automation / device-api...
   - $allowedFields       → lista blanca de columnas escribibles
   - $useTimestamps       → created_at/updated_at automáticos
   ============================================================================ */
