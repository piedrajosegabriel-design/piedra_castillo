<?php
$userName = (string) (session()->get('user_name') ?? 'Usuario');
$initial  = strtoupper(mb_substr(trim($userName), 0, 1) ?: 'U');
$ambientes = $ambientes ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'Eden Air · Ambientes',
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
                <h1>Ambientes</h1>
                <p>Los lugares físicos donde están tus dispositivos Eden Air</p>
            </div>
            <div class="ea-header-tools"><?= view('partials/theme_toggle') ?></div>
            <div class="ea-header-user" title="<?= esc($userName) ?>">
                <span class="ea-header-avatar"><?= esc($initial) ?></span>
                <span class="ea-header-name"><?= esc($userName) ?><small>Cuenta Eden Air</small></span>
            </div>
        </header>

        <div class="ea-content">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="ea-flash ea-flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="ea-flash ea-flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <section class="ea-dev-toolbar">
                <div>
                    <h2 class="ea-dev-toolbar-title">Tus ambientes</h2>
                    <p class="ea-dev-toolbar-sub">Cada ambiente representa un espacio físico (dormitorio, aula, oficina…). Podés ajustar sus rangos de confort y ver los dispositivos que tiene asignados.</p>
                </div>
                <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-button ea-button-secondary">+ Agregar dispositivo</a>
            </section>

            <?php if ($ambientes === []): ?>
                <section class="ea-dev-empty">
                    <span class="ea-dev-empty-orb" aria-hidden="true"></span>
                    <h3>Todavía no tenés ambientes</h3>
                    <p>Los ambientes se crean al vincular tu primer dispositivo Eden Air. Empezá agregando uno.</p>
                    <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-button ea-button-primary ea-button-buy">Conectá tu Eden Air</a>
                </section>
            <?php else: ?>
                <section class="ea-dev-grid">
                    <?php foreach ($ambientes as $a): ?>
                        <article class="ea-dev-card">
                            <header class="ea-dev-card-head">
                                <span class="ea-dev-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/></svg>
                                </span>
                                <span class="ea-dev-badge tone-info">
                                    <span class="ea-dev-badge-dot"></span><?= esc($a['tipo']) ?>
                                </span>
                            </header>
                            <h3 class="ea-dev-name"><?= esc($a['nombre']) ?></h3>
                            <dl class="ea-dev-meta">
                                <div><dt>Rango temp</dt><dd><?= esc($a['rango_temp']) ?></dd></div>
                                <div><dt>Rango humedad</dt><dd><?= esc($a['rango_hum']) ?></dd></div>
                                <div><dt>CO₂ máx</dt><dd><?= esc((string) $a['max_co2']) ?> ppm</dd></div>
                                <div><dt>Dispositivos</dt><dd><?= esc((string) count($a['devices'])) ?></dd></div>
                            </dl>
                            <?php if ($a['devices']): ?>
                                <ul class="ea-amb-devices">
                                    <?php foreach ($a['devices'] as $d): ?>
                                        <li><span class="ea-amb-dot" aria-hidden="true"></span><?= esc($d['name']) ?> <small>· <?= esc($d['tipo']) ?></small></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <footer class="ea-dev-card-foot">
                                <a href="<?= site_url('panel/ambientes/' . $a['id'] . '/editar') ?>" class="ea-button ea-button-secondary ea-button-sm">Editar ambiente</a>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
