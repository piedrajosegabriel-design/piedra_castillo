<?php

namespace App\Services;

use App\Models\DeviceModel;
use App\Models\MeasurementModel;
use App\Models\SpaceModel;
use App\Models\UserModel;

/* ============================================================
   PanelService
   QUÉ HACE: arma el dashboard. Lee la última medición del
   dispositivo activo y devuelve TODO ya cocinado para que
   panel.php solo recorra arrays y dibuje.

   REGLA DE ORO DE ESTE ARCHIVO: los números viajan como números
   hasta el último momento. El texto ("24.6 °C") se arma recién
   cuando se entrega a la vista, nunca antes — así no hay que
   volver a leer un número desde un texto.

   Camino de los datos (una sola pasada):
     contexto()   → usuario, dispositivo, ambiente, estado, historial
     lecturas()   → los 4 valores crudos de la última medición
     evaluar()    → un semáforo por variable (ÚNICO umbral del panel)
     sensores()/actuadores()/reglas()/historial() → los bloques visuales
     armarVista() → el paquete plano que consume la vista

   SE RELACIONA CON: UserModel, SpaceModel, DeviceModel,
   MeasurementModel, CommandService (estado del dispositivo) y
   EnvironmentPresetService (nombres legibles del ambiente).
   Lo usa PanelController.
   ============================================================ */
class PanelService
{
    // =========================================================================
    // UMBRALES — el único lugar del panel donde se decide qué está "fuera de
    // rango". Los usan por igual las tarjetas de sensores, las reglas, el
    // estado general y la tabla de lecturas.
    //
    // OJO CON LA DIFERENCIA: el panel PINTA, el equipo DECIDE. Los números que
    // deciden son los del ambiente (los edita el usuario y se los manda
    // DeviceConfigService al ESP32). Los de acá abajo son solo márgenes de
    // severidad: a partir de cuánto un desvío se pinta rojo en vez de amarillo.
    // Los umbrales que sí decide el equipo —CO₂ crítico y calidad de aire
    // mínima— se leen del ambiente, no se escriben acá.
    // =========================================================================

    /** Margen (en °C y en puntos de humedad) a partir del cual el desvío es grave. */
    private const MARGEN_TEMP = 2.0;
    private const MARGEN_HUM  = 10.0;

    /** Cuántos puntos por debajo del mínimo de aire el desvío ya es grave. */
    private const MARGEN_AIRE = 15;

    /** Escalas de los medidores (mínimo y máximo del gauge de cada variable). */
    private const ESCALA_TEMP = [10.0, 35.0];
    private const ESCALA_CO2  = [0.0, 1500.0];

    /** Cuántas lecturas trae la tabla del historial. */
    private const LECTURAS = 6;

    // -------------------------------------------------------------------------
    // Dependencias
    // -------------------------------------------------------------------------
    private UserModel $usuarios;
    private SpaceModel $espacios;
    private DeviceModel $dispositivos;
    private MeasurementModel $mediciones;
    private CommandService $comandos;
    private EnvironmentPresetService $presets;

    public function __construct()
    {
        $this->usuarios     = new UserModel();
        $this->espacios     = new SpaceModel();
        $this->dispositivos = new DeviceModel();
        $this->mediciones   = new MeasurementModel();
        $this->comandos     = new CommandService();
        $this->presets      = new EnvironmentPresetService();
    }

    // =========================================================================
    // 1) PUNTOS DE ENTRADA
    // =========================================================================

    /** Datos crudos + el bloque `view` listo para dibujar. Lo usa el panel. */
    public function obtenerVistaPanel(int $userId, ?int $activeDeviceId = null): array
    {
        $datos = $this->obtenerDatos($userId, $activeDeviceId);
        $datos['view'] = $this->armarVista($datos);

        return $datos;
    }

    /**
     * Contexto crudo de la cuenta: dispositivo activo, su ambiente, su estado
     * y sus últimas mediciones. Lo usa PanelController para las acciones
     * (necesita `device_raw` y `space_raw`).
     */
    public function obtenerDatos(int $userId, ?int $activeDeviceId = null): array
    {
        return $this->contexto($userId, $activeDeviceId);
    }

