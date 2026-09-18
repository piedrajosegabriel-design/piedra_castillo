<?php

namespace App\Controllers;

use App\Services\CompraService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Compra de EdenAir con Mercado Pago (Checkout Pro).
 *
 * El cobro NO pasa por nuestra web: el botón "Comprar" genera un link de pago
 * y el usuario paga en la página de Mercado Pago. Cuando termina, Mercado Pago
 * lo devuelve a resultado(), donde se confirma el pago contra la API.
 *
 * Toda la lógica vive en CompraService; este controller solo orquesta.
 *
 * Rutas (grupo panel, filtro auth → hay que haber iniciado sesión):
 *   GET  panel/compra            -> index()      pantalla del producto
 *   POST panel/compra/pagar      -> pagar()      genera el link y redirige
 *   GET  panel/compra/resultado  -> resultado()  vuelta desde Mercado Pago
 *
 * El aviso automático (webhook) lo recibe Api\MercadoPagoController.
 */
class CompraController extends BaseController
{
    /** Pantalla de compra: producto, botón y el historial de compras. */
    public function index(): string
    {
        $servicio = new CompraService();
        $userId   = $this->usuarioActual();

        $servicio->actualizarAbiertas($userId);

        return view('compra_mercadopago', [
            'producto' => $servicio->producto(),
            'compras'  => $servicio->listarDeUsuario($userId),
        ]);
    }

    /**
     * Clic en "Comprar EdenAir". Es un POST con CSRF (formulario de la vista),
     * no un link: generar un cobro es una acción, no una página.
     */
    public function pagar(): RedirectResponse
    {
        try {
            $urlPago = (new CompraService())->iniciarPago($this->usuarioActual());
        } catch (\RuntimeException $e) {
            // El detalle técnico va al log; el usuario ve un mensaje entendible.
            log_message('error', 'Mercado Pago (iniciar pago): ' . $e->getMessage());

            return redirect()->to('/panel/compra')
                ->with('error', 'No pudimos conectar con Mercado Pago. Probá de nuevo en unos minutos.');
        }

        // URL absoluta de Mercado Pago: redirect()->to() la respeta tal cual.
        return redirect()->to($urlPago);
    }

    /**
     * Vuelta desde Mercado Pago (back_urls). Llega con parámetros como
     *   ?payment_id=123&status=approved&external_reference=EA-...
     *
     * `status` NO se usa: se puede escribir a mano en la URL. Solo se toman la
     * referencia y el id para preguntarle a Mercado Pago qué pasó de verdad.
     * Después se redirige a la pantalla de compra, que queda con la URL limpia.
     */
    public function resultado(): RedirectResponse
    {
        $referencia = trim((string) $this->request->getGet('external_reference'));
        $paymentId  = trim((string) $this->request->getGet('payment_id'));

        // Si el usuario vuelve sin pagar, Mercado Pago manda payment_id=null.
        if (! ctype_digit($paymentId)) {
            $paymentId = '';
        }

        try {
            $resultado = (new CompraService())->confirmarRegreso($this->usuarioActual(), $referencia, $paymentId);
        } catch (\RuntimeException $e) {
            log_message('error', 'Mercado Pago (confirmar pago): ' . $e->getMessage());

            $resultado = [
                'tipo'    => 'info',
                'mensaje' => 'No pudimos confirmar el pago con Mercado Pago todavía. Volvé a entrar en unos minutos para ver el estado.',
            ];
        }

        return redirect()->to('/panel/compra')->with($resultado['tipo'], $resultado['mensaje']);
    }

    /** Devuelve el user_id guardado en sesión por el login. */
    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Métodos públicos (responden a rutas):
   - index()      → muestra el producto y "Tus compras" (antes re-consulta las
                    compras abiertas, por si se pagaron sin volver al sitio)
   - pagar()      → crea el link de pago y manda al usuario a Mercado Pago
   - resultado()  → vuelta de Mercado Pago: confirma el pago y deja un mensaje

   Helpers privados:
   - usuarioActual() → user_id de la sesión

   Funciones usadas acá:
   - CompraService::iniciarPago()/confirmarRegreso() → el flujo de cobro
   - redirect()->to($url)->with('success'|'info'|'error', $texto)
                  → (CI4) redirige y deja un mensaje flash (partials/flashes.php)
   - log_message('error', ...) → (CI4) escribe en writable/logs
   - ctype_digit() → (PHP) ¿el texto son solo dígitos?
   ============================================================================ */
