<?php

namespace App\Services;

use App\Libraries\MercadoPago;
use App\Models\PurchaseModel;
use App\Models\UserModel;
use RuntimeException;

/* ============================================================
   CompraService
   QUÉ HACE: todo el ciclo de "comprar un EdenAir" cobrando con
   Mercado Pago (Checkout Pro).

   EL FLUJO COMPLETO
     1. El usuario aprieta "Comprar EdenAir".
        → iniciarPago(): se genera una referencia propia, se le
          pide a Mercado Pago un link de pago (preferencia) y se
          guarda la compra como `iniciada`. El navegador se va a
          la página de pago de Mercado Pago.
     2. El usuario paga ALLÁ (tarjeta, dinero en cuenta, efectivo).
        Nuestra web nunca ve datos de tarjeta.
     3. Mercado Pago lo devuelve a /panel/compra/resultado.
        → confirmarRegreso(): NO se cree lo que dice la URL (se
          puede escribir a mano). Se le pregunta a Mercado Pago el
          estado real del pago y recién ahí se actualiza la compra.
     4. Si el sitio está publicado, Mercado Pago además avisa por
        webhook cada cambio (ej. un pago en efectivo que se
        acredita dos días después).
        → procesarAviso(): mismo criterio, se consulta y se aplica.

   UNA SOLA FUENTE DE VERDAD: la API de Mercado Pago. Tanto la
   vuelta del comprador como el webhook terminan en aplicarPago().

   SE RELACIONA CON: MercadoPago (cliente de la API),
   PurchaseModel y UserModel. Lo usan CompraController y
   Api\MercadoPagoController.
   ============================================================ */
class CompraService
{
    // -------------------------------------------------------------------------
    // EL PRODUCTO
    // Único lugar donde está el precio: la vista lo muestra desde acá y la
    // preferencia de Mercado Pago se arma desde acá. El navegador nunca manda
    // el monto, así que no se puede comprar más barato tocando el HTML.
    // -------------------------------------------------------------------------
    public const PRODUCTO_ID = 'eden-air-core';
    public const PRODUCTO    = 'EdenAir';
    public const DESCRIPCION = 'Dispositivo EdenAir (módulo ESP32) + acceso completo al dashboard';
    public const PRECIO      = 450000;
    public const MONEDA      = 'ARS';

    private PurchaseModel $compras;
    private ?MercadoPago $mercadoPago;

    public function __construct(?MercadoPago $mercadoPago = null)
    {
        $this->compras     = new PurchaseModel();
        $this->mercadoPago = $mercadoPago;
    }

    /**
     * El cliente de Mercado Pago se crea recién cuando hace falta: mostrar el
     * historial no necesita credenciales, cobrar sí.
     */
    private function mercadoPago(): MercadoPago
    {
        return $this->mercadoPago ??= new MercadoPago();
    }

    // -------------------------------------------------------------------------
    // 1) INICIAR EL PAGO (lo que pasa al apretar "Comprar")
    // -------------------------------------------------------------------------

