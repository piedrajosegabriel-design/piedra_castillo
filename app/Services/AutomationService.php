<?php

namespace App\Services;

use App\Models\MeasurementModel;

/* ============================================================
   AutomationService
   QUÉ HACE: es el "cerebro automático" del sistema. Compara una
   medición contra los rangos del ambiente y decide qué actuadores
   encolar (ej: si el CO₂ supera el límite, sugiere encender el
   ventilador; si la calidad de aire baja de 60, el aromatizador;
   si algo se va MUY fuera de rango, el LED de alerta).
   Solo actúa si el dispositivo está en modo 'automatic'.
   SE RELACIONA CON: CommandService (encola los comandos),
   MeasurementModel (lee la última medición). Lo invocan
   SimulationService (tras cada medición nueva) y PanelController
   (al volver a modo automático).
   ============================================================ */
class AutomationService
{
    // -------------------------------------------------------------------------
    // Dependencias
    // -------------------------------------------------------------------------
    private CommandService $commandService;
    private MeasurementModel $measurementModel;

    public function __construct()
    {
        $this->commandService  = new CommandService();
        $this->measurementModel = new MeasurementModel();
    }

    // -------------------------------------------------------------------------
    // Procesar una medición concreta
    // Devuelve siempre ['summary' => texto para el usuario, 'commands' => [...]]
    // -------------------------------------------------------------------------

    public function processMeasurement(array $device, array $space, array $measurement): array
    {
        // Guard 1: sin estado registrado no hay nada que automatizar.
        $state = $this->commandService->getStateByDeviceId((int) $device['id']);

        if ($state === null) {
            return [
                'summary'  => 'No se encontro el estado del dispositivo.',
                'commands' => [],
            ];
        }

        // Guard 2: en modo manual el sistema NO toma decisiones (manda el usuario).
        if (($state['operating_mode'] ?? 'automatic') !== 'automatic') {
            return [
                'summary'  => 'Modo manual activo.',
                'commands' => [],
            ];
        }

        $commands = [];
        $reasons  = [];

        // Valores medidos vs rangos ideales del ambiente.
        $temp      = (float) $measurement['temperature'];
        $humidity  = (float) $measurement['humidity'];
        $co2       = (int) $measurement['co2_ppm'];
        $airScore  = (int) $measurement['air_quality_index'];
        $minTemp   = (float) $space['min_temperature'];
        $maxTemp   = (float) $space['max_temperature'];
        $minHum    = (float) $space['min_humidity'];
        $maxHum    = (float) $space['max_humidity'];
        $maxCo2    = (int) $space['max_co2'];

        // REGLA 1 — Ventilador/aire: se enciende si la temperatura, la humedad
        // o el CO₂ superan el máximo del ambiente.
        $fanTarget = ($temp > $maxTemp || $humidity > $maxHum || $co2 > $maxCo2) ? 'on' : 'off';
        if ($fanTarget === 'on') {
            $reasons[] = 'Aire acondicionado sugerido.';
        }

        // REGLA 2 — Aromatizador: se enciende si la calidad de aire baja de 60/100.
        $aromatizerTarget = $airScore < 60 ? 'on' : 'off';
        if ($aromatizerTarget === 'on') {
            $reasons[] = 'Aromatizador sugerido.';
        }

        // REGLA 3 — LED de alerta: solo ante desvíos GRAVES (más allá de un
        // margen extra sobre el rango: ±2°C, ±8% humedad, +250 ppm, aire < 45).
        $ledTarget = (
            $temp > $maxTemp + 2 ||
            $temp < $minTemp - 2 ||
            $humidity > $maxHum + 8 ||
            $humidity < $minHum - 8 ||
            $co2 > $maxCo2 + 250 ||
            $airScore < 45
        ) ? 'on' : 'off';

        if ($ledTarget === 'on') {
            $reasons[] = 'Alerta visual sugerida.';
        }

        // Encolar un comando por actuador. CommandService evita duplicados:
        // si el actuador ya está en ese estado, devuelve null y no se encola.
        foreach ([
            'fan'        => $fanTarget,
            'aromatizer' => $aromatizerTarget,
            'alert_led'  => $ledTarget,
        ] as $commandType => $targetValue) {
            $command = $this->commandService->queueAutomationCommand(
                (int) $device['id'],
                $commandType,
                $targetValue,
                implode(' ', $reasons) !== '' ? implode(' ', $reasons) : 'Ajuste automático.'
            );

            if ($command) {
                $commands[] = $command;
            }
        }

        return [
            'summary'  => $reasons !== []
                ? implode(' ', $reasons)
                : 'Sin ajustes necesarios.',
            'commands' => $commands,
        ];
    }

    // -------------------------------------------------------------------------
    // Procesar la ÚLTIMA medición registrada
    // Atajo usado al volver a modo automático: busca la medición más
    // reciente del dispositivo y la procesa con las reglas de arriba.
    // -------------------------------------------------------------------------

    public function processLatestMeasurement(array $device, array $space): array
    {
        $measurement = $this->measurementModel
            ->where('device_id', $device['id'])
            ->orderBy('captured_at', 'DESC')
            ->first();

        if (! $measurement) {
            return [
                'summary'  => 'Sin mediciones.',
                'commands' => [],
            ];
        }

        return $this->processMeasurement($device, $space, $measurement);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - processMeasurement($device, $space, $measurement)
       → aplica las 3 reglas (fan / aromatizer / alert_led) a UNA medición;
         devuelve ['summary' => resumen legible, 'commands' => encolados]
   - processLatestMeasurement($device, $space)
       → busca la última medición del dispositivo y llama al anterior
   - getStateByDeviceId()  → (CommandService) estado actual del dispositivo
   - queueAutomationCommand() → (CommandService) encola si hace falta
   - implode(' ', $reasons) → (PHP) une los motivos en una sola frase
   ============================================================================ */
