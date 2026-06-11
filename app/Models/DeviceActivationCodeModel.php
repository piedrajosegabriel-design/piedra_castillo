<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Códigos de activación (claim codes) de los dispositivos Eden Air.
 * Cada código es único y sólo puede canjearse una vez.
 */
class DeviceActivationCodeModel extends Model
{
    protected $table            = 'device_activation_codes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'code',
        'device_type',
        'default_name',
        'mac_address',
        'status',
        'claimed_by_user_id',
        'device_id',
        'claimed_at',
        'batch',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Normaliza el formato que escribe el usuario: mayúsculas y sin espacios. */
    public static function normalizar(string $codigo): string
    {
        return strtoupper(trim(preg_replace('/\s+/', '', $codigo)));
    }

    /** Busca un código por su valor exacto (ya normalizado). */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $codigo = self::normalizar($codigo);

        if ($codigo === '') {
            return null;
        }

        return $this->where('code', $codigo)->first();
    }

    /** Marca un código como canjeado por un usuario/dispositivo. */
    public function marcarCanjeado(int $codeId, int $userId, int $deviceId): bool
    {
        return $this->update($codeId, [
            'status'             => 'claimed',
            'claimed_by_user_id' => $userId,
            'device_id'          => $deviceId,
            'claimed_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - normalizar($codigo)  → estático: mayúsculas + sin espacios, para que
                            "eden 1234" y "EDEN1234" se traten igual
   - buscarPorCodigo()    → fila del código (ya normalizado) o null
   - marcarCanjeado()     → status='claimed' + quién lo canjeó y cuándo
   - status posibles      → 'available' (libre), 'claimed' (usado), 'disabled'
   - preg_replace('/\s+/','',$c) → (PHP) elimina todos los espacios del texto
   ============================================================================ */