    /**
     * Crea la preferencia en Mercado Pago, registra la compra y devuelve la
     * URL de la página de pago a la que hay que redirigir.
     *
     * Primero se habla con Mercado Pago y después se guarda: si Mercado Pago
     * falla, no queda en la base una compra fantasma sin link.
     */
    public function iniciarPago(int $userId): string
    {
        $usuario = (new UserModel())->obtenerPorId($userId);

        if (! $usuario) {
            throw new RuntimeException('El usuario de la sesión no existe.');
        }

        $referencia = 'EA-' . strtoupper(bin2hex(random_bytes(6)));
        $vuelta     = site_url('panel/compra/resultado');

        $datos = [
            'items' => [[
                'id'          => self::PRODUCTO_ID,
                'title'       => self::PRODUCTO,
                'description' => self::DESCRIPCION,
                'category_id' => 'electronics',
                'quantity'    => 1,
                'currency_id' => self::MONEDA,
                'unit_price'  => (float) self::PRECIO,
            ]],
            // Solo nombre y apellido. El email NO se manda: con credenciales de
            // prueba, un email real del comprador hace que Mercado Pago rechace
            // el checkout ("una de las partes es de prueba").
            'payer' => [
                'name'    => mb_substr(trim((string) $usuario['nombre']), 0, 60),
                'surname' => mb_substr(trim((string) ($usuario['apellido'] ?? '')), 0, 60),
            ],
            'external_reference'   => $referencia,
            'back_urls'            => [
                'success' => $vuelta,
                'pending' => $vuelta,
                'failure' => $vuelta,
            ],
            'statement_descriptor' => 'EDENAIR',
            'metadata'             => ['user_id' => $userId],
        ];

        // auto_return (volver solo al sitio después de pagar) Mercado Pago lo
        // rechaza con URLs de localhost. Se pide solo cuando el sitio es público;
        // en local, el comprador vuelve con el botón "Volver al sitio".
        if (self::esUrlPublica($vuelta)) {
            $datos['auto_return'] = 'approved';
        }

        $aviso = self::urlAvisos();

        if ($aviso !== null) {
            $datos['notification_url'] = $aviso;
        }

        $preferencia = $this->mercadoPago()->crearPreferencia($datos);

        $this->compras->insert([
            'user_id'          => $userId,
            'reference'        => $referencia,
            'product'          => self::PRODUCTO,
            'amount'           => self::PRECIO,
            'currency'         => self::MONEDA,
            'status'           => 'iniciada',
            'mp_preference_id' => $preferencia['id'],
        ]);

        return $preferencia['init_point'];
    }

    // -------------------------------------------------------------------------
    // 2) EL COMPRADOR VUELVE DE MERCADO PAGO
    // -------------------------------------------------------------------------

    /**
     * Confirma la compra con la que volvió el usuario y arma el mensaje que
     * va a ver.
     *
     * La compra tiene que ser DE ESTE usuario: la referencia viaja en la URL
     * y cualquiera podría pegar la de otra persona.
     *
     * @return array{tipo:string, mensaje:string}  tipo: success | info | error
     */
    public function confirmarRegreso(int $userId, string $referencia, string $paymentId): array
    {
        $compra = $referencia !== '' ? $this->compras->buscarPorReferencia($referencia) : null;

        if (! $compra || (int) $compra['user_id'] !== $userId) {
            return ['tipo' => 'error', 'mensaje' => 'No encontramos esa compra en tu cuenta.'];
        }

        $compra = $this->sincronizar($compra, $paymentId);

        return match ($compra['status']) {
            'aprobada'  => ['tipo' => 'success', 'mensaje' => '¡Pago aprobado! Tu compra de ' . $compra['product'] . ' quedó registrada (referencia ' . $compra['reference'] . ').'],
            'pendiente' => ['tipo' => 'info',    'mensaje' => 'Tu pago quedó pendiente. Apenas Mercado Pago lo acredite, lo vas a ver aprobado acá.'],
            'rechazada' => ['tipo' => 'error',   'mensaje' => 'Mercado Pago rechazó el pago. Podés intentarlo de nuevo con otro medio de pago.'],
            'cancelada' => ['tipo' => 'info',    'mensaje' => 'El pago se canceló. No se te cobró nada.'],
            'devuelta'  => ['tipo' => 'info',    'mensaje' => 'Ese pago fue devuelto.'],
            default     => ['tipo' => 'info',    'mensaje' => 'No se completó ningún pago. Cuando quieras, podés volver a intentarlo.'],
        };
    }

    // -------------------------------------------------------------------------
    // 3) AVISO DE MERCADO PAGO (webhook)
    // -------------------------------------------------------------------------

    /**
     * Aplica el pago que avisó Mercado Pago. El aviso solo trae el id: el
     * estado se consulta a la API, así un aviso falso no puede aprobar nada.
     *
     * @return bool false si el pago no existe o no es de ninguna compra nuestra.
     */
    public function procesarAviso(string $paymentId): bool
    {
        $pago = $this->mercadoPago()->obtenerPago($paymentId);

        if (! $pago) {
            return false;
        }

        $compra = $this->compras->buscarPorReferencia((string) ($pago['external_reference'] ?? ''));

        if (! $compra) {
            return false;
        }

        $this->aplicarPago($compra, $pago);

        return true;
    }

    // -------------------------------------------------------------------------
    // 4) PANTALLA DE COMPRA
    // -------------------------------------------------------------------------

