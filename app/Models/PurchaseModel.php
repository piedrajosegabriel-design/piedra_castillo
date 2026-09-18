<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PurchaseModel — tabla `purchases`: las compras de EdenAir.
 *
 * Una fila = una vez que el usuario apretó "Comprar" y se generó su link de
 * Mercado Pago. Arranca como `iniciada` y cambia cuando Mercado Pago informa
 * el resultado del pago (`aprobada`, `pendiente`, `rechazada`...).
 *
 * `reference` es el código nuestro que viaja a Mercado Pago como
 * `external_reference`: es el hilo que une un pago con esta fila.
 */
class PurchaseModel extends Model
{
    protected $table            = 'purchases';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'reference',
        'product',
        'amount',
        'currency',
        'status',
        'mp_preference_id',
        'mp_payment_id',
        'mp_status',
        'mp_status_detail',
        'payment_method',
        'paid_at',
    ];

    // -------------------------------------------------------------------------
    // Consultas propias
    // -------------------------------------------------------------------------

    /** Busca una compra por su referencia (la que vuelve de Mercado Pago). */
    public function buscarPorReferencia(string $referencia): ?array
    {
        return $this->where('reference', $referencia)->first();
    }

    /**
     * Compras del usuario de la más nueva a la más vieja.
     * Las `iniciada` se pueden excluir: son links que nunca se pagaron.
     */
    public function deUsuario(int $userId, int $limite = 5, bool $conIniciadas = false): array
    {
        $consulta = $this->where('user_id', $userId);

        if (! $conIniciadas) {
            $consulta->where('status !=', 'iniciada');
        }

        return $consulta->orderBy('id', 'DESC')->findAll($limite);
    }

    /**
     * Compras recientes del usuario que todavía pueden cambiar de estado:
     * link generado sin pago confirmado, o pago pendiente (ej. efectivo).
     */
    public function abiertasDe(int $userId, int $dias = 3, int $limite = 3): array
    {
        return $this->where('user_id', $userId)
            ->whereIn('status', ['iniciada', 'pendiente'])
            ->where('created_at >=', date('Y-m-d H:i:s', time() - $dias * 86400))
            ->orderBy('id', 'DESC')
            ->findAll($limite);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - buscarPorReferencia($ref)   → la compra a la que pertenece un pago
   - deUsuario($id, $limite)     → historial para "Tus compras"
   - abiertasDe($id)             → compras que conviene volver a consultar
                                   (iniciadas o pendientes de los últimos días)
   - whereIn('status', [...])    → (CI4) WHERE status IN (...)
   - findAll($limite)            → (CI4) SELECT con LIMIT
   ============================================================================ */
