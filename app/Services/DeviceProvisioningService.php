<?php

namespace App\Services;

use App\Models\DeviceModel;
use App\Models\DeviceStateModel;
use App\Models\MeasurementModel;
use App\Models\SpaceModel;

class DeviceProvisioningService
{
    private SpaceModel $spaceModel;
    private DeviceModel $deviceModel;
    private DeviceStateModel $deviceStateModel;
    private MeasurementModel $measurementModel;
    private EnvironmentPresetService $presetService;
    private SimulationService $simulationService;

    public function __construct()
    {
        $this->spaceModel        = new SpaceModel();
        $this->deviceModel       = new DeviceModel();
        $this->deviceStateModel  = new DeviceStateModel();
        $this->measurementModel  = new MeasurementModel();
        $this->presetService     = new EnvironmentPresetService();
        $this->simulationService = new SimulationService();
    }

    public function ensureUserSetup(int $userId, array $spaceInput = [], bool $createSpaceIfMissing = true): array
    {
        $space = $this->spaceModel->where('user_id', $userId)->first();

        if (! $space) {
            if (! $createSpaceIfMissing) {
                throw new \RuntimeException('El usuario aun no tiene un ambiente configurado.');
            }

            $spaceId = (int) $this->spaceModel->insert(array_merge(
                ['user_id' => $userId],
                $this->presetService->buildSpaceData(
                    $spaceInput !== [] ? $spaceInput : ['environment_type' => 'hogar']
                )
            ));
            $space = $this->spaceModel->find($spaceId);
        }

        $device = $this->deviceModel->where('user_id', $userId)->first();

        if (! $device) {
            $deviceId = (int) $this->deviceModel->insert([
                'user_id'      => $userId,
                'space_id'     => $space['id'],
                'name'         => 'ESP32 simulada - ' . $this->presetService->getDisplayName($space),
                'device_uid'   => 'SIM-' . strtoupper(bin2hex(random_bytes(4))),
                'api_token'    => bin2hex(random_bytes(16)),
                'is_simulated' => 1,
                'is_active'    => 1,
            ]);
            $device = $this->deviceModel->find($deviceId);
        }

        $state = $this->deviceStateModel->where('device_id', $device['id'])->first();

        if (! $state) {
            $stateId = (int) $this->deviceStateModel->insert([
                'device_id'         => $device['id'],
                'operating_mode'    => 'automatic',
                'fan_state'         => 'off',
                'aromatizer_state'  => 'off',
                'alert_led_state'   => 'off',
                'last_reason'       => 'Inicio del sistema.',
                'updated_by'        => 'system',
            ]);
            $state = $this->deviceStateModel->find($stateId);
        }

        $hasMeasurements = $this->measurementModel
            ->where('device_id', $device['id'])
            ->countAllResults() > 0;

        if (! $hasMeasurements) {
            $this->simulationService->seedHistoryForDevice($device, $space);
        }

        return [
            'space'  => $space,
            'device' => $device,
            'state'  => $state,
        ];
    }
}