    // =========================================================================
    // 2) CONTEXTO — las únicas consultas a la base de todo el panel
    // =========================================================================
    private function contexto(int $userId, ?int $activeDeviceId): array
    {
        $usuario      = $this->usuarios->find($userId);
        $dispositivos = $this->dispositivos->obtenerDeUsuario($userId);

        if (! $usuario || $dispositivos === []) {
            throw new \RuntimeException('No fue posible preparar el panel del usuario.');
        }

        // Dispositivo del switcher si le pertenece; si no, el primero.
        $dispositivo = $dispositivos[0];
        foreach ($dispositivos as $d) {
            if ($activeDeviceId !== null && (int) $d['id'] === $activeDeviceId) {
                $dispositivo = $d;
                break;
            }
        }

        $espacio = $this->espacios->find((int) $dispositivo['space_id']);

        if (! $espacio) {
            throw new \RuntimeException('No fue posible preparar el panel del usuario.');
        }

        // Una sola consulta de mediciones: la primera fila ES la última lectura.
        $historial = $this->mediciones
            ->where('device_id', $dispositivo['id'])
            ->orderBy('captured_at', 'DESC')
            ->limit(self::LECTURAS)
            ->findAll();

        return [
            'usuario'    => $usuario,
            'dispositivo' => $dispositivo,
            'espacio'    => $espacio,
            'estado'     => $this->comandos->getStateByDeviceId((int) $dispositivo['id']),
            'ultima'     => $historial[0] ?? null,
            'historial'  => $historial,
            // Nombre del ambiente y rangos, usados por la vista y el controller.
            'space'      => [
                'nombre'     => $this->presets->getDisplayName($espacio),
                'tipo_label' => $this->presets->getEnvironmentLabel((string) $espacio['environment_type']),
                'perfil'     => $this->perfil($espacio),
            ],
            'space_raw'  => $espacio,
            'device_raw' => [
                'id'       => (int) $dispositivo['id'],
                'user_id'  => (int) $usuario['id'],
                'space_id' => (int) $espacio['id'],
            ] + $dispositivo,
            // El JOIN de obtenerDeUsuario() ya trae el ambiente de cada
            // dispositivo: no hace falta una consulta por fila.
            'devices_list' => array_map(fn (array $d): array => [
                'id'        => (int) $d['id'],
                'name'      => (string) $d['name'],
                'space'     => $this->presets->getDisplayName($d),
                'is_active' => (int) $d['id'] === (int) $dispositivo['id'],
            ], $dispositivos),
        ];
    }

    /**
     * Rangos ideales del ambiente, ya casteados, MÁS los umbrales de control.
     *
     * Los de control (mínimo de calidad de aire, histéresis, CO₂ crítico) los
     * resuelve EnvironmentPresetService::control(), que es el mismo método que
     * usa DeviceConfigService para armar lo que se le manda al equipo. Así lo
     * que el panel dice que va a pasar es exactamente lo que el equipo hace.
     */
    private function perfil(array $espacio): array
    {
        return [
            'min_temperature' => (float) $espacio['min_temperature'],
            'max_temperature' => (float) $espacio['max_temperature'],
            'min_humidity'    => (float) $espacio['min_humidity'],
            'max_humidity'    => (float) $espacio['max_humidity'],
            'max_co2'         => (int) $espacio['max_co2'],
        ] + $this->presets->control($espacio);
    }

    /**
     * Los umbrales de APAGADO del ambiente (la histéresis ya resuelta).
     *
     * Misma cuenta que hace DeviceConfigService para el equipo. Se muestran en
     * las reglas del panel porque son la mitad de la explicación: sin ellos
     * parece que el aire se apaga apenas baja un décimo del máximo, y no es así.
     */
    private function apagado(array $perfil): array
    {
        return [
            'temp' => $perfil['max_temperature'] - $perfil['temp_hysteresis'],
            'hum'  => $perfil['min_humidity'] + $perfil['hum_hysteresis'],
            'co2'  => max(0, $perfil['max_co2'] - $perfil['co2_hysteresis']),
            'aire' => min(100, $perfil['min_air_quality'] + $perfil['air_hysteresis']),
        ];
    }

    // =========================================================================
    // 3) LECTURAS Y SEMÁFOROS
    // Acá viven los números. `evaluar*()` es el único criterio de color del
    // panel: si mañana cambia un umbral, se cambia en un solo lugar.
    // =========================================================================

    /** Los 4 valores de una medición como números (null si no hay lectura). */
    private function lecturas(?array $medicion): array
    {
        if (! $medicion) {
            return ['temp' => null, 'hum' => null, 'co2' => null, 'aire' => null, 'etiqueta_aire' => 'Sin datos'];
        }

        return [
            'temp'          => (float) $medicion['temperature'],
            'hum'           => (float) $medicion['humidity'],
            'co2'           => (int) $medicion['co2_ppm'],
            'aire'          => (int) $medicion['air_quality_index'],
            'etiqueta_aire' => (string) $medicion['air_quality_label'],
        ];
    }

