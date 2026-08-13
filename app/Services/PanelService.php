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
    // rango". Los usan por igual las tarjetas de sensores, las reglas de
    // automatización, el estado general y la tabla de lecturas.
    // =========================================================================

    /** Margen (en °C y en puntos de humedad) a partir del cual el desvío es grave. */
    private const MARGEN_TEMP  = 2.0;
    private const MARGEN_HUM   = 10.0;
    private const MARGEN_CO2   = 250;

    /** Calidad de aire (0–100, más alto es mejor). */
    private const AIRE_MALO    = 55;
    private const AIRE_REGULAR = 70;

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

    /** Rangos ideales del ambiente, ya casteados. */
    private function perfil(array $espacio): array
    {
        return [
            'min_temperature' => (float) $espacio['min_temperature'],
            'max_temperature' => (float) $espacio['max_temperature'],
            'min_humidity'    => (float) $espacio['min_humidity'],
            'max_humidity'    => (float) $espacio['max_humidity'],
            'max_co2'         => (int) $espacio['max_co2'],
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

    private function evaluarCo2(?int $valor, array $perfil): string
    {
        if ($valor === null) {
            return 'neutral';
        }

        if ($valor > $perfil['max_co2'] + self::MARGEN_CO2) {
            return 'danger';
        }

        return $valor > $perfil['max_co2'] ? 'warning' : 'success';
    }

    private function evaluarAire(?int $valor): string
    {
        if ($valor === null) {
            return 'neutral';
        }

        if ($valor < self::AIRE_MALO) {
            return 'danger';
        }

        return $valor < self::AIRE_REGULAR ? 'warning' : 'success';
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
                'rango'    => 'Buena a partir de ' . self::AIRE_REGULAR . '/100',
                'pct'      => $this->posicion($lec['aire'] === null ? null : (float) $lec['aire'], 0, 100),
                'bandLow'  => (float) self::AIRE_REGULAR,
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

    /** Las 3 tarjetas de actuadores según el estado actual del dispositivo. */
    private function actuadores(?array $estado): array
    {
        $definicion = [
            ['fan',        'Aire acondicionado', 'Refresca el ambiente cuando sube la temperatura o el CO₂.'],
            ['aromatizer', 'Aromatizador',       'Acompaña cuando la calidad del aire baja.'],
            ['alert_led',  'Luz de alerta',      'Marca visualmente una condición fuera de rango.'],
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
     * Reglas de automatización mostradas en el panel. Usan los mismos umbrales
     * que los sensores, así lo que se ve en la tarjeta coincide siempre con el
     * color de la lectura.
     */
    private function reglas(array $lec, array $perfil): array
    {
        return [
            [
                'cuando' => 'el CO₂ supera ' . $perfil['max_co2'] . ' ppm',
                'accion' => 'Encender ventilación',
                'activa' => $lec['co2'] !== null && $lec['co2'] > $perfil['max_co2'],
            ],
            [
                'cuando' => sprintf('la temperatura supera %.1f °C', $perfil['max_temperature']),
                'accion' => 'Encender aire acondicionado',
                'activa' => $lec['temp'] !== null && $lec['temp'] > $perfil['max_temperature'],
            ],
            [
                'cuando' => 'la calidad de aire baja de ' . self::AIRE_REGULAR . '/100',
                'accion' => 'Encender aromatizador',
                'activa' => $lec['aire'] !== null && $lec['aire'] < self::AIRE_REGULAR,
            ],
            [
                'cuando' => 'una lectura queda muy fuera de rango',
                'accion' => 'Encender luz de alerta',
                'activa' => $lec['aire'] !== null && (
                    $lec['co2'] > $perfil['max_co2'] + self::MARGEN_CO2
                    || $lec['temp'] > $perfil['max_temperature'] + self::MARGEN_TEMP
                    || $lec['aire'] < self::AIRE_MALO
                ),
            ],
        ];
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
                $this->evaluarAire($lec['aire']),
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
            'aire' => $this->evaluarAire($lec['aire']),
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
        $nombre     = (string) $ctx['usuario']['nombre'];

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
   - actuadores() → fan / aromatizer / alert_led con su estado
   - reglas()     → automatizaciones (mismos umbrales que los sensores)
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
