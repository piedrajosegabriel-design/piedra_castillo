<?php

namespace App\Services;

/* ============================================================
   EnvironmentPresetService
   QUÉ HACE: es el catálogo de "presets" de ambiente: para cada
   tipo de espacio (oficina, aula, hogar, dormitorio, personali-
   zable) define los rangos ideales de temperatura/humedad y el
   límite de CO₂. También arma los datos listos para insertar en
   `spaces` y resuelve los nombres legibles de cada ambiente.
   Es el único service SIN modelos: solo constantes y helpers
   (no toca la base de datos).
   SE RELACIONA CON: lo usan DeviceClaimService y
   DeviceProvisioningService (al crear ambientes), PanelService,
   DispositivosController y AmbientesController (nombres legibles).
   ============================================================ */
class EnvironmentPresetService
{
    // -------------------------------------------------------------------------
    // Catálogo de presets: la "fuente de la verdad" de los rangos por tipo
    // -------------------------------------------------------------------------
    private const PRESETS = [
        'oficina' => [
            'label'           => 'Oficina',
            'description'     => 'Pensado para confort prolongado y productividad.',
            'min_temperature' => 21.0,
            'max_temperature' => 25.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 900,
        ],
        'aula' => [
            'label'           => 'Aula',
            'description'     => 'Rangos orientados a concentracion y permanencia.',
            'min_temperature' => 20.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        'hogar' => [
            'label'           => 'Hogar',
            'description'     => 'Balance general para convivencia diaria.',
            'min_temperature' => 20.0,
            'max_temperature' => 26.0,
            'min_humidity'    => 35.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        'dormitorio' => [
            'label'           => 'Dormitorio',
            'description'     => 'Confort suave para descanso nocturno.',
            'min_temperature' => 18.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 55.0,
            'max_co2'         => 900,
        ],
        'personalizable' => [
            'label'           => 'Personalizable',
            'description'     => 'Permite ajustar nombre y umbrales base.',
            'min_temperature' => 20.0,
            'max_temperature' => 25.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
    ];

    // -------------------------------------------------------------------------
    // Acceso al catálogo
    // -------------------------------------------------------------------------

    /** Todos los presets (para listarlos en formularios). */
    public function getPresets(): array
    {
        return self::PRESETS;
    }

    /** Un preset por su clave; si no existe, cae al de 'hogar'. */
    public function getPreset(string $type): array
    {
        return self::PRESETS[$type] ?? self::PRESETS['hogar'];
    }

    // -------------------------------------------------------------------------
    // Armado de datos para la tabla `spaces`
    // -------------------------------------------------------------------------

    /**
     * Combina lo que mandó el usuario con los valores del preset: cada campo
     * usa el valor recibido si vino, o el del preset como respaldo.
     * custom_name solo aplica al tipo 'personalizable'.
     */
    public function buildSpaceData(array $data): array
    {
        $environmentType = (string) ($data['environment_type'] ?? 'hogar');
        $preset          = $this->getPreset($environmentType);
        $customName      = trim((string) ($data['custom_name'] ?? ''));

        return [
            'environment_type' => $environmentType,
            'custom_name'      => $environmentType === 'personalizable'
                ? ($customName !== '' ? $customName : 'Espacio personalizable')
                : null,
            'min_temperature'  => $this->toFloat($data['min_temperature'] ?? $preset['min_temperature'], $preset['min_temperature']),
            'max_temperature'  => $this->toFloat($data['max_temperature'] ?? $preset['max_temperature'], $preset['max_temperature']),
            'min_humidity'     => $this->toFloat($data['min_humidity'] ?? $preset['min_humidity'], $preset['min_humidity']),
            'max_humidity'     => $this->toFloat($data['max_humidity'] ?? $preset['max_humidity'], $preset['max_humidity']),
            'max_co2'          => $this->toInt($data['max_co2'] ?? $preset['max_co2'], $preset['max_co2']),
        ];
    }

    // -------------------------------------------------------------------------
    // Nombres legibles
    // -------------------------------------------------------------------------

    /** Nombre para mostrar: el custom si es personalizable, o el del preset. */
    public function getDisplayName(array $space): string
    {
        if (($space['environment_type'] ?? '') === 'personalizable') {
            $customName = trim((string) ($space['custom_name'] ?? ''));

            return $customName !== '' ? $customName : 'Espacio personalizable';
        }

        return $this->getPreset((string) ($space['environment_type'] ?? 'hogar'))['label'];
    }

    /** Etiqueta del tipo de ambiente ('oficina' → 'Oficina'). */
    public function getEnvironmentLabel(string $type): string
    {
        return $this->getPreset($type)['label'];
    }

    // -------------------------------------------------------------------------
    // Conversores con valor de respaldo
    // -------------------------------------------------------------------------

    /** A float; si vino vacío o null, usa el valor del preset. */
    private function toFloat(mixed $value, float $fallback): float
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (float) $value;
    }

    /** A int; si vino vacío o null, usa el valor del preset. */
    private function toInt(mixed $value, int $fallback): int
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (int) $value;
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - getPresets()           → el catálogo completo (5 presets)
   - getPreset($type)       → un preset; desconocido → 'hogar' (nunca falla)
   - buildSpaceData($data)  → array listo para SpaceModel::insert(): tipo,
                              nombre custom y rangos (input del usuario o preset)
   - getDisplayName($space) → nombre legible del ambiente para las vistas
   - getEnvironmentLabel()  → etiqueta del tipo ('aula' → 'Aula')
   - toFloat()/toInt()      → conversión con fallback al valor del preset
   - ?? (null coalescing)   → (PHP) "usá esto, y si es null, esto otro"
   - private const PRESETS  → constante de clase: datos fijos, sin base de datos
   ============================================================================ */
