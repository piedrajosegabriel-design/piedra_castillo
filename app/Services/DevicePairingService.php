<?php

namespace App\Services;

use App\Libraries\QrCode;
use App\Models\DeviceModel;
use App\Models\DevicePairingModel;
use App\Models\DeviceStateModel;
use App\Models\SpaceModel;
use RuntimeException;

/* ============================================================
   DevicePairingService
   QUÉ HACE: maneja todo el ciclo de "conectar un dispositivo",
   sin códigos de activación y sin pasos intermedios.

   EL FLUJO COMPLETO
     1. El usuario aprieta "Conectar" en la web.
        → abrirVentana(): se abre una VENTANA DE VINCULACIÓN de
          10 minutos y se genera, en el momento, el QR con las
          credenciales del WiFi de configuración del equipo.
     2. Escanea el QR con la cámara del celular.
        → El celular entra solo a la red del Eden Air (el QR está
          en el formato estándar WIFI:, el mismo que usa cualquier
          cartel de WiFi) y se abre solo el portal del equipo.
     3. En ese portal elige su WiFi de casa y pone la clave.
     4. El equipo se conecta a internet y llama a la API.
        → registrarDispositivo(): el equipo que aparece mientras la
          ventana está abierta queda dado de alta en esa cuenta,
          con ambiente y estado inicial, y recibe sus credenciales.
     5. La página, que venía preguntando cada 2 segundos, ve que se
        vinculó y muestra el equipo listo.

   El usuario no escribe ningún código ni busca nada en la caja.

   POR QUÉ EL QR LLEVA EL WIFI DEL EQUIPO Y NO EL DE TU CASA
   Una ESP32 no tiene cámara: no puede leer un QR. El que escanea
   es siempre el celular. Por eso el QR lleva la red que publica
   el equipo, que es lo único que el celular puede usar para
   ponerse en contacto con él.

   SE RELACIONA CON: QrCode (dibuja el QR), DevicePairingModel,
   DeviceModel, SpaceModel, DeviceStateModel y
   EnvironmentPresetService (ambiente por defecto).
   Lo usan DispositivosController y DeviceApiController.
   ============================================================ */
class DevicePairingService
{
    /** Cuánto dura abierta una ventana de vinculación. */
    public const MINUTOS_VENTANA = 10;

    /**
     * Credenciales del WiFi de configuración que publica el equipo.
     *
     * Son una CONSTANTE DEL FIRMWARE: la ESP32 tiene que crear su punto de
     * acceso con exactamente este nombre y esta clave, porque la web arma el
     * QR sin poder preguntarle nada al equipo (todavía no está en la red).
     * Si en el firmware se cambian, se cambian acá también — o se sobreescriben
     * desde el `.env` con `edenair.apSsid` y `edenair.apPassword`.
     */
    public const AP_SSID_DEFECTO     = 'EdenAir-Setup';
    public const AP_PASSWORD_DEFECTO = 'edenair.setup';

    /** Nombre base de los equipos dados de alta (después se puede cambiar). */
    private const NOMBRE_BASE = 'Eden Air';

    private DevicePairingModel $ventanas;
    private DeviceModel $devices;
    private SpaceModel $spaces;
    private DeviceStateModel $states;
    private EnvironmentPresetService $presets;

    public function __construct()
    {
        $this->ventanas = new DevicePairingModel();
        $this->devices  = new DeviceModel();
        $this->spaces   = new SpaceModel();
        $this->states   = new DeviceStateModel();
        $this->presets  = new EnvironmentPresetService();
    }

    // -------------------------------------------------------------------------
    // 1) ABRIR LA VENTANA (lo que pasa al apretar "Conectar")
    // -------------------------------------------------------------------------

