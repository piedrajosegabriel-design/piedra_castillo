<?php
/**
 * EDITAR AMBIENTE — tipo de espacio, nombre y rangos de confort.
 * Ruta: /panel/ambientes/{id}/editar · Controlador: AmbientesController::editar
 * Recibe:
 *   $ambiente    fila de la tabla spaces
 *   $catalogo    los tipos de espacio con sus rangos (EnvironmentPresetService)
 *   $nombre      nombre que se muestra hoy (el propio o el del tipo)
 *   $sigue_tipo  bool: si los números guardados son los del tipo o están a medida
 *   $control     umbrales de la lógica de control ya resueltos (histéresis,
 *                calidad de aire mínima, CO₂ crítico)
 *
 * Estos números son los que el servidor le manda al equipo: la ESP32 decide
 * con ellos, sin preguntar. Ver DeviceConfigService.
 *
 * INTERACCIÓN: public/JS/ambientes.js — al elegir un tipo carga sus valores
 * recomendados, avisa cuándo quedaron "a medida" y permite restaurarlos.
 */
$ambiente   = $ambiente   ?? [];
$catalogo   = $catalogo   ?? [];
$nombre     = $nombre     ?? 'Ambiente';
$sigue_tipo = $sigue_tipo ?? true;
$control    = $control    ?? [];

$tipoActual = (string) old('environment_type', (string) ($ambiente['environment_type'] ?? 'hogar'));

// Datos del tipo elegido: los usan el texto de ayuda del nombre, el cartelito
// de estado y el botón de restaurar. Después JS/ambientes.js los va cambiando
// en vivo, pero sin JS ya salen bien de acá.
$tipoElegido = null;
foreach ($catalogo as $tipo) {
    if ($tipo['clave'] === $tipoActual) {
        $tipoElegido = $tipo;
        break;
    }
}
$labelActual = $tipoElegido['label'] ?? 'Hogar';
$esLibre     = (bool) ($tipoElegido['libre'] ?? false);

