<?php

namespace App\Services;

use App\Models\MeasurementModel;

/* ============================================================
   SimulationService
   QUÉ HACE: genera las mediciones del sistema. Tiene dos roles:
   (a) sembrar un historial inicial simulado para que el panel
   nunca aparezca vacío, y (b) crear CADA medición nueva (venga
   del ESP32, del formulario web o de la simulación), completando
   los valores faltantes con números realistas que "caminan" cerca
   de la última lectura, y disparando la automatización después.
   SE RELACIONA CON: MeasurementModel (escribe las mediciones) y
   AutomationService (procesa cada medición creada). Lo usan
   PanelController, DeviceApiController, DeviceClaimService y
   DeviceProvisioningService.
   ============================================================ */
class SimulationService
{
    // -------------------------------------------------------------------------
    // Dependencias
    // -------------------------------------------------------------------------
    private MeasurementModel $measurementModel;
    private AutomationService $automationService;

    public function __construct()
    {
        $this->measurementModel  = new MeasurementModel();
        $this->automationService = new AutomationService();
    }

    // -------------------------------------------------------------------------
    // Siembra de historial inicial
    // -------------------------------------------------------------------------

    /**
     * Crea $count mediciones simuladas "hacia atrás en el tiempo" (una por
     * hora) para que el panel tenga gráficos e historial desde el primer día.
     */
    public function seedHistoryForDevice(array $device, array $space, int $count = 6): void
    {
        // El bucle va de la más vieja a la más nueva (-5h, -4h, ... -0h).
        for ($i = $count - 1; $i >= 0; $i--) {
            $payload = $this->generateMeasurementPayload($space, null, [
                'captured_at' => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
            ]);

            $this->measurementModel->insert([
                'device_id'          => $device['id'],
                'user_id'            => $device['user_id'],
                'space_id'           => $device['space_id'],
                'source'             => 'seed',
                'temperature'        => $payload['temperature'],
                'humidity'           => $payload['humidity'],
                'co2_ppm'            => $payload['co2_ppm'],
                'air_quality_index'  => $payload['air_quality_index'],
                'air_quality_label'  => $payload['air_quality_label'],
                'notes'              => 'Medición precargada para la simulación inicial.',
                'captured_at'        => $payload['captured_at'],
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Creación de una medición (el camino de TODAS las mediciones nuevas)
    // -------------------------------------------------------------------------

    /**
     * Crea una medición y dispara la automatización.
     * $input puede traer valores reales (API/form); lo que falte se completa
     * con datos simulados a partir de la última medición.
     */
    public function createMeasurement(array $device, array $space, string $source = 'web', array $input = []): array
    {
        // La última medición sirve de "ancla" para que los valores simulados
        // varíen suavemente en vez de saltar a cualquier número.
        $lastMeasurement = $this->measurementModel
            ->where('device_id', $device['id'])
            ->orderBy('captured_at', 'DESC')
            ->first();

        $payload = $this->generateMeasurementPayload($space, $lastMeasurement, $input);

        $measurementId = (int) $this->measurementModel->insert([
            'device_id'         => $device['id'],
            'user_id'           => $device['user_id'],
            'space_id'          => $device['space_id'],
            'source'            => $source,
            'temperature'       => $payload['temperature'],
            'humidity'          => $payload['humidity'],
            'co2_ppm'           => $payload['co2_ppm'],
            'air_quality_index' => $payload['air_quality_index'],
            'air_quality_label' => $payload['air_quality_label'],
            'notes'             => $payload['notes'],
            'captured_at'       => $payload['captured_at'],
        ]);

        // Cada medición nueva pasa por la automatización: acá es donde el
        // sistema decide si prender/apagar actuadores.
        $measurement = $this->measurementModel->find($measurementId);
        $automation  = $this->automationService->processMeasurement($device, $space, $measurement);

        return [
            'measurement' => $measurement,
            'automation'  => $automation,
        ];
    }

    // -------------------------------------------------------------------------
    // Generación de valores (la "física" de la simulación)
    // -------------------------------------------------------------------------

    /**
     * Arma el payload completo de una medición. Por cada variable:
     * si vino en $input se respeta (acotada a su rango físico); si no,
     * se genera un valor cercano al anterior (o al punto medio del ambiente).
     */
    private function generateMeasurementPayload(array $space, ?array $lastMeasurement, array $input): array
    {
        // Puntos de partida: el medio del rango ideal del ambiente.
        $midTemp = (((float) $space['min_temperature']) + ((float) $space['max_temperature'])) / 2;
        $midHum  = (((float) $space['min_humidity']) + ((float) $space['max_humidity'])) / 2;
        $baseCo2 = max(420, (int) $space['max_co2'] - 160);

        $temperature = $this->resolveDecimal(
            $input['temperature'] ?? null,
            $lastMeasurement['temperature'] ?? $midTemp,
            max(14.0, ((float) $space['min_temperature']) - 4),
            min(35.0, ((float) $space['max_temperature']) + 6),
            1.20
        );

        $humidity = $this->resolveDecimal(
            $input['humidity'] ?? null,
            $lastMeasurement['humidity'] ?? $midHum,
            20.0,
            85.0,
            4.50
        );

        $co2 = $this->resolveInteger(
            $input['co2_ppm'] ?? null,
            $lastMeasurement['co2_ppm'] ?? $baseCo2,
            380,
            2400,
            90
        );

        $airQualityIndex = $input['air_quality_index'] ?? null;
        if ($airQualityIndex === null || $airQualityIndex === '') {
            $airQualityIndex = $this->calculateAirQualityIndex($temperature, $humidity, $co2, $space);
        }

        $airQualityIndex = max(20, min(100, (int) $airQualityIndex));

        return [
            'temperature'       => round($temperature, 1),
            'humidity'          => round($humidity, 1),
            'co2_ppm'           => $co2,
            'air_quality_index' => $airQualityIndex,
            'air_quality_label' => $this->getAirQualityLabel($airQualityIndex),
            'notes'             => trim((string) ($input['notes'] ?? '')) ?: null,
            'captured_at'       => (string) ($input['captured_at'] ?? date('Y-m-d H:i:s')),
        ];
    }

    /**
     * Índice de calidad de aire (20–100): arranca en 100 y descuenta puntos
     * por cada desvío (temperatura lejos del medio, humedad fuera de rango,
     * CO₂ sobre el límite). Es una fórmula propia y simple, pensada para
     * que el número "reaccione" de forma creíble.
     */
    private function calculateAirQualityIndex(float $temperature, float $humidity, int $co2, array $space): int
    {
        $score = 100;
        $midTemperature = (((float) $space['min_temperature']) + ((float) $space['max_temperature'])) / 2;

        $score -= (int) round(abs($temperature - $midTemperature) * 6);

        if ($humidity > (float) $space['max_humidity']) {
            $score -= (int) round(($humidity - (float) $space['max_humidity']) * 1.5);
        }

        if ($humidity < (float) $space['min_humidity']) {
            $score -= (int) round(((float) $space['min_humidity'] - $humidity) * 1.3);
        }

        if ($co2 > (int) $space['max_co2']) {
            $score -= (int) round(($co2 - (int) $space['max_co2']) / 12);
        }

        return max(20, min(100, $score));
    }

    /** Etiqueta legible del índice: 85+ Excelente, 70+ Buena, 55+ Aceptable. */
    private function getAirQualityLabel(int $score): string
    {
        if ($score >= 85) {
            return 'Excelente';
        }

        if ($score >= 70) {
            return 'Buena';
        }

        if ($score >= 55) {
            return 'Aceptable';
        }

        return 'Mala';
    }

    /**
     * Valor decimal: si vino un dato real lo acota a [min, max]; si no,
     * "camina" desde el último valor con un paso aleatorio de ±variation.
     */
    private function resolveDecimal(mixed $value, mixed $lastValue, float $min, float $max, float $variation): float
    {
        if ($value !== null && $value !== '') {
            return max($min, min($max, (float) $value));
        }

        $base   = (float) $lastValue;
        $offset = random_int(-100, 100) / 100 * $variation;

        return max($min, min($max, $base + $offset));
    }

    /** Igual que resolveDecimal pero para enteros (CO₂). */
    private function resolveInteger(mixed $value, mixed $lastValue, int $min, int $max, int $variation): int
    {
        if ($value !== null && $value !== '') {
            return max($min, min($max, (int) $value));
        }

        $offset = random_int(-$variation, $variation);

        return max($min, min($max, (int) $lastValue + $offset));
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Públicos:
   - seedHistoryForDevice($device, $space, $count = 6)
       → inserta $count mediciones 'seed', una por hora hacia atrás
   - createMeasurement($device, $space, $source, $input)
       → crea UNA medición (completa faltantes con simulación) y corre
         la automatización; devuelve ['measurement' => ..., 'automation' => ...]

   Privados:
   - generateMeasurementPayload() → arma todos los valores de la medición
   - calculateAirQualityIndex()   → score 20–100 según los desvíos
   - getAirQualityLabel()         → score → Excelente/Buena/Aceptable/Mala
   - resolveDecimal()/resolveInteger() → dato real acotado, o paso aleatorio
                                         desde el último valor

   Funciones clave:
   - max($min, min($max, $v))  → (PHP) "clamp": encierra $v entre min y max
   - random_int(-100, 100)/100 → paso aleatorio proporcional a la variación
   - strtotime("-{$i} hours")  → fecha de hace $i horas (para la siembra)
   - round($v, 1)              → redondeo a 1 decimal
   ============================================================================ */