    /** Datos del producto para la vista (precio ya formateado). */
    public function producto(): array
    {
        return [
            'nombre'      => self::PRODUCTO,
            'precio'      => self::formatearMonto(self::PRECIO),
            'moneda'      => self::MONEDA,
            'configurado' => MercadoPago::configurado(),
        ];
    }

    /**
     * Vuelve a consultar las compras recientes que siguen abiertas.
     *
     * En localhost Mercado Pago no puede mandar el webhook, así que si alguien
     * pagó y cerró la pestaña sin volver al sitio, esta es la forma de
     * enterarse. Si Mercado Pago no responde, la pantalla se muestra igual.
     */
    public function actualizarAbiertas(int $userId): void
    {
        if (! MercadoPago::configurado()) {
            return;
        }

        foreach ($this->compras->abiertasDe($userId) as $compra) {
            try {
                $this->sincronizar($compra);
            } catch (RuntimeException $e) {
                log_message('warning', 'Mercado Pago (actualizar compras): ' . $e->getMessage());

                return;
            }
        }
    }

    /** Historial "Tus compras" listo para la vista. */
    public function listarDeUsuario(int $userId): array
    {
        return array_map(static function (array $c): array {
            [$etiqueta, $tono] = self::estadoLegible((string) $c['status']);

            return [
                'referencia'   => (string) $c['reference'],
                'producto'     => (string) $c['product'],
                'monto'        => self::formatearMonto((float) $c['amount']),
                'moneda'       => (string) $c['currency'],
                'estado'       => (string) $c['status'],
                'estado_label' => $etiqueta,
                'estado_tono'  => $tono,
                'fecha'        => (string) ($c['paid_at'] ?? $c['created_at']),
            ];
        }, $this->compras->deUsuario($userId));
    }

    /** Texto + tono visual (clases tone-*) para cada estado de compra. */
    public static function estadoLegible(string $estado): array
    {
        return match ($estado) {
            'aprobada'  => ['Aprobada', 'success'],
            'pendiente' => ['Pendiente', 'warning'],
            'rechazada' => ['Rechazada', 'danger'],
            'cancelada' => ['Cancelada', 'neutral'],
            'devuelta'  => ['Devuelta', 'neutral'],
            default     => ['Sin completar', 'neutral'],
        };
    }

    // -------------------------------------------------------------------------
    // SINCRONIZACIÓN CON MERCADO PAGO
    // -------------------------------------------------------------------------

    /**
     * Trae de Mercado Pago el pago que corresponde a la compra y lo aplica.
     *
     * Con `$paymentId` (el que vino en la URL de vuelta) se pide ese pago
     * puntual, verificando que sea de ESTA compra. Sin id, se buscan todos
     * los pagos con nuestra referencia y se elige el más relevante.
     */
    private function sincronizar(array $compra, string $paymentId = ''): array
    {
        $pago = null;

        if ($paymentId !== '') {
            $pago = $this->mercadoPago()->obtenerPago($paymentId);

            // Un pago de OTRA compra no puede cerrar esta.
            if ($pago && (string) ($pago['external_reference'] ?? '') !== (string) $compra['reference']) {
                $pago = null;
            }
        }

        $pago ??= $this->elegirPago($this->mercadoPago()->buscarPagos((string) $compra['reference']));

        return $pago ? $this->aplicarPago($compra, $pago) : $compra;
    }

    /**
     * De varios intentos de pago de una misma compra, el que manda: si alguno
     * se aprobó, ese; si no, el más nuevo (la búsqueda ya viene ordenada).
     */
    private function elegirPago(array $pagos): ?array
    {
        foreach ($pagos as $pago) {
            if (($pago['status'] ?? '') === 'approved') {
                return $pago;
            }
        }

        return $pagos[0] ?? null;
    }

