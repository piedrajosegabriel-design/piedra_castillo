<?php

namespace App\Services;

use App\Models\DeviceActivationCodeModel;
use App\Models\DeviceModel;
use App\Models\DeviceStateModel;
use App\Models\SpaceModel;
use RuntimeException;

/**
 * Vinculación de dispositivos por código de activación (claim code).
 *
 * Flujo recomendado (ver docs/HITO_2_IMPLEMENTACION.md):
 *   1. El dispositivo trae un código único EDEN-XXXX-XXXX.
 *   2. El usuario lo ingresa (o escanea el QR) en "Agregar dispositivo".
 *   3. Se valida que exista y no haya sido usado.
 *   4. El usuario le pone nombre, tipo y ambiente.
 *   5. El código queda marcado como usado y el dispositivo asociado a la cuenta.
 *
 * La MAC es sólo un dato técnico interno, nunca una credencial.
 */
class DeviceClaimService
{
    /** Tipos de dispositivo ofrecidos en el alta. */
    public const TIPOS = [
        'Eden Air Core'           => 'Monitor + actuadores ambientales (modelo completo).',
        'Monitor ambiental'       => 'Sólo lectura de variables del ambiente.',
        'Ambientador inteligente' => 'Enfocado en aroma y humidificación.',
        'Prototipo educativo'     => 'Maqueta de laboratorio para pruebas.',
    ];

    /**
     * Espacios disponibles → cómo se mapean al esquema de `spaces`.
     * Los que no tienen preset propio usan "personalizable" con su nombre.
     */
    public const ESPACIOS = [
        'dormitorio'  => ['label' => 'Dormitorio',  'preset' => 'dormitorio'],
        'living'      => ['label' => 'Living',      'preset' => 'personalizable'],
        'aula'        => ['label' => 'Aula',        'preset' => 'aula'],
        'oficina'     => ['label' => 'Oficina',     'preset' => 'oficina'],
        'cocina'      => ['label' => 'Cocina',      'preset' => 'personalizable'],
        'laboratorio' => ['label' => 'Laboratorio', 'preset' => 'personalizable'],
        'otro'        => ['label' => 'Otro espacio','preset' => 'personalizable'],
    ];

    private DeviceActivationCodeModel $codes;
    private DeviceModel $devices;
    private SpaceModel $spaces;
    private DeviceStateModel $states;
    private EnvironmentPresetService $presets;
    private SimulationService $simulation;

    public function __construct()
    {
        $this->codes      = new DeviceActivationCodeModel();
        $this->devices    = new DeviceModel();
        $this->spaces     = new SpaceModel();
        $this->states     = new DeviceStateModel();
        $this->presets    = new EnvironmentPresetService();
        $this->simulation = new SimulationService();
    }

    public function tiposDispositivo(): array
    {
        return self::TIPOS;
    }

    public function espacios(): array
    {
        return self::ESPACIOS;
    }

    public function esTipoValido(string $tipo): bool
    {
        return array_key_exists($tipo, self::TIPOS);
    }

    public function esEspacioValido(string $espacio): bool
    {
        return array_key_exists($espacio, self::ESPACIOS);
    }

    /**
     * Inspecciona un código sin canjearlo. Pensado para feedback visual del
     * formulario (paso 1 del asistente).
     *
     * @return array{ok: bool, estado: string, mensaje: string, code: ?array}
     */
    public function inspeccionarCodigo(string $codigo): array
    {
        $normalizado = DeviceActivationCodeModel::normalizar($codigo);

        if ($normalizado === '') {
            return ['ok' => false, 'estado' => 'vacio', 'mensaje' => 'Ingresá el código de activación.', 'code' => null];
        }

        if (! preg_match('/^EDEN-[A-Z0-9]{3,5}-[A-Z0-9]{3,5}$/', $normalizado)) {
            return ['ok' => false, 'estado' => 'formato', 'mensaje' => 'El formato debe ser EDEN-XXXX-XXXX.', 'code' => null];
        }

        $code = $this->codes->buscarPorCodigo($normalizado);

        if (! $code) {
            return ['ok' => false, 'estado' => 'inexistente', 'mensaje' => 'No encontramos ese código. Revisá el etiquetado del producto.', 'code' => null];
        }

        if (($code['status'] ?? '') === 'claimed') {
            return ['ok' => false, 'estado' => 'usado', 'mensaje' => 'Este código ya fue utilizado por otra cuenta.', 'code' => $code];
        }

        if (($code['status'] ?? '') === 'disabled') {
            return ['ok' => false, 'estado' => 'deshabilitado', 'mensaje' => 'Este código está deshabilitado. Contactá soporte.', 'code' => $code];
        }

        return [
            'ok'      => true,
            'estado'  => 'disponible',
            'mensaje' => 'Código válido · ' . ($code['device_type'] ?? 'Eden Air Core'),
            'code'    => $code,
        ];
    }

