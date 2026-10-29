<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Permite que `purchases.user_id` quede en NULL.
 *
 * PARA QUÉ
 * Para poder borrar una cuenta sin borrar su comprobante de pago. Cuando el
 * usuario pide la baja (`/panel/perfil`), sus compras no se eliminan: se les
 * saca el `user_id` y quedan como una fila anónima, con el monto, la fecha y
 * el número de pago de Mercado Pago. Nada ahí identifica a una persona.
 *
 * POR QUÉ NO SE BORRAN
 * Son el respaldo de una operación de dinero. El derecho de supresión de la
 * Ley 25.326 alcanza a los datos personales, no obliga a destruir la
 * constancia de una transacción; y sin `user_id` la fila ya no es un dato
 * personal.
 *
 * `purchases` no tiene clave foránea hacia `users` (solo el índice
 * `idx_purchase_user`), así que alcanza con permitir el NULL: no hay que
 * tocar ninguna restricción.
 *
 * Idempotente: el esquema también vive en `mysql_setup.sql`, así que la
 * columna puede estar ya nullable.
 */
class PermitirCompraSinUsuario extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('purchases')) {
            return;
        }

        $this->forge->modifyColumn('purchases', [
            'user_id' => [
                'name'       => 'user_id',
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('purchases')) {
            return;
        }

        // Volver atrás exige que no queden compras anónimas: una columna
        // NOT NULL no admite los NULL que esta migración permitió.
        $this->db->query('DELETE FROM `purchases` WHERE `user_id` IS NULL');

        $this->forge->modifyColumn('purchases', [
            'user_id' => [
                'name'       => 'user_id',
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
    }
}
