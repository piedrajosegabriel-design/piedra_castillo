<?php
$errores = session()->getFlashdata('errors') ?? [];
$presetSeleccionado = old('environment_type');

if (! is_string($presetSeleccionado) || $presetSeleccionado === '') {
    $presetSeleccionado = 'hogar';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => 'EdenAir | Elegir ambiente']) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle' => 'Configuración inicial',
        'actions'  => '<a href="' . site_url('logout') . '" class="ea-button ea-button-secondary">Cerrar sesión</a>',
    ]) ?>

    <main>
        <section class="ea-section">
            <div class="ea-page">
                <div class="ea-page-header">
                    <div>
                        <p class="ea-eyebrow">Configuración inicial</p>
                        <h1>Elegí el tipo de <em>ambiente.</em></h1>
                    </div>
                    <p class="ea-lede">
                        Cada perfil define los rangos ideales de temperatura, humedad
                        y CO₂. Si elegís <strong>personalizable</strong> podés ajustar
                        tus propios límites.
                    </p>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="ea-message ea-message--success" style="margin-bottom: 22px;">
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="ea-message ea-message--error" style="margin-bottom: 22px;">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errores): ?>
                    <div class="ea-message ea-message--error" style="margin-bottom: 22px;">
                        <div>
                            <strong>Revisá los siguientes campos:</strong>
                            <ul>
                                <?php foreach ($errores as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="panelAmbiente" data-preset="<?= esc($presetSeleccionado) ?>">
                <form action="<?= site_url('panel/ambiente') ?>" method="POST"
                      id="formAmbiente" class="ea-stack-lg">
                    <?= csrf_field() ?>

                    <div class="ea-env-grid">
                        <?php foreach (($presets ?? []) as $key => $preset): ?>
                            <?php $resumen = sprintf(
                                '%.1f-%.1f °C · %.0f-%.0f %% · %d ppm',
                                (float) $preset['min_temperature'],
                                (float) $preset['max_temperature'],
                                (float) $preset['min_humidity'],
                                (float) $preset['max_humidity'],
                                (int) $preset['max_co2']
                            ); ?>
                            <label class="ea-env-card<?= $presetSeleccionado === $key ? ' activa' : '' ?>" data-preset-card>
                                <input type="radio" name="environment_type" value="<?= esc($key) ?>"
                                       <?= $presetSeleccionado === $key ? 'checked' : '' ?> required>
                                <span class="ea-spread">
                                    <span class="ea-env-code"><?= esc(strtoupper(substr((string) $preset['label'], 0, 2))) ?></span>
                                </span>
                                <strong><?= esc($preset['label']) ?></strong>
                                <small><?= esc($preset['description']) ?></small>
                                <span class="ea-env-range"><?= esc($resumen) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <article class="ea-card" id="resumenAmbiente">
                        <div class="ea-spread" style="margin-bottom: 14px;">
                            <div>
                                <p class="ea-eyebrow">Selección actual</p>
                                <h2 class="ea-serif" id="previewNombre" style="font-size: 32px; letter-spacing: -0.015em; margin-top: 8px;">Hogar</h2>
                            </div>
                            <span class="ea-badge ea-badge--info" id="previewEstado">Preset listo</span>
                        </div>

                        <p class="ea-lede" id="previewDescripcion" style="color: var(--ea-ink-2); font-size: 15px;">
                            Balance general para convivencia diaria.
                        </p>

                        <div class="ea-stat-grid" style="margin-top: 22px;">
                            <div class="ea-stat-card">
                                <div class="ea-stat-head">
                                    <span class="ea-stat-label">Temperatura</span>
                                </div>
                                <strong class="ea-stat-value" id="previewTemperatura">20.0 a 26.0 °C</strong>
                            </div>
                            <div class="ea-stat-card tone-info">
                                <div class="ea-stat-head">
                                    <span class="ea-stat-label">Humedad</span>
                                </div>
                                <strong class="ea-stat-value" id="previewHumedad">35 a 60 %</strong>
                            </div>
                            <div class="ea-stat-card tone-warning">
                                <div class="ea-stat-head">
                                    <span class="ea-stat-label">CO₂ límite</span>
                                </div>
                                <strong class="ea-stat-value" id="previewCo2">1000 ppm</strong>
                            </div>
                        </div>
                    </article>

                    <article id="bloquePersonalizado"
                             class="ea-card ea-card--cream<?= $presetSeleccionado === 'personalizable' ? '' : ' oculto' ?>">
                        <p class="ea-eyebrow">Perfil personalizable</p>
                        <h3 class="ea-serif" style="font-size: 24px; margin: 8px 0 18px;">Definí tus propios rangos.</h3>

                        <div class="ea-form">
                            <div class="ea-field">
                                <label for="custom_name">Nombre del ambiente</label>
                                <input type="text" id="custom_name" name="custom_name"
                                       value="<?= esc(old('custom_name')) ?>"
                                       placeholder="Ejemplo: Laboratorio de pruebas">
                            </div>

                            <div class="ea-field-row">
                                <div class="ea-field">
                                    <label for="min_temperature">Temperatura mínima (°C)</label>
                                    <input type="number" step="0.1" id="min_temperature" name="min_temperature"
                                           value="<?= esc(old('min_temperature')) ?>">
                                </div>
                                <div class="ea-field">
                                    <label for="max_temperature">Temperatura máxima (°C)</label>
                                    <input type="number" step="0.1" id="max_temperature" name="max_temperature"
                                           value="<?= esc(old('max_temperature')) ?>">
                                </div>
                            </div>

                            <div class="ea-field-row">
                                <div class="ea-field">
                                    <label for="min_humidity">Humedad mínima (%)</label>
                                    <input type="number" step="0.1" id="min_humidity" name="min_humidity"
                                           value="<?= esc(old('min_humidity')) ?>">
                                </div>
                                <div class="ea-field">
                                    <label for="max_humidity">Humedad máxima (%)</label>
                                    <input type="number" step="0.1" id="max_humidity" name="max_humidity"
                                           value="<?= esc(old('max_humidity')) ?>">
                                </div>
                            </div>

                            <div class="ea-field">
                                <label for="max_co2">Límite de CO₂ (ppm)</label>
                                <input type="number" id="max_co2" name="max_co2"
                                       value="<?= esc(old('max_co2')) ?>">
                                <p class="ea-hint">Si dejás campos vacíos se usan los valores base del preset personalizable.</p>
                            </div>
                        </div>
                    </article>

                    <div class="ea-spread" style="flex-wrap: wrap;">
                        <p class="ea-hint">
                            Al continuar se crea el espacio, se prepara la simulación inicial
                            y se habilita el panel.
                        </p>
                        <button type="submit" class="ea-button ea-button-primary" id="botonAmbiente">
                            Continuar al panel
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </section>
    </main>

    <?= view('partials/footer') ?>
</div>

<script id="presetData" type="application/json"><?= json_encode($presets ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/ambiente.js') ?>"></script>
</body>
</html>
