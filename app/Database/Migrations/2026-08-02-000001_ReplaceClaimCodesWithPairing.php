<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Reemplaza el alta por CÓDIGO DE ACTIVACIÓN por el alta por QR de vinculación.
 *
 * QUÉ CAMBIA Y POR QUÉ
 * El flujo viejo obligaba al cliente a buscar un código EDEN-XXXX-XXXX en la
 * caja y tipearlo en un asistente de 4 pasos. Ahora no hay nada que buscar ni
 * que escribir: el usuario aprieta "Conectar", la web abre una VENTANA DE
 * VINCULACIÓN y muestra un QR generado en el momento; el equipo que aparezca
 * durante esa ventana queda asociado a esa cuenta.
 *
 * - Borra `device_activation_codes` y la columna `devices.activation_code`.
 * - Crea `device_pairings`: una fila por cada intento de vinculación.
 *
 * Idempotente (guardas tableExists/fieldExists): el esquema también vive en
 * `mysql_setup.sql`, así que las tablas pueden existir de antes.
 */
class ReplaceClaimCodesWithPairing extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ---------------------------------------------------------------
        // 1) Fuera el esquema de códigos de activación
        // ---------------------------------------------------------------
        $this->forge->dropTable('device_activation_codes', true);

        if ($db->tableExists('devices') && $db->fieldExists('activation_code', 'devices')) {
            $this->forge->dropColumn('devices', 'activation_code');
        }

        // ---------------------------------------------------------------
        // 2) Ventanas de vinculación
        // ---------------------------------------------------------------
        if ($db->tableExists('device_pairings')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            // Identifica la ventana para el navegador que la abrió: con este
            // token la página pregunta "¿ya apareció mi equipo?".
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            // Credenciales del WiFi de configuración que publica el equipo.
            // Son las que viajan adentro del QR.
            'ap_ssid' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'ap_password' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            // esperando | vinculado | expirado | cancelado
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'esperando',
            ],
            // Se completa cuando el equipo aparece y queda dado de alta.
            'device_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            // IP desde donde se abrió la ventana. Se usa para desempatar si
            // hay más de una ventana abierta al mismo tiempo: el equipo sale a
            // internet por la misma red que el navegador del dueño.
            'client_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // Los índices se nombran a mano para que coincidan con los de
        // `mysql_setup.sql`. Si se dejan sin nombre, CodeIgniter les pone uno
        // automático (`token`, `user_id`…) y la base creada por migración deja
        // de ser idéntica a la creada desde el .sql.
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token', 'uq_pairing_token');
        $this->forge->addKey(['status', 'expires_at'], false, false, 'idx_pairing_estado');
        $this->forge->addKey('user_id', false, false, 'idx_pairing_user');
        $this->forge->createTable('device_pairings');
    }

    public function down()
    {
        $db = $this->db;

        $this->forge->dropTable('device_pairings', true);

        if ($db->tableExists('devices') && ! $db->fieldExists('activation_code', 'devices')) {
            $this->forge->addColumn('devices', [
                'activation_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ],
            ]);
        }
    }
}
