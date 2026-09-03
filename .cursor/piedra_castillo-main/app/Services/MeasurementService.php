<?php

namespace App\Services;

use App\Models\MeasurementModel;
use InvalidArgumentException;

/* ============================================================
   MeasurementService
   QUÉ HACE: guarda las mediciones REALES que manda el hardware
   y dispara la automatización con cada una.

   El dispositivo (ESP32 + sensor SCD41) mide tres cosas:
   temperatura, humedad y CO₂. El índice de calidad de aire NO
   es un sensor: es una cuenta, y la hace este servicio a partir
   de esos tres valores y de los rangos del ambiente.

   Este servicio NO inventa datos. Si falta un valor obligatorio,
   corta con una excepción: es preferible un error visible a un
   número inventado en el panel.

   TAMPOCO DECIDE NADA. Quien decide prender el ventilador o el
   aromatizador es el ESP32, comparando la medición con los
   umbrales que le mandó el servidor (ver DeviceConfigService).
   Si el equipo informa qué actuadores quedaron encendidos, este
   servicio se limita a REGISTRAR ese estado.

     ESP32     →  mide, decide, acciona, y recién ahí reporta
     servidor  →  valida, guarda y muestra

   SE RELACIONA CON: MeasurementModel (escribe) y CommandService
   (registra el estado reportado). Lo usa DeviceApiController.
   ============================================================ */
class MeasurementService
{
    /** Rangos físicos aceptables. Fuera de esto, el dato es un error de lectura. */
    private const LIMITES = [
        'temperature' => [-20.0, 60.0],   // °C
        'humidity'    => [0.0, 100.0],    // %
        'co2_ppm'     => [300, 10000],    // ppm (el aire libre ronda 420)
    ];

    private MeasurementModel $mediciones;
    private CommandService $comandos;

    public function __construct()
    {
        $this->mediciones = new MeasurementModel();
        $this->comandos   = new CommandService();
    }

    // -------------------------------------------------------------------------
    // Alta de una medición
    // -------------------------------------------------------------------------

    /**
     * Guarda una medición del dispositivo y registra lo que el equipo hizo.
     *
     * @param array $datos Debe traer temperature, humidity y co2_ppm.
     *                     air_quality_index es opcional: si no viene, se calcula.
     *                     actuadores  es opcional: ['fan' => 'on', ...] con lo
     *                     que el equipo dejó encendido tras decidir.
     *                     motivo      es opcional: por qué lo hizo.
     *
     * @return array ['measurement' => fila guardada, 'actuadores' => cambios]
     *
     * @throws InvalidArgumentException si falta un valor o está fuera de rango.
     */
    public function registrar(array $device, array $space, array $datos, string $origen = 'api'): array
    {
        $temperatura = $this->exigirNumero($datos, 'temperature');
        $humedad     = $this->exigirNumero($datos, 'humidity');
        $co2         = (int) $this->exigirNumero($datos, 'co2_ppm');

        // El índice puede venir calculado por el dispositivo; si no, lo hacemos acá.
        $indiceAire = isset($datos['air_quality_index']) && $datos['air_quality_index'] !== ''
            ? max(0, min(100, (int) $datos['air_quality_index']))
            : $this->calcularIndiceAire($temperatura, $humedad, $co2, $space);

        $medicionId = (int) $this->mediciones->insert([
            'device_id'         => $device['id'],
            'user_id'           => $device['user_id'],
            'space_id'          => $device['space_id'],
            'source'            => $origen,
            'temperature'       => round($temperatura, 1),
            'humidity'          => round($humedad, 1),
            'co2_ppm'           => $co2,
            'air_quality_index' => $indiceAire,
            'air_quality_label' => $this->etiquetaAire($indiceAire),
            'notes'             => trim((string) ($datos['notes'] ?? '')) ?: null,
            'captured_at'       => (string) ($datos['captured_at'] ?? date('Y-m-d H:i:s')),
        ]);

        $medicion = $this->mediciones->find($medicionId);

        // Si el equipo contó qué actuadores dejó encendidos, se guarda ese
        // estado. Si no manda nada (por ejemplo, un sensor sin actuadores),
        // simplemente no se toca el estado anterior.
        $cambios = [];

        if (isset($datos['actuadores']) && is_array($datos['actuadores'])) {
            $cambios = $this->comandos->registrarEstadoReportado(
                (int) $device['id'],
                $datos['actuadores'],
                trim((string) ($datos['motivo'] ?? ''))
            );
        }

        return [
            'measurement' => $medicion,
            'actuadores'  => $cambios,
        ];
    }

