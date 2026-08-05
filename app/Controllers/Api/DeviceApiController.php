<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DeviceModel;
use App\Models\SpaceModel;
use App\Services\CommandService;
use App\Services\DeviceConfigService;
use App\Services\DevicePairingService;
use App\Services\MeasurementService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DeviceApiController — la API REST que consume el ESP32 (hardware).
 *
 * A diferencia del resto del sistema, acá NO hay sesión de usuario:
 * el dispositivo se autentica con su token secreto (header X-Device-Token)
 * que se compara contra devices.api_token. Estas rutas están exentas de CSRF.
 *
 * Los endpoints (ver Routes.php, grupo api/devices):
 *   POST pair                    → el ESP32 se da de alta y recibe credenciales
 *   POST .../measurements        → el ESP32 sube una medición
 *   GET  .../commands/pending    → el ESP32 pregunta qué comandos ejecutar
 *   POST .../commands/N/executed → el ESP32 confirma que ejecutó un comando
 *
 * Cuerpo esperado en measurements (lo que da el sensor SCD41):
 *   { "temperature": 24.6, "humidity": 58.2, "co2_ppm": 812 }
 * El índice de calidad de aire lo calcula el servidor; el dispositivo puede
 * mandarlo (air_quality_index, 0–100) pero no está obligado.
 */
class DeviceApiController extends BaseController
{
    // =========================================================================
    // VINCULACIÓN
    // El equipo, apenas se conecta al WiFi de la casa, se presenta acá con su
    // MAC y pide sus credenciales. Es el único endpoint que NO exige token:
    // justamente viene a buscarlo.
    //
    // No manda ningún código: queda asociado a la cuenta que en ese momento
    // tenga abierta la ventana de vinculación (el usuario apretó "Conectar").
    //
    // `session` es el código que el equipo inventó en su portal de configuración
    // y que ya le entregó al celular del usuario. Guardarlo acá es lo que le
    // permite a ese celular seguir la vinculación sin iniciar sesión.
    //
    // Cuerpo esperado:
    //   { "mac": "A1:B2:C3:D4:E5:F6", "firmware": "1.0.0", "session": "a1b2..." }
    // =========================================================================
    public function pair()
    {
        $payload = $this->getJsonPayload();

        $resultado = (new DevicePairingService())->registrarDispositivo([
            'mac'      => (string) ($payload['mac'] ?? ''),
            'firmware' => (string) ($payload['firmware'] ?? ''),
            'nombre'   => (string) ($payload['nombre'] ?? ''),
            'session'  => (string) ($payload['session'] ?? ''),
        ], $this->request->getIPAddress());

        if ($resultado['estado'] === 'invalido') {
            return $this->responderErroresValidacion(['mac' => $resultado['mensaje']]);
        }

        // Nadie está esperando este equipo todavía: no es un error del hardware,
        // es que el dueño aún no apretó "Conectar". El firmware reintenta.
        if ($resultado['estado'] === 'sin_ventana') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_ACCEPTED)->setJSON([
                'status'          => 'esperando_vinculacion',
                'message'         => $resultado['mensaje'],
                'reintentar_en'   => 15,   // segundos sugeridos hasta el próximo intento
            ]);
        }

        $device = $resultado['device'];

        return $this->response->setJSON([
            'status'          => 'success',
            'message'         => $resultado['mensaje'],
            'device_uid'      => $device['device_uid'],
            'api_token'       => $device['api_token'],
            'device_name'     => $device['name'],
            'intervalo_envio' => 300,   // segundos sugeridos entre mediciones
        ]);
    }

    // =========================================================================
    // CONFIGURACIÓN DEL DISPOSITIVO
    // El equipo pide con qué reglas tiene que decidir: los umbrales del
    // ambiente, los márgenes de alerta y el modo (automático / manual).
    //
    // El servidor NO decide cuándo prender un actuador; eso lo hace el ESP32.
    // Acá solo le decimos con qué números comparar. Así el usuario puede
    // cambiar los rangos desde /panel/ambientes sin reprogramar la placa, y
    // el equipo sigue funcionando aunque se corte internet.
    // =========================================================================
    public function config(string $deviceUid)
    {
        try {
            [$device, $space] = $this->resolveAuthenticatedDevice($deviceUid);
        } catch (\InvalidArgumentException $exception) {
            return $this->responderNoAutorizado($exception->getMessage());
        }

        $this->actualizarActividadDispositivo((int) $device['id']);

        return $this->response->setJSON([
            'status' => 'success',
        ] + (new DeviceConfigService())->paraDispositivo($device, $space));
    }

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

        // El dispositivo manda lo que mide (temperatura, humedad, CO₂). Si algo
        // falta o es imposible, MeasurementService corta con una excepción: el
        // servidor NO completa datos por su cuenta.
        try {
            $result = (new MeasurementService())->registrar($device, $space, $payload, 'api');
        } catch (\InvalidArgumentException $exception) {
            return $this->responderErroresValidacion(['payload' => $exception->getMessage()]);
        }

        $this->actualizarActividadDispositivo((int) $device['id']);

        return $this->response->setJSON([
            'status'      => 'success',
            'message'     => 'Medicion recibida correctamente.',
            'measurement' => $result['measurement'],
            // Qué actuadores cambiaron de estado respecto de lo que el
            // servidor tenía registrado. Vacío = no cambió nada.
            'actuadores'  => $result['actuadores'],
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

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Endpoints públicos (rutas api/devices/...):
   - pair()                          → alta del equipo por MAC durante la
                                       ventana de vinculación; devuelve
                                       device_uid + api_token. 202 si todavía
                                       nadie apretó "Conectar" en la web.
   - storeMeasurement($uid)          → recibe y guarda una medición del ESP32;
                                       corre la automatización y responde JSON
   - pendingCommands($uid)           → lista los comandos pendientes del dispositivo
   - markCommandExecuted($uid, $id)  → marca un comando como ejecutado

   Helpers privados:
   - resolveAuthenticatedDevice()    → busca el device por device_uid y compara
                                       el token; si falla lanza excepción → 401
   - getJsonPayload()                → lee el body JSON (o cae a POST clásico)
   - (la validación de la medición vive en MeasurementService::registrar)
   - actualizarActividadDispositivo()→ actualiza last_seen_at (y sync de comandos)
   - responderNoAutorizado()         → JSON de error con HTTP 401
   - responderErroresValidacion()    → JSON de error con HTTP 422
   - responderNoEncontrado()         → JSON de error con HTTP 404
   - formatCommand()                 → da forma al comando para el JSON de salida

   Conceptos clave:
   - device_uid  → identificador PÚBLICO del dispositivo (va en la URL)
   - api_token   → secreto PRIVADO del dispositivo (va en el header)
   - getHeaderLine('X-Device-Token') → (CI4) lee un header HTTP del request
   - getJSON(true)                   → (CI4) decodifica el body JSON a array
   - setStatusCode()/setJSON()       → (CI4) arman la respuesta HTTP de la API
   - json_decode($texto, true)       → (PHP) convierte texto JSON en array
   ============================================================================ */
