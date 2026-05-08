<?php

namespace App\Services;

class EnvironmentPresetService
{
    private const PRESETS = [
        'oficina' => [
            'label'           => 'Oficina',
            'description'     => 'Pensado para confort prolongado y productividad.',
            'min_temperature' => 21.0,
            'max_temperature' => 25.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 900,
        ],
        'aula' => [
            'label'           => 'Aula',
            'description'     => 'Rangos orientados a concentracion y permanencia.',
            'min_temperature' => 20.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        'hogar' => [
            'label'           => 'Hogar',
            'description'     => 'Balance general para convivencia diaria.',
            'min_temperature' => 20.0,
            'max_temperature' => 26.0,
            'min_humidity'    => 35.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        'dormitorio' => [
            'label'           => 'Dormitorio',
            'description'     => 'Confort suave para descanso nocturno.',
            'min_temperature' => 18.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 55.0,
            'max_co2'         => 900,
        ],
        'personalizable' => [
            'label'           => 'Personalizable',
            'description'     => 'Permite ajustar nombre y umbrales base.',
            'min_temperature' => 20.0,
            'max_temperature' => 25.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
    ];

    public function getPresets(): array
    {
        return self::PRESETS;
    }

    public function getPreset(string $type): array
    {
        return self::PRESETS[$type] ?? self::PRESETS['hogar'];
    }

    public function buildSpaceData(array $data): array
    {
        $environmentType = (string) ($data['environment_type'] ?? 'hogar');
        $preset          = $this->getPreset($environmentType);
        $customName      = trim((string) ($data['custom_name'] ?? ''));

        return [
            'environment_type' => $environmentType,
            'custom_name'      => $environmentType === 'personalizable'
                ? ($customName !== '' ? $customName : 'Espacio personalizable')
                : null,
            'min_temperature'  => $this->toFloat($data['min_temperature'] ?? $preset['min_temperature'], $preset['min_temperature']),
            'max_temperature'  => $this->toFloat($data['max_temperature'] ?? $preset['max_temperature'], $preset['max_temperature']),
            'min_humidity'     => $this->toFloat($data['min_humidity'] ?? $preset['min_humidity'], $preset['min_humidity']),
            'max_humidity'     => $this->toFloat($data['max_humidity'] ?? $preset['max_humidity'], $preset['max_humidity']),
            'max_co2'          => $this->toInt($data['max_co2'] ?? $preset['max_co2'], $preset['max_co2']),
        ];
    }

    public function getDisplayName(array $space): string
    {
        if (($space['environment_type'] ?? '') === 'personalizable') {
            $customName = trim((string) ($space['custom_name'] ?? ''));

            return $customName !== '' ? $customName : 'Espacio personalizable';
        }

        return $this->getPreset((string) ($space['environment_type'] ?? 'hogar'))['label'];
    }

    public function getEnvironmentLabel(string $type): string
    {
        return $this->getPreset($type)['label'];
    }

    private function toFloat(mixed $value, float $fallback): float
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (float) $value;
    }

    private function toInt(mixed $value, int $fallback): int
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (int) $value;
    }
}
