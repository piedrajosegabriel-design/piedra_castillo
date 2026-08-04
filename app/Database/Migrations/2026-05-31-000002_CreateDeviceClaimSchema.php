<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hito 2 — Vinculación de dispositivos por código de activación (claim code)
 * y soporte de varios dispositivos por cuenta.
 *
 * - Agrega metadatos a `devices` (tipo, estado, MAC técnica, notas y el código
 *   con el que se vinculó).
 * - Crea `device_activation_codes`: cada dispositivo físico vendido / maqueta
 *   trae un código único EDEN-XXXX-XXXX que sólo puede canjearse una vez.
 * - Carga el lote inicial de códigos disponibles (incluye EDEN-CORE-2026).
 *
 * Idempotente: usa guardas fieldExists()/tableExists() para poder re-ejecutarse.
 */
class CreateDeviceClaimSchema extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ---------------------------------------------------------------
        // 1) Metadatos nuevos en devices
        // ---------------------------------------------------------------
        if ($db->tableExists('devices')) {
            $nuevos = [];

            if (! $db->fieldExists('device_type', 'devices')) {
                $nuevos['device_type'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'default'    => 'Eden Air Core',
                    'after'      => 'name',
                ];
            }
            if (! $db->fieldExists('status', 'devices')) {
                $nuevos['status'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'simulated',
                    'after'      => 'is_active',
                ];
            }
            if (! $db->fieldExists('mac_address', 'devices')) {
                // Dato técnico interno solamente. NO se usa como contraseña.
                $nuevos['mac_address'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                ];
            }
            if (! $db->fieldExists('activation_code', 'devices')) {
                $nuevos['activation_code'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ];
            }
            if (! $db->fieldExists('notes', 'devices')) {
                $nuevos['notes'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ];
            }

            if ($nuevos !== []) {
                $this->forge->addColumn('devices', $nuevos);
            }
        }

        // ---------------------------------------------------------------
        // 2) Tabla de códigos de activación
        // ---------------------------------------------------------------
        if (! $db->tableExists('device_activation_codes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 10,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                ],
                'device_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'default'    => 'Eden Air Core',
                ],
                'default_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                // MAC de fábrica: identificador técnico, nunca credencial.
                'mac_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'available', // available | claimed | disabled
                ],
                'claimed_by_user_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'device_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'claimed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'batch' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
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
            $this->forge->addKey('code', false, true); // único
            $this->forge->addKey('status');
            $this->forge->createTable('device_activation_codes');

            $this->sembrarLoteInicial();
        }
    }

    public function down()
    {
        $db = $this->db;

        $this->forge->dropTable('device_activation_codes', true);

        if ($db->tableExists('devices')) {
            foreach (['device_type', 'status', 'mac_address', 'activation_code', 'notes'] as $col) {
                if ($db->fieldExists($col, 'devices')) {
                    $this->forge->dropColumn('devices', $col);
                }
            }
        }
    }

    /**
     * Lote inicial de códigos de activación (batch `lote-inicial`): representa
     * las unidades fabricadas y listas para vincular. Mezcla un código fijo
     * documentado (EDEN-CORE-2026) con varios códigos aleatorios disponibles.
     */
    private function sembrarLoteInicial(): void
    {
        $ahora = date('Y-m-d H:i:s');
        $filas = [
            [
                'code'         => 'EDEN-CORE-2026',
                'device_type'  => 'Eden Air Core',
                'default_name' => 'Eden Air Core',
                // La MAC la graba el equipo real al aprovisionarse, no se inventa acá.
                'mac_address'  => null,
                'status'       => 'available',
                'batch'        => 'lote-inicial',
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ],
        ];

        // Códigos extra: una cuenta puede vincular más de un dispositivo.
        $tipos = ['Eden Air Core', 'Monitor ambiental', 'Ambientador inteligente', 'Prototipo educativo'];
        for ($i = 0; $i < 8; $i++) {
            $filas[] = [
                'code'         => $this->generarCodigo(),
                'device_type'  => $tipos[$i % count($tipos)],
                'default_name' => null,
                'mac_address'  => null,
                'status'       => 'available',
                'batch'        => 'lote-inicial',
                'created_at'   => $ahora,
                'updated_at'   => $ahora,
            ];
        }

        $this->db->table('device_activation_codes')->insertBatch($filas);
    }

    private function generarCodigo(): string
    {
        $bloque = static fn (): string => strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        return 'EDEN-' . $bloque() . '-' . $bloque();
    }
}
