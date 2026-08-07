<?php
/**
 * EDITAR AMBIENTE — los rangos de confort de un espacio.
 * Ruta: /panel/ambientes/{id}/editar · Controlador: AmbientesController::editar
 * Recibe: $ambiente (fila de la tabla spaces)
 *
 * Estos números son los que el servidor le manda al equipo: la ESP32 decide
 * con ellos, sin preguntar. Ver DeviceConfigService.
 */
$ambiente = $ambiente ?? [];
$nombre   = (new \App\Services\EnvironmentPresetService())->getDisplayName($ambiente);
$esPersonalizable = ($ambiente['environment_type'] ?? '') === 'personalizable';

$this->setData([
    'tituloPagina'  => 'Eden Air · Editar ambiente',
    'sidebarActivo' => 'ambientes',
    'cabecera'      => ['titulo' => 'Editar ambiente', 'bajada' => $nombre],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <a href="<?= site_url('panel/ambientes') ?>" class="ea-back-link">← Volver a Ambientes</a>

    <!-- ===== FORMULARIO: rangos de confort del ambiente =====
         Guarda en POST /panel/ambientes/{id} → AmbientesController::actualizar -->
    <form method="post" action="<?= site_url('panel/ambientes/' . (int) $ambiente['id']) ?>" class="ea-wizard-form ea-form-angosto">
        <?= csrf_field() ?>

        <h2 class="ea-step-title">Configuración del ambiente</h2>
        <p class="ea-step-lede">
            Definí los rangos de confort para <?= esc($nombre) ?>.
            Eden Air va a usar estos valores para evaluar el estado del aire.
        </p>

        <?php if ($esPersonalizable): ?>
            <label class="ea-field">
                <span class="ea-field-label">Nombre del ambiente</span>
                <input type="text" name="custom_name" class="ea-input" maxlength="120" required
                       value="<?= esc(old('custom_name', (string) ($ambiente['custom_name'] ?? '')), 'attr') ?>">
            </label>
        <?php else: ?>
            <?php /* Los ambientes con preset no cambian de nombre, pero el campo
                     viaja igual para que el POST no lo borre. */ ?>
            <input type="hidden" name="custom_name" value="<?= esc((string) ($ambiente['custom_name'] ?? ''), 'attr') ?>">
        <?php endif; ?>

        <div class="ea-amb-row">
            <label class="ea-field">
                <span class="ea-field-label">Temperatura mín. (°C)</span>
                <input type="number" step="0.1" name="min_temperature" class="ea-input" required
                       value="<?= esc(old('min_temperature', (string) $ambiente['min_temperature']), 'attr') ?>">
            </label>
            <label class="ea-field">
                <span class="ea-field-label">Temperatura máx. (°C)</span>
                <input type="number" step="0.1" name="max_temperature" class="ea-input" required
                       value="<?= esc(old('max_temperature', (string) $ambiente['max_temperature']), 'attr') ?>">
            </label>
        </div>

        <div class="ea-amb-row">
            <label class="ea-field">
                <span class="ea-field-label">Humedad mín. (%)</span>
                <input type="number" step="0.1" name="min_humidity" class="ea-input" required
                       value="<?= esc(old('min_humidity', (string) $ambiente['min_humidity']), 'attr') ?>">
            </label>
            <label class="ea-field">
                <span class="ea-field-label">Humedad máx. (%)</span>
                <input type="number" step="0.1" name="max_humidity" class="ea-input" required
                       value="<?= esc(old('max_humidity', (string) $ambiente['max_humidity']), 'attr') ?>">
            </label>
        </div>

        <label class="ea-field">
            <span class="ea-field-label">CO₂ máximo (ppm)</span>
            <input type="number" name="max_co2" class="ea-input" required
                   value="<?= esc(old('max_co2', (string) $ambiente['max_co2']), 'attr') ?>">
        </label>

        <div class="ea-wizard-nav">
            <a href="<?= site_url('panel/ambientes') ?>" class="ea-button ea-button-ghost">Cancelar</a>
            <button type="submit" class="ea-button ea-button-primary">Guardar cambios</button>
        </div>
    </form>

<?= $this->endSection() ?>
