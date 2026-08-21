<?php

namespace App\Services;

/* ============================================================
   EnvironmentPresetService
   QUÉ HACE: es el catálogo de "presets" de ambiente: para cada
   tipo de espacio (oficina, aula, hogar, dormitorio, personali-
   zable) define los rangos ideales de temperatura/humedad y el
   límite de CO₂. También arma los datos listos para insertar en
   `spaces`, resuelve los nombres legibles y formatea los rangos
   para las vistas.
   Es el único service SIN modelos: solo constantes y helpers
   (no toca la base de datos).
   SE RELACIONA CON: lo usan DevicePairingService (al crear
   ambientes), PanelService, DeviceConfigService y
   AmbientesController (catálogo, nombres y rangos).

   ¿CÓMO AGREGO UN TIPO DE ESPACIO? Una entrada más en PRESETS.
   El selector de /panel/ambientes/{id}/editar, la referencia del
   listado y la validación del POST salen todos de acá.
   ============================================================ */
class EnvironmentPresetService
{
    // -------------------------------------------------------------------------
    // Catálogo de presets: la "fuente de la verdad" de los rangos por tipo.
    // 'icono' es una clave del catálogo de icono() (app/Helpers/eden_helper.php).
    // -------------------------------------------------------------------------
    private const PRESETS = [
        'oficina' => [
            'label'           => 'Oficina',
            'description'     => 'Confort estable durante toda la jornada laboral.',
            'icono'           => 'oficina',
            'min_temperature' => 21.0,
            'max_temperature' => 25.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 900,
        ],
        'aula' => [
            'label'           => 'Aula',
            'description'     => 'Aire renovado para sostener la concentración del curso.',
            'icono'           => 'aula',
            'min_temperature' => 20.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        'hogar' => [
            'label'           => 'Hogar',
            'description'     => 'Balance general para los ambientes de uso diario.',
            'icono'           => 'casa',
            'min_temperature' => 20.0,
            'max_temperature' => 26.0,
            'min_humidity'    => 35.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        'dormitorio' => [
            'label'           => 'Dormitorio',
            'description'     => 'Temperatura y humedad suaves para dormir mejor.',
            'icono'           => 'dormitorio',
            'min_temperature' => 18.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 55.0,
            'max_co2'         => 900,
        ],
        'personalizable' => [
            'label'           => 'Personalizable',
            'description'     => 'Los valores los elegís vos, sin seguir ningún tipo.',
            'icono'           => 'ajustes',
            'min_temperature' => 20.0,
            'max_temperature' => 25.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
    ];

    /** Los campos numéricos de un ambiente, en el orden en que se muestran. */
    private const CAMPOS = [
        'min_temperature',
        'max_temperature',
        'min_humidity',
        'max_humidity',
        'max_co2',
    ];

    // -------------------------------------------------------------------------
    // Acceso al catálogo
    // -------------------------------------------------------------------------

    /** Un preset por su clave; si no existe, cae al de 'hogar'. */
    public function getPreset(string $type): array
    {
        return self::PRESETS[$type] ?? self::PRESETS['hogar'];
    }

    /** ¿Es una clave de tipo que existe? (para validar lo que llega por POST) */
    public function existePreset(string $type): bool
    {
        return isset(self::PRESETS[$type]);
    }

    /**
     * El catálogo listo para las vistas: cada tipo con su clave, etiqueta,
     * descripción, icono, los valores crudos (los usa el JS del selector) y
     * los rangos ya formateados para leer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCatalogo(): array
    {
        $catalogo = [];

        foreach (self::PRESETS as $clave => $preset) {
            $catalogo[] = [
                'clave'       => $clave,
                'label'       => $preset['label'],
                'descripcion' => $preset['description'],
                'icono'       => $preset['icono'],
                'libre'       => $clave === 'personalizable',
                'valores'     => $this->soloCampos($preset),
                'rangos'      => $this->formatearRangos($preset),
            ];
        }

        return $catalogo;
    }

    // -------------------------------------------------------------------------
    // Armado de datos para la tabla `spaces`
    // -------------------------------------------------------------------------

    /**
     * Combina lo que mandó el usuario con los valores del preset: cada campo
     * usa el valor recibido si vino, o el del preset como respaldo. El nombre
     * propio vale para cualquier tipo; vacío significa "usá el del tipo".
     */
    public function buildSpaceData(array $data): array
    {
        $environmentType = (string) ($data['environment_type'] ?? 'hogar');
        $preset          = $this->getPreset($environmentType);
        $customName      = trim((string) ($data['custom_name'] ?? ''));

        return [
            'environment_type' => $this->existePreset($environmentType) ? $environmentType : 'hogar',
            'custom_name'      => $customName !== '' ? $customName : null,
            'min_temperature'  => $this->toFloat($data['min_temperature'] ?? null, $preset['min_temperature']),
            'max_temperature'  => $this->toFloat($data['max_temperature'] ?? null, $preset['max_temperature']),
            'min_humidity'     => $this->toFloat($data['min_humidity'] ?? null, $preset['min_humidity']),
            'max_humidity'     => $this->toFloat($data['max_humidity'] ?? null, $preset['max_humidity']),
            'max_co2'          => $this->toInt($data['max_co2'] ?? null, $preset['max_co2']),
        ];
    }

    // -------------------------------------------------------------------------
    // Nombres legibles
    // -------------------------------------------------------------------------

    /**
     * Nombre para mostrar: el que puso el usuario (sirve para cualquier tipo)
     * o, si no puso ninguno, la etiqueta del tipo ("Oficina", "Aula"...).
     */
    public function getDisplayName(array $space): string
    {
        $customName = trim((string) ($space['custom_name'] ?? ''));

        if ($customName !== '') {
            return $customName;
        }

        return $this->getEnvironmentLabel((string) ($space['environment_type'] ?? 'hogar'));
    }

    /** Etiqueta del tipo de ambiente ('oficina' → 'Oficina'). */
    public function getEnvironmentLabel(string $type): string
    {
        return $this->getPreset($type)['label'];
    }

    // -------------------------------------------------------------------------
    // Rangos para mostrar
    // -------------------------------------------------------------------------

    /**
     * Los tres rangos de un ambiente (o de un preset) en texto corto:
     * ['temp' => '21° a 25°', 'hum' => '40% a 60%', 'co2' => '900 ppm'].
     * Lo usan el listado, la referencia por tipo y el form de edición: un
     * solo formato para los tres lugares.
     */
    public function formatearRangos(array $valores): array
    {
        return [
            'temp' => sprintf('%.0f° a %.0f°', (float) $valores['min_temperature'], (float) $valores['max_temperature']),
            'hum'  => sprintf('%.0f%% a %.0f%%', (float) $valores['min_humidity'], (float) $valores['max_humidity']),
            'co2'  => sprintf('%d ppm', (int) $valores['max_co2']),
        ];
    }

    /**
     * ¿Los valores del ambiente son los del preset de su tipo, o el usuario
     * los movió? Sirve para avisar en la interfaz "ajustado a medida".
     */
    public function siguePreset(array $space): bool
    {
        $preset = $this->getPreset((string) ($space['environment_type'] ?? 'hogar'));

        foreach (self::CAMPOS as $campo) {
            if (abs((float) $space[$campo] - (float) $preset[$campo]) > 0.001) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers internos
    // -------------------------------------------------------------------------

    /** Solo los campos numéricos de un preset (sin label/descripción/icono). */
    private function soloCampos(array $preset): array
    {
        $valores = [];

        foreach (self::CAMPOS as $campo) {
            $valores[$campo] = $preset[$campo];
        }

        return $valores;
    }

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
   - getPreset($type)       → un preset; desconocido → 'hogar' (nunca falla)
   - existePreset($type)    → ¿la clave existe? (valida el POST del form)
   - getCatalogo()          → los 5 tipos listos para la vista: etiqueta,
                              descripción, icono, valores crudos y rangos en texto
   - buildSpaceData($data)  → array listo para SpaceModel::insert(): tipo,
                              nombre propio y rangos (input del usuario o preset)
   - getDisplayName($space) → nombre propio si lo hay, si no la etiqueta del tipo
   - getEnvironmentLabel()  → etiqueta del tipo ('aula' → 'Aula')
   - formatearRangos()      → los rangos en texto corto para mostrar
   - siguePreset($space)    → ¿los números son los del tipo o están a medida?
   - soloCampos()           → los 5 campos numéricos de un preset
   - toFloat()/toInt()      → conversión con fallback al valor del preset
   - ?? (null coalescing)   → (PHP) "usá esto, y si es null, esto otro"
   - private const PRESETS  → constante de clase: datos fijos, sin base de datos
   ============================================================================ */
