<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Agrega `users.last_login_at`: la fecha del último inicio de sesión.
 *
 * PARA QUÉ
 * Para poder borrar las cuentas que nadie usa (`php spark edenair:limpiar`).
 * Una cuenta está "sin uso" cuando no inició sesión hace mucho Y ninguno de
 * sus equipos reportó en ese tiempo. Lo segundo ya se sabía
 * (`devices.last_seen_at`); lo primero no se guardaba en ningún lado.
 *
 * LAS CUENTAS QUE YA EXISTEN
 * Quedan con la columna en NULL: no hay forma de saber cuándo entraron por
 * última vez. La limpieza usa entonces la fecha de registro (`created_at`),
 * y apenas la persona vuelve a entrar se completa sola.
 *
 * Idempotente (guardas fieldExists/indexExists): el esquema también vive en
 * `mysql_setup.sql`, así que la columna puede existir de antes.
 */
class RegistrarUltimoIngreso extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('users')) {
            return;
        }

        if (! $db->fieldExists('last_login_at', 'users')) {
            $this->forge->addColumn('users', [
                'last_login_at' => [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'reset_expires_at',
                ],
            ]);
        }

        // Índice con nombre propio para que coincida con `mysql_setup.sql`.
        // La limpieza filtra por esta fecha.
        if (! $this->indexExists('users', 'idx_users_last_login')) {
            $db->query('ALTER TABLE `users` ADD INDEX `idx_users_last_login` (`last_login_at`)');
        }
    }

    public function down()
    {
        $db = $this->db;

        if (! $db->tableExists('users')) {
            return;
        }

        if ($this->indexExists('users', 'idx_users_last_login')) {
            $db->query('ALTER TABLE `users` DROP INDEX `idx_users_last_login`');
        }

        if ($db->fieldExists('last_login_at', 'users')) {
            $this->forge->dropColumn('users', 'last_login_at');
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
