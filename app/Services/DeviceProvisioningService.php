<?php

namespace App\Services;

use App\Models\DeviceModel;
use App\Models\DeviceStateModel;
use App\Models\MeasurementModel;
use App\Models\SpaceModel;

/* ============================================================
   DeviceProvisioningService
   QUÉ HACE: prepara la cuenta de un usuario para que el panel
   tenga TODO lo que necesita: un ambiente, un dispositivo, un
   estado inicial y un historial de mediciones. Si algo falta,
   lo crea (versión simulada); si ya existe, lo respeta.
   Es la acción detrás del botón "Ver demo del sistema".
   SE RELACIONA CON: SpaceModel, DeviceModel, DeviceStateModel,
   MeasurementModel (tablas), EnvironmentPresetService (rangos
   del ambiente) y SimulationService (historial inicial).
   Lo usa PanelController (iniciarDemo y crearPanel).
   ============================================================ */
class DeviceProvisioningService
{
    // -------------------------------------------------------------------------
    // Dependencias
    // -------------------------------------------------------------------------
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

    // -------------------------------------------------------------------------
    // Setup completo del usuario (el único método público)
    // Patrón "ensure": cada bloque pregunta "¿existe?" y crea solo si falta.
    // -------------------------------------------------------------------------

    public function ensureUserSetup(int $userId, array $spaceInput = [], bool $createSpaceIfMissing = true): array
    {
        // 1) AMBIENTE: el primero del usuario, o uno nuevo con preset 'hogar'.
        //    Con $createSpaceIfMissing=false NO se crea nada en silencio
        //    (se usa así desde el panel, donde la demo es acción explícita).
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

        // 2) DISPOSITIVO: si no hay ninguno, se crea uno simulado con
        //    credenciales aleatorias (uid SIM-XXXX + api_token).
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

        // 3) ESTADO: arranca en automático con todos los actuadores apagados.
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

        // 4) HISTORIAL: si no hay mediciones, se siembran 6 simuladas para
        //    que el dashboard no aparezca vacío.
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

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - ensureUserSetup($userId, $spaceInput, $createSpaceIfMissing)
       → garantiza ambiente + dispositivo + estado + historial; devuelve los
         tres primeros. Con $createSpaceIfMissing=false lanza RuntimeException
         si el usuario no tiene ambiente (en vez de crearlo en silencio).
   - buildSpaceData()       → (EnvironmentPresetService) rangos según preset
   - seedHistoryForDevice() → (SimulationService) siembra mediciones iniciales
   - where()->first()       → (CI4) la primera fila que cumple la condición
   - array_merge($a, $b)    → (PHP) combina arrays (acá: user_id + datos preset)
   ============================================================================ */