    private function evaluarTemp(?float $valor, array $perfil): string
    {
        if ($valor === null) {
            return 'neutral';
        }

        $min = $perfil['min_temperature'];
        $max = $perfil['max_temperature'];

        if ($valor > $max + self::MARGEN_TEMP || $valor < $min - self::MARGEN_TEMP) {
            return 'danger';
        }

        return ($valor > $max || $valor < $min) ? 'warning' : 'success';
    }

    private function evaluarHumedad(?float $valor, array $perfil): string
    {
        if ($valor === null) {
            return 'neutral';
        }

        $min = $perfil['min_humidity'];
        $max = $perfil['max_humidity'];

        if ($valor > $max + self::MARGEN_HUM || $valor < $min - self::MARGEN_HUM) {
            return 'danger';
        }

        return ($valor > $max || $valor < $min) ? 'warning' : 'success';
    }

    /**
     * CO₂: amarillo cuando pasa el límite del ambiente, rojo cuando llega al
     * crítico. Son los mismos dos números con los que decide el equipo, así
     * que el color y el LED rojo siempre cuentan la misma historia.
     */
    private function evaluarCo2(?int $valor, array $perfil): string
    {
        if ($valor === null) {
            return 'neutral';
        }

        if ($valor > $perfil['critical_co2']) {
            return 'danger';
        }

        return $valor > $perfil['max_co2'] ? 'warning' : 'success';
    }

    /**
     * Calidad de aire: amarillo por debajo del mínimo del ambiente (que es
     * cuando el equipo pide ventilar), rojo bastante más abajo.
     */
    private function evaluarAire(?int $valor, array $perfil): string
    {
        if ($valor === null) {
            return 'neutral';
        }

        if ($valor < $perfil['min_air_quality'] - self::MARGEN_AIRE) {
            return 'danger';
        }

        return $valor < $perfil['min_air_quality'] ? 'warning' : 'success';
    }

    /** El peor de varios tonos manda: danger > warning > success. */
    private function tonoGeneral(array $tonos): string
    {
        if (in_array('danger', $tonos, true)) {
            return 'danger';
        }

        return in_array('warning', $tonos, true) ? 'warning' : 'success';
    }

    /** Posición (0–100 %) de un valor dentro de la escala de su medidor. */
    private function posicion(?float $valor, float $min, float $max): float
    {
        if ($valor === null || $max <= $min) {
            return 0.0;
        }

        return max(0.0, min(100.0, ($valor - $min) / ($max - $min) * 100));
    }

    // =========================================================================
    // 4) BLOQUES VISUALES
    // =========================================================================

    /**
     * Las 4 tarjetas de sensor: valor, unidad, tono, rango ideal en texto y
     * la posición del pin + la banda verde del medidor (todo en % de escala).
     */
    private function sensores(array $lec, array $perfil, array $tonos): array
    {
        [$tempMin, $tempMax] = self::ESCALA_TEMP;
        [$co2Min, $co2Max]   = self::ESCALA_CO2;

        return [
            [
                'icono'    => 'temp',
                'titulo'   => 'Temperatura',
                'valor'    => $lec['temp'] === null ? '--' : number_format($lec['temp'], 1),
                'unidad'   => '°C',
                'tono'     => $tonos['temp'],
                'rango'    => sprintf('Ideal %.1f–%.1f °C', $perfil['min_temperature'], $perfil['max_temperature']),
                'pct'      => $this->posicion($lec['temp'], $tempMin, $tempMax),
                'bandLow'  => $this->posicion($perfil['min_temperature'], $tempMin, $tempMax),
                'bandHigh' => $this->posicion($perfil['max_temperature'], $tempMin, $tempMax),
                'accent'   => 'eden',
            ],
            [
                'icono'    => 'hum',
                'titulo'   => 'Humedad',
                'valor'    => $lec['hum'] === null ? '--' : (string) (int) round($lec['hum']),
                'unidad'   => '%',
                'tono'     => $tonos['hum'],
                'rango'    => sprintf('Ideal %.0f–%.0f %%', $perfil['min_humidity'], $perfil['max_humidity']),
                'pct'      => $this->posicion($lec['hum'], 0, 100),
                'bandLow'  => $perfil['min_humidity'],
                'bandHigh' => $perfil['max_humidity'],
                'accent'   => 'breath',
            ],
            [
                'icono'    => 'air',
                'titulo'   => 'Calidad de aire',
                'valor'    => $lec['aire'] === null ? '--' : (string) $lec['aire'],
                'unidad'   => '/100 · ' . mb_strtolower($lec['etiqueta_aire']),
                'tono'     => $tonos['aire'],
                'rango'    => 'Buena a partir de ' . $perfil['min_air_quality'] . '/100',
                'pct'      => $this->posicion($lec['aire'] === null ? null : (float) $lec['aire'], 0, 100),
                'bandLow'  => (float) $perfil['min_air_quality'],
                'bandHigh' => 100.0,
                'accent'   => 'citrus',
            ],
            [
                'icono'    => 'co2',
                'titulo'   => 'CO₂',
                'valor'    => $lec['co2'] === null ? '--' : (string) $lec['co2'],
                'unidad'   => 'ppm',
                'tono'     => $tonos['co2'],
                'rango'    => 'Límite ' . $perfil['max_co2'] . ' ppm',
                'pct'      => $this->posicion($lec['co2'] === null ? null : (float) $lec['co2'], $co2Min, $co2Max),
                'bandLow'  => 0.0,
                'bandHigh' => $this->posicion((float) $perfil['max_co2'], $co2Min, $co2Max),
                'accent'   => 'clay',
            ],
        ];
    }

