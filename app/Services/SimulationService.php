<?php

namespace App\Services;

use App\Models\MeasurementModel;

class SimulationService
{
    private MeasurementModel $measurementModel;
    private AutomationService $automationService;

    public function __construct()
    {
        $this->measurementModel  = new MeasurementModel();
        $this->automationService = new AutomationService();
    }

    public function seedHistoryForDevice(array $device, array $space, int $count = 6): void
    {
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

    public function createMeasurement(array $device, array $space, string $source = 'web', array $input = []): array
    {
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

        $measurement = $this->measurementModel->find($measurementId);
        $automation  = $this->automationService->processMeasurement($device, $space, $measurement);

        return [
            'measurement' => $measurement,
            'automation'  => $automation,
        ];
    }

    private function generateMeasurementPayload(array $space, ?array $lastMeasurement, array $input): array
    {
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

    private function resolveDecimal(mixed $value, mixed $lastValue, float $min, float $max, float $variation): float
    {
        if ($value !== null && $value !== '') {
            return max($min, min($max, (float) $value));
        }

        $base   = (float) $lastValue;
        $offset = random_int(-100, 100) / 100 * $variation;

        return max($min, min($max, $base + $offset));
    }

    private function resolveInteger(mixed $value, mixed $lastValue, int $min, int $max, int $variation): int
    {
        if ($value !== null && $value !== '') {
            return max($min, min($max, (int) $value));
        }

        $offset = random_int(-$variation, $variation);

        return max($min, min($max, (int) $lastValue + $offset));
    }
}
