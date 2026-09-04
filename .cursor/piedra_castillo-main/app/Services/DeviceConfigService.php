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
   /panel/ambientes) y los márgenes de alerta. El equipo los pide
   al arrancar y cada tanto, y aplica esas reglas localmente.

     servidor  →  "para este ambiente: 18–24 °C, 40–55 %, 900 ppm"
     ESP32     →  mide, compara, acciona, y después reporta

   Estos números vivían antes en AutomationService, dentro del
   PHP. Ahora viajan al equipo, que es quien los usa.

   SE RELACIONA CON: SpaceModel (umbrales del ambiente),
   CommandService (modo automático/manual) y
   EnvironmentPresetService (nombre legible). Lo usa
   DeviceApiController::config().
   ============================================================ */
class DeviceConfigService
{
    /**
     * Cuánto tiene que empeorar un valor, más allá del rango del ambiente,
     * para que se considere una ALERTA grave y se prenda el LED.
     *
     * Se mandan al equipo como parte de la configuración para que las reglas
     * se puedan afinar desde el servidor sin reprogramar la ESP32.
     */
    public const MARGEN_ALERTA_TEMP = 2.0;   // °C por fuera del rango
    public const MARGEN_ALERTA_HUM  = 8.0;   // puntos porcentuales
    public const MARGEN_ALERTA_CO2  = 250;   // ppm por encima del máximo

    /** Debajo de este índice de aire (0–100) se enciende el aromatizador. */
    public const AIRE_AROMATIZADOR = 60;

    /** Debajo de este índice de aire se considera alerta grave. */
    public const AIRE_ALERTA = 45;

    /** Segundos sugeridos entre mediciones. */
    public const INTERVALO_MEDICION = 300;

    /** Segundos sugeridos entre consultas de comandos manuales. */
    public const INTERVALO_COMANDOS = 15;

    /** Cada cuánto conviene que el equipo vuelva a pedir su configuración. */
    public const INTERVALO_CONFIG = 3600;

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
        $estado = $this->comandos->asegurarEstado((int) $device['id']);

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
            'umbrales' => [
                'temp_min' => (float) $space['min_temperature'],
                'temp_max' => (float) $space['max_temperature'],
                'hum_min'  => (float) $space['min_humidity'],
                'hum_max'  => (float) $space['max_humidity'],
                'co2_max'  => (int)   $space['max_co2'],
            ],

            // Cuánto hay que pasarse del rango para que sea alerta grave.
            'margenes_alerta' => [
                'temp' => self::MARGEN_ALERTA_TEMP,
                'hum'  => self::MARGEN_ALERTA_HUM,
                'co2'  => self::MARGEN_ALERTA_CO2,
                'aire' => self::AIRE_ALERTA,
            ],

            // Umbral del índice de aire para el aromatizador.
            'aire_aromatizador' => self::AIRE_AROMATIZADOR,

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

   Constantes (las reglas que antes estaban dentro de AutomationService):
   - MARGEN_ALERTA_TEMP/HUM/CO2 → cuánto hay que pasarse del rango del
                                  ambiente para que sea alerta grave
   - AIRE_AROMATIZADOR (60)     → debajo de esto se prende el aromatizador
   - AIRE_ALERTA (45)           → debajo de esto se prende el LED de alerta
   - INTERVALO_*                → cada cuánto medir, consultar comandos y
                                  refrescar la configuración

   Idea clave:
   El servidor manda los NÚMEROS; el equipo aplica la REGLA. Así se pueden
   cambiar los umbrales desde la web sin volver a programar la ESP32, pero
   la decisión sigue siendo local y no depende de que haya internet.
   ============================================================================ */