    /**
     * Las tarjetas de actuadores según el estado actual del dispositivo.
     *
     * LAS CLAVES NO SON LAS ETIQUETAS. `fan`, `aromatizer` y `alert_led` son
     * los nombres internos de siempre (base de datos, API y firmware) y no se
     * tocan: renombrarlos rompería el historial guardado. Lo que cambió es qué
     * son en la maqueta y, por lo tanto, cómo se llaman en pantalla.
     */
    private function actuadores(?array $estado): array
    {
        $definicion = [
            [
                'fan',
                'Aire acondicionado',
                'Recibe la orden por infrarrojo cuando la temperatura pasa el máximo del ambiente.',
            ],
            [
                'aromatizer',
                'Humidificador / atomizador',
                'Sube la humedad, por ciclos, cuando baja del mínimo. Puede llevar esencia para perfumar.',
            ],
            [
                'alert_led',
                'Luz de alerta',
                'LED rojo: se enciende cuando el CO₂ o la calidad de aire piden ventilar.',
            ],
        ];

        $salida = [];

        foreach ($definicion as [$clave, $titulo, $detalle]) {
            $encendido = ($estado[$clave . '_state'] ?? 'off') === 'on';

            $salida[] = [
                'clave'     => $clave,
                'titulo'    => $titulo,
                'detalle'   => $detalle,
                'encendido' => $encendido,
                'tono'      => $encendido
                    ? ($clave === 'alert_led' ? 'danger' : ($clave === 'fan' ? 'info' : 'success'))
                    : 'neutral',
            ];
        }

        return $salida;
    }

    /**
     * Los tres LEDs del equipo: el resumen visual de todo, de un vistazo.
     *
     * Es lo mismo que se ve en la maqueta parada a un metro de distancia, y
     * por eso está en el panel: si el jurado mira el equipo y mira la pantalla,
     * tienen que decir lo mismo.
     *
     * El rojo se sigue llamando `alert_led` en la base por compatibilidad.
     */
    private function leds(?array $estado): array
    {
        $definicion = [
            ['green_led', 'Verde', 'Todo normal, monitoreo pasivo.',           'success'],
            ['blue_led',  'Azul',  'Orden de aire acondicionado activa.',      'info'],
            ['alert_led', 'Rojo',  'Alerta: CO₂ alto o calidad de aire mala.', 'danger'],
        ];

        $salida = [];

        foreach ($definicion as [$clave, $titulo, $detalle, $tono]) {
            $encendido = ($estado[$clave . '_state'] ?? 'off') === 'on';

            $salida[] = [
                'clave'     => $clave,
                'titulo'    => $titulo,
                'detalle'   => $detalle,
                'encendido' => $encendido,
                'tono'      => $encendido ? $tono : 'neutral',
            ];
        }

        return $salida;
    }

