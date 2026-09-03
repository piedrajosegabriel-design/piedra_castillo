<?php

namespace App\Services;

use App\Models\DeviceCommandModel;
use App\Models\DeviceStateModel;

/* ============================================================
   CommandService
   QUÉ HACE: administra la COLA de comandos hacia el dispositivo
   y mantiene sincronizado su estado (device_states).

   DOS CAMINOS, MUY DISTINTOS:

   1. ÓRDENES DEL USUARIO (source 'web'). Nacen acá: el usuario
      aprieta un botón del dashboard en modo manual y el comando
      queda 'pending' hasta que el equipo lo consulta y lo aplica.
      Es el ÚNICO caso en que el servidor le dice al equipo qué
      hacer.

   2. LO QUE EL EQUIPO DECIDIÓ SOLO (source 'device'). No son
      órdenes: son hechos consumados. El ESP32 comparó la medición
      con sus umbrales, accionó, y recién después avisó. Acá se
      registran con registrarEstadoReportado(), ya en estado
      'executed'.

   El servidor NO tiene reglas de automatización: viven en el
   firmware. Los umbrales se los manda DeviceConfigService.

   SE RELACIONA CON: DeviceCommandModel y DeviceStateModel (sus
   dos tablas). Lo usan MeasurementService (registra lo reportado),
   PanelController (modo y control manual), DeviceApiController
   (el ESP32 consulta pendientes y confirma ejecución),
   DeviceConfigService y PanelService (leen el estado).
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
     * Encola una orden del usuario y la deja PENDIENTE.
     *
     * Antes se marcaba como ejecutada al instante, porque no había hardware
     * real que esperar. Con un equipo de verdad eso seria mentir: el panel
     * mostraría el ventilador encendido antes de que el ESP32 se enterara.
     *
     * Ahora el comando espera en la cola hasta que el equipo lo consulta
     * (GET .../commands/pending), lo aplica y lo confirma
     * (POST .../commands/N/executed). Recién ahí cambia `device_states`.
     *
     * Antes de encolar se cancelan los pendientes del mismo actuador, para
     * que no queden dos órdenes contradictorias esperando.
     */
    public function encolarComandoManual(
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

        return $this->commandModel->find($commandId);
    }

    // -------------------------------------------------------------------------
    // Lo que el EQUIPO decidió por su cuenta
    //
    // El servidor no manda prender nada por automatización: el ESP32 compara
    // la medición con sus umbrales y acciona solo. Después informa qué quedó
    // encendido, y acá se guarda ese estado.
    // -------------------------------------------------------------------------

    /**
     * Registra el estado de actuadores que reportó el dispositivo.
     *
     * Actualiza `device_states` siempre, y deja una fila en `device_commands`
     * SOLO por los actuadores que cambiaron. Así el historial cuenta "a las
     * 14:03 el equipo prendió el ventilador porque el CO₂ estaba alto", sin
     * llenarse de filas repetidas en cada medición.
     *
     * @param array<string,string> $actuadores ['fan' => 'on', 'aromatizer' => 'off', ...]
     * @return array<int,string> los actuadores que cambiaron
     */
    public function registrarEstadoReportado(int $deviceId, array $actuadores, string $motivo = ''): array
    {
        $estado = $this->asegurarEstado($deviceId);
        $cambios     = [];
        $actualizar  = [];

        foreach ($this->actuatorMap as $tipo => $campo) {
            if (! isset($actuadores[$tipo])) {
                continue;
            }

            $valor = $actuadores[$tipo] === 'on' ? 'on' : 'off';

            if (($estado[$campo] ?? 'off') === $valor) {
                continue;   // ya estaba así: nada que anotar
            }

            $actualizar[$campo] = $valor;
            $cambios[]          = $tipo;

            // Queda como comando ya ejecutado: es un hecho consumado, no una
            // orden pendiente. El equipo ya lo hizo antes de avisarnos.
            $this->commandModel->insert([
                'device_id'    => $deviceId,
                'source'       => 'device',
                'command_type' => $tipo,
                'target_value' => $valor,
                'payload'      => json_encode(['reason' => $motivo], JSON_UNESCAPED_UNICODE),
                'status'       => 'executed',
                'executed_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        if ($actualizar !== []) {
            $actualizar['last_reason'] = $motivo !== '' ? $motivo : 'Ajuste decidido por el equipo.';
            $actualizar['updated_by']  = 'device';
            $this->stateModel->update($estado['id'], $actualizar);
        }

        return $cambios;
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

    /**
     * Limpia órdenes viejas de la automatización del servidor.
     *
     * Ya no se generan comandos con source 'automation' (esa lógica se mudó al
     * firmware), pero pueden quedar filas pendientes de antes. Si el equipo las
     * consultara, ejecutaría órdenes de una arquitectura que ya no existe.
     * Se limpian al pasar a modo manual.
     */
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

    /**
     * Igual que getStateByDeviceId(), pero si el dispositivo no tiene fila de
     * estado la crea con los valores de arranque (automático, todo apagado).
     *
     * Sin esta fila la automatización no puede decidir nada: el hardware
     * mandaría mediciones y ningún actuador se movería nunca. Por eso la
     * automatización usa esta versión y no la anterior.
     */
    public function asegurarEstado(int $deviceId): array
    {
        $estado = $this->getStateByDeviceId($deviceId);

        if ($estado !== null) {
            return $estado;
        }

        $estadoId = (int) $this->stateModel->insert([
            'device_id'        => $deviceId,
            'operating_mode'   => 'automatic',
            'fan_state'        => 'off',
            'aromatizer_state' => 'off',
            'alert_led_state'  => 'off',
            'last_reason'      => 'Estado inicial creado al recibir la primera medición.',
            'updated_by'       => 'system',
        ]);

        return $this->stateModel->find($estadoId);
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
   - encolarComandoManual()      → orden del usuario: queda PENDIENTE hasta
                                   que el equipo la aplique y la confirme
   - registrarEstadoReportado()  → guarda lo que el equipo decidió por su
                                   cuenta; anota en el historial solo los
                                   actuadores que realmente cambiaron
   - getPendingCommands()        → cola pendiente del dispositivo (para la API)
   - markCommandAsExecuted()     → lo llama el equipo tras aplicar la orden;
                                   es lo único que cambia device_states
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
