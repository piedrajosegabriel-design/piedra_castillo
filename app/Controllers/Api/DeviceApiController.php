<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DeviceModel;
use App\Models\SpaceModel;
use App\Services\CommandService;
use App\Services\SimulationService;
use CodeIgniter\HTTP\ResponseInterface;

class DeviceApiController extends BaseController
{
    // =========================================================================
    // API DE MEDICIONES
    // =========================================================================
    public function storeMeasurement(string $deviceUid)
    {
        try {
            [$device, $space] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->responderNoAutorizado($exception->getMessage());
        }

        $payload = $this->getJsonPayload();
        $errors  = $this->validarPayloadMedicion($payload);

        if ($errors !== []) {
            return $this->responderErroresValidacion($errors);
        }

        $result = (new SimulationService())->createMeasurement($device, $space, 'api', $payload);

        $this->actualizarActividadDispositivo((int) $device['id']);

        return $this->response->setJSON([
            'status'      => 'success',
            'message'     => 'Medicion recibida correctamente.',
            'measurement' => $result['measurement'],
            'automation'  => $result['automation'],
        ]);
    }

    // =========================================================================
    // API DE COMANDOS PENDIENTES
    // =========================================================================
    public function pendingCommands(string $deviceUid)
    {
        try {
            [$device] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->responderNoAutorizado($exception->getMessage());
        }

        $commands = (new CommandService())->getPendingCommands((int) $device['id']);

        $this->actualizarActividadDispositivo((int) $device['id'], true);

        return $this->response->setJSON([
            'status'           => 'success',
            'device_uid'       => $device['device_uid'],
            'pending_commands' => array_map([$this, 'formatCommand'], $commands),
        ]);
    }

    // =========================================================================
    // API DE CONFIRMACION DE COMANDO
    // =========================================================================
    public function markCommandExecuted(string $deviceUid, int $commandId)
    {
        try {
            [$device] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->responderNoAutorizado($exception->getMessage());
        }

        $command = (new CommandService())->markCommandAsExecuted((int) $device['id'], $commandId, 'device-api');

        if (! $command) {
            return $this->responderNoEncontrado('No se encontro el comando solicitado para este dispositivo.');
        }

        $this->actualizarActividadDispositivo((int) $device['id'], true);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Comando marcado como ejecutado.',
            'command' => $this->formatCommand($command),
        ]);
    }

    // =========================================================================
    // AUTENTICACION DEL DISPOSITIVO
    // =========================================================================
    private function resolveAuthenticatedDevice(string $deviceUid): array
    {
        $deviceModel = new DeviceModel();
        $device      = $deviceModel->where('device_uid', $deviceUid)->first();

        if (! $device) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Dispositivo no encontrado.');
        }

        $token = $this->request->getHeaderLine('X-Device-Token');

        if ($token === '') {
            $token = (string) ($this->request->getGetPost('api_token') ?? '');
        }

        if ($token !== (string) $device['api_token']) {
            throw new \InvalidArgumentException('Token de dispositivo invalido.');
        }

        $space = (new SpaceModel())->find($device['space_id']);

        return [$device, $space];
    }

    // =========================================================================
    // LECTURA Y VALIDACION DE PAYLOAD
    // =========================================================================
    private function getJsonPayload(): array
    {
        $json = $this->request->getJSON(true);

        if (is_array($json) && $json !== []) {
            return $json;
        }

        return $this->request->getPost();
    }

    private function validarPayloadMedicion(array $payload): array
    {
        $errors = [];

        foreach (['temperature', 'humidity', 'co2_ppm', 'air_quality_index'] as $field) {
            if (! isset($payload[$field]) || $payload[$field] === '') {
                $errors[$field] = 'El campo ' . $field . ' es obligatorio.';
            }
        }

        if (
            isset($payload['air_quality_index'])
            && ((int) $payload['air_quality_index'] < 0 || (int) $payload['air_quality_index'] > 100)
        ) {
            $errors['air_quality_index'] = 'La calidad del aire debe estar entre 0 y 100.';
        }

        return $errors;
    }

    // =========================================================================
    // ACTIVIDAD DEL DISPOSITIVO
    // =========================================================================
    private function actualizarActividadDispositivo(int $deviceId, bool $sincronizoComandos = false): void
    {
        $data = [
            'last_seen_at' => date('Y-m-d H:i:s'),
        ];

        if ($sincronizoComandos) {
            $data['last_command_sync_at'] = date('Y-m-d H:i:s');
        }

        (new DeviceModel())->update($deviceId, $data);
    }

    // =========================================================================
    // RESPUESTAS JSON
    // =========================================================================
    private function responderNoAutorizado(string $mensaje)
    {
        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
            ->setJSON([
                'status'  => 'error',
                'message' => $mensaje,
            ]);
    }

    private function responderErroresValidacion(array $errors)
    {
        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
            ->setJSON([
                'status'  => 'error',
                'message' => implode(' ', array_values($errors)),
                'errors'  => $errors,
            ]);
    }

    private function responderNoEncontrado(string $mensaje)
    {
        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
            ->setJSON([
                'status'  => 'error',
                'message' => $mensaje,
            ]);
    }

    // =========================================================================
    // FORMATEO DE COMANDOS
    // =========================================================================
    private function formatCommand(array $command): array
    {
        $payload = json_decode((string) ($command['payload'] ?? ''), true);

        return [
            'id'           => (int) $command['id'],
            'command_type' => $command['command_type'],
            'target_value' => $command['target_value'],
            'status'       => $command['status'],
            'source'       => $command['source'],
            'reason'       => is_array($payload) ? ($payload['reason'] ?? '') : '',
            'created_at'   => $command['created_at'],
            'executed_at'  => $command['executed_at'],
        ];
    }
}
