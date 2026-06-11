<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DeviceCommandModel — tabla `device_commands`: la COLA de comandos al ESP32.
 *
 * Cada fila es una orden ("prendé el ventilador") con su origen (web o
 * automation), su estado (pending/executed/cancelled) y cuándo se ejecutó.
 * El ESP32 consulta los pendientes por la API y los confirma al ejecutarlos.
 * No tiene métodos propios: CommandService maneja toda la lógica.
 */
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

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO (solo configuración, sin métodos propios)
   - command_type / target_value → qué hacer y con qué valor (ej: fan → on)
   - source              → quién originó el comando: 'web' o 'automation'
   - issued_by_user_id   → usuario que lo pidió (si fue una persona)
   - payload             → JSON extra (incluye 'reason': el porqué del comando)
   - status              → 'pending' / 'executed' / 'cancelled'
   - executed_at         → cuándo lo confirmó el dispositivo
   ============================================================================ */
