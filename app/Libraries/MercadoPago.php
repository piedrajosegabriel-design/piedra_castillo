<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\Services;
use RuntimeException;

/* ============================================================
   MercadoPago
   QUÉ HACE: habla con la API REST de Mercado Pago. Es un
   cliente chico, escrito a mano, con solo las cuatro cosas que
   usa EdenAir:
     1. crearPreferencia()  → arma el link de pago (Checkout Pro)
     2. obtenerPago()       → trae UN pago por su id
     3. buscarPagos()       → trae los pagos de una compra nuestra
     4. firmaValida()       → comprueba que un aviso (webhook)
                              venga de verdad de Mercado Pago

   POR QUÉ NO SE USA EL SDK OFICIAL: el SDK de PHP se instala con
   Composer y el proyecto no usa Composer (mismo criterio que
   QrCode). La API son cuatro llamadas HTTP con JSON, así que
   alcanza con el cliente cURL que ya trae CodeIgniter.

   CREDENCIALES (.env, nunca en el código):
     mercadopago.accessToken   → secreto del vendedor (servidor)
     mercadopago.webhookSecret → firma de los avisos (opcional)

   SE RELACIONA CON: CompraService (el flujo de compra) y
   Api\MercadoPagoController (recibe los avisos).
   ============================================================ */
class MercadoPago
{
    private const API = 'https://api.mercadopago.com';

    /** Segundos máximos de espera por respuesta de Mercado Pago. */
    private const TIMEOUT = 20;

    private string $accessToken;
    private CURLRequest $http;

    public function __construct(?string $accessToken = null)
    {
        $this->accessToken = trim((string) ($accessToken ?? env('mercadopago.accessToken', '')));

        if ($this->accessToken === '') {
            throw new RuntimeException('Falta mercadopago.accessToken en el .env.');
        }

        // Instancia propia (getShared = false): los headers de una llamada no
        // se mezclan con los de otra parte del sistema que use cURL.
        $this->http = Services::curlrequest([], null, null, false);
    }

    /** ¿Está cargado el access token? Sirve para avisar antes de intentar cobrar. */
    public static function configurado(): bool
    {
        return trim((string) env('mercadopago.accessToken', '')) !== '';
    }

    // -------------------------------------------------------------------------
    // 1) PREFERENCIA — el "link de pago"
    // -------------------------------------------------------------------------

    /**
     * Crea una preferencia de Checkout Pro.
     *
     * Una preferencia es la descripción de lo que se cobra (producto, precio,
     * a dónde vuelve el comprador). Mercado Pago responde con `init_point`:
     * la URL de SU página de pago, a la que se redirige al comprador.
     *
     * @param array<string, mixed> $datos Cuerpo tal como lo pide la API.
     * @return array{id:string, init_point:string}
     */
    public function crearPreferencia(array $datos): array
    {
        $respuesta = $this->llamar('POST', '/checkout/preferences', $datos);

        if (empty($respuesta['id']) || empty($respuesta['init_point'])) {
            throw new RuntimeException('Mercado Pago no devolvió el link de pago.');
        }

        return [
            'id'         => (string) $respuesta['id'],
            'init_point' => (string) $respuesta['init_point'],
        ];
    }

    // -------------------------------------------------------------------------
    // 2 y 3) PAGOS — la fuente de verdad sobre si se cobró o no
    // -------------------------------------------------------------------------

    /** Un pago por su id. Null si Mercado Pago no lo encuentra. */
    public function obtenerPago(string $paymentId): ?array
    {
        if (preg_match('/^\d{1,20}$/', $paymentId) !== 1) {
            return null;
        }

        return $this->llamar('GET', '/v1/payments/' . $paymentId, null, true);
    }

    /**
     * Pagos asociados a una referencia nuestra (`external_reference`), del más
     * nuevo al más viejo. Una misma compra puede tener varios intentos: por
     * ejemplo, una tarjeta rechazada y después otra aprobada.
     */
    public function buscarPagos(string $referencia): array
    {
        $consulta = http_build_query([
            'external_reference' => $referencia,
            'sort'               => 'date_created',
            'criteria'           => 'desc',
        ]);

        $respuesta = $this->llamar('GET', '/v1/payments/search?' . $consulta);

        return is_array($respuesta['results'] ?? null) ? $respuesta['results'] : [];
    }

    // -------------------------------------------------------------------------
    // 4) FIRMA DE LOS AVISOS (webhooks)
    // -------------------------------------------------------------------------