    /**
     * Abre una ventana de vinculación y devuelve todo lo que la pantalla
     * necesita mostrar, incluido el SVG del QR ya dibujado.
     *
     * Se cancelan las ventanas anteriores del usuario: siempre hay una sola
     * abierta por cuenta, así no queda ninguna colgada esperando un equipo.
     *
     * @return array{token:string, ssid:string, password:string, payload:string,
     *               svg:string, expira_en:int, expires_at:string}
     */
    public function abrirVentana(int $userId, ?string $ip = null): array
    {
        $this->ventanas->marcarVencidas();
        $this->ventanas->cancelarAbiertasDe($userId);

        $token   = bin2hex(random_bytes(16));
        $expira  = date('Y-m-d H:i:s', time() + self::MINUTOS_VENTANA * 60);

        $this->ventanas->insert([
            'user_id'     => $userId,
            'token'       => $token,
            'ap_ssid'     => self::ssid(),
            'ap_password' => self::password(),
            'status'      => 'esperando',
            'client_ip'   => $ip,
            'expires_at'  => $expira,
        ]);

        return $this->paquetePantalla($this->ventanas->buscarPorToken($token));
    }

    /**
     * Arma el paquete de datos de una ventana para la pantalla: credenciales,
     * QR dibujado y cuántos segundos le quedan.
     */
    public function paquetePantalla(array $ventana, int $tamanoQr = 260): array
    {
        $payload = self::payloadWifi((string) $ventana['ap_ssid'], (string) $ventana['ap_password']);

        return [
            'token'      => (string) $ventana['token'],
            'ssid'       => (string) $ventana['ap_ssid'],
            'password'   => (string) $ventana['ap_password'],
            'payload'    => $payload,
            'svg'        => (new QrCode($payload))->toSvg($tamanoQr),
            'expira_en'  => max(0, strtotime((string) $ventana['expires_at']) - time()),
            'expires_at' => (string) $ventana['expires_at'],
        ];
    }

    /**
     * Texto que se codifica adentro del QR. Es el formato estándar de WiFi
     * (el mismo que genera cualquier router moderno): al escanearlo, el celular
     * ofrece conectarse a esa red sin que nadie escriba la clave.
     *
     *   WIFI:T:WPA;S:EdenAir-Setup;P:edenair.setup;;
     */
    public static function payloadWifi(string $ssid, string $password): string
    {
        if ($password === '') {
            return 'WIFI:T:nopass;S:' . self::escaparWifi($ssid) . ';;';
        }

        return 'WIFI:T:WPA;S:' . self::escaparWifi($ssid) . ';P:' . self::escaparWifi($password) . ';;';
    }

    /** En el formato WIFI: los caracteres \ ; , : y " se escapan con barra. */
    private static function escaparWifi(string $valor): string
    {
        return str_replace(
            ['\\', ';', ',', ':', '"'],
            ['\\\\', '\\;', '\\,', '\\:', '\\"'],
            $valor
        );
    }

    public static function ssid(): string
    {
        return (string) (env('edenair.apSsid') ?: self::AP_SSID_DEFECTO);
    }

    public static function password(): string
    {
        return (string) (env('edenair.apPassword') ?: self::AP_PASSWORD_DEFECTO);
    }

    // -------------------------------------------------------------------------
    // 2) CONSULTAR / CERRAR LA VENTANA (lo que pregunta la página mientras espera)
    // -------------------------------------------------------------------------

