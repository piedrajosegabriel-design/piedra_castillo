<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Crea `purchases`: las compras de EdenAir cobradas con Mercado Pago.
 *
 * POR QUÉ HACE FALTA UNA TABLA
 * El cobro pasa en la página de Mercado Pago, no en la nuestra. El sistema
 * tiene que acordarse de qué link de pago generó para quién, para poder
 * reconocer el pago cuando Mercado Pago avisa o cuando el comprador vuelve.
 *
 * DOS ESTADOS, A PROPÓSITO
 * - `status`    → el nuestro, en castellano y corto: iniciada | pendiente |
 *                 aprobada | rechazada | cancelada | devuelta.
 * - `mp_status` → el crudo de Mercado Pago (approved, in_process...), junto
 *                 con `mp_status_detail`, para poder rastrear un caso raro.
 *
 * Idempotente (guarda tableExists): el esquema también vive en
 * `mysql_setup.sql`, así que la tabla puede existir de antes.
 */
class CreatePurchasesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('purchases')) {
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
            // Código propio que viaja como external_reference ("EA-7F3A9C21B4D0").
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            // Qué y cuánto se cobró, congelado al momento de la compra: si
            // mañana cambia el precio, el historial sigue diciendo la verdad.
            'product' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'default'    => 'ARS',
            ],
            // iniciada | pendiente | aprobada | rechazada | cancelada | devuelta
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'iniciada',
            ],
            'mp_preference_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'mp_payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'mp_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'mp_status_detail' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            // visa, master, account_money, rapipago...
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'paid_at' => [
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

        // Índices con nombre, para que coincidan con los de mysql_setup.sql.
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference', 'uq_purchase_reference');
        $this->forge->addKey('user_id', false, false, 'idx_purchase_user');
        $this->forge->addKey('mp_payment_id', false, false, 'idx_purchase_payment');
        $this->forge->createTable('purchases');
    }

    public function down()
    {
        $this->forge->dropTable('purchases', true);
    }
}
