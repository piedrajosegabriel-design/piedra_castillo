<?php

namespace App\Services;

use App\Models\DeviceModel;
use App\Models\MeasurementModel;
use App\Models\SpaceModel;
use App\Models\UserModel;

class PanelService
{
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

    /**
     * Devuelve los datos del panel + un bloque "view" con todo listo para que
     * la vista panel.php sólo itere y dibuje (sin lógica PHP encima).
     *
     * @return array
     */
    public function obtenerVistaPanel(int $userId, ?int $activeDeviceId = null): array
    {
        $datos = $this->obtenerDatos($userId, $activeDeviceId);
        $datos['view'] = $this->armarBloqueVista($datos);

        return $datos;
    }

    public function obtenerDatos(int $userId, ?int $activeDeviceId = null): array
    {
        $usuario      = $this->usuarios->find($userId);
        $dispositivos = $this->dispositivos
            ->where('user_id', $userId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        if (! $usuario || $dispositivos === []) {
            throw new \RuntimeException('No fue posible preparar el panel del usuario.');
        }

        // Multi-dispositivo: si el usuario eligió uno activo y le pertenece, lo
        // usamos; si no, el primero. La lista completa se devuelve en `devices`
        // para alimentar el switcher de la vista.
        $dispositivo = $dispositivos[0];
        if ($activeDeviceId !== null) {
            foreach ($dispositivos as $d) {
                if ((int) $d['id'] === $activeDeviceId) {
                    $dispositivo = $d;
                    break;
                }
            }
        }
        $espacio = $this->espacios->find((int) $dispositivo['space_id']);

        if (! $espacio) {
            throw new \RuntimeException('No fue posible preparar el panel del usuario.');
        }

        $estado = $this->comandos->getStateByDeviceId((int) $dispositivo['id']);
        $ultimaMedicion = $this->mediciones
            ->where('device_id', $dispositivo['id'])
            ->orderBy('captured_at', 'DESC')
            ->first();
        $historial = $this->mediciones
            ->where('device_id', $dispositivo['id'])
            ->orderBy('captured_at', 'DESC')
            ->limit(6)
            ->findAll();

        return [
            'user' => [
                'id'     => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
            ],
            'space' => [
                'tipo'        => $espacio['environment_type'],
                'tipo_label'  => $this->presets->getEnvironmentLabel((string) $espacio['environment_type']),
                'nombre'      => $this->presets->getDisplayName($espacio),
                'resumen'     => sprintf(
                    '%.1f a %.1f C | %.0f a %.0f %% | CO2 hasta %d ppm',
                    (float) $espacio['min_temperature'],
                    (float) $espacio['max_temperature'],
                    (float) $espacio['min_humidity'],
                    (float) $espacio['max_humidity'],
                    (int) $espacio['max_co2']
                ),
                'perfil' => [
                    'min_temperature' => (float) $espacio['min_temperature'],
                    'max_temperature' => (float) $espacio['max_temperature'],
                    'min_humidity'    => (float) $espacio['min_humidity'],
                    'max_humidity'    => (float) $espacio['max_humidity'],
                    'max_co2'         => (int) $espacio['max_co2'],
                ],
            ],
            'device' => [
                'nombre'          => $dispositivo['name'],
                'uid'             => $dispositivo['device_uid'],
                'token'           => $dispositivo['api_token'],
                'ultimo_envio'    => $this->fechaHumana($dispositivo['last_seen_at'] ?? null, 'Sin envíos todavía'),
                'ultima_consulta' => $this->fechaHumana($dispositivo['last_command_sync_at'] ?? null, 'Sin consultas todavía'),
            ],
            'state' => [
                'modo'       => $estado['operating_mode'] ?? 'automatic',
                'modo_label' => ($estado['operating_mode'] ?? 'automatic') === 'manual' ? 'Manual' : 'Automático',
                'detalle'    => $estado['last_reason'] ?? 'Sin cambios recientes.',
            ],
            'resumen' => [
                'mediciones'     => $this->mediciones->where('device_id', $dispositivo['id'])->countAllResults(),
                'ultima_lectura' => $ultimaMedicion ? $this->fechaHumana($ultimaMedicion['captured_at']) : 'Sin lecturas',
            ],
            'metrics' => $this->crearMetricas($ultimaMedicion, $espacio),
            'charts' => $this->crearGraficos($historial, $espacio),
            'actuators' => $this->crearActuadores($estado),
            'latest_measurement' => $this->formatearMedicion($ultimaMedicion),
            'history' => array_map(fn (array $fila) => $this->formatearMedicion($fila), $historial),
            'alerts' => $this->crearAlertas($ultimaMedicion, $espacio),
            'api' => [
                'routes_file'      => 'app/Config/Routes.php',
                'controller_file'  => 'app/Controllers/Api/DeviceApiController.php',
                'measurements_url' => site_url('api/devices/' . $dispositivo['device_uid'] . '/measurements'),
                'commands_url'     => site_url('api/devices/' . $dispositivo['device_uid'] . '/commands/pending'),
                'executed_url'     => site_url('api/devices/' . $dispositivo['device_uid'] . '/commands/{id}/executed'),
            ],
            'space_raw'  => $espacio,
            'device_raw' => [
                'id'       => (int) $dispositivo['id'],
                'user_id'  => (int) $usuario['id'],
                'space_id' => (int) $espacio['id'],
            ] + $dispositivo,
            // Lista corta para el switcher del header del panel.
            'devices_list' => array_map(function (array $d) use ($dispositivo): array {
                $tipoEsp = (string) ($d['environment_type'] ?? 'hogar');
                return [
                    'id'         => (int) $d['id'],
                    'name'       => (string) $d['name'],
                    'space'      => $this->presets->getDisplayName(
                        $this->espacios->find((int) $d['space_id']) ?? []
                    ),
                    'is_active'  => (int) $d['id'] === (int) $dispositivo['id'],
                ];
            }, $dispositivos),
        ];
    }

    private function crearGraficos(array $historial, array $espacio): array
    {
        if ($historial === []) {
            return [];
        }

        $puntos = array_reverse($historial);
        $temperaturas = array_map(static fn (array $fila) => (float) $fila['temperature'], $puntos);
        $humedades = array_map(static fn (array $fila) => (float) $fila['humidity'], $puntos);
        $co2 = array_map(static fn (array $fila) => (int) $fila['co2_ppm'], $puntos);
        $aire = array_map(static fn (array $fila) => (int) $fila['air_quality_index'], $puntos);

        return [
            [
                'titulo' => 'Temperatura',
                'detalle' => 'Últimas lecturas registradas.',
                'actual' => number_format(end($temperaturas), 1) . ' C',
                'tono' => $this->tonoTemperatura((float) end($temperaturas), $espacio),
                'rango' => sprintf('Rango ideal: %.1f a %.1f C', (float) $espacio['min_temperature'], (float) $espacio['max_temperature']),
                'puntos' => $this->crearPuntosGrafico(
                    $puntos,
                    'temperature',
                    max(max($temperaturas), (float) $espacio['max_temperature']) + 5,
                    static fn (float $valor) => number_format($valor, 1),
                    fn (float $valor) => $this->tonoTemperatura($valor, $espacio)
                ),
            ],
            [
                'titulo' => 'Humedad',
                'detalle' => 'Comparación simple del ambiente.',
                'actual' => number_format(end($humedades), 1) . ' %',
                'tono' => $this->tonoHumedad((float) end($humedades), $espacio),
                'rango' => sprintf('Rango ideal: %.0f a %.0f %%', (float) $espacio['min_humidity'], (float) $espacio['max_humidity']),
                'puntos' => $this->crearPuntosGrafico(
                    $puntos,
                    'humidity',
                    100,
                    static fn (float $valor) => number_format($valor, 1),
                    fn (float $valor) => $this->tonoHumedad($valor, $espacio)
                ),
            ],
            [
                'titulo' => 'CO2',
                'detalle' => 'Nivel de concentración en el espacio.',
                'actual' => (int) end($co2) . ' ppm',
                'tono' => $this->tonoCo2((int) end($co2), $espacio),
                'rango' => 'Límite recomendado: ' . (int) $espacio['max_co2'] . ' ppm',
                'puntos' => $this->crearPuntosGrafico(
                    $puntos,
                    'co2_ppm',
                    max(max($co2), (int) $espacio['max_co2']) + 300,
                    static fn (float $valor) => (string) (int) round($valor),
                    fn (float $valor) => $this->tonoCo2((int) round($valor), $espacio)
                ),
            ],
            [
                'titulo' => 'Calidad del aire',
                'detalle' => 'Mientras más alto, mejor estado.',
                'actual' => (int) end($aire) . '/100',
                'tono' => $this->tonoAire((int) end($aire)),
                'rango' => 'Escala simple: de 0 a 100',
                'puntos' => $this->crearPuntosGrafico(
                    $puntos,
                    'air_quality_index',
                    100,
                    static fn (float $valor) => (string) (int) round($valor),
                    fn (float $valor) => $this->tonoAire((int) round($valor))
                ),
            ],
        ];
    }

    private function crearMetricas(?array $medicion, array $espacio): array
    {
        if (! $medicion) {
            return [];
        }

        $temperatura = (float) $medicion['temperature'];
        $humedad     = (float) $medicion['humidity'];
        $co2         = (int) $medicion['co2_ppm'];
        $aire        = (int) $medicion['air_quality_index'];

        return [
            [
                'titulo' => 'Temperatura',
                'valor'  => number_format($temperatura, 1) . ' C',
                'estado' => $temperatura < (float) $espacio['min_temperature']
                    ? 'Baja'
                    : ($temperatura > (float) $espacio['max_temperature'] ? 'Alta' : 'En rango'),
                'tono'   => $temperatura > (float) $espacio['max_temperature']
                    ? 'danger'
                    : ($temperatura < (float) $espacio['min_temperature'] ? 'warning' : 'success'),
                'detalle' => sprintf('Ideal entre %.1f y %.1f C', (float) $espacio['min_temperature'], (float) $espacio['max_temperature']),
            ],
            [
                'titulo' => 'Humedad',
                'valor'  => number_format($humedad, 1) . ' %',
                'estado' => $humedad < (float) $espacio['min_humidity']
                    ? 'Baja'
                    : ($humedad > (float) $espacio['max_humidity'] ? 'Alta' : 'Estable'),
                'tono'   => ($humedad < (float) $espacio['min_humidity'] || $humedad > (float) $espacio['max_humidity'])
                    ? 'warning'
                    : 'success',
                'detalle' => sprintf('Ideal entre %.0f y %.0f %%', (float) $espacio['min_humidity'], (float) $espacio['max_humidity']),
            ],
            [
                'titulo' => 'CO2',
                'valor'  => $co2 . ' ppm',
                'estado' => $co2 > (int) $espacio['max_co2'] ? 'Elevado' : 'Controlado',
                'tono'   => $co2 > ((int) $espacio['max_co2'] + 250)
                    ? 'danger'
                    : ($co2 > (int) $espacio['max_co2'] ? 'warning' : 'success'),
                'detalle' => 'Límite recomendado: ' . (int) $espacio['max_co2'] . ' ppm',
            ],
            [
                'titulo' => 'Calidad del aire',
                'valor'  => $aire . '/100',
                'estado' => $medicion['air_quality_label'],
                'tono'   => $aire < 55 ? 'danger' : ($aire < 70 ? 'warning' : 'success'),
                'detalle' => 'Etiqueta actual: ' . $medicion['air_quality_label'],
            ],
        ];
    }

    private function crearActuadores(?array $estado): array
    {
        if (! $estado) {
            return [];
        }

        return [
            $this->crearActuador('fan', 'Aire acondicionado', $estado['fan_state'] ?? 'off', 'Refresca el ambiente cuando sube la temperatura o el CO2.'),
            $this->crearActuador('aromatizer', 'Aromatizador', $estado['aromatizer_state'] ?? 'off', 'Apoya cuando la calidad del aire baja.'),
            $this->crearActuador('alert_led', 'Luz de alerta', $estado['alert_led_state'] ?? 'off', 'Marca visualmente una condición fuera de rango.'),
        ];
    }

    private function crearActuador(string $clave, string $titulo, string $valor, string $detalle): array
    {
        return [
            'clave'  => $clave,
            'titulo' => $titulo,
            'estado' => $valor === 'on' ? 'Encendido' : 'Apagado',
            'tono'   => $valor === 'on'
                ? ($clave === 'alert_led' ? 'danger' : ($clave === 'fan' ? 'info' : 'success'))
                : 'neutral',
            'detalle' => $detalle,
        ];
    }

    private function formatearMedicion(?array $medicion): ?array
    {
        if (! $medicion) {
            return null;
        }

        return [
            'temperatura' => number_format((float) $medicion['temperature'], 1) . ' C',
            'humedad'     => number_format((float) $medicion['humidity'], 1) . ' %',
            'co2'         => (int) $medicion['co2_ppm'] . ' ppm',
            'aire'        => $medicion['air_quality_label'] . ' (' . (int) $medicion['air_quality_index'] . '/100)',
            'origen'      => $this->etiquetaOrigen((string) $medicion['source']),
            'notas'       => trim((string) ($medicion['notes'] ?? '')),
            'fecha'       => $this->fechaHumana($medicion['captured_at']),
        ];
    }

    private function crearAlertas(?array $medicion, array $espacio): array
    {
        if (! $medicion) {
            return [[
                'tono'   => 'info',
                'titulo' => 'Sin mediciones aún',
                'texto'  => 'Registra una medición para empezar a ver el estado del ambiente.',
            ]];
        }

        $alertas = [];

        if ((float) $medicion['temperature'] > (float) $espacio['max_temperature']) {
            $alertas[] = [
                'tono'   => 'danger',
                'titulo' => 'Temperatura alta',
                'texto'  => 'Conviene favorecer el aire acondicionado o el refresco del ambiente.',
            ];
        }

        if ((float) $medicion['humidity'] > (float) $espacio['max_humidity']) {
            $alertas[] = [
                'tono'   => 'warning',
                'titulo' => 'Humedad alta',
                'texto'  => 'El ambiente está por encima del rango recomendado.',
            ];
        }

        if ((int) $medicion['co2_ppm'] > (int) $espacio['max_co2']) {
            $alertas[] = [
                'tono'   => 'warning',
                'titulo' => 'CO2 elevado',
                'texto'  => 'El aire acondicionado puede ser necesario para recuperar el rango ideal.',
            ];
        }

        if ((int) $medicion['air_quality_index'] < 60) {
            $alertas[] = [
                'tono'   => 'warning',
                'titulo' => 'Calidad del aire comprometida',
                'texto'  => 'Conviene revisar el ambiente y sus acciones de control.',
            ];
        }

        if ($alertas === []) {
            $alertas[] = [
                'tono'   => 'success',
                'titulo' => 'Estado estable',
                'texto'  => 'Las últimas mediciones se mantienen dentro del rango esperado.',
            ];
        }

        return $alertas;
    }

    private function crearPuntosGrafico(
        array $historial,
        string $campo,
        float $maximo,
        callable $formateador,
        callable $tono
    ): array {
        $escala = $maximo > 0 ? $maximo : 1;

        return array_map(function (array $fila) use ($campo, $escala, $formateador, $tono): array {
            $valor = (float) $fila[$campo];
            $porcentaje = (int) round(($valor / $escala) * 100);

            return [
                'valor'      => $formateador($valor),
                'porcentaje' => max(10, min($porcentaje, 100)),
                'tono'       => $tono($valor),
                'etiqueta'   => date('H:i', strtotime($fila['captured_at'])),
            ];
        }, $historial);
    }

    private function tonoTemperatura(float $valor, array $espacio): string
    {
        if ($valor > (float) $espacio['max_temperature']) {
            return 'danger';
        }

        if ($valor < (float) $espacio['min_temperature']) {
            return 'warning';
        }

        return 'success';
    }

    private function tonoHumedad(float $valor, array $espacio): string
    {
        if ($valor < (float) $espacio['min_humidity'] || $valor > (float) $espacio['max_humidity']) {
            return 'warning';
        }

        return 'success';
    }

    private function tonoCo2(int $valor, array $espacio): string
    {
        if ($valor > ((int) $espacio['max_co2'] + 250)) {
            return 'danger';
        }

        if ($valor > (int) $espacio['max_co2']) {
            return 'warning';
        }

        return 'success';
    }

    private function tonoAire(int $valor): string
    {
        if ($valor < 55) {
            return 'danger';
        }

        if ($valor < 70) {
            return 'warning';
        }

        return 'success';
    }

    private function etiquetaOrigen(string $origen): string
    {
        return match ($origen) {
            'web'        => 'Web',
            'automation' => 'Automatizacion',
            'api'        => 'API',
            'seed'       => 'Inicial',
            default      => ucfirst($origen),
        };
    }

    private function fechaHumana(?string $fecha, string $fallback = 'Sin fecha'): string
    {
        if ($fecha === null || $fecha === '') {
            return $fallback;
        }

        return date('d/m/Y H:i', strtotime($fecha));
    }

    // =========================================================================
    // BLOQUE PARA LA VISTA panel.php
    // Toda la lógica de "default si no hay datos" + cálculos de tonos,
    // sparkline y sensorCards que antes vivía en panel.php se centraliza acá.
    // =========================================================================
    private function armarBloqueVista(array $datos): array
    {
        $user    = $datos['user']    ?? [];
        $space   = $datos['space']   ?? [];
        $device  = $datos['device']  ?? [];
        $state   = $datos['state']   ?? [];
        $metrics = $datos['metrics'] ?? [];
        $alerts  = $datos['alerts']  ?? [];
        $charts  = $datos['charts']  ?? [];
        $api     = $datos['api']     ?? [];

        $perfil = is_array($space['perfil'] ?? null) ? $space['perfil'] : [];

        $userName    = (string) ($user['nombre'] ?? 'Usuario');
        $spaceName   = (string) ($space['nombre'] ?? 'Ambiente principal');
        $spaceLabel  = (string) ($space['tipo_label'] ?? 'Monitoreo ambiental');
        $deviceUid   = (string) ($device['uid'] ?? 'EA-ENV-01');
        $deviceToken = (string) ($device['token'] ?? 'Token disponible al enlazar el dispositivo.');

        $modeKey    = (string) ($state['modo'] ?? 'automatic');
        $modoManual = $modeKey === 'manual';
        $modeLabel  = $modoManual ? 'Manual' : 'Automático';

        $defaultTempDetail = isset($perfil['min_temperature'], $perfil['max_temperature'])
            ? sprintf('Rango %.1f–%.1f °C', (float) $perfil['min_temperature'], (float) $perfil['max_temperature'])
            : 'Rango sugerido 22.0–26.0 °C';
        $defaultHumidityDetail = isset($perfil['min_humidity'], $perfil['max_humidity'])
            ? sprintf('Óptimo %.0f–%.0f %%', (float) $perfil['min_humidity'], (float) $perfil['max_humidity'])
            : 'Óptimo 45–60 %';
        $defaultCo2Detail = isset($perfil['max_co2'])
            ? 'Límite ' . (int) $perfil['max_co2'] . ' ppm'
            : 'Límite recomendado 900 ppm';

        $defaultMetrics = [
            ['titulo' => 'Temperatura',      'valor' => '24.6 C',  'estado' => 'En rango',   'tono' => 'success', 'detalle' => $defaultTempDetail],
            ['titulo' => 'Humedad',          'valor' => '58 %',    'estado' => 'Estable',    'tono' => 'success', 'detalle' => $defaultHumidityDetail],
            ['titulo' => 'CO2',              'valor' => '640 ppm', 'estado' => 'Controlado', 'tono' => 'info',    'detalle' => $defaultCo2Detail],
            ['titulo' => 'Calidad del aire', 'valor' => '78/100',  'estado' => 'Bueno',      'tono' => 'success', 'detalle' => 'Aire en franja cómoda.'],
        ];

        if ($metrics === []) {
            $metrics = $defaultMetrics;
        }

        $metricIndex = [];
        foreach ($metrics as $metric) {
            $titulo = strtolower((string) ($metric['titulo'] ?? ''));
            if ($titulo !== '') {
                $metricIndex[$titulo] = $metric;
            }
        }

        $tempMetric     = $metricIndex['temperatura']      ?? $defaultMetrics[0];
        $humidityMetric = $metricIndex['humedad']          ?? $defaultMetrics[1];
        $co2Metric      = $metricIndex['co2']              ?? $defaultMetrics[2];
        $airMetric      = $metricIndex['calidad del aire'] ?? $defaultMetrics[3];

        $currentTemp     = $this->extraerNumero($tempMetric['valor'] ?? null, 24.6);
        $currentHumidity = max(0, min(100, (int) round($this->extraerNumero($humidityMetric['valor'] ?? null, 58))));
        $currentCo2      = max(0, (int) round($this->extraerNumero($co2Metric['valor'] ?? null, 640)));
        $currentAir      = max(0, min(100, (int) round($this->extraerNumero($airMetric['valor'] ?? null, 78))));

        $airStateLabel = $currentAir >= 70 ? 'Buena' : ($currentAir >= 55 ? 'Regular' : 'Mala');
        $airTone       = $currentAir >= 70 ? 'success' : ($currentAir >= 55 ? 'warning' : 'danger');

        $metricTones = [
            (string) ($tempMetric['tono']     ?? 'success'),
            (string) ($humidityMetric['tono'] ?? 'success'),
            (string) ($co2Metric['tono']      ?? 'info'),
            $airTone,
        ];
        $dangerCount  = count(array_filter($metricTones, static fn (string $t): bool => $t === 'danger'));
        $warningCount = count(array_filter($metricTones, static fn (string $t): bool => in_array($t, ['warning', 'danger'], true)));
        $baseTone     = $dangerCount > 0 ? 'danger' : ($warningCount > 0 ? 'warning' : 'success');

        $defaultAlerts = [
            ['tono' => $baseTone,                                         'titulo' => 'Resumen del ambiente', 'texto' => 'La vista informa el estado del ambiente seleccionado.'],
            ['tono' => (string) ($tempMetric['tono']     ?? 'success'),   'titulo' => 'Temperatura',          'texto' => (string) ($tempMetric['detalle']     ?? $defaultTempDetail)],
            ['tono' => (string) ($humidityMetric['tono'] ?? 'success'),   'titulo' => 'Humedad',              'texto' => (string) ($humidityMetric['detalle'] ?? $defaultHumidityDetail)],
        ];
        $alertsFinal   = $alerts !== [] ? $alerts : $defaultAlerts;
        $criticalCount = count(array_filter($alertsFinal, static fn (array $a): bool => in_array((string) ($a['tono'] ?? 'neutral'), ['warning', 'danger'], true)));

        $generalTone   = $criticalCount > 1 ? 'danger' : ($criticalCount === 1 ? 'warning' : $baseTone);
        $generalLabel  = $generalTone === 'success' ? 'Normal' : ($generalTone === 'warning' ? 'Advertencia' : ($generalTone === 'danger' ? 'Crítico' : 'Activo'));
        $generalTitle  = $generalTone === 'success' ? 'Ambiente estable' : ($generalTone === 'warning' ? 'Atención requerida' : ($generalTone === 'danger' ? 'Condición crítica' : 'Ambiente monitorizado'));
        $generalDetail = $criticalCount > 0
            ? 'Hay ' . $criticalCount . ' lectura' . ($criticalCount === 1 ? '' : 's') . ' fuera de rango. Revise sensores y actuadores.'
            : 'Las variables principales se mantienen dentro de los rangos seguros.';

        $defaultActuators = [
            ['clave' => 'fan',        'titulo' => 'Aire acondicionado', 'estado' => 'Encendido', 'tono' => 'info',    'detalle' => 'Refresca el ambiente cuando sube la temperatura o el CO₂.'],
            ['clave' => 'aromatizer', 'titulo' => 'Aromatizador',       'estado' => 'Apagado',   'tono' => 'neutral', 'detalle' => 'Acompaña la sensación general del ambiente.'],
            ['clave' => 'alert_led',  'titulo' => 'LED de alerta',      'estado' => 'Apagado',   'tono' => 'neutral', 'detalle' => 'Referencia visual cuando una condición sale del rango.'],
        ];
        $actuators       = ($datos['actuators'] ?? []) !== [] ? $datos['actuators'] : $defaultActuators;
        $activeActuators = count(array_filter($actuators, static fn (array $a): bool => strtolower((string) ($a['estado'] ?? 'apagado')) !== 'apagado'));

        $latestIsSample = ($datos['latest_measurement'] ?? null) === null;
        $latest = $datos['latest_measurement'] ?? [
            'fecha'       => 'Hoy 18:00',
            'temperatura' => number_format($currentTemp, 1) . ' °C',
            'humedad'     => $currentHumidity . ' %',
            'co2'         => $currentCo2 . ' ppm',
            'aire'        => $airStateLabel . ' (' . $currentAir . '/100)',
            'origen'      => 'Panel web',
            'notas'       => 'Dato de ejemplo visual.',
        ];

        $historyIsSample = ($datos['history'] ?? []) === [];
        $historyRows = $historyIsSample ? [
            ['fecha' => '14/05/2026 08:00', 'temperatura' => '23.8 °C',                                       'humedad' => '55 %',                       'co2' => '610 ppm',         'aire' => 'Buena (80/100)',                                          'origen' => 'Web'],
            ['fecha' => '14/05/2026 10:00', 'temperatura' => '24.1 °C',                                       'humedad' => '57 %',                       'co2' => '640 ppm',         'aire' => 'Buena (78/100)',                                          'origen' => 'API'],
            ['fecha' => '14/05/2026 12:00', 'temperatura' => '24.5 °C',                                       'humedad' => '59 %',                       'co2' => '680 ppm',         'aire' => 'Buena (74/100)',                                          'origen' => 'API'],
            ['fecha' => '14/05/2026 14:00', 'temperatura' => '24.9 °C',                                       'humedad' => '60 %',                       'co2' => '710 ppm',         'aire' => 'Regular (68/100)',                                        'origen' => 'API'],
            ['fecha' => '14/05/2026 16:00', 'temperatura' => '25.1 °C',                                       'humedad' => '59 %',                       'co2' => '740 ppm',         'aire' => 'Regular (66/100)',                                        'origen' => 'API'],
            ['fecha' => '14/05/2026 18:00', 'temperatura' => number_format($currentTemp, 1) . ' °C',          'humedad' => $currentHumidity . ' %',      'co2' => $currentCo2 . ' ppm', 'aire' => $airStateLabel . ' (' . $currentAir . '/100)',           'origen' => 'Web'],
        ] : $datos['history'];

        $minTempProf = isset($perfil['min_temperature']) ? (float) $perfil['min_temperature'] : 22.0;
        $maxTempProf = isset($perfil['max_temperature']) ? (float) $perfil['max_temperature'] : 26.0;
        $minHumProf  = isset($perfil['min_humidity'])    ? (float) $perfil['min_humidity']    : 45.0;
        $maxHumProf  = isset($perfil['max_humidity'])    ? (float) $perfil['max_humidity']    : 60.0;
        $maxCo2Prof  = isset($perfil['max_co2'])         ? (int)   $perfil['max_co2']         : 900;

        $tempPct = max(0, min(100, ($currentTemp - 10) / (35 - 10) * 100));
        $co2Pct  = max(0, min(100, $currentCo2 / 1500 * 100));

        $clamp = static fn (float $v): float => max(0.0, min(100.0, $v));

        // Zona ideal proyectada sobre la misma escala que el porcentaje de cada gauge.
        $tempBandLow  = $clamp(($minTempProf - 10) / 25 * 100);
        $tempBandHigh = $clamp(($maxTempProf - 10) / 25 * 100);
        $co2BandHigh  = $clamp($maxCo2Prof / 1500 * 100);

        $tempStatus = ($currentTemp < $minTempProf - 1 || $currentTemp > $maxTempProf + 2) ? 'danger'
            : (($currentTemp < $minTempProf || $currentTemp > $maxTempProf) ? 'warning' : 'success');
        $humStatus  = ($currentHumidity < $minHumProf - 5 || $currentHumidity > $maxHumProf + 5) ? 'danger'
            : (($currentHumidity < $minHumProf || $currentHumidity > $maxHumProf) ? 'warning' : 'success');
        $co2Status  = $currentCo2 > $maxCo2Prof + 250 ? 'danger' : ($currentCo2 > $maxCo2Prof ? 'warning' : 'success');

        $sensorCards = [
            ['icon' => 'temp', 'titulo' => 'Temperatura',     'valor' => number_format($currentTemp, 1), 'unidad' => '°C',                    'estado' => $tempStatus, 'detalle' => $defaultTempDetail,             'pct' => $tempPct,         'bandLow' => $tempBandLow,       'bandHigh' => $tempBandHigh,      'accent' => 'eden'],
            ['icon' => 'hum',  'titulo' => 'Humedad',         'valor' => (string) $currentHumidity,      'unidad' => '%',                     'estado' => $humStatus,  'detalle' => $defaultHumidityDetail,         'pct' => $currentHumidity, 'bandLow' => $clamp($minHumProf), 'bandHigh' => $clamp($maxHumProf), 'accent' => 'breath'],
            ['icon' => 'air',  'titulo' => 'Calidad de aire', 'valor' => $airStateLabel,                 'unidad' => 'AQI ' . $currentAir,    'estado' => $airTone,    'detalle' => 'Lectura combinada del aire.',  'pct' => $currentAir,      'bandLow' => 70.0,               'bandHigh' => 100.0,              'accent' => 'citrus'],
            ['icon' => 'co2',  'titulo' => 'CO₂',             'valor' => (string) $currentCo2,           'unidad' => 'ppm',                   'estado' => $co2Status,  'detalle' => $defaultCo2Detail,              'pct' => $co2Pct,          'bandLow' => 0.0,                'bandHigh' => $co2BandHigh,       'accent' => 'clay'],
        ];

        $automationRules = [
            ['icon' => 'co2',   'when' => 'CO₂ > ' . $maxCo2Prof . ' ppm',                              'then' => 'Encender ventilación',        'active' => $currentCo2 > $maxCo2Prof],
            ['icon' => 'temp',  'when' => 'Temperatura > ' . number_format($maxTempProf, 1) . ' °C',    'then' => 'Encender aire acondicionado', 'active' => $currentTemp > $maxTempProf],
            ['icon' => 'hum',   'when' => 'Humedad < ' . number_format($minHumProf, 0) . ' %',          'then' => 'Sugerir humidificador',       'active' => $currentHumidity < $minHumProf, 'pending' => true],
            ['icon' => 'air',   'when' => 'Calidad de aire < 60/100',                                   'then' => 'Encender aromatizador',       'active' => $currentAir < 60],
            ['icon' => 'alert', 'when' => 'Lectura fuera de rango crítico',                             'then' => 'Encender LED de alerta',      'active' => ($currentTemp > $maxTempProf + 2) || ($currentCo2 > $maxCo2Prof + 250) || ($currentAir < 45)],
        ];
        $automationActiveCount = count(array_filter($automationRules, static fn ($r) => ! empty($r['active'])));

        $tempSeries = $this->extraerSerieGrafico($charts, 'temperatura');
        if ($tempSeries === []) {
            $tempSeries = [22.8, 23.4, 24.1, 24.9, 25.2, 24.6, 24.4, 24.2];
        }

        return [
            'userName'              => $userName,
            'userInitial'           => strtoupper(mb_substr($userName, 0, 1)),
            'spaceName'             => $spaceName,
            'spaceLabel'            => $spaceLabel,
            'deviceName'            => (string) ($device['nombre']         ?? 'Módulo EdenAir'),
            'deviceUid'             => $deviceUid,
            'deviceToken'           => $deviceToken,
            'deviceTokenPreview'    => strlen($deviceToken) > 8
                ? substr($deviceToken, 0, 4) . str_repeat('*', 8) . substr($deviceToken, -4)
                : $deviceToken,
            'deviceLastSeen'        => (string) ($device['ultimo_envio']    ?? 'Sin envíos todavía'),
            'deviceLastSync'        => (string) ($device['ultima_consulta'] ?? 'Sin consultas todavía'),
            'modeKey'               => $modeKey,
            'modoManual'            => $modoManual,
            'modeLabel'             => $modeLabel,
            'modeDetail'            => (string) ($state['detalle'] ?? 'El sistema está listo para operar con supervisión ambiental.'),
            'currentTemp'           => $currentTemp,
            'currentHumidity'       => $currentHumidity,
            'currentCo2'            => $currentCo2,
            'currentAir'            => $currentAir,
            'airStateLabel'         => $airStateLabel,
            'airTone'               => $airTone,
            'generalTone'           => $generalTone,
            'generalLabel'          => $generalLabel,
            'generalTitle'          => $generalTitle,
            'generalDetail'         => $generalDetail,
            'alerts'                => $alertsFinal,
            'criticalCount'         => $criticalCount,
            'sensorCards'           => $sensorCards,
            'actuators'             => $actuators,
            'activeActuators'       => $activeActuators,
            'automationRules'       => $automationRules,
            'automationActiveCount' => $automationActiveCount,
            'historyRows'           => $historyRows,
            'historyIsSample'       => $historyIsSample,
            'latest'                => $latest,
            'latestIsSample'        => $latestIsSample,
            'lastUpdate'            => (string) ($latest['fecha'] ?? 'Hoy'),
            'sparkPath'             => $this->construirSparkPath($tempSeries),
            'api'                   => $api,
            'peakCo2'               => max($currentCo2, 980),
            'minHumidityRecent'     => max(40, $currentHumidity - 3),
            'maxTempRecent'         => number_format(max((float) $currentTemp, 25.4), 1),
        ];
    }

    private function extraerNumero(mixed $valor, float $default = 0.0): float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        if (is_string($valor) && preg_match('/-?\d+(?:[.,]\d+)?/', $valor, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[0]);
        }

        return $default;
    }

    private function extraerSerieGrafico(array $charts, string $titulo): array
    {
        foreach ($charts as $chart) {
            if (strtolower((string) ($chart['titulo'] ?? '')) === $titulo) {
                $puntos = isset($chart['puntos']) && is_array($chart['puntos']) ? $chart['puntos'] : [];

                return array_map(fn ($p) => $this->extraerNumero($p['valor'] ?? null), $puntos);
            }
        }

        return [];
    }

    private function construirSparkPath(array $values): string
    {
        if ($values === []) {
            return '';
        }

        $min   = min($values);
        $max   = max($values);
        $range = max(0.001, $max - $min);
        $count = count($values);
        $stepX = $count > 1 ? 220 / ($count - 1) : 0;
        $cmds  = [];

        foreach ($values as $i => $v) {
            $x      = round($i * $stepX, 2);
            $y      = round(50 - (($v - $min) / $range) * 40, 2);
            $cmds[] = ($i === 0 ? 'M' : 'L') . $x . ' ' . $y;
        }

        return implode(' ', $cmds);
    }
}