    /**
     * Estado de una ventana, para el sondeo de la página.
     *
     * @return array{estado:string, mensaje:string, expira_en:int, device:?array}
     *         estado: esperando | vinculado | expirado | cancelado | desconocida
     */
    public function estado(string $token, int $userId): array
    {
        $ventana = $this->ventanas->buscarPorToken($token);

        if (! $ventana || (int) $ventana['user_id'] !== $userId) {
            return ['estado' => 'desconocida', 'mensaje' => 'Esta vinculación ya no existe.', 'expira_en' => 0, 'device' => null];
        }

        $restante = max(0, strtotime((string) $ventana['expires_at']) - time());

        if ($ventana['status'] === 'vinculado') {
            $device = $ventana['device_id'] ? $this->devices->find((int) $ventana['device_id']) : null;

            return [
                'estado'    => 'vinculado',
                'mensaje'   => $device
                    ? '“' . $device['name'] . '” quedó conectado a tu cuenta.'
                    : 'Tu Eden Air quedó conectado a tu cuenta.',
                'expira_en' => 0,
                'device'    => $device,
            ];
        }

        if ($ventana['status'] === 'esperando' && $restante <= 0) {
            $this->ventanas->update((int) $ventana['id'], ['status' => 'expirado']);

            return ['estado' => 'expirado', 'mensaje' => 'Se agotó el tiempo. Volvé a apretar "Conectar".', 'expira_en' => 0, 'device' => null];
        }

        if ($ventana['status'] !== 'esperando') {
            return [
                'estado'    => (string) $ventana['status'],
                'mensaje'   => $ventana['status'] === 'expirado'
                    ? 'Se agotó el tiempo. Volvé a apretar "Conectar".'
                    : 'Esta vinculación se canceló.',
                'expira_en' => 0,
                'device'    => null,
            ];
        }

        return [
            'estado'    => 'esperando',
            'mensaje'   => 'Esperando a que tu Eden Air se conecte…',
            'expira_en' => $restante,
            'device'    => null,
        ];
    }

    /** Cierra la ventana a pedido del usuario (botón "Cancelar"). */
    public function cancelar(string $token, int $userId): void
    {
        $ventana = $this->ventanas->buscarPorToken($token);

        if ($ventana && (int) $ventana['user_id'] === $userId && $ventana['status'] === 'esperando') {
            $this->ventanas->update((int) $ventana['id'], ['status' => 'cancelado']);
        }
    }

    // -------------------------------------------------------------------------
    // 3) EL EQUIPO SE PRESENTA (lo llama la API, sin token: justamente viene a
    //    buscarlo)
    // -------------------------------------------------------------------------

    /**
     * Da de alta el equipo que acaba de salir a internet.
     *
     * Casos:
     *  - La MAC ya está registrada → se le devuelven SUS credenciales de
     *    siempre. Así un equipo que se reinicia o al que le regrabaron el
     *    firmware vuelve a entrar sin duplicarse.
     *  - Hay una ventana abierta → se crea el dispositivo en esa cuenta.
     *  - No hay ninguna ventana → 'sin_ventana': no es un error, es que el
     *    dueño todavía no apretó "Conectar". El firmware reintenta.
     *
     * @param array{mac?:string, firmware?:string, nombre?:string} $datos
     * @return array{estado:string, mensaje:string, device:?array}
     */
    public function registrarDispositivo(array $datos, ?string $ipDispositivo = null): array
    {
        $mac = strtoupper(trim((string) ($datos['mac'] ?? '')));

        if ($mac === '') {
            return ['estado' => 'invalido', 'mensaje' => 'El equipo no informó su MAC.', 'device' => null];
        }

        // ¿Este equipo ya fue dado de alta antes? Le devolvemos lo suyo.
        $existente = $this->devices->where('mac_address', $mac)->first();

        if ($existente) {
            $this->devices->update((int) $existente['id'], [
                'status'       => 'active',
                'last_seen_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'estado'  => 'ok',
                'mensaje' => 'Equipo ya vinculado: se reenvían sus credenciales.',
                'device'  => $this->devices->find((int) $existente['id']),
            ];
        }

        $this->ventanas->marcarVencidas();
        $ventana = $this->elegirVentana($ipDispositivo);

        if (! $ventana) {
            return [
                'estado'  => 'sin_ventana',
                'mensaje' => 'Nadie está esperando este equipo. Entrá a la web y apretá "Conectar".',
                'device'  => null,
            ];
        }

        return $this->crearDispositivo($ventana, $mac, $datos);
    }

    /**
     * Elige a qué ventana pertenece el equipo que se presentó.
     *
     * Si hay varias abiertas (dos personas conectando su equipo al mismo
     * tiempo), se prefiere la que se abrió desde la MISMA RED que el equipo:
     * el navegador del dueño y su Eden Air comparten el WiFi de la casa, así
     * que sus IP caen en el mismo /24. Si eso no desempata, se toma la más
     * reciente.
     */
    private function elegirVentana(?string $ipDispositivo): ?array
    {
        $abiertas = $this->ventanas->abiertas();

        if ($abiertas === []) {
            return null;
        }

        if ($ipDispositivo !== null && count($abiertas) > 1) {
            $redEquipo = self::red24($ipDispositivo);

            foreach ($abiertas as $ventana) {
                if ($redEquipo !== null && self::red24((string) ($ventana['client_ip'] ?? '')) === $redEquipo) {
                    return $ventana;
                }
            }
        }

        return $abiertas[0];
    }

    /** Los tres primeros octetos de una IPv4 ("192.168.1.7" → "192.168.1."). */
    private static function red24(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return null;
        }

        return implode('.', array_slice(explode('.', $ip), 0, 3)) . '.';
    }

