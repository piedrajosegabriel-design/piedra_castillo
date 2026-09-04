<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MeasurementModel — tabla `measurements`: el HISTORIAL de mediciones.
 *
 * Es la fuente de datos del dashboard: cada fila es una lectura de
 * temperatura, humedad, CO₂ y calidad de aire, con su origen (source) y
 * el momento en que se capturó. Acá escriben el ESP32 (vía API), el form
 * manual del panel y el simulador.
 * No tiene métodos propios: los services arman sus propias consultas.
 */
class MeasurementModel extends Model
{
    // Configuracion base del modelo y tabla historica de mediciones ambientales.
    protected $table            = 'measurements';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'device_id',
        'user_id',
        'space_id',
        'source',
        'temperature',
        'humidity',
        'co2_ppm',
        'air_quality_index',
        'air_quality_label',
        'notes',
        'captured_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO (solo configuración, sin métodos propios)
   - source              → de dónde vino la lectura: 'api' (ESP32), 'web'
                           (carga manual), 'seed'/'sim' (simulada)
   - temperature/humidity→ °C y % de humedad relativa
   - co2_ppm             → CO₂ en partes por millón
   - air_quality_index   → índice 0–100 calculado; air_quality_label = texto
   - captured_at         → momento real de la medición (≠ created_at)
   ============================================================================ */
