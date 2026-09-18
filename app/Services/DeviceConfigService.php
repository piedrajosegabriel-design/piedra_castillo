<?php

namespace App\Services;

/* ============================================================
   DeviceConfigService
   QUÉ HACE: arma la CONFIGURACIÓN que el equipo descarga para
   poder decidir por su cuenta.

   EL REPARTO DE RESPONSABILIDADES
   El servidor NO decide cuándo prender un actuador. Eso lo hace
   el ESP32, porque tiene que seguir funcionando aunque se corte
   internet, y porque reaccionar a un CO₂ alto no puede depender
   de la latencia de una petición HTTP.

   Lo que sí hace el servidor es DECIR CON QUÉ REGLAS decidir:
   los umbrales del ambiente (que el usuario edita desde
   /panel/ambientes), los umbrales de apagado y los tiempos.
   El equipo los pide al arrancar y cada tanto, y aplica esas
   reglas localmente.

     servidor  →  "aire ON arriba de 26 °C, OFF abajo de 24"
     ESP32     →  mide, compara, acciona, y después reporta

   POR QUÉ SE MANDAN LOS UMBRALES DE APAGADO YA CALCULADOS
   La histéresis se guarda como una diferencia ("2 °C por debajo
   del máximo") porque así sigue al valor que edita el usuario:
   si mueve el máximo a 28, el apagado pasa solo a 26. Pero al
   equipo se le manda el número final, no la resta: el firmware
   no tiene que rehacer una cuenta que el servidor ya hizo, y si
   mañana la regla cambia, cambia en un solo lugar.

   SE RELACIONA CON: SpaceModel (umbrales del ambiente),
   CommandService (modo automático/manual) y
   EnvironmentPresetService (nombre legible y valores de
   control). Lo usa DeviceApiController::config().
   ============================================================ */
class DeviceConfigService
{
    // -------------------------------------------------------------------------
    // TIEMPOS Y PROTECCIONES
    //
    // Estos números no describen el ambiente sino cómo se comporta el equipo,
    // así que no se editan desde /panel/ambientes. Viven acá para poder
    // afinarlos sin reprogramar la placa; el firmware trae los mismos valores
    // en config.py como arranque, para el rato en que todavía no bajó nada.
    // -------------------------------------------------------------------------

    /**
     * Segundos entre mediciones. 30 y no 8: con 8 eran unas 10.800 filas y
     * otras tantas peticiones por día; con 30 son 2.880, y la reacción sigue
     * viéndose en vivo. No bajar de 5: el SCD41 no da un dato nuevo antes.
     */
    public const INTERVALO_MEDICION = 30;

    /** Segundos entre consultas de comandos manuales. */
    public const INTERVALO_COMANDOS = 15;

    /** Cada cuánto conviene que el equipo vuelva a pedir su configuración. */
    public const INTERVALO_CONFIG = 3600;

    /** Segundos que un relé tiene que quedarse quieto antes de volver a conmutar. */
    public const RELE_MINIMO_ESTADO = 30;

    /** La trama infrarroja no se emite más seguido que esto (segundos). */
    public const IR_MINIMO_ENTRE_TRAMAS = 60;

    /** Cuánto se espera la confirmación del receptor infrarrojo (milisegundos). */
    public const IR_ESPERA_CONFIRMACION_MS = 1000;

    /** El MQ-135 tiene calefactor: hasta acá su lectura no sirve (segundos). */
    public const MQ135_WARMUP = 300;

    /** Segundos que se sigue ignorando al MQ-135 después de humidificar. */
    public const MQ135_ENMASCARADO = 90;

    /** Ciclo del atomizador mientras la humedad siga baja (segundos). */
    public const ATOMIZADOR_CICLO_ON = 60;
    public const ATOMIZADOR_CICLO_OFF = 120;

    /**
     * Cuánto por debajo del CO₂ crítico hay que bajar para salir del estado
     * crítico. Con 1400 de crítico, se sale por debajo de 1100.
     */
    public const CO2_CRITICO_HISTERESIS = 300;

    private EnvironmentPresetService $presets;
    private CommandService $comandos;

    public function __construct()
    {
        $this->presets  = new EnvironmentPresetService();
        $this->comandos = new CommandService();
    }

