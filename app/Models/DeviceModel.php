<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DeviceModel — tabla `devices`: los dispositivos EdenAir (ESP32) del usuario.
 *
 * Cada fila es un equipo físico (o simulado/demo) vinculado a una cuenta
 * y a un ambiente (space). Guarda sus credenciales de API (device_uid público
 * + api_token secreto) y metadatos de actividad.
 */
class DeviceModel extends Model
{
    // -------------------------------------------------------------------------
    // Configuración base del modelo (tabla, clave, timestamps automáticos)
    // -------------------------------------------------------------------------
    protected $table            = 'devices';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    // Lista blanca de columnas que se pueden insertar/actualizar masivamente.
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

    // -------------------------------------------------------------------------
    // Consultas propias
    // -------------------------------------------------------------------------

    /** Todos los dispositivos del usuario con el nombre de su ambiente. */
    public function obtenerDeUsuario(int $userId): array
    {
        return $this->select('devices.*, spaces.environment_type, spaces.custom_name')
            ->join('spaces', 'spaces.id = devices.space_id', 'left')
            ->where('devices.user_id', $userId)
            ->orderBy('devices.created_at', 'ASC')
            ->findAll();
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - obtenerDeUsuario($userId) → SELECT con JOIN a spaces: cada dispositivo
                                 sale con environment_type y custom_name de
                                 su ambiente (para mostrar nombres legibles)
   - select()/join()/where()/orderBy()/findAll() → (CI4 query builder) arman
     la consulta SQL por partes y la ejecutan
   - join(..., 'left')  → LEFT JOIN: trae el dispositivo aunque no tenga ambiente
   - $allowedFields     → lista blanca: insert()/update() ignoran cualquier
                          columna que no esté acá (protege contra mass assignment)
   - $useTimestamps     → CI4 completa created_at/updated_at automáticamente
   ============================================================================ */