    /**
     * Canjea el código y crea el dispositivo + ambiente + estado.
     *
     * @param array{code:string, name:string, device_type:string, space:string, space_custom?:string, notes?:string} $datos
     * @return array{device: array, space: array}
     *
     * @throws RuntimeException con un mensaje apto para mostrar al usuario.
     */
    public function vincular(int $userId, array $datos): array
    {
        $inspeccion = $this->inspeccionarCodigo((string) ($datos['code'] ?? ''));

        if (! $inspeccion['ok']) {
            throw new RuntimeException($inspeccion['mensaje']);
        }

        $code   = $inspeccion['code'];
        $nombre = trim((string) ($datos['name'] ?? '')) ?: ($code['default_name'] ?? $code['device_type']);
        $tipo   = (string) ($datos['device_type'] ?? $code['device_type'] ?? 'Eden Air Core');

        if (! $this->esTipoValido($tipo)) {
            $tipo = (string) ($code['device_type'] ?? 'Eden Air Core');
        }

        $db = db_connect();
        $db->transStart();

        // 1) Ambiente. Dos caminos:
        //    a) Reusar uno existente del usuario (datos['space_id']).
        //    b) Crear uno nuevo (datos['space'] = key del catálogo + optional space_custom).
        $space = null;
        $existingId = (int) ($datos['space_id'] ?? 0);

        if ($existingId > 0) {
            $candidato = $this->spaces->find($existingId);
            if ($candidato && (int) ($candidato['user_id'] ?? 0) === $userId) {
                $space = $candidato;
            }
        }

        if ($space === null) {
            $espacioKey  = (string) ($datos['space'] ?? 'otro');
            $espacioMeta = self::ESPACIOS[$espacioKey] ?? self::ESPACIOS['otro'];

            $custom = $espacioMeta['preset'] === 'personalizable'
                ? (trim((string) ($datos['space_custom'] ?? '')) ?: $espacioMeta['label'])
                : '';

            $spaceData = $this->presets->buildSpaceData([
                'environment_type' => $espacioMeta['preset'],
                'custom_name'      => $custom,
            ]);
            $spaceId = (int) $this->spaces->insert(array_merge(['user_id' => $userId], $spaceData));
            $space   = $this->spaces->find($spaceId);
        }

        // 2) Dispositivo asociado a la cuenta.
        $deviceId = (int) $this->devices->insert([
            'user_id'         => $userId,
            'space_id'        => (int) $space['id'],
            'name'            => $nombre,
            'device_type'     => $tipo,
            'device_uid'      => 'EDN-' . strtoupper(bin2hex(random_bytes(4))),
            'api_token'       => bin2hex(random_bytes(20)),
            'is_simulated'    => 1,
            'is_active'       => 1,
            'status'          => 'simulated',
            'mac_address'     => $code['mac_address'] ?? null,
            'activation_code' => $code['code'],
            'notes'           => trim((string) ($datos['notes'] ?? '')) ?: null,
        ]);
        $device = $this->devices->find($deviceId);

        // 3) Estado inicial del dispositivo.
        $this->states->insert([
            'device_id'        => $deviceId,
            'operating_mode'   => 'automatic',
            'fan_state'        => 'off',
            'aromatizer_state' => 'off',
            'alert_led_state'  => 'off',
            'last_reason'      => 'Dispositivo vinculado por código de activación.',
            'updated_by'       => 'system',
        ]);

        // 4) Historial simulado para que el panel tenga datos de inmediato.
        $this->simulation->seedHistoryForDevice($device, $space);

        // 5) Marcar el código como usado (evita doble canje).
        $this->codes->marcarCanjeado((int) $code['id'], $userId, $deviceId);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new RuntimeException('No pudimos completar la vinculación. Intentá nuevamente.');
        }

        return ['device' => $device, 'space' => $space];
    }

    /**
     * Lista los dispositivos del usuario con metadatos listos para la vista
     * "Mis dispositivos" (etiqueta y tono de estado incluidos).
     */
    public function listarDeUsuario(int $userId): array
    {
        $devices = $this->devices->obtenerDeUsuario($userId);

        return array_map(function (array $d): array {
            [$estadoLabel, $estadoTono] = $this->estadoLegible((string) ($d['status'] ?? 'simulated'), $d['last_seen_at'] ?? null);

            $espacio = ($d['environment_type'] ?? '') === 'personalizable'
                ? (trim((string) ($d['custom_name'] ?? '')) ?: 'Personalizable')
                : $this->presets->getEnvironmentLabel((string) ($d['environment_type'] ?? 'hogar'));

            return [
                'id'            => (int) $d['id'],
                'nombre'        => (string) $d['name'],
                'tipo'          => (string) ($d['device_type'] ?? 'Eden Air Core'),
                'espacio'       => $espacio,
                'uid'           => (string) $d['device_uid'],
                'estado'        => (string) ($d['status'] ?? 'simulated'),
                'estado_label'  => $estadoLabel,
                'estado_tono'   => $estadoTono,
                'mac'           => $d['mac_address'] ?? null,
                'codigo'        => $d['activation_code'] ?? null,
                'notas'         => $d['notes'] ?? null,
                'es_simulado'   => (int) ($d['is_simulated'] ?? 1) === 1,
            ];
        }, $devices);
    }

    /** Texto + tono visual para cada estado de dispositivo. */
    public function estadoLegible(string $status, ?string $lastSeen): array
    {
        return match ($status) {
            'active'  => ['Activo', 'success'],
            'offline' => ['Sin conexión', 'danger'],
            'pending' => ['Pendiente de configuración', 'warning'],
            default   => ['Simulado', 'info'],
        };
    }
}
