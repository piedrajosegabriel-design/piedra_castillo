<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApellidoToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users') || $this->db->fieldExists('apellido', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'apellido' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => '',
                'after'      => 'nombre',
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('users') || ! $this->db->fieldExists('apellido', 'users')) {
            return;
        }

        $this->forge->dropColumn('users', 'apellido');
    }
}