$this->setData([
    'tituloPagina'  => 'Eden Air · Editar ambiente',
    'sidebarActivo' => 'ambientes',
    'cabecera'      => ['titulo' => 'Editar ambiente', 'bajada' => $nombre],
    'scripts'       => ['JS/ambientes.js'],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <a href="<?= site_url('panel/ambientes') ?>" class="ea-back-link">← Volver a Ambientes</a>

    <!-- ===== FORMULARIO: tipo, nombre y rangos del ambiente =====
         Guarda en POST /panel/ambientes/{id} → AmbientesController::actualizar -->
    <form method="post" action="<?= site_url('panel/ambientes/' . (int) $ambiente['id']) ?>"
          class="ea-wizard-form ea-amb-form" data-ea-ambiente>
        <?= csrf_field() ?>

        <h2 class="ea-step-title">Configuración del ambiente</h2>
        <p class="ea-step-lede">
            Cada tipo de espacio trae los rangos con los que Eden Air evalúa el aire.
            Elegí el que corresponda y, si querés otros números, cambialos abajo.
        </p>

        <!-- ---------- 1. Tipo de espacio ---------- -->
        <fieldset class="ea-amb-tipos">
            <legend class="ea-field-label">¿Qué tipo de espacio es?</legend>

            <div class="ea-amb-tipos-grid">
                <?php foreach ($catalogo as $tipo): ?>
                    <label class="ea-amb-tipo">
                        <input type="radio" name="environment_type" value="<?= esc($tipo['clave'], 'attr') ?>"
                               data-ea-tipo
                               data-label="<?= esc($tipo['label'], 'attr') ?>"
                               data-libre="<?= $tipo['libre'] ? '1' : '0' ?>"
                               data-valores="<?= esc(json_encode($tipo['valores']), 'attr') ?>"
                               <?= $tipo['clave'] === $tipoActual ? 'checked' : '' ?>>

                        <span class="ea-amb-tipo-icon" aria-hidden="true"><?= icono($tipo['icono'], 20) ?></span>

                        <span class="ea-amb-tipo-body">
                            <span class="ea-amb-tipo-label"><?= esc($tipo['label']) ?></span>
                            <span class="ea-amb-tipo-desc"><?= esc($tipo['descripcion']) ?></span>

                            <?php if ($tipo['libre']): ?>
                                <span class="ea-amb-tipo-libre">Los rangos los ponés vos</span>
                            <?php else: ?>
                                <span class="ea-amb-tipo-rangos">
                                    <span><?= icono('temp', 13) ?><?= esc($tipo['rangos']['temp']) ?></span>
                                    <span><?= icono('hum', 13) ?><?= esc($tipo['rangos']['hum']) ?></span>
                                    <span><?= icono('co2', 13) ?><?= esc($tipo['rangos']['co2']) ?></span>
                                </span>
                            <?php endif; ?>
                        </span>

                        <span class="ea-amb-tipo-check" aria-hidden="true"><?= icono('check', 14) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- ---------- 2. Nombre propio ---------- -->
        <label class="ea-field">
            <span class="ea-field-label">Nombre del ambiente <span class="ea-field-opt">· opcional</span></span>
            <input type="text" name="custom_name" class="ea-input" maxlength="120" data-ea-nombre
                   placeholder="<?= esc($nombre, 'attr') ?>"
                   value="<?= esc(old('custom_name', (string) ($ambiente['custom_name'] ?? '')), 'attr') ?>">
            <span class="ea-field-hint">
                Es el nombre que ves en el panel y el que recibe el equipo. Si lo dejás vacío
                se usa el del tipo: <strong data-ea-nombre-tipo><?= esc($labelActual) ?></strong>.
            </span>
        </label>

        <!-- ---------- 3. Rangos de confort ---------- -->
        <section class="ea-amb-valores">
            <header class="ea-amb-valores-head">
                <div>
                    <h3 class="ea-amb-valores-title">Rangos de confort</h3>
                    <p class="ea-amb-valores-sub">Fuera de estos valores el equipo actúa y el panel avisa.</p>
                </div>
                <span class="ea-dev-badge <?= $esLibre || $sigue_tipo ? 'tone-info' : 'tone-warning' ?>" data-ea-estado>
                    <span class="ea-dev-badge-dot"></span>
                    <span data-ea-estado-texto><?php
                        echo $esLibre
                            ? 'Valores propios'
                            : ($sigue_tipo ? 'Valores de ' . esc($labelActual) : 'Ajustados a medida');
                    ?></span>
                </span>
            </header>

            <div class="ea-amb-row">
                <label class="ea-field">
                    <span class="ea-field-label">Temperatura mín. (°C)</span>
                    <input type="number" step="0.1" min="-20" max="60" name="min_temperature" class="ea-input" required
                           data-ea-valor="min_temperature"
                           value="<?= esc(old('min_temperature', (string) $ambiente['min_temperature']), 'attr') ?>">
                </label>
                <label class="ea-field">
                    <span class="ea-field-label">Temperatura máx. (°C)</span>
                    <input type="number" step="0.1" min="-20" max="60" name="max_temperature" class="ea-input" required
                           data-ea-valor="max_temperature"
                           value="<?= esc(old('max_temperature', (string) $ambiente['max_temperature']), 'attr') ?>">
                </label>
            </div>

            <div class="ea-amb-row">
                <label class="ea-field">
                    <span class="ea-field-label">Humedad mín. (%)</span>
                    <input type="number" step="0.1" min="0" max="100" name="min_humidity" class="ea-input" required
                           data-ea-valor="min_humidity"
                           value="<?= esc(old('min_humidity', (string) $ambiente['min_humidity']), 'attr') ?>">
                </label>
                <label class="ea-field">
                    <span class="ea-field-label">Humedad máx. (%)</span>
                    <input type="number" step="0.1" min="0" max="100" name="max_humidity" class="ea-input" required
                           data-ea-valor="max_humidity"
                           value="<?= esc(old('max_humidity', (string) $ambiente['max_humidity']), 'attr') ?>">
                </label>
            </div>

            <label class="ea-field">
                <span class="ea-field-label">CO₂ máximo (ppm)</span>
                <input type="number" step="10" min="400" max="5000" name="max_co2" class="ea-input" required
                       data-ea-valor="max_co2"
                       value="<?= esc(old('max_co2', (string) $ambiente['max_co2']), 'attr') ?>">
                <span class="ea-field-hint">Al aire libre hay unas 400 ppm; arriba de 1000 el aire ya se siente cargado.</span>
            </label>

            <button type="button" class="ea-button ea-button-ghost ea-button-sm ea-amb-restaurar"
                    data-ea-restaurar <?= $esLibre || $sigue_tipo ? 'hidden' : '' ?>>
                Volver a los valores de <span data-ea-restaurar-tipo><?= esc($labelActual) ?></span>
            </button>
        </section>

        <!-- ---------- 4. Ajustes avanzados de la lógica de control ----------
             Van plegados a propósito. El 99 % de las veces no hay que tocarlos:
             los valores recomendados ya vienen bien y son iguales para todos
             los tipos de ambiente. Pero son los números con los que el equipo
             decide, así que tienen que poder verse y cambiarse desde acá.

             Llevan data-ea-valor como los de arriba, así que JS/ambientes.js
             los carga y los restaura igual, sin una línea de código nueva. -->
        <details class="ea-amb-avanzado">
            <summary class="ea-amb-avanzado-head">
                <span class="ea-amb-avanzado-title">Ajustes avanzados</span>
                <span class="ea-amb-avanzado-sub">Cuándo se apaga cada regla y cuándo el CO₂ pasa a ser crítico</span>
            </summary>

            <div class="ea-amb-avanzado-body">
                <p class="ea-amb-valores-sub">
                    <strong>Histéresis</strong> es la diferencia entre encender y apagar. Sin ella, con la
                    temperatura justo en el límite, el aire acondicionado prendería y apagaría sin parar.
                    Con 2 °C, si enciende a 26 recién corta a 24.
                </p>

                <label class="ea-field">
                    <span class="ea-field-label">Calidad de aire mínima (0–100)</span>
                    <input type="number" step="1" min="1" max="99" name="min_air_quality" class="ea-input" required
                           data-ea-valor="min_air_quality"
                           value="<?= esc(old('min_air_quality', (string) ($ambiente['min_air_quality'] ?? $control['min_air_quality'] ?? 70)), 'attr') ?>">
                    <span class="ea-field-hint">
                        Por debajo de este índice el equipo prende el LED rojo y te pide ventilar.
                        No enciende ningún actuador: EdenAir no renueva el aire, así que esa decisión es tuya.
                    </span>
                </label>

                <div class="ea-amb-row">
                    <label class="ea-field">
                        <span class="ea-field-label">Histéresis de temperatura (°C)</span>
                        <input type="number" step="0.1" min="0.1" max="10" name="temp_hysteresis" class="ea-input" required
                               data-ea-valor="temp_hysteresis"
                               value="<?= esc(old('temp_hysteresis', (string) ($ambiente['temp_hysteresis'] ?? $control['temp_hysteresis'] ?? 2)), 'attr') ?>">
                        <span class="ea-field-hint">Cuánto tiene que bajar para que corte el aire acondicionado.</span>
                    </label>
                    <label class="ea-field">
                        <span class="ea-field-label">Histéresis de humedad (puntos)</span>
                        <input type="number" step="0.1" min="0.1" max="30" name="hum_hysteresis" class="ea-input" required
                               data-ea-valor="hum_hysteresis"
                               value="<?= esc(old('hum_hysteresis', (string) ($ambiente['hum_hysteresis'] ?? $control['hum_hysteresis'] ?? 8)), 'attr') ?>">
                        <span class="ea-field-hint">Cuánto tiene que subir para que corte el humidificador.</span>
                    </label>
                </div>

                <div class="ea-amb-row">
                    <label class="ea-field">
                        <span class="ea-field-label">Histéresis de CO₂ (ppm)</span>
                        <input type="number" step="10" min="10" max="500" name="co2_hysteresis" class="ea-input" required
                               data-ea-valor="co2_hysteresis"
                               value="<?= esc(old('co2_hysteresis', (string) ($ambiente['co2_hysteresis'] ?? $control['co2_hysteresis'] ?? 150)), 'attr') ?>">
                        <span class="ea-field-hint">Cuánto tiene que bajar el CO₂ para que se levante el aviso.</span>
                    </label>
                    <label class="ea-field">
                        <span class="ea-field-label">Histéresis de calidad de aire (puntos)</span>
                        <input type="number" step="1" min="1" max="30" name="air_hysteresis" class="ea-input" required
                               data-ea-valor="air_hysteresis"
                               value="<?= esc(old('air_hysteresis', (string) ($ambiente['air_hysteresis'] ?? $control['air_hysteresis'] ?? 5)), 'attr') ?>">
                        <span class="ea-field-hint">Cuánto tiene que recuperar el aire para que se levante el aviso.</span>
                    </label>
                </div>

                <label class="ea-field">
                    <span class="ea-field-label">CO₂ crítico (ppm)</span>
                    <input type="number" step="50" min="500" max="5000" name="critical_co2" class="ea-input" required
                           data-ea-valor="critical_co2"
                           value="<?= esc(old('critical_co2', (string) ($ambiente['critical_co2'] ?? $control['critical_co2'] ?? 1400)), 'attr') ?>">
                    <span class="ea-field-hint">
                        A partir de acá el aviso se muestra más fuerte. Tiene que ser mayor que el límite de CO₂ de arriba.
                    </span>
                </label>
            </div>
        </details>

        <div class="ea-wizard-nav">
            <a href="<?= site_url('panel/ambientes') ?>" class="ea-button ea-button-ghost">Cancelar</a>
            <button type="submit" class="ea-button ea-button-primary">Guardar cambios</button>
        </div>
    </form>

<?= $this->endSection() ?>
