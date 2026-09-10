<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Acompaña el cambio de lógica de control de EdenAir.
 *
 * QUÉ CAMBIÓ Y POR QUÉ HACEN FALTA COLUMNAS NUEVAS
 *
 * 1. Las reglas ahora tienen HISTÉRESIS (un umbral para encender y otro, más
 *    adentro, para apagar) y un umbral propio de calidad de aire. Esos números
 *    los edita el usuario por ambiente, así que viven en `spaces` junto a los
 *    que ya estaban.
 *
 * 2. El índice de calidad de aire ya no siempre se calcula: cuando el MQ-135
 *    está conectado y templado, se MIDE. `measurements.air_quality_source`
 *    guarda de dónde salió cada número, para que el panel no mienta.
 *
 * 3. Los LEDs pasaron de uno a tres (verde / azul / rojo) y el equipo reporta
 *    diagnóstico: si la orden infrarroja fue confirmada, en qué estado está el
 *    sensor de aire y qué avisos hay que mostrar. Todo eso va a
 *    `device_states`, que es la foto del "ahora" del dispositivo.
 *
 * NADA SE RENOMBRA. `fan_state`, `aromatizer_state` y `alert_led_state` siguen
 * llamándose igual aunque su etiqueta visible haya cambiado: renombrarlos
 * rompería el historial ya guardado en `device_commands`.
 */
class ActualizarLogicaControl extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // spaces — los umbrales nuevos que el usuario puede editar
        // ---------------------------------------------------------------
        $this->agregarColumnas('spaces', [
            // Debajo de este índice (0–100) el equipo prende el LED rojo y
            // pide ventilar. NO enciende ningún actuador: no hay extractor.
            'min_air_quality' => [
                'type'       => 'INT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 70,
                'after'      => 'max_co2',
            ],

            // Histéresis: cuánto tiene que mejorar el valor para que la regla
            // se apague. Sin esto el relé traquetea sobre el umbral.
            'temp_hysteresis' => [
                'type'       => 'DECIMAL',
                'constraint' => '4,2',
                'default'    => 2.00,
                'after'      => 'min_air_quality',
            ],
            'hum_hysteresis' => [
                'type'       => 'DECIMAL',
                'constraint' => '4,2',
                'default'    => 8.00,
                'after'      => 'temp_hysteresis',
            ],
            'co2_hysteresis' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 150,
                'after'      => 'hum_hysteresis',
            ],
            'air_hysteresis' => [
                'type'       => 'INT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 5,
                'after'      => 'co2_hysteresis',
            ],

            // Por encima de esto el aviso de CO₂ pasa a ser crítico.
            'critical_co2' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1400,
                'after'      => 'air_hysteresis',
            ],
        ]);

        // ---------------------------------------------------------------
        // measurements — de dónde salió el índice de calidad de aire
        // ---------------------------------------------------------------
        $this->agregarColumnas('measurements', [
            // 'sensor'    → lo midió el MQ-135
            // 'calculado' → fórmula de respaldo (sin sensor o calentando)
            'air_quality_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'calculado',
                'after'      => 'air_quality_label',
            ],
        ]);

        // ---------------------------------------------------------------
        // device_states — los dos LEDs nuevos y el diagnóstico del equipo
        // ---------------------------------------------------------------
        $this->agregarColumnas('device_states', [
            'green_led_state' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'off',
                'after'      => 'alert_led_state',
            ],
            'blue_led_state' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'off',
                'after'      => 'green_led_state',
            ],

            // 1 = el receptor confirmó la trama, 0 = se emitió sin confirmar,
            // NULL = todavía no se mandó ninguna orden (o no hay cadena IR).
            'ir_confirmed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'after'      => 'blue_led_state',
            ],

            // 'ok' | 'warmup' | 'pausa' | 'ausente'  (ver firmware/aire.py)
            'air_sensor_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'ok',
                'after'      => 'ir_confirmed',
            ],

            // Códigos de aviso del último reporte, como JSON. El texto lo pone
            // la web (PanelService), así se puede reescribir un mensaje sin
            // reprogramar la placa.
            'avisos' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'air_sensor_status',
            ],
        ]);
    }

    public function down()
    {
        $this->quitarColumnas('device_states', [
            'green_led_state', 'blue_led_state', 'ir_confirmed',
            'air_sensor_status', 'avisos',
        ]);

        $this->quitarColumnas('measurements', ['air_quality_source']);

        $this->quitarColumnas('spaces', [
            'min_air_quality', 'temp_hysteresis', 'hum_hysteresis',
            'co2_hysteresis', 'air_hysteresis', 'critical_co2',
        ]);
    }

    /**
     * Agrega solo las columnas que todavía no existen.
     *
     * La base de esta máquina se armó a mano con mysql_setup.sql y no siempre
     * pasó por las migraciones: si la columna ya está, volver a agregarla
     * aborta la migración entera.
     */
    private function agregarColumnas(string $tabla, array $columnas): void
    {
        if (! $this->db->tableExists($tabla)) {
            return;
        }

        $existentes = $this->db->getFieldNames($tabla);
        $faltantes  = array_diff_key($columnas, array_flip($existentes));

        if ($faltantes !== []) {
            $this->forge->addColumn($tabla, $faltantes);
        }
    }

    /** Quita solo las columnas que existan. */
    private function quitarColumnas(string $tabla, array $columnas): void
    {
        if (! $this->db->tableExists($tabla)) {
            return;
        }

        $existentes = $this->db->getFieldNames($tabla);

        foreach ($columnas as $columna) {
            if (in_array($columna, $existentes, true)) {
                $this->forge->dropColumn($tabla, $columna);
            }
        }
    }
}