    /**
     * Verifica el header `x-signature` de un aviso de Mercado Pago.
     *
     * El header trae "ts=...,v1=...". v1 es un HMAC-SHA256 que Mercado Pago
     * calcula con la clave secreta de Webhooks sobre este texto:
     *
     *     id:{data.id};request-id:{x-request-id};ts:{ts};
     *
     * Si nosotros calculamos lo mismo y da igual, el aviso es auténtico.
     *
     * Sin `mercadopago.webhookSecret` en el .env no hay con qué comparar y se
     * acepta el aviso. No es un agujero: el aviso solo trae un id y el sistema
     * igual le pregunta a Mercado Pago el estado real de ese pago.
     */
    public static function firmaValida(string $firma, string $requestId, string $dataId): bool
    {
        $secreto = trim((string) env('mercadopago.webhookSecret', ''));

        if ($secreto === '') {
            return true;
        }

        $partes = [];

        foreach (explode(',', $firma) as $par) {
            [$clave, $valor] = array_pad(explode('=', trim($par), 2), 2, '');
            $partes[$clave]  = $valor;
        }

        if (($partes['ts'] ?? '') === '' || ($partes['v1'] ?? '') === '') {
            return false;
        }

        // Si algún dato no vino, se saca del texto (así lo documenta Mercado Pago).
        $manifiesto = ($dataId !== '' ? 'id:' . strtolower($dataId) . ';' : '')
            . ($requestId !== '' ? 'request-id:' . $requestId . ';' : '')
            . 'ts:' . $partes['ts'] . ';';

        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $partes['v1']);
    }

    // -------------------------------------------------------------------------
    // HTTP
    // -------------------------------------------------------------------------

    /**
     * Hace la llamada y devuelve el JSON como array.
     *
     * Cualquier error (sin conexión, credenciales mal, datos inválidos) termina
     * en RuntimeException con el mensaje de Mercado Pago, para que quede en el
     * log. Con `$nullSi404` un 404 no es error: es "ese pago no existe".
     */
    private function llamar(string $metodo, string $ruta, ?array $cuerpo = null, bool $nullSi404 = false): ?array
    {
        $opciones = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept'        => 'application/json',
            ],
            'timeout'     => self::TIMEOUT,
            'http_errors' => false,   // los 4xx/5xx se leen acá, no como excepción de cURL
        ];

        if ($cuerpo !== null) {
            $opciones['json'] = $cuerpo;
        }

        try {
            $respuesta = $this->http->request($metodo, self::API . $ruta, $opciones);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo conectar con Mercado Pago: ' . $e->getMessage(), 0, $e);
        }

        $codigo = $respuesta->getStatusCode();
        $json   = json_decode((string) $respuesta->getBody(), true);

        if ($codigo === 404 && $nullSi404) {
            return null;
        }

        if ($codigo < 200 || $codigo >= 300) {
            $detalle = is_array($json) ? ($json['message'] ?? $json['error'] ?? '') : '';

            throw new RuntimeException('Mercado Pago respondió ' . $codigo . ($detalle !== '' ? ': ' . $detalle : '.'));
        }

        return is_array($json) ? $json : [];
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   - configurado()               → ¿hay access token en el .env?
   - crearPreferencia($datos)    → POST /checkout/preferences; devuelve el id y
                                   el init_point (URL de la página de pago)
   - obtenerPago($id)            → GET /v1/payments/{id}; null si no existe
   - buscarPagos($referencia)    → GET /v1/payments/search?external_reference=...
   - firmaValida($firma, ...)    → comprueba el HMAC del header x-signature
   - llamar(...)                 → la llamada HTTP en sí (token, JSON, errores)

   Conceptos de Mercado Pago:
   - Access token        → credencial SECRETA del vendedor; con ella se cobra
   - Preferencia         → qué se vende, cuánto sale y a dónde se vuelve
   - init_point          → la URL del checkout de Mercado Pago para esa preferencia
   - external_reference  → código NUESTRO que viaja con el pago; así un pago se
                           asocia a una fila de `purchases`
   - Webhook             → aviso que manda Mercado Pago cuando un pago cambia

   Funciones clave:
   - Services::curlrequest(..., false) → (CI4) cliente HTTP propio, no compartido
   - hash_hmac('sha256', ...)          → (PHP) firma con clave secreta
   - hash_equals()                     → (PHP) compara sin filtrar tiempos
   ============================================================================ */
