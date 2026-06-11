<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SpaceModel — tabla `spaces`: los AMBIENTES del usuario (perfiles de rangos).
 *
 * Un ambiente define qué es "normal" para un espacio: rangos ideales de
 * temperatura y humedad y el límite de CO₂. Cada medición se compara contra
 * estos rangos para decidir el estado (normal/advertencia/crítico) y para
 * disparar la automatización. Desde el Hito 2 un usuario puede tener VARIOS.
 * No tiene métodos propios: controllers y services usan el query builder.
 */
class SpaceModel extends Model
{
    // Configuracion base del modelo y tabla de ambientes configurados por usuario.
    protected $table            = 'spaces';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'user_id',
        'environment_type',
        'custom_name',
        'min_temperature',
        'max_temperature',
        'min_humidity',
        'max_humidity',
        'max_co2',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO (solo configuración, sin métodos propios)
   - environment_type    → tipo de ambiente: oficina, aula, hogar, dormitorio...
   - custom_name         → nombre propio si el usuario lo personalizó
   - min/max_temperature → rango ideal de temperatura (°C)
   - min/max_humidity    → rango ideal de humedad (%)
   - max_co2             → límite de CO₂ (ppm) antes de considerarlo alto
   ============================================================================ */
