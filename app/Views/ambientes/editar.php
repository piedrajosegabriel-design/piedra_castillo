<?php
$userName = (string) (session()->get('user_name') ?? 'Usuario');
$initial  = strtoupper(mb_substr(trim($userName), 0, 1) ?: 'U');
$ambiente = $ambiente ?? [];
$presets  = new \App\Services\EnvironmentPresetService();
$nombre   = $presets->getDisplayName($ambiente);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'Eden Air · Editar ambiente',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="robots" content="noindex, nofollow"><meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-ready">
<div class="ea-dashboard" data-dashboard-app>

    <?= view('partials/dashboard_sidebar', ['active' => 'ambientes']) ?>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>
            <div class="ea-header-titles">
                <h1>Editar ambiente</h1>
                <p><?= esc($nombre) ?></p>
            </div>
            <div class="ea-header-tools"><?= view('partials/theme_toggle') ?></div>
            <div class="ea-header-user" title="<?= esc($userName) ?>">
                <span class="ea-header-avatar"><?= esc($initial) ?></span>
                <span class="ea-header-name"><?= esc($userName) ?><small>Cuenta Eden Air</small></span>
            </div>
        </header>

        <div class="ea-content">
            <a href="<?= site_url('panel/ambientes') ?>" class="ea-back-link">← Volver a Ambientes</a>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="ea-flash ea-flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('panel/ambientes/' . (int) $ambiente['id']) ?>" class="ea-wizard-form" style="max-width:620px;">
                <?= csrf_field() ?>

                <h2 class="ea-step-title">Configuración del ambiente</h2>
                <p class="ea-step-lede">Definí los rangos de confort para <?= esc($nombre) ?>. Eden Air va a usar estos valores para evaluar el estado del aire.</p>

                <?php if (($ambiente['environment_type'] ?? '') === 'personalizable'): ?>
                    <label class="ea-field">
                        <span class="ea-field-label">Nombre del ambiente</span>
                        <input type="text" name="custom_name" value="<?= esc(old('custom_name', (string) ($ambiente['custom_name'] ?? '')), 'attr') ?>" class="ea-input" maxlength="120" required>
                    </label>
                <?php else: ?>
                    <input type="hidden" name="custom_name" value="<?= esc((string) ($ambiente['custom_name'] ?? ''), 'attr') ?>">
                <?php endif; ?>

                <div class="ea-amb-row">
                    <label class="ea-field">
                        <span class="ea-field-label">Temperatura mín. (°C)</span>
                        <input type="number" step="0.1" name="min_temperature" value="<?= esc(old('min_temperature', (string) $ambiente['min_temperature']), 'attr') ?>" class="ea-input" required>
                    </label>
                    <label class="ea-field">
                        <span class="ea-field-label">Temperatura máx. (°C)</span>
                        <input type="number" step="0.1" name="max_temperature" value="<?= esc(old('max_temperature', (string) $ambiente['max_temperature']), 'attr') ?>" class="ea-input" required>
                    </label>
                </div>

                <div class="ea-amb-row">
                    <label class="ea-field">
                        <span class="ea-field-label">Humedad mín. (%)</span>
                        <input type="number" step="0.1" name="min_humidity" value="<?= esc(old('min_humidity', (string) $ambiente['min_humidity']), 'attr') ?>" class="ea-input" required>
                    </label>
                    <label class="ea-field">
                        <span class="ea-field-label">Humedad máx. (%)</span>
                        <input type="number" step="0.1" name="max_humidity" value="<?= esc(old('max_humidity', (string) $ambiente['max_humidity']), 'attr') ?>" class="ea-input" required>
                    </label>
                </div>

                <label class="ea-field">
                    <span class="ea-field-label">CO₂ máximo (ppm)</span>
                    <input type="number" name="max_co2" value="<?= esc(old('max_co2', (string) $ambiente['max_co2']), 'attr') ?>" class="ea-input" required>
                </label>

                <div class="ea-wizard-nav">
                    <a href="<?= site_url('panel/ambientes') ?>" class="ea-button ea-button-ghost">Cancelar</a>
                    <button type="submit" class="ea-button ea-button-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
