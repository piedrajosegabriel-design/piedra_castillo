<?php
/**
 * Pantalla de bienvenida del dashboard, mostrada cuando la cuenta del usuario
 * todavía no tiene dispositivos vinculados. Tres caminos posibles:
 *  · Agregar mi primer dispositivo  → wizard de alta
 *  · Ver demo del sistema           → crea un dispositivo simulado
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
                <span class="ea-welcome-eyebrow">
                    <span class="ea-welcome-eyebrow-dot" aria-hidden="true"></span>
                    Cuenta Eden Air · Primer ingreso
                </span>
                <h2 id="ea-welcome-title" class="ea-welcome-title">
                    Bienvenido a <em>Eden&nbsp;Air</em>, <?= esc($nombre) ?>.
                </h2>
                <p class="ea-welcome-lede">
                    Desde acá vas a poder <strong>conectar tus dispositivos</strong>,
                    <strong>organizarlos por ambiente</strong> y
                    <strong>visualizar el estado de cada espacio</strong> en tiempo real.
                    No necesitás conocimientos técnicos para empezar.
                </p>

                <ul class="ea-welcome-bullets" aria-label="Cómo funciona Eden Air en una cuenta">
                    <li>
                        <span class="ea-welcome-bullet-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                        </span>
                        <div>
                            <strong>Dispositivos</strong>
                            <span>Cada Eden Air que comprás se vincula con tu cuenta por su código de activación.</span>
                        </div>
                    </li>
                    <li>
                        <span class="ea-welcome-bullet-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/></svg>
                        </span>
                        <div>
                            <strong>Ambientes</strong>
                            <span>Son los lugares donde está instalado cada dispositivo (dormitorio, aula, oficina…).</span>
                        </div>
                    </li>
                    <li>
                        <span class="ea-welcome-bullet-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l4-5 4 3 4-7 6 9"/><path d="M3 20h18"/></svg>
                        </span>
                        <div>
                            <strong>Monitoreo</strong>
                            <span>Tu ambiente, bajo control: temperatura, humedad, CO₂ y calidad del aire en tiempo real.</span>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="ea-welcome-actions" aria-label="¿Qué querés hacer?">
                <h3 class="ea-welcome-actions-title">¿Qué querés hacer ahora?</h3>

                <div class="ea-welcome-grid">
                    <article class="ea-welcome-card ea-welcome-card--primary">
                        <header class="ea-welcome-card-head">
                            <span class="ea-welcome-card-tag">Recomendado</span>
                            <span class="ea-welcome-card-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/><path d="M9 8.5l2.3 2.3L15 7"/></svg>
                            </span>
                        </header>
                        <h4>Agregar mi primer dispositivo</h4>
                        <p>Ingresá el código de activación que viene con tu Eden Air, asignale un nombre y elegí su ambiente.</p>
                        <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-button ea-button-primary ea-button-buy ea-button-block">Conectá tu Eden Air</a>
                    </article>

                    <article class="ea-welcome-card">
                        <header class="ea-welcome-card-head">
                            <span class="ea-welcome-card-tag ea-welcome-card-tag--info">Sin compromiso</span>
                            <span class="ea-welcome-card-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 8l6 4-6 4z" fill="currentColor"/></svg>
                            </span>
                        </header>
                        <h4>Ver demo del sistema</h4>
                        <p>Cargamos un dispositivo simulado en tu cuenta para que recorras el dashboard como si fuera real.</p>
                        <form method="post" action="<?= site_url('panel/demo') ?>" class="ea-welcome-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="ea-button ea-button-secondary ea-button-block">Probar la demo</button>
                        </form>
                    </article>

                    <article class="ea-welcome-card">
                        <header class="ea-welcome-card-head">
                            <span class="ea-welcome-card-tag ea-welcome-card-tag--neutral">Plan demo</span>
                            <span class="ea-welcome-card-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                            </span>
                        </header>
                        <h4>Comprar Eden Air</h4>
                        <p>Dispositivo + dashboard + configuración personalizada por ambiente. Todo incluido, sin costos ocultos.</p>
                        <a href="<?= site_url('panel/compra') ?>" class="ea-button ea-button-ghost ea-button-block">Ver plan / Comprar</a>
                    </article>
                </div>

                <p class="ea-welcome-help">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1 1-1 1.7M12 17h.01" stroke-linecap="round"/></svg>
                    ¿No sabés qué es un código de activación? Lo encontrás en la caja, etiqueta del dispositivo o en el QR de activación.
                    Se usa una sola vez y deja al dispositivo asociado únicamente a tu cuenta.
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
