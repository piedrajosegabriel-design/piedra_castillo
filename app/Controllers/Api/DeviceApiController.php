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
    public function storeMeasurement(string $deviceUid)
    {
        try {
            [$device, $space] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 'error',
                    'message' => $exception->getMessage(),
                ]);
        }

        $payload          = $this->getJsonPayload();

        $errors = [];

        foreach (['temperature', 'humidity', 'co2_ppm', 'air_quality_index'] as $field) {
            if (! isset($payload[$field]) || $payload[$field] === '') {
                $errors[$field] = 'El campo ' . $field . ' es obligatorio.';
            }
        }

        if (isset($payload['air_quality_index']) && ((int) $payload['air_quality_index'] < 0 || (int) $payload['air_quality_index'] > 100)) {
            $errors['air_quality_index'] = 'La calidad del aire debe estar entre 0 y 100.';
        }

        if ($errors !== []) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON([
                    'status'  => 'error',
                    'message' => implode(' ', array_values($errors)),
                    'errors'  => $errors,
                ]);
        }

        $result = (new SimulationService())->createMeasurement($device, $space, 'api', $payload);

        (new DeviceModel())->update($device['id'], [
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => 'Medicion recibida correctamente.',
            'measurement'=> $result['measurement'],
            'automation' => $result['automation'],
        ]);
    }

    public function pendingCommands(string $deviceUid)
    {
        try {
            [$device] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 'error',
                    'message' => $exception->getMessage(),
                ]);
        }

        $commands = (new CommandService())->getPendingCommands((int) $device['id']);

        (new DeviceModel())->update($device['id'], [
            'last_seen_at'         => date('Y-m-d H:i:s'),
            'last_command_sync_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'           => 'success',
            'device_uid'       => $device['device_uid'],
            'pending_commands' => array_map([$this, 'formatCommand'], $commands),
        ]);
    }

    public function markCommandExecuted(string $deviceUid, int $commandId)
    {
        try {
            [$device] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 'error',
                    'message' => $exception->getMessage(),
                ]);
        }

        $command = (new CommandService())->markCommandAsExecuted((int) $device['id'], $commandId, 'device-api');

        if (! $command) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'No se encontro el comando solicitado para este dispositivo.',
                ]);
        }

        (new DeviceModel())->update($device['id'], [
            'last_seen_at'         => date('Y-m-d H:i:s'),
            'last_command_sync_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Comando marcado como ejecutado.',
            'command' => $this->formatCommand($command),
        ]);
    }

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

    private function getJsonPayload(): array
    {
        $json = $this->request->getJSON(true);

        if (is_array($json) && $json !== []) {
            return $json;
        }

        return $this->request->getPost();
    }

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
