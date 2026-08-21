<?php
/**
 * EDITAR AMBIENTE — tipo de espacio, nombre y rangos de confort.
 * Ruta: /panel/ambientes/{id}/editar · Controlador: AmbientesController::editar
 * Recibe:
 *   $ambiente    fila de la tabla spaces
 *   $catalogo    los tipos de espacio con sus rangos (EnvironmentPresetService)
 *   $nombre      nombre que se muestra hoy (el propio o el del tipo)
 *   $sigue_tipo  bool: si los números guardados son los del tipo o están a medida
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

        <div class="ea-wizard-nav">
            <a href="<?= site_url('panel/ambientes') ?>" class="ea-button ea-button-ghost">Cancelar</a>
            <button type="submit" class="ea-button ea-button-primary">Guardar cambios</button>
        </div>
    </form>

<?= $this->endSection() ?>
