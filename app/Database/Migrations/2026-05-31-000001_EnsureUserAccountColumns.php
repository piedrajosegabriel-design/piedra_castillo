<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureUserAccountColumns extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        $columns = [];

        if (! $this->db->fieldExists('apellido', 'users')) {
            $columns['apellido'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => '',
                'after'      => 'nombre',
            ];
        }

        if (! $this->db->fieldExists('reset_token', 'users')) {
            $columns['reset_token'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'password_hash',
            ];
        }

        if (! $this->db->fieldExists('reset_expires_at', 'users')) {
            $columns['reset_expires_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'reset_token',
            ];
        }

        if ($columns !== []) {
            $this->forge->addColumn('users', $columns);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        foreach (['reset_expires_at', 'reset_token', 'apellido'] as $column) {
            if ($this->db->fieldExists($column, 'users')) {
                $this->forge->dropColumn('users', $column);
            }
        }
    }
}
