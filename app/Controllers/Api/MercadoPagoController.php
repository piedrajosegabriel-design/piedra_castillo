<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\MercadoPago;
use App\Services\CompraService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * MercadoPagoController — recibe los avisos (webhooks) de Mercado Pago.
 *
 * Cada vez que un pago se crea o cambia de estado, Mercado Pago hace un POST
 * a `api/pagos/mercadopago`. No hay sesión (quien llama es Mercado Pago, no
 * un usuario) y la ruta está exenta de CSRF por estar bajo api/*.
 *
 * El aviso NO trae el estado del pago, solo su id:
 *   POST api/pagos/mercadopago?data.id=123456&type=payment
 *   { "action": "payment.updated", "type": "payment", "data": { "id": "123456" } }
 *
 * Por eso no hace falta creerle: CompraService consulta ese pago a la API con
 * nuestro access token y aplica lo que responde Mercado Pago.
 *
 * Solo funciona con el sitio publicado (o un túnel tipo ngrok): Mercado Pago
 * no puede llegar a localhost. En local, el pago se confirma cuando el
 * comprador vuelve al sitio (CompraController::resultado).
 */
class MercadoPagoController extends BaseController
{
    public function notificacion(): ResponseInterface
    {
        $cuerpo = $this->request->getJSON(true);
        $cuerpo = is_array($cuerpo) ? $cuerpo : [];

        // Webhooks mandan `type` + `data.id`; el formato viejo (IPN), `topic` + `id`.
        // PHP convierte el "data.id" de la URL en "data_id".
        $tipo = (string) ($this->request->getGet('type') ?? $this->request->getGet('topic') ?? $cuerpo['type'] ?? '');
        $id   = (string) ($this->request->getGet('data_id') ?? $cuerpo['data']['id'] ?? $this->request->getGet('id') ?? '');

        if (! MercadoPago::firmaValida(
            $this->request->getHeaderLine('x-signature'),
            $this->request->getHeaderLine('x-request-id'),
            (string) ($this->request->getGet('data_id') ?? '')
        )) {
            return $this->responder(ResponseInterface::HTTP_UNAUTHORIZED, 'Firma inválida.');
        }

        // Solo interesan los pagos. Otros avisos (merchant_order, etc.) se
        // confirman con 200 para que Mercado Pago no los reintente.
        if ($tipo !== 'payment' || ! ctype_digit($id)) {
            return $this->responder(ResponseInterface::HTTP_OK, 'Aviso ignorado.');
        }

        try {
            $aplicado = (new CompraService())->procesarAviso($id);
        } catch (\RuntimeException $e) {
            // 500 → Mercado Pago reintenta más tarde. Es lo que queremos si
            // justo no pudimos consultar su API.
            log_message('error', 'Mercado Pago (webhook pago ' . $id . '): ' . $e->getMessage());

            return $this->responder(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR, 'No se pudo consultar el pago.');
        }

        return $this->responder(ResponseInterface::HTTP_OK, $aplicado ? 'Pago aplicado.' : 'El pago no pertenece a ninguna compra.');
    }

    private function responder(int $codigo, string $mensaje): ResponseInterface
    {
        return $this->response->setStatusCode($codigo)->setJSON([
            'status'  => $codigo < 300 ? 'success' : 'error',
            'message' => $mensaje,
        ]);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   - notificacion()  → recibe el aviso, verifica la firma (si hay clave en el
                       .env) y, si es de un pago, lo aplica vía CompraService
   - responder()     → JSON corto + código HTTP

   Códigos que ve Mercado Pago:
   - 200 → recibido (aplicado o ignorado a propósito): no reintenta
   - 401 → firma inválida: no viene de Mercado Pago
   - 500 → no pudimos consultar su API: Mercado Pago reintenta después

   Funciones usadas acá:
   - getJSON(true)            → (CI4) body JSON como array
   - getGet('data_id')        → (CI4) el ?data.id= de la URL (PHP cambia . por _)
   - getHeaderLine('x-signature') → (CI4) header con la firma HMAC
   ============================================================================ */
