<?php

namespace App\Services;

use App\Models\DeviceCommandModel;
use App\Models\DeviceStateModel;

/* ============================================================
   CommandService
   QUÉ HACE: administra la COLA de comandos hacia el dispositivo
   y mantiene sincronizado su estado (device_states). Todo cambio
   de modo o de actuador pasa por acá: se registra como comando
   (auditoría de quién pidió qué y por qué) y, al ejecutarse, se
   refleja en el estado actual del dispositivo.
   SE RELACIONA CON: DeviceCommandModel y DeviceStateModel (sus
   dos tablas). Lo usan AutomationService (comandos automáticos),
   PanelController (modo y control manual), DeviceApiController
   (el ESP32 consulta pendientes y confirma ejecución) y
   PanelService (lee el estado para el dashboard).
   ============================================================ */
class CommandService
{
    // -------------------------------------------------------------------------
    // Dependencias y mapeo actuador → columna de device_states
    // -------------------------------------------------------------------------
    private DeviceCommandModel $commandModel;
    private DeviceStateModel $stateModel;

    // Traduce el command_type al nombre de la columna que guarda su estado.
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

    // -------------------------------------------------------------------------
    // Cambio de modo (automatic / manual)
    // -------------------------------------------------------------------------

    /**
     * Cambia el modo de operación. El cambio se registra como comando ya
     * ejecutado (es instantáneo, no espera al ESP32) y se actualiza el estado.
     * Al pasar a manual se cancelan los comandos automáticos pendientes:
     * desde ese momento manda el usuario.
     */
    public function changeOperatingMode(int $deviceId, string $mode, ?int $userId, string $source = 'web'): array
    {
        $state = $this->getStateByDeviceId($deviceId);

        if ($state === null) {
            throw new \RuntimeException('No se encontro el estado del dispositivo.');
        }

        // Si ya está en ese modo, no hay nada que hacer.
        if (($state['operating_mode'] ?? 'automatic') === $mode) {
            return $state;
        }

        $this->commandModel->insert([
            'device_id'          => $deviceId,
            'issued_by_user_id'  => $userId,
            'source'             => $source,
            'command_type'       => 'mode',
            'target_value'       => $mode,
            'payload'            => json_encode(['reason' => 'Cambio de modo desde la aplicación'], JSON_UNESCAPED_UNICODE),
            'status'             => 'executed',
            'executed_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->stateModel->update($state['id'], [
            'operating_mode' => $mode,
            'updated_by'     => $source,
            'last_reason'    => $mode === 'automatic'
                ? 'Modo automático activo.'
                : 'Modo manual activo.',
        ]);

        if ($mode === 'manual') {
            $this->cancelPendingAutomationCommands($deviceId);
        }

        return $this->getStateByDeviceId($deviceId) ?? $state;
    }

    // -------------------------------------------------------------------------
    // Comandos MANUALES (botones del panel web)
    // -------------------------------------------------------------------------

    /**
     * Encola un comando manual y lo ejecuta al instante (en el simulador no
     * hay hardware real que esperar). Antes cancela los pendientes del mismo
     * tipo para que no queden órdenes contradictorias en la cola.
     */
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

    // -------------------------------------------------------------------------
    // Comandos AUTOMÁTICOS (los encola AutomationService)
    // -------------------------------------------------------------------------

    /**
     * Encola un comando de automatización SOLO si hace falta:
     * - si el actuador ya está en el valor pedido → null (nada que hacer)
     * - si ya existe el mismo comando pendiente   → devuelve ese (no duplica)
     * - si hay pendientes contradictorios del mismo tipo → los cancela primero
     */
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

    // -------------------------------------------------------------------------
    // Lectura y ejecución de la cola
    // -------------------------------------------------------------------------

    /** Comandos pendientes en orden de llegada (los consulta el ESP32). */
    public function getPendingCommands(int $deviceId): array
    {
        return $this->commandModel
            ->where('device_id', $deviceId)
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** Ejecuta TODOS los pendientes de una (lo usa el dispositivo simulado). */
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

    /**
     * Marca un comando como ejecutado Y actualiza el estado del dispositivo
     * (la columna del actuador correspondiente + el motivo). Es idempotente:
     * si ya estaba ejecutado, lo devuelve tal cual sin tocar nada.
     */
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

    // -------------------------------------------------------------------------
    // Cancelaciones y consultas de estado
    // -------------------------------------------------------------------------

    /** Cancela los pendientes de la automatización (al pasar a modo manual). */
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

    /** Fila de device_states del dispositivo (o null si no existe). */
    public function getStateByDeviceId(int $deviceId): ?array
    {
        return $this->stateModel->where('device_id', $deviceId)->first();
    }

    /** Cancela los pendientes de UN tipo (antes de encolar uno nuevo). */
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

    /** Extrae el 'reason' del payload JSON; si no hay, arma uno genérico. */
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

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Públicos:
   - changeOperatingMode()       → cambia automatic/manual; registra el comando
                                   como ejecutado y actualiza device_states
   - queueAndExecuteManualCommand() → comando manual: encola + ejecuta al toque
   - queueAutomationCommand()    → comando automático: solo si cambia algo y
                                   sin duplicar pendientes
   - getPendingCommands()        → cola pendiente del dispositivo (para la API)
   - applyPendingCommands()      → ejecuta todos los pendientes (simulador)
   - markCommandAsExecuted()     → comando → 'executed' + refleja el cambio en
                                   device_states (idempotente)
   - cancelPendingAutomationCommands() → al pasar a manual, limpia la cola
   - getStateByDeviceId()        → estado actual (fila de device_states)

   Privados:
   - cancelPendingByType()       → cancela pendientes de un mismo command_type
   - buildReasonFromCommand()    → saca el 'reason' del payload JSON

   Conceptos:
   - $actuatorMap                → command_type → columna de device_states
                                   (fan → fan_state, etc.)
   - 'idempotente'               → llamarlo dos veces da el mismo resultado
                                   que llamarlo una (no rompe ni duplica)
   - json_encode/json_decode     → (PHP) array ↔ texto JSON para el payload
   - insert()/update()/find()/where()/first()/findAll() → (CI4 Model) CRUD
   ============================================================================ */