    /**
     * Configuración completa para un dispositivo.
     *
     * @param array $device fila de `devices`
     * @param array $space  fila de `spaces` (el ambiente donde está instalado)
     */
    public function paraDispositivo(array $device, array $space): array
    {
        $estado  = $this->comandos->asegurarEstado((int) $device['id']);
        $control = $this->presets->control($space);

        $tempMax = (float) $space['max_temperature'];
        $humMin  = (float) $space['min_humidity'];
        $co2Max  = (int)   $space['max_co2'];

        return [
            'device_uid' => (string) $device['device_uid'],
            'nombre'     => (string) $device['name'],

            // En qué ambiente está instalado y cómo se llama.
            'ambiente' => [
                'id'     => (int) $space['id'],
                'nombre' => $this->presets->getDisplayName($space),
                'tipo'   => (string) $space['environment_type'],
            ],

            // Rangos ideales configurados por el usuario en /panel/ambientes.
            // Son los umbrales de ENCENDIDO / de aviso.
            'umbrales' => [
                'temp_min' => (float) $space['min_temperature'],
                'temp_max' => $tempMax,
                'hum_min'  => $humMin,
                'hum_max'  => (float) $space['max_humidity'],
                'co2_max'  => $co2Max,
                'aire_min' => $control['min_air_quality'],
            ],

            // Umbrales de APAGADO (la histéresis, ya resuelta).
            //   temperatura → el aire corta 2 °C por debajo del máximo
            //   humedad     → el atomizador corta 8 puntos por encima del mínimo
            //   CO₂         → el aviso se levanta 150 ppm por debajo del máximo
            //   aire        → el aviso se levanta 5 puntos por encima del mínimo
            'apagado' => [
                'temp' => round($tempMax - $control['temp_hysteresis'], 2),
                'hum'  => round($humMin + $control['hum_hysteresis'], 2),
                'co2'  => max(0, $co2Max - $control['co2_hysteresis']),
                'aire' => min(100, $control['min_air_quality'] + $control['air_hysteresis']),
            ],

            // Cuándo el aviso de CO₂ pasa a ser crítico, y cuándo deja de serlo.
            'critico' => [
                'co2'        => $control['critical_co2'],
                'co2_salida' => max(0, $control['critical_co2'] - self::CO2_CRITICO_HISTERESIS),
            ],

            // Protecciones y ciclos del hardware.
            'tiempos' => [
                'rele_minimo'       => self::RELE_MINIMO_ESTADO,
                'ir_minimo'         => self::IR_MINIMO_ENTRE_TRAMAS,
                'ir_espera_ms'      => self::IR_ESPERA_CONFIRMACION_MS,
                'mq135_warmup'      => self::MQ135_WARMUP,
                'mq135_enmascarado' => self::MQ135_ENMASCARADO,
                'atomizador_on'     => self::ATOMIZADOR_CICLO_ON,
                'atomizador_off'    => self::ATOMIZADOR_CICLO_OFF,
            ],

            // 'automatic' → el equipo decide.
            // 'manual'    → el equipo NO decide; solo obedece los comandos
            //               que el usuario manda desde el dashboard.
            'modo' => (string) ($estado['operating_mode'] ?? 'automatic'),

            // Estado que el servidor cree que tienen los actuadores. Le sirve
            // al equipo para reconciliarse después de un reinicio.
            'actuadores' => [
                'fan'        => (string) ($estado['fan_state'] ?? 'off'),
                'aromatizer' => (string) ($estado['aromatizer_state'] ?? 'off'),
                'alert_led'  => (string) ($estado['alert_led_state'] ?? 'off'),
                'green_led'  => (string) ($estado['green_led_state'] ?? 'off'),
                'blue_led'   => (string) ($estado['blue_led_state'] ?? 'off'),
            ],

            'intervalos' => [
                'medicion' => self::INTERVALO_MEDICION,
                'comandos' => self::INTERVALO_COMANDOS,
                'config'   => self::INTERVALO_CONFIG,
            ],
        ];
    }
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO

   - paraDispositivo($device, $space) → el JSON de configuración completo

   Bloques que viajan al equipo:
   - umbrales   → dónde ENCIENDE cada regla (y dónde empieza cada aviso)
   - apagado    → dónde APAGA cada regla: la histéresis ya calculada
   - critico    → a partir de qué CO₂ el aviso es crítico, y cuándo deja de serlo
   - tiempos    → protecciones del hardware: mínimo del relé, límite de tramas
                  infrarrojas, warm-up y enmascarado del MQ-135, ciclo del
                  atomizador
   - modo       → 'automatic' (decide el equipo) o 'manual' (manda el usuario)
   - actuadores → cómo cree el servidor que quedó cada salida
   - intervalos → cada cuánto medir, consultar comandos y refrescar config

   Idea clave:
   El servidor manda los NÚMEROS; el equipo aplica la REGLA. Así se pueden
   cambiar los umbrales desde la web sin volver a programar la ESP32, pero
   la decisión sigue siendo local y no depende de que haya internet.
   ============================================================================ */
