<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTesinaSimulationSchema extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 10,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                ],
                'usuario' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                ],
                'password_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
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
            $this->forge->addKey('id', true);
            $this->forge->addKey('email', false, true);
            $this->forge->addKey('usuario', false, true);
            $this->forge->createTable('users');
        }

        if (! $this->db->tableExists('spaces')) {
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
                'environment_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                ],
                'custom_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                'min_temperature' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 20.00,
                ],
                'max_temperature' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 25.00,
                ],
                'min_humidity' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 40.00,
                ],
                'max_humidity' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 60.00,
                ],
                'max_co2' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'default'    => 1000,
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
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id', false, true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('spaces');
        }

        if (! $this->db->tableExists('devices')) {
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
                'space_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                ],
                'device_uid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'api_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                ],
                'is_simulated' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'last_seen_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'last_command_sync_at' => [
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
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('space_id');
            $this->forge->addKey('device_uid', false, true);
            $this->forge->addKey('api_token', false, true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('space_id', 'spaces', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('devices');
        }

        if (! $this->db->tableExists('measurements')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'device_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'space_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'web',
                ],
                'temperature' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                ],
                'humidity' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                ],
                'co2_ppm' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'air_quality_index' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'air_quality_label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'captured_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('id', true);
            $this->forge->addKey(['device_id', 'captured_at']);
            $this->forge->addForeignKey('device_id', 'devices', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('space_id', 'spaces', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('measurements');
        }

        if (! $this->db->tableExists('device_states')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 10,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'device_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'operating_mode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'automatic',
                ],
                'fan_state' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'off',
                ],
                'aromatizer_state' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'off',
                ],
                'alert_led_state' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'off',
                ],
                'last_reason' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'updated_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'system',
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
            $this->forge->addKey('id', true);
            $this->forge->addKey('device_id', false, true);
            $this->forge->addForeignKey('device_id', 'devices', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('device_states');
        }

        if (! $this->db->tableExists('device_commands')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'device_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'issued_by_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'web',
                ],
                'command_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                ],
                'target_value' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                ],
                'payload' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'pending',
                ],
                'executed_at' => [
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
            $this->forge->addKey('id', true);
            $this->forge->addKey(['device_id', 'status']);
            $this->forge->addForeignKey('device_id', 'devices', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('issued_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('device_commands');
        }
    }

    public function down()
    {
        $this->forge->dropTable('device_commands', true);
        $this->forge->dropTable('device_states', true);
        $this->forge->dropTable('measurements', true);
        $this->forge->dropTable('devices', true);
        $this->forge->dropTable('spaces', true);
        $this->forge->dropTable('users', true);
    }
}
