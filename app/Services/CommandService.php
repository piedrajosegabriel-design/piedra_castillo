<?php

namespace App\Services;

use App\Models\DeviceCommandModel;
use App\Models\DeviceStateModel;

class CommandService
{
    private DeviceCommandModel $commandModel;
    private DeviceStateModel $stateModel;

    private array $actuatorMap = [
        'fan'        => 'fan_state',
        'aromatizer' => 'aromatizer_state',
        'alert_led'  => 'alert_led_state',
    ];

    public function __construct()
    {
        $this->commandModel = new DeviceCommandModel();
        $this->stateModel   = new DeviceStateModel();
    }

    public function changeOperatingMode(int $deviceId, string $mode, ?int $userId, string $source = 'web'): array
    {
        $state = $this->getStateByDeviceId($deviceId);

        if ($state === null) {
            throw new \RuntimeException('No se encontro el estado del dispositivo.');
        }

        if (($state['operating_mode'] ?? 'automatic') === $mode) {
            return $state;
        }

        $this->commandModel->insert([
            'device_id'          => $deviceId,
            'issued_by_user_id'  => $userId,
            'source'             => $source,
            'command_type'       => 'mode',
            'target_value'       => $mode,
            'payload'            => json_encode(['reason' => 'Cambio de modo desde la aplicacion'], JSON_UNESCAPED_UNICODE),
            'status'             => 'executed',
            'executed_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->stateModel->update($state['id'], [
            'operating_mode' => $mode,
            'updated_by'     => $source,
            'last_reason'    => $mode === 'automatic'
                ? 'Modo automatico activo.'
                : 'Modo manual activo.',
        ]);

        if ($mode === 'manual') {
            $this->cancelPendingAutomationCommands($deviceId);
        }

        return $this->getStateByDeviceId($deviceId) ?? $state;
    }

    public function queueAndExecuteManualCommand(
        int $deviceId,
        string $commandType,
        string $targetValue,
        ?int $userId,
        string $source = 'web'
    ): array {
        $this->cancelPendingByType($deviceId, $commandType);

        $commandId = (int) $this->commandModel->insert([
            'device_id'         => $deviceId,
            'issued_by_user_id' => $userId,
            'source'            => $source,
            'command_type'      => $commandType,
            'target_value'      => $targetValue,
            'payload'           => json_encode(['reason' => 'Control manual desde la web'], JSON_UNESCAPED_UNICODE),
            'status'            => 'pending',
        ]);

        $this->markCommandAsExecuted($deviceId, $commandId, 'web-simulator');

        return $this->commandModel->find($commandId);
    }

    public function queueAutomationCommand(int $deviceId, string $commandType, string $targetValue, string $reason): ?array
    {
        $state = $this->getStateByDeviceId($deviceId);
        $field = $this->actuatorMap[$commandType] ?? null;

        if ($state === null || $field === null) {
            return null;
        }

        if (($state[$field] ?? 'off') === $targetValue) {
            return null;
        }

        $existing = $this->commandModel
            ->where('device_id', $deviceId)
            ->where('command_type', $commandType)
            ->where('target_value', $targetValue)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return $existing;
        }

        $this->cancelPendingByType($deviceId, $commandType);

        $commandId = (int) $this->commandModel->insert([
            'device_id'     => $deviceId,
            'source'        => 'automation',
            'command_type'  => $commandType,
            'target_value'  => $targetValue,
            'payload'       => json_encode(['reason' => $reason], JSON_UNESCAPED_UNICODE),
            'status'        => 'pending',
        ]);

        return $this->commandModel->find($commandId);
    }

    public function getPendingCommands(int $deviceId): array
    {
        return $this->commandModel
            ->where('device_id', $deviceId)
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function applyPendingCommands(int $deviceId, string $executor = 'simulated-device'): array
    {
        $pending  = $this->getPendingCommands($deviceId);
        $executed = [];

        foreach ($pending as $command) {
            $updated = $this->markCommandAsExecuted($deviceId, (int) $command['id'], $executor);

            if ($updated) {
                $executed[] = $updated;
            }
        }

        return $executed;
    }

    public function markCommandAsExecuted(int $deviceId, int $commandId, string $executor = 'device-api'): ?array
    {
        $command = $this->commandModel->find($commandId);

        if (! $command || (int) $command['device_id'] !== $deviceId) {
            return null;
        }

        if (($command['status'] ?? '') === 'executed') {
            return $command;
        }

        $state = $this->getStateByDeviceId($deviceId);

        if ($state === null) {
            return null;
        }

        $stateUpdate = [
            'updated_by'  => $executor,
            'last_reason' => $this->buildReasonFromCommand($command),
        ];

        switch ($command['command_type']) {
            case 'fan':
                $stateUpdate['fan_state'] = $command['target_value'];
                break;

            case 'aromatizer':
                $stateUpdate['aromatizer_state'] = $command['target_value'];
                break;

            case 'alert_led':
                $stateUpdate['alert_led_state'] = $command['target_value'];
                break;
        }

        $this->stateModel->update($state['id'], $stateUpdate);
        $this->commandModel->update($commandId, [
            'status'      => 'executed',
            'executed_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->commandModel->find($commandId);
    }

    public function cancelPendingAutomationCommands(int $deviceId): void
    {
        $pending = $this->commandModel
            ->where('device_id', $deviceId)
            ->where('status', 'pending')
            ->where('source', 'automation')
            ->findAll();

        foreach ($pending as $command) {
            $this->commandModel->update($command['id'], ['status' => 'cancelled']);
        }
    }

    public function getStateByDeviceId(int $deviceId): ?array
    {
        return $this->stateModel->where('device_id', $deviceId)->first();
    }

    private function cancelPendingByType(int $deviceId, string $commandType): void
    {
        $pending = $this->commandModel
            ->where('device_id', $deviceId)
            ->where('command_type', $commandType)
            ->where('status', 'pending')
            ->findAll();

        foreach ($pending as $command) {
            $this->commandModel->update($command['id'], ['status' => 'cancelled']);
        }
    }

    private function buildReasonFromCommand(array $command): string
    {
        $payload = json_decode((string) ($command['payload'] ?? ''), true);
        $reason  = is_array($payload) ? ($payload['reason'] ?? '') : '';

        if ($reason !== '') {
            return $reason;
        }

        return sprintf(
            '%s en %s.',
            $command['command_type'] ?? 'desconocido',
            $command['target_value'] ?? 'sin valor'
        );
    }
}
