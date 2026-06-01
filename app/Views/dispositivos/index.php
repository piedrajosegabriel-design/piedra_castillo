<?php
/**
 * Mis dispositivos — listado de todos los dispositivos vinculados a la cuenta.
 * Recibe: $dispositivos (array preparado por DeviceClaimService::listarDeUsuario).
 */
$userName = (string) (session()->get('user_name') ?? 'Usuario');
$initial  = strtoupper(mb_substr(trim($userName), 0, 1) ?: 'U');
$dispositivos = $dispositivos ?? [];
$total    = count($dispositivos);
$errors   = session()->getFlashdata('errors') ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'EdenAir · Mis dispositivos',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="description" content="Administrá todos los dispositivos Eden Air vinculados a tu cuenta: estado, ambiente y alta de nuevos equipos.">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-ready">
<div class="ea-dashboard" data-dashboard-app>

    <?= view('partials/dashboard_sidebar', ['active' => 'dispositivos', 'devicesCount' => $total]) ?>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>
            <div class="ea-header-titles">
                <h1>Mis dispositivos</h1>
                <p><?= esc((string) $total) ?> <?= $total === 1 ? 'dispositivo vinculado' : 'dispositivos vinculados' ?></p>
            </div>
            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>
            </div>
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
            <?php if ($errors): ?>
                <div class="ea-flash ea-flash-danger"><ul><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <section class="ea-dev-toolbar" aria-label="Acciones de dispositivos">
                <div>
                    <h2 class="ea-dev-toolbar-title">Equipos vinculados</h2>
                    <p class="ea-dev-toolbar-sub">Administrá el estado y el ambiente de cada Eden Air. Una misma cuenta puede tener varios dispositivos.</p>
                </div>
                <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-button ea-button-primary ea-button-buy">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Agregar dispositivo
                </a>
            </section>

            <?php if ($total === 0): ?>
                <section class="ea-dev-empty">
                    <span class="ea-dev-empty-orb" aria-hidden="true"></span>
                    <h3>Todavía no vinculaste ningún dispositivo</h3>
                    <p>Conectá tu Eden Air con el código de activación que viene con el producto y empezá a monitorear tu ambiente.</p>
                    <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-button ea-button-primary ea-button-buy">Conectá tu Eden Air</a>
                    <p class="ea-dev-empty-hint">¿Probando la demo? Usá el código <strong>EDEN-DEMO-2026</strong>.</p>
                </section>
            <?php else: ?>
                <section class="ea-dev-grid" aria-label="Listado de dispositivos">
                    <?php foreach ($dispositivos as $d): ?>
                        <article class="ea-dev-card">
                            <header class="ea-dev-card-head">
                                <span class="ea-dev-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                                </span>
                                <span class="ea-dev-badge tone-<?= esc($d['estado_tono']) ?>">
                                    <span class="ea-dev-badge-dot"></span><?= esc($d['estado_label']) ?>
                                </span>
                            </header>
                            <h3 class="ea-dev-name"><?= esc($d['nombre']) ?></h3>
                            <dl class="ea-dev-meta">
                                <div><dt>Tipo</dt><dd><?= esc($d['tipo']) ?></dd></div>
                                <div><dt>Ambiente</dt><dd><?= esc($d['espacio']) ?></dd></div>
                                <div><dt>ID técnico</dt><dd class="ea-mono"><?= esc($d['uid']) ?></dd></div>
                                <?php if (! empty($d['mac'])): ?>
                                    <div><dt>MAC <span class="ea-dev-tag">técnica</span></dt><dd class="ea-mono"><?= esc($d['mac']) ?></dd></div>
                                <?php endif; ?>
                            </dl>
                            <?php if (! empty($d['notas'])): ?>
                                <p class="ea-dev-notes"><?= esc($d['notas']) ?></p>
                            <?php endif; ?>
                            <footer class="ea-dev-card-foot">
                                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary ea-button-sm">Ver panel</a>
                                <?php if (! empty($d['codigo'])): ?>
                                    <span class="ea-dev-code" title="Código de activación usado">· <?= esc($d['codigo']) ?></span>
                                <?php endif; ?>
                            </footer>
                        </article>
                    <?php endforeach; ?>

                    <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-dev-card ea-dev-card-add">
                        <span class="ea-dev-add-plus" aria-hidden="true">+</span>
                        <span class="ea-dev-add-label">Agregar otro dispositivo</span>
                        <span class="ea-dev-add-hint">Vinculá un nuevo Eden Air con su código</span>
                    </a>
                </section>
            <?php endif; ?>

            <section class="ea-dev-info" aria-label="Cómo funciona la vinculación">
                <h3>¿Cómo se vincula un dispositivo?</h3>
                <ol class="ea-dev-steps">
                    <li><span>1</span> Encendé tu dispositivo Eden Air.</li>
                    <li><span>2</span> Buscá el código de activación del producto (o de la maqueta).</li>
                    <li><span>3</span> Ingresalo en “Agregar dispositivo”.</li>
                    <li><span>4</span> Asignale un nombre y un ambiente.</li>
                    <li><span>5</span> Finalizá la vinculación: queda asociado a tu cuenta.</li>
                </ol>
                <p class="ea-dev-info-note">Podés administrar varios dispositivos desde un mismo dashboard. Luego vas a poder configurar automatizaciones ambientales por espacio.</p>
            </section>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
