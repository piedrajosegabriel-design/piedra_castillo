<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hito 2 — Soporte de varios dispositivos (y ambientes) por cuenta.
 *
 * El esquema original imponía UNIQUE(user_id) en `spaces`, limitando a un
 * único ambiente por usuario. Para que cada dispositivo pueda tener su propio
 * ambiente/perfil, reemplazamos ese índice único por uno normal.
 *
 * Idempotente: sólo actúa si el índice único existe.
 */
class AllowMultipleSpacesPerUser extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('spaces')) {
            return;
        }

        // Primero creamos un índice normal sobre user_id: la FK fk_spaces_user
        // exige que exista un índice antes de poder quitar el UNIQUE.
        if (! $this->indexExists('spaces', 'idx_spaces_user_id')) {
            $this->db->query('ALTER TABLE `spaces` ADD INDEX `idx_spaces_user_id` (`user_id`)');
        }

        if ($this->indexExists('spaces', 'uq_spaces_user_id')) {
            $this->db->query('ALTER TABLE `spaces` DROP INDEX `uq_spaces_user_id`');
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('spaces')) {
            return;
        }

        // Revertir sólo es seguro si no hay usuarios con más de un ambiente.
        $duplicados = $this->db->query(
            'SELECT user_id FROM `spaces` GROUP BY user_id HAVING COUNT(*) > 1 LIMIT 1'
        )->getRowArray();

        if ($duplicados) {
            // No se puede restaurar el UNIQUE sin perder datos: se deja el índice normal.
            return;
        }

        if ($this->indexExists('spaces', 'idx_spaces_user_id')) {
            $this->db->query('ALTER TABLE `spaces` DROP INDEX `idx_spaces_user_id`');
        }
        if (! $this->indexExists('spaces', 'uq_spaces_user_id')) {
            $this->db->query('ALTER TABLE `spaces` ADD UNIQUE KEY `uq_spaces_user_id` (`user_id`)');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        foreach ($this->db->getIndexData($table) as $data) {
            if (($data->name ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
}
