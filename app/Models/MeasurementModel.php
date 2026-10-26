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
 * Las consultas del dashboard las arman los services; acá solo vive el
 * recorte que deja las últimas 15 lecturas de cada equipo.
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
        // 'sensor' (lo midió el MQ-135) o 'calculado' (fórmula de respaldo).
        'air_quality_source',
        'notes',
        'captured_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    // -------------------------------------------------------------------------
    // Solo se guardan las últimas lecturas de cada equipo
    // El panel muestra las 6 más nuevas y la portada la última: el resto no lo
    // lee nadie. Se recorta cada vez que llega una medición
    // (MeasurementService::registrar) y, para equipos que dejaron de reportar,
    // con `php spark edenair:limpiar`.
    // Siempre de a un equipo: así las consultas usan el índice
    // (device_id, captured_at) en vez de recorrer la tabla entera.
    // -------------------------------------------------------------------------

    /** Cuántas lecturas se guardan por equipo: las 6 del panel y margen. */
    public const POR_EQUIPO = 15;

    /** De a cuántas filas se borra: un DELETE gigante traba la tabla. */
    private const TANDA = 5000;

    /** Cuántas lecturas de ese equipo sobran (las que borraría conservarUltimas). */
    public function contarSobrantes(int $deviceId): int
    {
        $corte = $this->fechaDeCorte($deviceId);

        if ($corte === null) {
            return 0;
        }

        return $this->where('device_id', $deviceId)
            ->where('captured_at <', $corte)
            ->countAllResults();
    }

    /**
     * Deja solo las últimas POR_EQUIPO lecturas del equipo y devuelve cuántas
     * borró. Normalmente es una sola (la que quedó de más con la nueva); la
     * primera vez puede ser un montón, por eso va en tandas.
     */
    public function conservarUltimas(int $deviceId): int
    {
        $corte = $this->fechaDeCorte($deviceId);

        if ($corte === null) {
            return 0;
        }

        $total = 0;

        do {
            $this->builder()
                ->where('device_id', $deviceId)
                ->where('captured_at <', $corte)
                ->delete('', self::TANDA);

            $borradas = $this->db->affectedRows();
            $total += $borradas;
        } while ($borradas === self::TANDA);

        return $total;
    }

    /**
     * captured_at de la lectura número POR_EQUIPO contando desde la más nueva,
     * o null si el equipo no tiene tantas. Lo anterior a esa fecha sobra.
     * Si dos lecturas comparten el mismo segundo justo en el corte, quedan las
     * dos: alguna fila de más, nunca una de menos.
     */
    private function fechaDeCorte(int $deviceId): ?string
    {
        $fila = $this->builder()
            ->select('captured_at')
            ->where('device_id', $deviceId)
            ->orderBy('captured_at', 'DESC')
            ->limit(1, self::POR_EQUIPO - 1)
            ->get()
            ->getRowArray();

        return $fila['captured_at'] ?? null;
    }
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO
   - source              → de dónde vino la lectura: 'api' (ESP32), 'web'
                           (carga manual), 'seed'/'sim' (simulada)
   - temperature/humidity→ °C y % de humedad relativa
   - co2_ppm             → CO₂ en partes por millón
   - air_quality_index   → índice 0–100; air_quality_label = texto
   - air_quality_source  → 'sensor' (MQ-135) o 'calculado' (fórmula de respaldo)
   - captured_at         → momento real de la medición (≠ created_at)

   Métodos:
   - POR_EQUIPO          → cuántas lecturas se guardan por equipo (15)
   - contarSobrantes($deviceId)  → cuántas pasan de esas 15
   - conservarUltimas($deviceId) → borra las que pasan de 15, de a 5000
                           (DELETE ... LIMIT)
   - fechaDeCorte() (privado)    → fecha de la lectura número 15; lo anterior
                           sobra
   - limit(1, 14)        → (CI4) LIMIT 1 OFFSET 14: saltea 14 y trae la 15.ª
   ============================================================================ */