    /**
     * Pasa el estado de un pago de Mercado Pago a la fila de la compra.
     *
     * Regla de protección: una compra APROBADA no vuelve atrás. Solo puede
     * pasar a `devuelta`, y solo si lo dice ese mismo pago. Así un aviso
     * atrasado o un intento rechazado anterior no deshacen un cobro real.
     */
    private function aplicarPago(array $compra, array $pago): array
    {
        $estado    = self::estadoDesdeMercadoPago((string) ($pago['status'] ?? ''));
        $paymentId = (string) ($pago['id'] ?? '');

        if ($compra['status'] === 'aprobada'
            && ($estado !== 'devuelta' || $paymentId !== (string) $compra['mp_payment_id'])) {
            return $compra;
        }

        $cambios = [
            'status'           => $estado,
            'mp_payment_id'    => $paymentId,
            'mp_status'        => mb_substr((string) ($pago['status'] ?? ''), 0, 30),
            'mp_status_detail' => mb_substr((string) ($pago['status_detail'] ?? ''), 0, 60),
            'payment_method'   => mb_substr((string) ($pago['payment_method_id'] ?? ''), 0, 40) ?: null,
            'paid_at'          => ! empty($pago['date_approved'])
                ? date('Y-m-d H:i:s', strtotime((string) $pago['date_approved']))
                : null,
        ];

        $this->compras->update((int) $compra['id'], $cambios);

        return array_merge($compra, $cambios);
    }

    /** Estado de Mercado Pago → estado nuestro. */
    private static function estadoDesdeMercadoPago(string $estado): string
    {
        return match ($estado) {
            'approved'                 => 'aprobada',
            'rejected'                 => 'rechazada',
            'cancelled'                => 'cancelada',
            'refunded', 'charged_back' => 'devuelta',
            // pending, in_process, authorized, in_mediation
            default                    => 'pendiente',
        };
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /** 450000 → "450.000" (formato argentino; decimales solo si hay centavos). */
    private static function formatearMonto(float $monto): string
    {
        $decimales = fmod($monto, 1.0) === 0.0 ? 0 : 2;

        return number_format($monto, $decimales, ',', '.');
    }

    /**
     * URL a la que Mercado Pago manda los avisos. La del .env tiene prioridad
     * (sirve para probar con un túnel); si no, la propia, pero solo si es
     * pública: a localhost Mercado Pago no puede llegar.
     */
    private static function urlAvisos(): ?string
    {
        $configurada = trim((string) env('mercadopago.notificationUrl', ''));

        if ($configurada !== '') {
            return $configurada;
        }

        $propia = site_url('api/pagos/mercadopago');

        return self::esUrlPublica($propia) ? $propia : null;
    }

    /** ¿Mercado Pago puede llegar a esta URL? (https y no localhost/red interna) */
    private static function esUrlPublica(string $url): bool
    {
        $partes = parse_url($url);
        $host   = strtolower((string) ($partes['host'] ?? ''));

        if (($partes['scheme'] ?? '') !== 'https' || $host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Cobrar:
   - iniciarPago($userId)          → preferencia en Mercado Pago + fila
                                     `iniciada`; devuelve la URL de pago
   - confirmarRegreso(...)         → el comprador volvió: consulta el pago real
                                     y arma el mensaje (success | info | error)
   - procesarAviso($paymentId)     → webhook: consulta el pago y lo aplica

   Pantalla:
   - producto()                    → nombre y precio para la vista
   - actualizarAbiertas($userId)   → re-consulta compras iniciadas/pendientes
   - listarDeUsuario($userId)      → historial "Tus compras"
   - estadoLegible($estado)        → [texto, tono] para el badge

   Internos:
   - sincronizar($compra, $id)     → busca el pago (por id o por referencia)
   - elegirPago($pagos)            → el aprobado si hay; si no, el más nuevo
   - aplicarPago($compra, $pago)   → guarda el estado; una aprobada no retrocede
   - estadoDesdeMercadoPago()      → approved → aprobada, rejected → rechazada...
   - formatearMonto()              → 450000 → "450.000"
   - urlAvisos()/esUrlPublica()    → webhook y auto_return solo con URL pública

   Funciones clave:
   - bin2hex(random_bytes(6))      → referencia imposible de adivinar
   - $a ??= $b                     → (PHP) asigna solo si $a es null
   - match (...) { ... }           → (PHP 8) switch que devuelve un valor
   - filter_var(..., FILTER_FLAG_NO_PRIV_RANGE) → (PHP) descarta IPs internas
   ============================================================================ */