    /**
     * Crea ambiente (si hace falta) + dispositivo + estado inicial, y cierra la
     * ventana. Todo en una transacción: o queda todo, o no queda nada.
     */
    private function crearDispositivo(array $ventana, string $mac, array $datos): array
    {
        $userId = (int) $ventana['user_id'];

        $db = db_connect();
        $db->transStart();

        // Ambiente: se reusa el primero del usuario; si no tiene ninguno, se
        // crea uno "hogar" que después puede editar desde Ambientes.
        $space = $this->spaces->where('user_id', $userId)->orderBy('id', 'ASC')->first();

        if (! $space) {
            $spaceData = $this->presets->buildSpaceData(['environment_type' => 'hogar']);
            $spaceId   = (int) $this->spaces->insert(array_merge(['user_id' => $userId], $spaceData));
            $space     = $this->spaces->find($spaceId);
        }

        $deviceId = (int) $this->devices->insert([
            'user_id'      => $userId,
            'space_id'     => (int) $space['id'],
            'name'         => $this->nombreLibre($userId, (string) ($datos['nombre'] ?? '')),
            'device_type'  => 'Eden Air Core',
            'device_uid'   => 'EDN-' . strtoupper(bin2hex(random_bytes(4))),
            'api_token'    => bin2hex(random_bytes(20)),
            'is_simulated' => 0,
            'is_active'    => 1,
            // Ya está en la red: se presentó por HTTP para pedir credenciales.
            'status'       => 'active',
            'mac_address'  => $mac,
            'notes'        => ($datos['firmware'] ?? '') !== '' ? 'Firmware ' . $datos['firmware'] : null,
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);

        $this->states->insert([
            'device_id'        => $deviceId,
            'operating_mode'   => 'automatic',
            'fan_state'        => 'off',
            'aromatizer_state' => 'off',
            'alert_led_state'  => 'off',
            'last_reason'      => 'Equipo conectado por QR de vinculación.',
            'updated_by'       => 'system',
        ]);

        $this->ventanas->update((int) $ventana['id'], [
            'status'       => 'vinculado',
            'device_id'    => $deviceId,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new RuntimeException('No se pudo dar de alta el dispositivo.');
        }

        return [
            'estado'  => 'ok',
            'mensaje' => 'Dispositivo vinculado.',
            'device'  => $this->devices->find($deviceId),
        ];
    }

    /** "Eden Air", "Eden Air 2", "Eden Air 3"… según cuántos tenga la cuenta. */
    private function nombreLibre(int $userId, string $sugerido): string
    {
        $sugerido = trim($sugerido);

        if ($sugerido !== '') {
            return mb_substr($sugerido, 0, 60);
        }

        $cuantos = $this->devices->where('user_id', $userId)->countAllResults();

        return $cuantos === 0 ? self::NOMBRE_BASE : self::NOMBRE_BASE . ' ' . ($cuantos + 1);
    }

    // -------------------------------------------------------------------------
    // 4) LISTADO PARA "MIS DISPOSITIVOS"
    // -------------------------------------------------------------------------

    /**
     * Lista los dispositivos del usuario con metadatos listos para la vista
     * (nombre del ambiente, etiqueta y tono del estado).
     */
    public function listarDeUsuario(int $userId): array
    {
        $devices = $this->devices->obtenerDeUsuario($userId);

        return array_map(function (array $d): array {
            [$estadoLabel, $estadoTono] = $this->estadoLegible((string) ($d['status'] ?? 'pending'));

            $espacio = ($d['environment_type'] ?? '') === 'personalizable'
                ? (trim((string) ($d['custom_name'] ?? '')) ?: 'Personalizable')
                : $this->presets->getEnvironmentLabel((string) ($d['environment_type'] ?? 'hogar'));

            return [
                'id'           => (int) $d['id'],
                'nombre'       => (string) $d['name'],
                'tipo'         => (string) ($d['device_type'] ?? 'Eden Air Core'),
                'espacio'      => $espacio,
                'uid'          => (string) $d['device_uid'],
                'estado'       => (string) ($d['status'] ?? 'pending'),
                'estado_label' => $estadoLabel,
                'estado_tono'  => $estadoTono,
                'mac'          => $d['mac_address'] ?? null,
                'notas'        => $d['notes'] ?? null,
                'visto'        => $d['last_seen_at'] ?? null,
            ];
        }, $devices);
    }

    /** Texto + tono visual para cada estado de dispositivo. */
    public function estadoLegible(string $status): array
    {
        return match ($status) {
            'active'  => ['Conectado', 'success'],
            'offline' => ['Sin conexión', 'danger'],
            default   => ['Esperando primera lectura', 'info'],
        };
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Abrir la ventana (web):
   - abrirVentana($userId, $ip)   → cierra las anteriores, crea la nueva y
                                    devuelve el paquete con el QR dibujado
   - paquetePantalla($ventana)    → credenciales + SVG + segundos restantes
   - payloadWifi($ssid, $pass)    → el texto que va adentro del QR
                                    (formato estándar WIFI:T:WPA;S:...;P:...;;)
   - escaparWifi($valor)          → escapa \ ; , : " como pide ese formato
   - ssid()/password()            → constantes del firmware, sobreescribibles
                                    desde el .env

   Esperar (web):
   - estado($token, $userId)      → esperando | vinculado | expirado |
                                    cancelado | desconocida
   - cancelar($token, $userId)    → cierra la ventana a pedido del usuario

   El equipo aparece (API):
   - registrarDispositivo($datos, $ip) → alta del equipo; si la MAC ya existe
                                    devuelve sus credenciales de siempre
   - elegirVentana($ip)           → a qué cuenta pertenece el equipo
   - red24($ip)                   → "192.168.1.7" → "192.168.1." (desempate)
   - crearDispositivo(...)        → ambiente + dispositivo + estado, en
                                    transacción, y cierra la ventana
   - nombreLibre($userId, ...)    → "Eden Air", "Eden Air 2", …

   Listado:
   - listarDeUsuario($userId)     → dispositivos con etiquetas listas para la vista
   - estadoLegible($status)       → [texto, tono] para el badge

   Funciones clave:
   - bin2hex(random_bytes(n))     → token de la ventana, device_uid y api_token
   - db_connect()->transStart()/transComplete()/transStatus() → todo o nada
   - filter_var($ip, FILTER_VALIDATE_IP) → (PHP) valida que sea una IP real
   - match ($status) { ... }      → (PHP 8) switch que devuelve un valor
   ============================================================================ */
