<?php

namespace App\Services;

use App\Models\MeasurementModel;

class AutomationService
{
    private CommandService $commandService;
    private MeasurementModel $measurementModel;

    public function __construct()
    {
        $this->commandService  = new CommandService();
        $this->measurementModel = new MeasurementModel();
    }

    public function processMeasurement(array $device, array $space, array $measurement): array
    {
        $state = $this->commandService->getStateByDeviceId((int) $device['id']);

        if ($state === null) {
            return [
                'summary'  => 'No se encontro el estado del dispositivo.',
                'commands' => [],
            ];
        }

        if (($state['operating_mode'] ?? 'automatic') !== 'automatic') {
            return [
                'summary'  => 'Modo manual activo.',
                'commands' => [],
            ];
        }

        $commands = [];
        $reasons  = [];

        $temp      = (float) $measurement['temperature'];
        $humidity  = (float) $measurement['humidity'];
        $co2       = (int) $measurement['co2_ppm'];
        $airScore  = (int) $measurement['air_quality_index'];
        $minTemp   = (float) $space['min_temperature'];
        $maxTemp   = (float) $space['max_temperature'];
        $minHum    = (float) $space['min_humidity'];
        $maxHum    = (float) $space['max_humidity'];
        $maxCo2    = (int) $space['max_co2'];

        $fanTarget = ($temp > $maxTemp || $humidity > $maxHum || $co2 > $maxCo2) ? 'on' : 'off';
        if ($fanTarget === 'on') {
            $reasons[] = 'Aire acondicionado sugerido.';
        }

        $aromatizerTarget = $airScore < 60 ? 'on' : 'off';
        if ($aromatizerTarget === 'on') {
            $reasons[] = 'Aromatizador sugerido.';
        }

        $ledTarget = (
            $temp > $maxTemp + 2 ||
            $temp < $minTemp - 2 ||
            $humidity > $maxHum + 8 ||
            $humidity < $minHum - 8 ||
            $co2 > $maxCo2 + 250 ||
            $airScore < 45
        ) ? 'on' : 'off';

        if ($ledTarget === 'on') {
            $reasons[] = 'Alerta visual sugerida.';
        }

        foreach ([
            'fan'        => $fanTarget,
            'aromatizer' => $aromatizerTarget,
            'alert_led'  => $ledTarget,
        ] as $commandType => $targetValue) {
            $command = $this->commandService->queueAutomationCommand(
                (int) $device['id'],
                $commandType,
                $targetValue,
                implode(' ', $reasons) !== '' ? implode(' ', $reasons) : 'Ajuste automático.'
            );

            if ($command) {
                $commands[] = $command;
            }
        }

        return [
            'summary'  => $reasons !== []
                ? implode(' ', $reasons)
                : 'Sin ajustes necesarios.',
            'commands' => $commands,
        ];
    }

    public function processLatestMeasurement(array $device, array $space): array
    {
        $measurement = $this->measurementModel
            ->where('device_id', $device['id'])
            ->orderBy('captured_at', 'DESC')
            ->first();

        if (! $measurement) {
            return [
                'summary'  => 'Sin mediciones.',
                'commands' => [],
            ];
        }

        return $this->processMeasurement($device, $space, $measurement);
    }
}
