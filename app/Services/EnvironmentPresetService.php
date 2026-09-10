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
   Además define los valores de la lógica de control que no
   dependen del cuarto (histéresis, mínimo de calidad de aire y
   CO₂ crítico): están en CONTROL y los hereda cada preset.
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
    // Valores de la lógica de control que NO dependen del tipo de ambiente.
    //
    // Un aula y un dormitorio quieren temperaturas distintas, pero ninguno de
    // los dos quiere que el relé traquetee: la histéresis y los umbrales de
    // aviso son propiedades del sistema, no del cuarto. Se definen una sola
    // vez acá y cada preset los hereda; el usuario igual puede moverlos desde
    // /panel/ambientes si quiere afinar su equipo.
    // -------------------------------------------------------------------------
    private const CONTROL = [
        // Debajo de este índice (0–100) el equipo prende el LED rojo y pide
        // ventilar. No enciende ningún actuador: EdenAir no renueva el aire.
        'min_air_quality' => 70,

        // Cuánto tiene que mejorar cada variable para que la regla se apague.
        //   temperatura → enciende sobre el máximo, corta 2 °C por debajo
        //   humedad     → enciende bajo el mínimo, corta 8 puntos por encima
        //   CO₂         → avisa sobre el máximo, deja de avisar 150 ppm abajo
        //   aire        → avisa bajo el mínimo, deja de avisar 5 puntos arriba
        'temp_hysteresis' => 2.0,
        'hum_hysteresis'  => 8.0,
        'co2_hysteresis'  => 150,
        'air_hysteresis'  => 5,

        // Por encima de esto el aviso de CO₂ deja de ser "alto" y pasa a ser
        // crítico (mismo LED rojo, mensaje más fuerte en el panel).
        'critical_co2' => 1400,
    ];

    // -------------------------------------------------------------------------
    // Catálogo de presets: la "fuente de la verdad" de los rangos por tipo.
    // 'icono' es una clave del catálogo de icono() (app/Helpers/eden_helper.php).
    //
    // Los valores de control (histéresis, calidad de aire, CO₂ crítico) NO se
    // repiten acá: los agrega getPreset() desde CONTROL.
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
            'description'     => 'Aire estable para sostener la concentración del curso.',
            'icono'           => 'aula',
            'min_temperature' => 20.0,
            'max_temperature' => 24.0,
            'min_humidity'    => 40.0,
            'max_humidity'    => 60.0,
            'max_co2'         => 1000,
        ],
        // 'hogar' es además el preset de respaldo: si llega un tipo que no
        // existe, se usa este. Por eso es el que lleva los valores de base de
        // la lógica nueva: 26 °C de máximo, 40 % de mínimo y 1000 ppm.
        'hogar' => [
            'label'           => 'Hogar',
            'description'     => 'Balance general para los ambientes de uso diario.',
            'icono'           => 'casa',
            'min_temperature' => 20.0,
            'max_temperature' => 26.0,
            'min_humidity'    => 40.0,
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
            'max_temperature' => 26.0,
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
        'min_air_quality',
        'temp_hysteresis',
        'hum_hysteresis',
        'co2_hysteresis',
        'air_hysteresis',
        'critical_co2',
    ];

    /**
     * Los campos "avanzados": los que no describen el confort del ambiente
     * sino cómo se comporta el control. El formulario los muestra aparte y
     * plegados, para que la pantalla siga siendo simple.
     */
    public const CAMPOS_CONTROL = [
        'min_air_quality',
        'temp_hysteresis',
        'hum_hysteresis',
        'co2_hysteresis',
        'air_hysteresis',
        'critical_co2',
    ];

    // -------------------------------------------------------------------------
    // Acceso al catálogo
    // -------------------------------------------------------------------------

    /**
     * Un preset por su clave; si no existe, cae al de 'hogar'.
     *
     * Los valores de control se agregan acá y no se escriben en cada preset:
     * son los mismos para todos los tipos y así no se puede olvidar uno.
     */
    public function getPreset(string $type): array
    {
        return (self::PRESETS[$type] ?? self::PRESETS['hogar']) + self::CONTROL;
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

        foreach (array_keys(self::PRESETS) as $clave) {
            // getPreset() y no PRESETS a secas: así los valores de control
            // (histéresis, calidad de aire) viajan a la vista junto al resto y
            // el selector del formulario los puede cargar de una.
            $preset = $this->getPreset($clave);

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

            // Valores de la lógica de control. Si el formulario no los mandó
            // (por ejemplo, un alta automática al vincular un equipo) quedan
            // los del preset, que son los recomendados.
            'min_air_quality'  => $this->toInt($data['min_air_quality'] ?? null, $preset['min_air_quality']),
            'temp_hysteresis'  => $this->toFloat($data['temp_hysteresis'] ?? null, $preset['temp_hysteresis']),
            'hum_hysteresis'   => $this->toFloat($data['hum_hysteresis'] ?? null, $preset['hum_hysteresis']),
            'co2_hysteresis'   => $this->toInt($data['co2_hysteresis'] ?? null, $preset['co2_hysteresis']),
            'air_hysteresis'   => $this->toInt($data['air_hysteresis'] ?? null, $preset['air_hysteresis']),
            'critical_co2'     => $this->toInt($data['critical_co2'] ?? null, $preset['critical_co2']),
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
            // ?? $preset[$campo]: una fila guardada antes de que existieran los
            // campos de control no está "a medida", le falta el dato. Tratarla
            // como distinta haría que todos los ambientes viejos aparecieran
            // como ajustados a mano sin que nadie los tocara.
            $valor = $space[$campo] ?? $preset[$campo];

            if (abs((float) $valor - (float) $preset[$campo]) > 0.001) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Valores efectivos de un ambiente
    // -------------------------------------------------------------------------

    /**
     * Los umbrales de control de un ambiente, ya casteados y con respaldo.
     *
     * Es el único lugar que sabe qué hacer cuando una fila de `spaces` todavía
     * no tiene los campos nuevos: usa los del preset. Lo consumen
     * DeviceConfigService (lo que se le manda al equipo) y PanelService (lo que
     * se muestra), así los dos leen exactamente los mismos números.
     */
    public function control(array $space): array
    {
        $preset = $this->getPreset((string) ($space['environment_type'] ?? 'hogar'));

        return [
            'min_air_quality' => (int)   ($space['min_air_quality'] ?? $preset['min_air_quality']),
            'temp_hysteresis' => (float) ($space['temp_hysteresis'] ?? $preset['temp_hysteresis']),
            'hum_hysteresis'  => (float) ($space['hum_hysteresis']  ?? $preset['hum_hysteresis']),
            'co2_hysteresis'  => (int)   ($space['co2_hysteresis']  ?? $preset['co2_hysteresis']),
            'air_hysteresis'  => (int)   ($space['air_hysteresis']  ?? $preset['air_hysteresis']),
            'critical_co2'    => (int)   ($space['critical_co2']    ?? $preset['critical_co2']),
        ];
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
   - control($space)        → los umbrales de control efectivos del ambiente
                              (histéresis, calidad de aire mínima, CO₂ crítico),
                              con respaldo al preset si la fila es anterior a
                              esos campos. Lo leen DeviceConfigService y
                              PanelService, así los dos usan los mismos números
   - soloCampos()           → los campos numéricos de un preset
   - toFloat()/toInt()      → conversión con fallback al valor del preset
   - ?? (null coalescing)   → (PHP) "usá esto, y si es null, esto otro"
   - private const PRESETS  → constante de clase: datos fijos, sin base de datos
   ============================================================================ */
