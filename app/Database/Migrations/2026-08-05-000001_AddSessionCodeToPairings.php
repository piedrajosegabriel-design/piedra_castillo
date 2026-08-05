<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Agrega `session_code` a `device_pairings`: el puente que lleva al celular
 * del portal del equipo hasta el panel, sin pedirle que inicie sesión.
 *
 * EL PROBLEMA QUE RESUELVE
 * Después de configurar el WiFi desde el celular, el usuario quedaba en una
 * pantalla del propio equipo ("listo, volvé a la web") y tenía que buscar a
 * mano la pestaña de la computadora. En un celular ajeno, o si la abrió desde
 * el teléfono, no tenía forma de volver: la página del panel exige login.
 *
 * CÓMO FUNCIONA
 * El QR no puede llevar ningún dato hasta el equipo (la web arma el QR sin
 * poder hablarle a la placa, que todavía no está en la red). Así que el dato
 * viaja al revés:
 *
 *   1. La ESP32 inventa un código de sesión al abrir su portal.
 *   2. La página de "listo" del portal ofrece un botón hacia
 *      /vinculacion/seguir?s=CODIGO  — el celular lo abre cuando vuelve a su
 *      WiFi normal.
 *   3. La placa manda ese MISMO código en POST /api/devices/pair.
 *   4. El servidor lo guarda acá, y esa página pública puede mostrar el equipo
 *      ya vinculado sin exigir login: el código es la credencial.
 *
 * Es de un solo uso e igual de efímero que la ventana (10 minutos).
 *
 * Idempotente (guardas fieldExists/tableExists): el esquema también vive en
 * `mysql_setup.sql`, así que la columna puede existir de antes.
 */
class AddSessionCodeToPairings extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('device_pairings')) {
            return;
        }

        if (! $db->fieldExists('session_code', 'device_pairings')) {
            $this->forge->addColumn('device_pairings', [
                'session_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                    'after'      => 'token',
                ],
            ]);
        }

        // Índice con nombre propio para que coincida con `mysql_setup.sql`.
        // Sin él, la búsqueda del sondeo del celular haría full scan cada 2 s.
        if (! $this->indexExists('device_pairings', 'idx_pairing_sesion')) {
            $db->query('ALTER TABLE `device_pairings` ADD INDEX `idx_pairing_sesion` (`session_code`)');
        }
    }

    public function down()
    {
        $db = $this->db;

        if (! $db->tableExists('device_pairings')) {
            return;
        }

        if ($this->indexExists('device_pairings', 'idx_pairing_sesion')) {
            $db->query('ALTER TABLE `device_pairings` DROP INDEX `idx_pairing_sesion`');
        }

        if ($db->fieldExists('session_code', 'device_pairings')) {
            $this->forge->dropColumn('device_pairings', 'session_code');
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
