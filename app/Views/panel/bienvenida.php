<?php
/**
 * Pantalla de bienvenida del dashboard, mostrada cuando la cuenta del usuario
 * todavía no tiene dispositivos vinculados. Dos caminos posibles:
 *  · Conectar mi primer dispositivo → pantalla de conexión por QR
 *  · Comprar Eden Air               → vista de compra
 */
$usuario = isset($usuario) && is_array($usuario) ? $usuario : ['nombre' => 'usuario', 'apellido' => ''];
$nombre  = trim((string) ($usuario['nombre'] ?? '')) ?: 'usuario';
$initial = strtoupper(mb_substr($nombre, 0, 1) ?: 'U');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'Eden Air · Bienvenido',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="description" content="Bienvenido a Eden Air. Conectá tus dispositivos, organizalos por ambiente y visualizá el estado de tus espacios en tiempo real.">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-ready">
<div class="ea-dashboard" data-dashboard-app>

    <?= view('partials/dashboard_sidebar', ['active' => 'inicio', 'devicesCount' => 0]) ?>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>
            <div class="ea-header-titles">
                <h1>Inicio</h1>
                <p>Configurá tu primer dispositivo Eden Air</p>
            </div>
            <div class="ea-header-tools"><?= view('partials/theme_toggle') ?></div>
            <div class="ea-header-user" title="<?= esc($nombre) ?>">
                <span class="ea-header-avatar"><?= esc($initial) ?></span>
                <span class="ea-header-name"><?= esc($nombre) ?><small>Cuenta Eden Air</small></span>
            </div>
        </header>

        <div class="ea-content ea-welcome">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="ea-flash ea-flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="ea-flash ea-flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <section class="ea-welcome-hero" aria-labelledby="ea-welcome-title">
                <h2 id="ea-welcome-title" class="ea-welcome-title">
                    Bienvenido a <em>Eden&nbsp;Air</em>, <?= esc($nombre) ?>.
                </h2>
                <p class="ea-welcome-lede">
                    Tu cuenta está lista. Falta un solo paso: vincular tu dispositivo
                    para empezar a ver el aire de tu espacio en tiempo real.
                </p>
            </section>

            <section class="ea-welcome-actions" aria-label="Cómo empezar">
                <div class="ea-welcome-grid">
                    <article class="ea-welcome-card ea-welcome-card--primary">
                        <header class="ea-welcome-card-head">
                            <span class="ea-welcome-card-tag">Recomendado</span>
                            <span class="ea-welcome-card-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/><path d="M9 8.5l2.3 2.3L15 7"/></svg>
                            </span>
                        </header>
                        <h4>Conectar mi primer dispositivo</h4>
                        <p>Enchufá tu Eden Air, apretá Conectar y escaneá el QR con el celular. Se vincula solo.</p>
                        <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-button ea-button-primary ea-button-buy ea-button-block">Conectá tu Eden Air</a>
                    </article>

                    <article class="ea-welcome-card">
                        <header class="ea-welcome-card-head">
                            <span class="ea-welcome-card-tag ea-welcome-card-tag--neutral">Plan único</span>
                            <span class="ea-welcome-card-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                            </span>
                        </header>
                        <h4>Comprar Eden Air</h4>
                        <p>Dispositivo, dashboard y configuración por ambiente. Todo incluido.</p>
                        <a href="<?= site_url('panel/compra') ?>" class="ea-button ea-button-ghost ea-button-block">Ver plan / Comprar</a>
                    </article>
                </div>

                <p class="ea-welcome-help">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1 1-1 1.7M12 17h.01" stroke-linecap="round"/></svg>
                    No hace falta ningún código ni buscar nada en la caja: el QR se genera en el momento y el equipo se da de alta solo.
                </p>
            </section>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
