<?php
/**
 * "Conectá tu Eden Air" — pantalla única de conexión por QR.
 *
 * No hay pasos, ni código de activación, ni formulario: el usuario aprieta
 * "Conectar", el servidor abre una ventana de vinculación y devuelve el QR
 * dibujado en el momento. La página lo muestra y se queda preguntando hasta
 * que el equipo aparece.
 *
 * Variables esperadas:
 *   $ssid    string  nombre del WiFi de configuración (solo para mostrarlo)
 *   $minutos int     cuánto dura abierta la ventana
 *
 * Endpoints que usa (ver DispositivosController):
 *   POST panel/dispositivos/conectar          → abre la ventana, devuelve el QR
 *   GET  panel/dispositivos/conectar/estado   → sondeo
 *   POST panel/dispositivos/conectar/cancelar → cierra la ventana
 */
$userName = (string) (session()->get('user_name') ?? 'Usuario');
$initial  = strtoupper(mb_substr(trim($userName), 0, 1) ?: 'U');
$ssid     = $ssid ?? 'EdenAir-Setup';
$minutos  = $minutos ?? 10;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'Eden Air · Conectá tu dispositivo',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="robots" content="noindex, nofollow"><meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-ready">
<div class="ea-dashboard" data-dashboard-app>

    <?= view('partials/dashboard_sidebar', ['active' => 'dispositivos']) ?>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>
            <div class="ea-header-titles">
                <h1>Conectá tu Eden Air</h1>
                <p>Escaneá un QR y listo: sin códigos ni configuración manual</p>
            </div>
            <div class="ea-header-tools"><?= view('partials/theme_toggle') ?></div>
            <div class="ea-header-user" title="<?= esc($userName) ?>">
                <span class="ea-header-avatar"><?= esc($initial) ?></span>
                <span class="ea-header-name"><?= esc($userName) ?><small>Cuenta Eden Air</small></span>
            </div>
        </header>

        <div class="ea-content">
            <a href="<?= site_url('panel/dispositivos') ?>" class="ea-back-link">← Volver a Mis dispositivos</a>

            <section class="ea-pair" data-pair
                     data-url-iniciar="<?= site_url('panel/dispositivos/conectar') ?>"
                     data-url-estado="<?= site_url('panel/dispositivos/conectar/estado') ?>"
                     data-url-cancelar="<?= site_url('panel/dispositivos/conectar/cancelar') ?>"
                     data-url-listado="<?= site_url('panel/dispositivos') ?>"
                     data-csrf-name="<?= esc(csrf_token(), 'attr') ?>"
                     data-csrf-hash="<?= esc(csrf_hash(), 'attr') ?>">

                <div class="ea-pair-card">

                    <!-- ============ Reposo: el botón ============ -->
                    <div class="ea-pair-panel" data-pair-panel="reposo">
                        <span class="ea-step-eyebrow">Conexión directa</span>
                        <h2 class="ea-step-title">Enchufá tu Eden Air y apretá Conectar</h2>
                        <p class="ea-step-lede">
                            Te vamos a mostrar un código QR generado en el momento. No tenés que buscar
                            nada en la caja ni escribir ningún código.
                        </p>

                        <ol class="ea-pair-steps">
                            <li><b>1</b><span>Enchufá el equipo y esperá unos segundos.<small>Si todavía no tiene WiFi configurado, crea su propia red para que puedas configurarlo.</small></span></li>
                            <li><b>2</b><span>Escaneá el QR con la cámara del celular.<small>El celular se conecta solo a la red del equipo, sin escribir contraseñas.</small></span></li>
                            <li><b>3</b><span>Elegí tu WiFi de casa en la pantalla que se abre sola.<small>El Eden Air se conecta y aparece acá en unos segundos.</small></span></li>
                        </ol>

                        <button type="button" class="ea-button ea-button-primary ea-button-buy ea-pair-cta" data-pair-start>
                            Conectar
                        </button>
                    </div>

                    <!-- ============ En vivo: QR + espera ============ -->
                    <div class="ea-pair-panel" data-pair-panel="vivo" hidden>
                        <div class="ea-pair-live">
                            <div>
                                <div class="ea-pair-qr" data-pair-qr></div>
                                <p class="ea-pair-qr-note">Apuntá la cámara del celular al código</p>
                            </div>

                            <div>
                                <span class="ea-step-eyebrow">Paso final</span>
                                <h2 class="ea-step-title">Escaneá el código</h2>
                                <p class="ea-step-lede">
                                    Tu celular te va a ofrecer conectarse a la red del equipo. Aceptá, y en la
                                    pantalla que se abre sola elegí el WiFi de tu casa y poné su contraseña.
                                </p>

                                <dl class="ea-pair-net">
                                    <div><dt>Red del equipo</dt><dd data-pair-ssid><?= esc($ssid) ?></dd></div>
                                    <div><dt>Contraseña</dt><dd data-pair-pass>—</dd></div>
                                </dl>

                                <p class="ea-pair-timer">Este código vale por <b data-pair-timer><?= esc((string) $minutos) ?>:00</b> minutos.</p>

                                <p class="ea-pair-status" data-pair-status role="status" aria-live="polite">
                                    <span class="ea-pair-spinner" aria-hidden="true"></span>
                                    <span data-pair-status-text>Esperando a que tu Eden Air se conecte…</span>
                                </p>

                                <div class="ea-wizard-nav" style="justify-content: flex-start;">
                                    <button type="button" class="ea-button ea-button-ghost" data-pair-cancel>Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Resultado: conectado ============ -->
                    <div class="ea-pair-panel" data-pair-panel="ok" hidden>
                        <div class="ea-pair-result">
                            <span class="ea-pair-result-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                            <h2 data-pair-ok-title>¡Tu Eden Air quedó conectado!</h2>
                            <p data-pair-ok-text>Ya está midiendo el aire de tu ambiente. En unos minutos vas a ver las primeras lecturas en el panel.</p>
                            <div class="ea-pair-actions">
                                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary ea-button-buy">Ir al panel</a>
                                <a href="<?= site_url('panel/dispositivos') ?>" class="ea-button ea-button-secondary">Ver mis dispositivos</a>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Resultado: se venció o falló ============ -->
                    <div class="ea-pair-panel" data-pair-panel="fin" hidden>
                        <div class="ea-pair-result ea-pair-result--warn">
                            <span class="ea-pair-result-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.01"/></svg>
                            </span>
                            <h2 data-pair-fin-title>Se agotó el tiempo</h2>
                            <p data-pair-fin-text>Nadie conectó un equipo durante la espera. Podés volver a intentarlo cuando quieras.</p>
                            <div class="ea-pair-actions">
                                <button type="button" class="ea-button ea-button-primary ea-button-buy" data-pair-retry>Intentar de nuevo</button>
                                <a href="<?= site_url('panel/dispositivos') ?>" class="ea-button ea-button-secondary">Volver</a>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
<script src="<?= base_url('JS/conectar.js') ?>"></script>
</body>
</html>