    // -------------------------------------------------------------------------
    // Validación de los valores que llegan del hardware
    // -------------------------------------------------------------------------

    /** Exige el campo, que sea numérico y que caiga dentro del rango físico. */
    private function exigirNumero(array $datos, string $campo): float
    {
        if (! isset($datos[$campo]) || $datos[$campo] === '' || ! is_numeric($datos[$campo])) {
            throw new InvalidArgumentException('Falta el valor de ' . $campo . ' o no es un número.');
        }

        $valor           = (float) $datos[$campo];
        [$min, $max]     = self::LIMITES[$campo];

        if ($valor < $min || $valor > $max) {
            throw new InvalidArgumentException(
                sprintf('El valor de %s (%s) está fuera del rango físico esperado (%s a %s).', $campo, $valor, $min, $max)
            );
        }

        return $valor;
    }

    // -------------------------------------------------------------------------
    // Índice de calidad de aire (0–100, más alto es mejor)
    // -------------------------------------------------------------------------

    /**
     * Arranca en 100 y descuenta puntos por cada desvío respecto del ambiente:
     * temperatura lejos del centro del rango, humedad fuera de rango y CO₂ por
     * encima del límite. Es una fórmula propia y simple, pensada para que el
     * número reaccione de forma creíble a lo que mide el SCD41.
     */
    public function calcularIndiceAire(float $temperatura, float $humedad, int $co2, array $space): int
    {
        $puntaje = 100;
        $centroTemp = (((float) $space['min_temperature']) + ((float) $space['max_temperature'])) / 2;

        $puntaje -= (int) round(abs($temperatura - $centroTemp) * 6);

        if ($humedad > (float) $space['max_humidity']) {
            $puntaje -= (int) round(($humedad - (float) $space['max_humidity']) * 1.5);
        }

        if ($humedad < (float) $space['min_humidity']) {
            $puntaje -= (int) round(((float) $space['min_humidity'] - $humedad) * 1.3);
        }

        if ($co2 > (int) $space['max_co2']) {
            $puntaje -= (int) round(($co2 - (int) $space['max_co2']) / 12);
        }

        return max(0, min(100, $puntaje));
    }

    /** Etiqueta legible del índice: 85+ Excelente, 70+ Buena, 55+ Aceptable. */
    public function etiquetaAire(int $indice): string
    {
        if ($indice >= 85) {
            return 'Excelente';
        }

        if ($indice >= 70) {
            return 'Buena';
        }

        if ($indice >= 55) {
            return 'Aceptable';
        }

        return 'Mala';
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Público:
   - registrar($device, $space, $datos, $origen)
       → valida, guarda la medición y registra los actuadores que el equipo
         informó. Devuelve ['measurement' => ..., 'actuadores' => cambios]
   - calcularIndiceAire($temp, $hum, $co2, $space) → índice 0–100
   - etiquetaAire($indice) → 'Excelente' | 'Buena' | 'Aceptable' | 'Mala'

   Privado:
   - exigirNumero($datos, $campo) → el valor o una excepción si falta / está
                                    fuera del rango físico posible

   Conceptos:
   - InvalidArgumentException → error de datos; el controller lo traduce a
                                una respuesta HTTP 422 para el dispositivo
   - max(0, min(100, $v))     → "clamp": encierra el índice entre 0 y 100
   ============================================================================ */