    /**
     * Reglas mostradas en el panel, con los mismos números que usa el equipo.
     *
     * LA COLUMNA QUE MÁS IMPORTA ES 'tipo'. EdenAir mide cuatro variables pero
     * solo puede ACTUAR sobre dos: temperatura (aire acondicionado) y humedad
     * (humidificador). Sobre CO₂ y calidad de aire solo AVISA, porque el
     * sistema no renueva el aire: no hay extractor. En esos casos decide la
     * persona, y el panel tiene que decirlo con todas las letras en vez de
     * dejar pensar que algo se va a encender solo.
     */
    private function reglas(array $lec, array $perfil): array
    {
        $off = $this->apagado($perfil);

        return [
            [
                'cuando' => 'la calidad de aire baja de ' . $perfil['min_air_quality'] . '/100',
                'accion' => 'Avisar: ventilá el ambiente',
                'tipo'   => 'avisa',
                'detalle' => 'El aviso se levanta al recuperar ' . $off['aire'] . '/100.',
                'activa' => $lec['aire'] !== null && $lec['aire'] < $perfil['min_air_quality'],
            ],
            [
                'cuando' => 'el CO₂ supera ' . $perfil['max_co2'] . ' ppm',
                'accion' => 'Avisar: ventilá el ambiente',
                'tipo'   => 'avisa',
                'detalle' => 'Crítico arriba de ' . $perfil['critical_co2'] . ' ppm. El aviso se levanta por debajo de ' . $off['co2'] . ' ppm.',
                'activa' => $lec['co2'] !== null && $lec['co2'] > $perfil['max_co2'],
            ],
            [
                'cuando' => sprintf('la temperatura supera %.1f °C', $perfil['max_temperature']),
                'accion' => 'Encender el aire acondicionado',
                'tipo'   => 'actua',
                'detalle' => sprintf('La orden sale por infrarrojo. Corta por debajo de %.1f °C.', $off['temp']),
                'activa' => $lec['temp'] !== null && $lec['temp'] > $perfil['max_temperature'],
            ],
            [
                'cuando' => sprintf('la humedad baja de %.0f %%', $perfil['min_humidity']),
                'accion' => 'Encender el humidificador',
                'tipo'   => 'actua',
                'detalle' => sprintf('Trabaja por ciclos de 60 s. Corta por encima de %.0f %%.', $off['hum']),
                'activa' => $lec['hum'] !== null && $lec['hum'] < $perfil['min_humidity'],
            ],
            [
                'cuando' => sprintf('la humedad supera %.0f %%', $perfil['max_humidity']),
                'accion' => 'Avisar: el equipo no puede bajarla',
                'tipo'   => 'avisa',
                'detalle' => 'El atomizador solo agrega humedad; no hay deshumidificador.',
                'activa' => $lec['hum'] !== null && $lec['hum'] > $perfil['max_humidity'],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Mensajes de estado
    // -------------------------------------------------------------------------

    /**
     * Catálogo de avisos: código del equipo → texto y tono para la pantalla.
     *
     * El firmware manda CÓDIGOS ('ventilar', 'co2_critico'), no frases. Así se
     * puede reescribir un mensaje, corregirle una coma o traducirlo sin
     * volver a programar la ESP32. El orden de este array es el orden en que
     * se muestran: primero lo urgente.
     */
    private const AVISOS = [
        'co2_critico'        => ['CO₂ crítico: ventilá el ambiente ahora.', 'danger'],
        'ventilar'           => ['Ventilá el ambiente.', 'danger'],
        'aire_acondicionado' => ['Aire acondicionado activado.', 'info'],
        'ir_sin_confirmar'   => ['Orden enviada, sin confirmación IR.', 'warning'],
        'humidificando'      => ['Humidificador en marcha.', 'info'],
        'humedad_alta'       => ['Humedad alta. EdenAir solo puede subirla, así que únicamente lo informa.', 'warning'],
        'aire_en_pausa'      => ['Medición de aire en pausa por humidificación.', 'neutral'],
        'aire_calentando'    => ['Sensor de aire calentando.', 'neutral'],
    ];

    /**
     * Los mensajes que el equipo quiere mostrar ahora.
     *
     * Salen de dos lados que se completan: la lista de avisos que mandó el
     * equipo, y el estado de su sensor de aire (por si el equipo tiene un
     * firmware anterior a los avisos y solo reporta el estado). Se recorre el
     * catálogo y no la lista recibida, así el orden es siempre el mismo y un
     * código desconocido no ensucia la pantalla.
     */
    private function mensajes(?array $estado): array
    {
        $codigos = [];

        $crudos = json_decode((string) ($estado['avisos'] ?? ''), true);

        if (is_array($crudos)) {
            $codigos = array_map(static fn ($c): string => (string) $c, $crudos);
        }

        // El estado del MQ-135 vale por sí solo: es lo que explica por qué el
        // índice de aire está quieto o todavía no es confiable.
        $sensorAire = (string) ($estado['air_sensor_status'] ?? 'ok');

        if ($sensorAire === 'warmup') {
            $codigos[] = 'aire_calentando';
        } elseif ($sensorAire === 'pausa') {
            $codigos[] = 'aire_en_pausa';
        }

        $salida = [];

        foreach (self::AVISOS as $codigo => [$texto, $tono]) {
            if (in_array($codigo, $codigos, true)) {
                $salida[] = ['codigo' => $codigo, 'texto' => $texto, 'tono' => $tono];
            }
        }

        return $salida;
    }

    /**
     * Qué LED manda ahora: 'Rojo', 'Azul', 'Verde' o 'Apagados'.
     *
     * Sirve para el resumen de una línea. Se recorre de mayor a menor
     * urgencia, que es el mismo orden en que los mira una persona: si hay rojo
     * prendido, lo demás no importa.
     */
    private function ledActivo(array $leds): string
    {
        foreach (['alert_led', 'blue_led', 'green_led'] as $clave) {
            foreach ($leds as $led) {
                if ($led['clave'] === $clave && $led['encendido']) {
                    return $led['titulo'];
                }
            }
        }

        return 'Apagados';
    }

    /**
     * De dónde salió el índice de calidad de aire de la última lectura, en
     * texto corto para poner debajo del número.
     *
     * No es un detalle técnico de más: un índice medido por el MQ-135 y uno
     * estimado con una fórmula no valen lo mismo, y el panel no puede
     * presentarlos como si fueran la misma cosa.
     */
    private function origenDelAire(?array $medicion, ?array $estado): string
    {
        $sensorAire = (string) ($estado['air_sensor_status'] ?? 'ok');

        if ($sensorAire === 'pausa') {
            return 'último valor válido · medición en pausa';
        }

        if ($sensorAire === 'warmup') {
            return 'estimado · el sensor está calentando';
        }

        return ((string) ($medicion['air_quality_source'] ?? 'calculado')) === 'sensor'
            ? 'medido por el sensor de aire'
            : 'estimado a partir de las otras variables';
    }

    /**
     * Filas de la tabla de lecturas: textos listos + el tono de la fila,
     * calculado con los mismos umbrales que el resto del panel.
     */
    private function historial(array $historial, array $perfil): array
    {
        return array_map(function (array $fila) use ($perfil): array {
            $lec = $this->lecturas($fila);

            $tono = $this->tonoGeneral([
                $this->evaluarTemp($lec['temp'], $perfil),
                $this->evaluarCo2($lec['co2'], $perfil),
                $this->evaluarAire($lec['aire'], $perfil),
            ]);

            return [
                'fecha'       => $this->fechaHumana($fila['captured_at']),
                'origen'      => $this->etiquetaOrigen((string) $fila['source']),
                'temperatura' => number_format((float) $lec['temp'], 1) . ' °C',
                'humedad'     => (int) round((float) $lec['hum']) . ' %',
                'aire'        => $lec['etiqueta_aire'] . ' (' . $lec['aire'] . '/100)',
                'co2'         => $lec['co2'] . ' ppm',
                'tono'        => $tono,
            ];
        }, $historial);
    }

    /**
     * Mini-curva de temperatura (sparkline) como path SVG: 220 px de ancho,
     * el valor más alto arriba (y=10) y el más bajo abajo (y=50).
     */
    private function sparkPath(array $historial): string
    {
        $valores = array_map(
            static fn (array $fila): float => (float) $fila['temperature'],
            array_reverse($historial)
        );

        if (count($valores) < 2) {
            return '';
        }

        $min   = min($valores);
        $rango = max(0.001, max($valores) - $min);
        $pasoX = 220 / (count($valores) - 1);
        $cmds  = [];

        foreach ($valores as $i => $v) {
            $x      = round($i * $pasoX, 2);
            $y      = round(50 - (($v - $min) / $rango) * 40, 2);
            $cmds[] = ($i === 0 ? 'M' : 'L') . $x . ' ' . $y;
        }

        return implode(' ', $cmds);
    }

    // =========================================================================
    // 5) PAQUETE FINAL PARA panel.php
    // =========================================================================
    private function armarVista(array $ctx): array
    {
        $perfil = $ctx['space']['perfil'];
        $lec    = $this->lecturas($ctx['ultima']);

        $tonos = [
            'temp' => $this->evaluarTemp($lec['temp'], $perfil),
            'hum'  => $this->evaluarHumedad($lec['hum'], $perfil),
            'co2'  => $this->evaluarCo2($lec['co2'], $perfil),
            'aire' => $this->evaluarAire($lec['aire'], $perfil),
        ];

        $fueraDeRango = count(array_filter(
            $tonos,
            static fn (string $t): bool => $t === 'warning' || $t === 'danger'
        ));

        // Sin mediciones todavía: el dispositivo está vinculado pero aún no
        // mandó nada. No es un problema del ambiente, así que no se pinta de
        // rojo: se avisa y se espera.
        $sinLecturas = $ctx['ultima'] === null;

        $tono   = $sinLecturas ? 'neutral' : $this->tonoGeneral(array_values($tonos));
        $manual = ($ctx['estado']['operating_mode'] ?? 'automatic') === 'manual';

        $actuadores = $this->actuadores($ctx['estado']);
        $reglas     = $this->reglas($lec, $perfil);
        $leds       = $this->leds($ctx['estado']);
        $mensajes   = $sinLecturas ? [] : $this->mensajes($ctx['estado']);
        $nombre     = (string) $ctx['usuario']['nombre'] . ' ' . (string) $ctx['usuario']['apellido'];

        return [
            // Identidad y contexto
            'userName'     => $nombre,
            'userInitial'  => strtoupper(mb_substr($nombre, 0, 1) ?: 'U'),
            'spaceName'    => $ctx['space']['nombre'],
            'spaceLabel'   => $ctx['space']['tipo_label'],
            'deviceName'   => (string) $ctx['dispositivo']['name'],
            'deviceUid'    => (string) $ctx['dispositivo']['device_uid'],
            'deviceLastSeen' => $this->fechaHumana($ctx['dispositivo']['last_seen_at'] ?? null, 'Sin envíos todavía'),

            // Estado general (el titular del panel)
            'tono'         => $tono,
            'estadoLabel'  => match ($tono) {
                'danger'  => 'Crítico',
                'warning' => 'Advertencia',
                'neutral' => 'Sin datos',
                default   => 'Normal',
            },
            'estadoTitulo' => match ($tono) {
                'danger'  => 'Condición crítica',
                'warning' => 'Atención requerida',
                'neutral' => 'Esperando la primera lectura',
                default   => 'Ambiente estable',
            },
            'estadoDetalle' => match (true) {
                $sinLecturas    => 'El dispositivo ya está vinculado. En cuanto envíe su primera medición vas a verla acá.',
                $fueraDeRango > 0 => 'Hay ' . $fueraDeRango . ' lectura' . ($fueraDeRango === 1 ? '' : 's') . ' fuera del rango de este ambiente.',
                default         => 'Las cuatro variables se mantienen dentro del rango de este ambiente.',
            },

            // Los mensajes que manda el equipo con cada medición: qué está
            // haciendo, qué necesita de la persona y en qué estado están sus
            // sensores. Es lo que convierte un número en una instrucción.
            'mensajes'     => $mensajes,

            // Los tres LEDs del equipo, para que la maqueta y la pantalla
            // digan lo mismo.
            'leds'         => $leds,
            'ledActivo'    => $this->ledActivo($leds),

            // De dónde salió el índice de calidad de aire de esta lectura.
            'aireOrigen'   => $sinLecturas ? '' : $this->origenDelAire($ctx['ultima'], $ctx['estado']),
            'ultimaLectura' => $ctx['ultima'] ? $this->fechaHumana($ctx['ultima']['captured_at']) : 'Sin lecturas',

            // La MISMA fecha en segundos desde 1970. La necesita panel-vivo.js
            // para saber qué tan vieja es la medición: el texto de arriba está
            // pensado para leerse, no para hacerle cuentas. Sin esto, el sello
            // de "actualizado hace…" medía la antigüedad DEL PEDIDO y no la del
            // DATO, así que una placa muerta seguía figurando como "recién".
            'ultimaLecturaEpoch' => $ctx['ultima'] ? strtotime((string) $ctx['ultima']['captured_at']) : null,

            // Modo de operación
            'modoManual' => $manual,
            'modoLabel'  => $manual ? 'Manual' : 'Automático',

            // Bloques
            'sensores'         => $this->sensores($lec, $perfil, $tonos),
            'actuadores'       => $actuadores,
            'actuadoresActivos' => count(array_filter($actuadores, static fn (array $a): bool => $a['encendido'])),
            'reglas'           => $reglas,
            'reglasActivas'    => count(array_filter($reglas, static fn (array $r): bool => $r['activa'])),
            'historial'        => $this->historial($ctx['historial'], $perfil),
            'sparkPath'        => $this->sparkPath($ctx['historial']),

            // Topes de la curva de tendencia. Una línea sin escala no se puede
            // leer: no se sabe si sube dos décimas o diez grados. Con estos dos
            // números el dibujo pasa de decorativo a informativo.
            ...$this->escalaTendencia($ctx['historial']),
        ];
    }

    /**
     * Valor mínimo y máximo de temperatura del tramo que dibuja la curva.
     * Devuelve un array vacío si no hay suficientes lecturas: la vista solo
     * pinta la escala cuando el gráfico existe.
     *
     * @return array{trendMin?: string, trendMax?: string}
     */
    private function escalaTendencia(array $historial): array
    {
        $valores = array_map(
            static fn (array $fila): float => (float) $fila['temperature'],
            $historial
        );

        if (count($valores) < 2) {
            return [];
        }

        return [
            'trendMin' => number_format(min($valores), 1),
            'trendMax' => number_format(max($valores), 1),
        ];
    }

    // =========================================================================
    // 6) FORMATEO
    // =========================================================================

    /** Origen técnico de la medición → etiqueta legible para la tabla. */
    private function etiquetaOrigen(string $origen): string
    {
        return match ($origen) {
            'web'        => 'Panel',
            'automation' => 'Automatización',
            'api'        => 'Dispositivo',
            'seed', 'sim' => 'Inicial',
            default      => ucfirst($origen),
        };
    }

    /** Fecha SQL → 'dd/mm/aaaa hh:mm', o el texto de respaldo si no hay. */
    private function fechaHumana(?string $fecha, string $fallback = 'Sin fecha'): string
    {
        if ($fecha === null || $fecha === '') {
            return $fallback;
        }

        return date('d/m/Y H:i', strtotime($fecha));
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Públicos:
   - obtenerVistaPanel($userId, $activeDeviceId) → contexto + bloque `view`
   - obtenerDatos($userId, $activeDeviceId)      → solo el contexto crudo
                                                   (device_raw/space_raw para
                                                   las acciones del controller)

   Datos (privados):
   - contexto()   → las únicas consultas a la base: usuario, dispositivos,
                    ambiente, estado y las últimas 6 mediciones
   - perfil()     → los rangos del ambiente ya casteados
   - lecturas()   → los 4 valores de una medición como números (o null)

   Semáforos (privados) — ÚNICO criterio de color del panel:
   - evaluarTemp()/evaluarHumedad()/evaluarCo2()/evaluarAire()
       → 'success' (en rango) | 'warning' (fuera) | 'danger' (muy fuera)
   - tonoGeneral() → el peor tono de una lista
   - posicion()    → valor → % dentro de la escala del medidor

   Bloques visuales (privados):
   - sensores()   → las 4 tarjetas con medidor
   - actuadores() → fan (aire acondicionado) / aromatizer (humidificador) /
                    alert_led (luz de alerta) con su estado
   - leds()       → los tres LEDs del equipo: verde, azul y rojo
   - ledActivo()  → cuál de los tres manda ahora
   - reglas()     → qué hace el equipo con cada variable, y si ACTÚA o AVISA
   - mensajes()   → los avisos del equipo, traducidos por el catálogo AVISOS
   - origenDelAire() → si el índice lo midió el MQ-135 o se estimó
   - historial()  → filas de la tabla con su tono
   - sparkPath()  → serie de temperatura → path SVG de la mini-curva

   Armado y formateo (privados):
   - armarVista()     → el paquete plano que consume panel.php
   - etiquetaOrigen() → 'api' → 'Dispositivo', 'seed' → 'Inicial'
   - fechaHumana()    → fecha SQL → 'dd/mm/aaaa hh:mm'

   Funciones/conceptos:
   - match($x) { ... }        → (PHP 8) switch que devuelve un valor
   - array_filter/array_map   → filtrar / transformar arrays
   - max(0, min(100, $v))     → "clamp": encierra un valor entre 0 y 100
   ============================================================================ */
