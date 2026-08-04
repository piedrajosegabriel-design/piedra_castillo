<?php

namespace App\Database\Seeds;

use App\Models\DevicePairingModel;
use CodeIgniter\Database\Seeder;

/**
 * Tareas de mantenimiento de la base.
 *
 * Ya NO hay lote de códigos de activación que sembrar: los equipos se dan de
 * alta solos cuando el usuario aprieta "Conectar" y escanea el QR (ver
 * DevicePairingService). No queda nada que precargar para poder vincular.
 *
 * Lo único que hace este seeder es limpieza: cerrar las ventanas de
 * vinculación que quedaron abiertas y vencidas. Es seguro correrlo cuantas
 * veces se quiera.
 *
 * Uso: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $cerradas = (new DevicePairingModel())->marcarVencidas();

        echo $cerradas === 0
            ? "No había ventanas de vinculación vencidas.\n"
            : "Se cerraron {$cerradas} ventanas de vinculación vencidas.\n";
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - run()             → punto de entrada del seeder (lo llama `spark db:seed`)
   - marcarVencidas()  → (DevicePairingModel) pasa a 'expirado' las ventanas
                         que se quedaron abiertas más allá de su hora
   ============================================================================ */
