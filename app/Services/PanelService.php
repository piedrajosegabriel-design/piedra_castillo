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

    public function obtenerDatos(int $userId): array
    {
        $usuario     = $this->usuarios->find($userId);
        $espacio     = $this->espacios->where('user_id', $userId)->first();
        $dispositivo = $this->dispositivos->where('user_id', $userId)->first();

        if (! $usuario || ! $espacio || ! $dispositivo) {
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
                'ultimo_envio'    => $this->fechaHumana($dispositivo['last_seen_at'] ?? null, 'Sin envios todavia'),
                'ultima_consulta' => $this->fechaHumana($dispositivo['last_command_sync_at'] ?? null, 'Sin consultas todavia'),
            ],
            'state' => [
                'modo'       => $estado['operating_mode'] ?? 'automatic',
                'modo_label' => ($estado['operating_mode'] ?? 'automatic') === 'manual' ? 'Manual' : 'Automatico',
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
                'detalle' => 'Ultimas lecturas registradas.',
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
                'detalle' => 'Comparacion simple del ambiente.',
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
                'detalle' => 'Nivel de concentracion en el espacio.',
                'actual' => (int) end($co2) . ' ppm',
                'tono' => $this->tonoCo2((int) end($co2), $espacio),
                'rango' => 'Limite recomendado: ' . (int) $espacio['max_co2'] . ' ppm',
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
                'detalle' => 'Mientras mas alto, mejor estado.',
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
                'detalle' => 'Limite recomendado: ' . (int) $espacio['max_co2'] . ' ppm',
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
            $this->crearActuador('fan', 'Ventilador', $estado['fan_state'] ?? 'off', 'Renueva el aire cuando sube la temperatura o el CO2.'),
            $this->crearActuador('aromatizer', 'Aromatizador', $estado['aromatizer_state'] ?? 'off', 'Apoya cuando la calidad del aire baja.'),
            $this->crearActuador('alert_led', 'Luz de alerta', $estado['alert_led_state'] ?? 'off', 'Marca visualmente una condicion fuera de rango.'),
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
                'titulo' => 'Sin mediciones aun',
                'texto'  => 'Registra una medicion para empezar a ver el estado del ambiente.',
            ]];
        }

        $alertas = [];

        if ((float) $medicion['temperature'] > (float) $espacio['max_temperature']) {
            $alertas[] = [
                'tono'   => 'danger',
                'titulo' => 'Temperatura alta',
                'texto'  => 'Conviene favorecer ventilacion o refresco.',
            ];
        }

        if ((float) $medicion['humidity'] > (float) $espacio['max_humidity']) {
            $alertas[] = [
                'tono'   => 'warning',
                'titulo' => 'Humedad alta',
                'texto'  => 'El ambiente esta por encima del rango recomendado.',
            ];
        }

        if ((int) $medicion['co2_ppm'] > (int) $espacio['max_co2']) {
            $alertas[] = [
                'tono'   => 'warning',
                'titulo' => 'CO2 elevado',
                'texto'  => 'La ventilacion puede ser necesaria para recuperar el rango ideal.',
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
                'texto'  => 'Las ultimas mediciones se mantienen dentro del rango esperado.',
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
}
