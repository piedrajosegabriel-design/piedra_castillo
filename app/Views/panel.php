<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'EdenAir | Panel',
        'extraCss' => ['CSS/dashboard.css'],
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-loading">
<?php
/**
 * Vista del panel principal.
 *
 * Todo el cálculo (tonos, sparkline, valores actuales, defaults para datos
 * faltantes, reglas de automatización visibles, etc.) está en
 * App\Services\PanelService::obtenerVistaPanel(). Esta vista sólo recorre el
 * array y dibuja.
 */
$panel  = (isset($panel) && is_array($panel)) ? $panel : [];
$view   = (isset($panel['view']) && is_array($panel['view'])) ? $panel['view'] : [];
$api    = (isset($panel['api']) && is_array($panel['api'])) ? $panel['api'] : ($view['api'] ?? []);
$errors = session()->getFlashdata('errors') ?? [];
?>

<noscript>
    <style>
        .dashboard-loading .dashboard-loader { display: none; }
        .dashboard-loading .ea-dashboard { opacity: 1; transform: none; }
    </style>
</noscript>

<div class="dashboard-loader" data-dashboard-loader>
    <div class="dashboard-loader-grid"></div>
    <div class="dashboard-loader-shell">
        <div class="dashboard-loader-brand">
            <svg viewBox="0 0 64 64" width="44" height="44" aria-hidden="true">
                <path d="M 20 46 C 17 32, 25 18, 48 14 C 44 30, 34 42, 20 46 Z" fill="rgba(201,216,112,0.22)" stroke="#bcd2bd" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M 20 46 C 28 38, 38 26, 48 14" fill="none" stroke="#ecf2e8" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="20" cy="46" r="2.6" fill="#c9d870"/>
            </svg>
            <strong class="dashboard-loader-name">Eden<em style="font-style:italic;color:#c9d870;">Air</em></strong>
        </div>

        <div class="dashboard-loader-scene" aria-hidden="true">
            <div class="dashboard-loader-core">
                <div class="dashboard-loader-orbit dashboard-loader-orbit-a"></div>
                <div class="dashboard-loader-orbit dashboard-loader-orbit-b"></div>
                <div class="dashboard-loader-orbit dashboard-loader-orbit-c"></div>
                <div class="dashboard-loader-pulse"><div class="dashboard-loader-emblem"></div></div>
            </div>
        </div>

        <div class="dashboard-loader-content">
            <h1>Preparando el panel</h1>
            <div class="dashboard-loader-progress" aria-hidden="true"><span></span></div>
        </div>
    </div>
</div>

<div class="ea-dashboard" data-dashboard-app>

    <!-- =========================== SIDEBAR =========================== -->
    <aside class="ea-sidebar" id="dashboardSidebar" aria-label="Navegación principal">
        <div class="ea-sidebar-brand">
            <span class="ea-sidebar-mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none">
                    <path d="M22.5 7.5C12.8 7.5 7 13.4 7 21c0 1.8.4 3.4 1 4.8C13.6 23 18.6 19 22 13" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                    <path d="M22.5 7.5c1.6 5-.2 11.4-4.3 14.7-2.7 2.1-5.8 2.7-8.7 1.9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="ea-sidebar-word">
                <b>Eden<em>Air</em></b>
                <span>Panel · v0.4</span>
            </span>
        </div>

        <div class="ea-sidebar-section">Sistema</div>
        <a href="#dashboard" class="sidebar-link ea-sidebar-item is-active">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="9" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="5" rx="1.6"/><rect x="13.5" y="11.5" width="7" height="9" rx="1.6"/><rect x="3.5" y="15.5" width="7" height="5" rx="1.6"/></svg></span>
            <span class="ea-sidebar-label">Dashboard</span>
        </a>
        <a href="#sensores" class="sidebar-link ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l4-5 4 3 4-7 6 9"/><path d="M3 20h18"/></svg></span>
            <span class="ea-sidebar-label">Sensores</span>
            <span class="ea-sidebar-meta">4</span>
        </a>
        <a href="#configuracion" class="sidebar-link ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg></span>
            <span class="ea-sidebar-label">Actuadores</span>
            <span class="ea-sidebar-meta"><?= esc((string) count($view['actuators'] ?? [])) ?></span>
        </a>
        <a href="#historial" class="sidebar-link ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v4.5h4.5"/><path d="M12 7.5V12l3 2"/></svg></span>
            <span class="ea-sidebar-label">Lecturas</span>
        </a>

        <div class="ea-sidebar-section">Lógica</div>
        <a href="#automatizaciones" class="sidebar-link ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h7"/><path d="M4 17h11"/><circle cx="14" cy="7" r="2.2"/><circle cx="18" cy="17" r="2.2"/></svg></span>
            <span class="ea-sidebar-label">Automatizaciones</span>
            <span class="ea-sidebar-meta"><?= esc((string) count($view['automationRules'] ?? [])) ?></span>
        </a>
        <div class="ea-sidebar-footer">
            <span class="ea-sidebar-dot tone-<?= esc((string) ($view['generalTone'] ?? 'success')) ?>"></span>
            <span class="ea-sidebar-foot-label">Sistema en línea · ESP32 preparada</span>
        </div>
    </aside>

    <!-- =========================== MAIN =========================== -->
    <main class="ea-main">

        <!-- =========================== HEADER =========================== -->
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-header-icon-btn" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M9 4.5v15"/></svg>
            </button>

            <div class="ea-header-titles">
                <h1>Dashboard</h1>
                <p><?= esc((string) ($view['spaceName'] ?? '')) ?> · <?= esc((string) ($view['spaceLabel'] ?? '')) ?> · modo <?= esc((string) ($view['modeLabel'] ?? '')) ?></p>
            </div>

            <span class="ea-chip ea-chip-status status-<?= esc((string) ($view['generalTone'] ?? 'success')) ?>" title="Estado general del ambiente">
                <span class="ea-pulse"></span>
                <span><?= esc((string) ($view['generalLabel'] ?? '')) ?></span>
            </span>

            <span class="ea-chip ea-chip-update" title="Última actualización">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M20 12a8 8 0 11-2.5-5.8"/><path d="M20 4v4h-4"/></svg>
                <span>Actualizado</span>
                <span class="ea-mono"><?= esc((string) ($view['lastUpdate'] ?? 'Hoy')) ?></span>
            </span>

            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>

                <a href="<?= site_url('logout') ?>" class="ea-header-icon-btn ea-header-logout" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4"/><path d="M10 16l-4-4 4-4"/><path d="M6 12h12"/></svg>
                </a>
            </div>

            <div class="ea-header-user" title="<?= esc((string) ($view['userName'] ?? '')) ?>">
                <span class="ea-header-avatar"><?= esc((string) ($view['userInitial'] ?? 'U')) ?></span>
                <span class="ea-header-name">
                    <?= esc((string) ($view['userName'] ?? '')) ?>
                    <small><?= esc((string) ($view['modeLabel'] ?? '')) ?></small>
                </span>
            </div>
        </header>

        <!-- =========================== CONTENT =========================== -->
        <div class="ea-content">

            <?php if (session()->getFlashdata('success')): ?>
                <div class="ea-flash ea-flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="ea-flash ea-flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="ea-flash ea-flash-danger">
                    <ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <!-- Sección: Resumen -->
            <div class="ea-sec" id="dashboard">
                <h2>Resumen del ambiente</h2>
                <span class="ea-sec-right">tiempo real · <?= esc((string) count($view['automationRules'] ?? [])) ?> reglas · <?= esc((string) count($view['actuators'] ?? [])) ?> actuadores</span>
            </div>

            <article class="ea-card ea-summary-card tone-<?= esc((string) ($view['generalTone'] ?? 'success')) ?>">
                <svg class="ea-summary-pattern" viewBox="0 0 600 240" preserveAspectRatio="xMaxYMid slice" aria-hidden="true">
                    <defs>
                        <linearGradient id="ea-summary-lg" x1="0" x2="1">
                            <stop offset="0" stop-color="var(--eden-200)" stop-opacity="0"/>
                            <stop offset="1" stop-color="var(--eden-200)" stop-opacity=".55"/>
                        </linearGradient>
                    </defs>
                    <g stroke="url(#ea-summary-lg)" stroke-width="1.1" fill="none">
                        <path d="M380 20 C360 90 430 150 560 220"/>
                        <path d="M410 20 C390 90 460 150 590 220"/>
                        <path d="M440 20 C420 90 490 150 620 220"/>
                        <path d="M470 20 C450 90 520 150 650 220"/>
                        <path d="M500 20 C480 90 550 150 680 220"/>
                    </g>
                </svg>

                <div class="ea-summary-top">
                    <span class="ea-badge tone-<?= esc((string) ($view['generalTone'] ?? 'success')) ?>"><span class="ea-dot"></span><?= esc((string) ($view['generalLabel'] ?? '')) ?></span>
                    <span class="ea-mono ea-summary-stamp">ACTUALIZADO · <?= esc((string) ($view['lastUpdate'] ?? 'Hoy')) ?></span>
                    <span class="ea-summary-dev">
                        <span class="ea-mono">DISP-PRINCIPAL</span>
                        <span class="ea-summary-devname"><?= esc((string) ($view['deviceUid'] ?? '')) ?></span>
                    </span>
                </div>

                <div class="ea-summary-headline">
                    <h2 class="ea-serif ea-summary-title"><?= esc((string) ($view['generalTitle'] ?? '')) ?></h2>
                    <p class="ea-summary-diag"><?= esc((string) ($view['generalDetail'] ?? '')) ?></p>
                </div>

                <div class="ea-summary-metrics">
                    <div class="ea-metric"><span class="ea-mono ea-metric-label">SENSORES</span><strong class="ea-metric-val">4/4</strong></div>
                    <span class="ea-summary-sep"></span>
                    <div class="ea-metric"><span class="ea-mono ea-metric-label">ACTUADORES</span><strong class="ea-metric-val"><?= esc((string) (int) ($view['activeActuators'] ?? 0)) ?> ON <small>/ <?= esc((string) (count($view['actuators'] ?? []) - (int) ($view['activeActuators'] ?? 0))) ?> OFF</small></strong></div>
                    <span class="ea-summary-sep"></span>
                    <div class="ea-metric"><span class="ea-mono ea-metric-label">REGLAS ACTIVAS</span><strong class="ea-metric-val"><?= esc((string) (int) ($view['automationActiveCount'] ?? 0)) ?>/<?= esc((string) count($view['automationRules'] ?? [])) ?></strong></div>
                    <span class="ea-summary-sep"></span>
                    <div class="ea-metric"><span class="ea-mono ea-metric-label">ESP32</span><strong class="ea-metric-val ea-metric-muted">Preparada</strong></div>
                </div>
            </article>

            <article class="ea-card ea-pulse-card">
                <div class="ea-pulse-top">
                    <span class="ea-mono ea-pulse-label">TENDENCIA · 24 H</span>
                    <span class="ea-badge tone-neutral"><span class="ea-dot"></span>Estable</span>
                </div>

                <svg viewBox="0 0 220 60" class="ea-pulse-spark" aria-hidden="true">
                    <defs>
                        <linearGradient id="ea-spark-grad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="var(--eden-500)" stop-opacity=".35"/>
                            <stop offset="1" stop-color="var(--eden-500)" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?> L 220 60 L 0 60 Z" fill="url(#ea-spark-grad)"/>
                    <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?>" fill="none" stroke="var(--eden-600)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                <div class="ea-pulse-grid">
                    <div class="ea-mini">
                        <span class="ea-mono ea-mini-label">PICO CO₂</span>
                        <span class="ea-mini-val"><?= esc((string) (int) ($view['peakCo2'] ?? 0)) ?><span class="ea-mono"> ppm</span></span>
                        <span class="ea-mono ea-mini-time">14:22</span>
                    </div>
                    <div class="ea-mini">
                        <span class="ea-mono ea-mini-label">MIN HUMEDAD</span>
                        <span class="ea-mini-val"><?= esc((string) (int) ($view['minHumidityRecent'] ?? 0)) ?><span class="ea-mono"> %</span></span>
                        <span class="ea-mono ea-mini-time">06:10</span>
                    </div>
                    <div class="ea-mini">
                        <span class="ea-mono ea-mini-label">MAX TEMP</span>
                        <span class="ea-mini-val"><?= esc((string) ($view['maxTempRecent'] ?? '0')) ?><span class="ea-mono"> °C</span></span>
                        <span class="ea-mono ea-mini-time">15:05</span>
                    </div>
                    <div class="ea-mini">
                        <span class="ea-mono ea-mini-label">AIRE</span>
                        <span class="ea-mini-val"><?= esc((string) (int) ($view['currentAir'] ?? 0)) ?><span class="ea-mono"> aqi</span></span>
                        <span class="ea-mono ea-mini-time">ahora</span>
                    </div>
                </div>
            </article>

            <!-- Sección: Sensores -->
            <div class="ea-sec" id="sensores">
                <h2>Sensores</h2>
                <span class="ea-sec-right">4 activos · lecturas en tiempo real</span>
            </div>

            <?php foreach (($view['sensorCards'] ?? []) as $sensor):
                $sStatus = (string) ($sensor['estado'] ?? 'success');
                $sLabel  = $sStatus === 'danger' ? 'Crítico' : ($sStatus === 'warning' ? 'Atención' : 'Normal');
            ?>
                <article class="ea-card ea-sensor-card accent-<?= esc((string) ($sensor['accent'] ?? 'eden')) ?>">
                    <div class="ea-sensor-head">
                        <span class="ea-sensor-icon" aria-hidden="true">
                            <?php switch ($sensor['icon'] ?? 'temp'):
                                case 'temp': ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14V5a2 2 0 014 0v9a4 4 0 11-4 0z"/><path d="M12 8v6"/></svg>
                            <?php break; case 'hum': ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5c3.5 4 6 7.2 6 10.5a6 6 0 11-12 0c0-3.3 2.5-6.5 6-10.5z"/><path d="M9 14a3 3 0 003 3"/></svg>
                            <?php break; case 'air': ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h11a3 3 0 100-6"/><path d="M3 14h14a3 3 0 110 6"/><path d="M3 19h5a2 2 0 100-4"/></svg>
                            <?php break; case 'co2': ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="12" r="3"/><path d="M14 9.5a3 3 0 110 5"/><path d="M19 14.5a2 2 0 110 3"/></svg>
                            <?php break; endswitch; ?>
                        </span>
                        <span class="ea-sensor-title"><?= esc((string) ($sensor['titulo'] ?? '')) ?></span>
                        <span class="ea-badge tone-<?= esc($sStatus) ?> ea-sensor-badge"><span class="ea-dot"></span><?= esc($sLabel) ?></span>
                    </div>

                    <div class="ea-sensor-value">
                        <span class="ea-mono ea-sensor-num"><?= esc((string) ($sensor['valor'] ?? '')) ?></span>
                        <span class="ea-mono ea-sensor-unit"><?= esc((string) ($sensor['unidad'] ?? '')) ?></span>
                    </div>

                    <div class="ea-sensor-foot">
                        <div class="ea-sensor-track">
                            <span class="ea-sensor-fill tone-<?= esc($sStatus) ?>" style="width: <?= esc((string) round((float) ($sensor['pct'] ?? 0), 1)) ?>%;"></span>
                        </div>
                        <div class="ea-sensor-hint">
                            <span><?= esc((string) ($sensor['detalle'] ?? '')) ?></span>
                            <span class="ea-mono"><?= esc((string) round((float) ($sensor['pct'] ?? 0))) ?>%</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <!-- Sección: Estado del sistema (Actuadores + Automatizaciones) -->
            <div class="ea-sec" id="configuracion">
                <h2>Estado del sistema</h2>
                <span class="ea-sec-right">ESP32 · <span class="ea-mono">integración preparada</span></span>
            </div>

            <article class="ea-card ea-actuators-card">
                <div class="ea-card-head">
                    <h3>Actuadores</h3>
                    <span class="ea-mono ea-card-meta"><?= esc((string) (int) ($view['activeActuators'] ?? 0)) ?> ACTIVOS · <?= esc((string) (count($view['actuators'] ?? []) - (int) ($view['activeActuators'] ?? 0))) ?> EN ESPERA</span>
                    <form class="ea-card-head-action" action="<?= site_url('panel/modo') ?>" method="POST" data-preserve-scroll>
                        <?= csrf_field() ?>
                        <input type="hidden" name="mode" value="<?= ! empty($view['modoManual']) ? 'automatic' : 'manual' ?>">
                        <button type="submit" class="ea-kbtn"><?= ! empty($view['modoManual']) ? 'Pasar a automático' : 'Activar manual' ?></button>
                    </form>
                </div>

                <ul class="ea-actuators-list">
                    <?php foreach (($view['actuators'] ?? []) as $act):
                        $on      = strtolower((string) ($act['estado'] ?? 'apagado')) !== 'apagado';
                        $key     = (string) ($act['clave'] ?? 'fan');
                        $iconKey = $key === 'aromatizer' ? 'spray' : ($key === 'alert_led' ? 'led' : ($key === 'humid' ? 'drop' : 'fan'));
                    ?>
                        <li class="ea-actuator-row">
                            <span class="ea-actuator-icon <?= $on ? 'is-on' : '' ?>" aria-hidden="true">
                                <?php switch ($iconKey):
                                    case 'fan': ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1.6"/><path d="M12 10.4c0-3 1.2-5.4 3.5-5.4S18 7 16.5 9.4c-1 1.5-3 2-4.5 1"/><path d="M13.6 12c3 0 5.4 1.2 5.4 3.5S17 18 14.6 16.5c-1.5-1-2-3-1-4.5"/><path d="M12 13.6c0 3-1.2 5.4-3.5 5.4S6 17 7.5 14.6c1-1.5 3-2 4.5-1"/><path d="M10.4 12c-3 0-5.4-1.2-5.4-3.5S7 6 9.4 7.5c1.5 1 2 3 1 4.5"/></svg>
                                <?php break; case 'spray': ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="9" width="8" height="12" rx="1.6"/><path d="M10 9V6h4v3"/><path d="M18 6h2M18 9h3M18 12h2"/></svg>
                                <?php break; case 'led': ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 21h4"/><path d="M7 13a5 5 0 1110 0c0 2-1 3-2 4H9c-1-1-2-2-2-4z"/><path d="M12 5V3"/></svg>
                                <?php break; case 'drop': ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5c3.5 4 6 7.2 6 10.5a6 6 0 11-12 0c0-3.3 2.5-6.5 6-10.5z"/></svg>
                                <?php break; endswitch; ?>
                            </span>
                            <div class="ea-actuator-body">
                                <strong class="ea-actuator-name"><?= esc((string) ($act['titulo'] ?? 'Actuador')) ?></strong>
                                <span class="ea-actuator-reason"><?= esc((string) ($act['detalle'] ?? 'Sin detalle disponible.')) ?></span>
                            </div>
                            <span class="ea-actuator-state">
                                <?php if (! empty($view['modoManual'])): ?>
                                    <form action="<?= site_url('panel/actuador') ?>" method="POST" data-preserve-scroll class="ea-actuator-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="actuator" value="<?= esc($key) ?>">
                                        <input type="hidden" name="value" value="<?= $on ? 'off' : 'on' ?>">
                                        <button type="submit" class="ea-actuator-toggle <?= $on ? 'is-on' : '' ?>" aria-label="<?= $on ? 'Apagar' : 'Encender' ?>">
                                            <span class="ea-actuator-toggle-thumb"></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <span class="ea-badge <?= $on ? 'tone-success' : 'tone-neutral' ?> ea-actuator-badge"><span class="ea-dot"></span><?= $on ? 'ON' : 'OFF' ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (empty($view['modoManual'])): ?>
                    <p class="ea-actuators-note">El modo automático evalúa las reglas. Pasá a manual para forzar un actuador.</p>
                <?php endif; ?>
            </article>

            <article class="ea-card ea-rules-card" id="automatizaciones">
                <div class="ea-card-head">
                    <h3>Automatizaciones</h3>
                    <span class="ea-mono ea-card-meta"><?= esc((string) count($view['automationRules'] ?? [])) ?> REGLAS · <?= esc((string) (int) ($view['automationActiveCount'] ?? 0)) ?> ACTIVAS</span>
                    <span class="ea-kbtn ea-kbtn-primary ea-card-head-action" aria-disabled="true" title="Disponible próximamente">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M12 5v14M5 12h14"/></svg>
                        Nueva regla
                    </span>
                </div>

                <div class="ea-rules-grid">
                    <?php foreach (($view['automationRules'] ?? []) as $rule):
                        $active  = ! empty($rule['active']);
                        $pending = ! empty($rule['pending']);
                    ?>
                        <div class="ea-rule">
                            <div class="ea-rule-row">
                                <span class="ea-mono ea-rule-tag">SI</span>
                                <span class="ea-rule-when"><?= esc((string) ($rule['when'] ?? '')) ?></span>
                            </div>
                            <div class="ea-rule-row">
                                <span class="ea-mono ea-rule-tag ea-rule-tag-then">ENTONCES</span>
                                <span class="ea-rule-then"><?= esc((string) ($rule['then'] ?? '')) ?></span>
                            </div>
                            <div class="ea-rule-foot">
                                <span class="ea-badge <?= $pending ? 'tone-info' : ($active ? 'tone-success' : 'tone-neutral') ?>">
                                    <span class="ea-dot"></span>
                                    <?= $pending ? 'PREPARADA' : ($active ? 'ACTIVA' : 'EN ESPERA') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <!-- Sección: Lecturas -->
            <div class="ea-sec" id="historial">
                <h2>Lecturas</h2>
                <span class="ea-sec-right"><span class="ea-mono"><?= esc((string) count($view['historyRows'] ?? [])) ?> registros recientes</span></span>
            </div>

            <article class="ea-card ea-readings-card">
                <div class="ea-card-head">
                    <h3>Últimas lecturas</h3>
                    <span class="ea-mono ea-card-meta">· <?= ! empty($view['historyIsSample']) ? 'EJEMPLO' : 'REALES' ?></span>
                    <span class="ea-kbtn ea-card-head-action" aria-disabled="true">Exportar</span>
                </div>

                <div class="ea-readings-wrap">
                    <table class="ea-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Dispositivo</th>
                                <th class="ea-num">Temp.</th>
                                <th class="ea-num">Humedad</th>
                                <th>Calidad</th>
                                <th class="ea-num">CO₂</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $historyRows = $view['historyRows'] ?? [];
                            $maxCo2Prof  = isset($panel['space']['perfil']['max_co2']) ? (int) $panel['space']['perfil']['max_co2'] : 900;
                            ?>
                            <?php if ($historyRows === []): ?>
                                <tr><td colspan="7" class="ea-table-empty">
                                    <div class="ea-empty">
                                        <strong>Sin lecturas registradas todavía.</strong>
                                        <p>Cuando el dispositivo principal envíe datos, las lecturas aparecerán acá.</p>
                                    </div>
                                </td></tr>
                            <?php else:
                                foreach ($historyRows as $row):
                                    $rowCo2Raw = (string) ($row['co2'] ?? '');
                                    preg_match('/-?\d+(?:[.,]\d+)?/', $rowCo2Raw, $m);
                                    $rowCo2   = isset($m[0]) ? (float) str_replace(',', '.', $m[0]) : 0.0;
                                    $rowState = $rowCo2 > $maxCo2Prof + 200 ? 'danger' : ($rowCo2 > $maxCo2Prof ? 'warning' : 'success');
                                    $rowLabel = $rowState === 'danger' ? 'Crítico' : ($rowState === 'warning' ? 'Atención' : 'Normal');
                            ?>
                                <tr>
                                    <td class="ea-mono ea-table-time"><?= esc((string) ($row['fecha'] ?? '--')) ?></td>
                                    <td>
                                        <span class="ea-table-dev">
                                            <span class="ea-table-dev-dot"></span>
                                            <span class="ea-mono"><?= esc((string) ($row['origen'] ?? 'edenair-node-01')) ?></span>
                                        </span>
                                    </td>
                                    <td class="ea-num ea-mono"><?= esc((string) ($row['temperatura'] ?? '--')) ?></td>
                                    <td class="ea-num ea-mono"><?= esc((string) ($row['humedad'] ?? '--')) ?></td>
                                    <td><?= esc((string) ($row['aire'] ?? '--')) ?></td>
                                    <td class="ea-num ea-mono"><?= esc((string) ($row['co2'] ?? '--')) ?></td>
                                    <td><span class="ea-badge tone-<?= esc($rowState) ?>"><span class="ea-dot"></span><?= esc($rowLabel) ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ea-readings-foot">
                    <span class="ea-mono">Mostrando <?= esc((string) count($historyRows)) ?> registros</span>
                    <span class="ea-readings-note <?= ! empty($view['latestIsSample']) ? 'is-sample' : '' ?>">
                        <?= ! empty($view['latestIsSample']) ? 'Datos de ejemplo' : 'Última: ' . esc((string) ($view['latest']['fecha'] ?? '')) ?>
                    </span>
                </div>
            </article>

            <!-- Sección: Accesos rápidos -->
            <div class="ea-sec">
                <h2>Accesos rápidos</h2>
            </div>

            <a href="#sensores" class="ea-card ea-quick-card accent-eden">
                <span class="ea-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="10" rx="1.6"/><rect x="15" y="9" width="6" height="11" rx="1.6"/><path d="M6 18h6"/></svg></span>
                <span class="ea-quick-body">
                    <strong>Ver sensores</strong>
                    <small>4 lecturas activas · intervalo regular</small>
                </span>
                <svg class="ea-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>

            <a href="#historial" class="ea-card ea-quick-card accent-breath">
                <span class="ea-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l4-5 4 3 4-7 6 9"/><path d="M3 20h18"/></svg></span>
                <span class="ea-quick-body">
                    <strong>Ver lecturas</strong>
                    <small><?= esc((string) count($view['historyRows'] ?? [])) ?> registros · historial reciente</small>
                </span>
                <svg class="ea-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>

            <a href="#automatizaciones" class="ea-card ea-quick-card accent-citrus">
                <span class="ea-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3L5 14h6l-1 7 8-11h-6l1-7z"/></svg></span>
                <span class="ea-quick-body">
                    <strong>Automatizaciones</strong>
                    <small><?= esc((string) (int) ($view['automationActiveCount'] ?? 0)) ?>/<?= esc((string) count($view['automationRules'] ?? [])) ?> reglas activas</small>
                </span>
                <svg class="ea-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>

            <a href="#configuracion" class="ea-card ea-quick-card accent-clay">
                <span class="ea-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg></span>
                <span class="ea-quick-body">
                    <strong>Revisar actuadores</strong>
                    <small><?= esc((string) (int) ($view['activeActuators'] ?? 0)) ?> activos · <?= esc((string) (count($view['actuators'] ?? []) - (int) ($view['activeActuators'] ?? 0))) ?> en espera</small>
                </span>
                <svg class="ea-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>

            <!-- Información técnica colapsable -->
            <details class="ea-card ea-tech-details">
                <summary>
                    <span class="ea-tech-summary">
                        <strong>Información técnica · API REST</strong>
                        <small>Endpoints preparados para la integración con ESP32</small>
                    </span>
                    <span class="ea-mono ea-tech-id"><?= esc((string) ($view['deviceUid'] ?? '')) ?></span>
                </summary>
                <div class="ea-tech-grid">
                    <div><span class="ea-mono">Routes</span><code><?= esc((string) ($api['routes_file'] ?? 'app/Config/Routes.php')) ?></code></div>
                    <div><span class="ea-mono">Controller</span><code><?= esc((string) ($api['controller_file'] ?? 'app/Controllers/Api/DeviceApiController.php')) ?></code></div>
                    <div><span class="ea-mono">Mediciones</span><code><?= esc((string) ($api['measurements_url'] ?? site_url('api/devices/' . ($view['deviceUid'] ?? '') . '/measurements'))) ?></code></div>
                    <div><span class="ea-mono">Comandos</span><code><?= esc((string) ($api['commands_url'] ?? site_url('api/devices/' . ($view['deviceUid'] ?? '') . '/commands/pending'))) ?></code></div>
                    <div><span class="ea-mono">Ejecutado</span><code><?= esc((string) ($api['executed_url'] ?? site_url('api/devices/' . ($view['deviceUid'] ?? '') . '/commands/{id}/executed'))) ?></code></div>
                    <div><span class="ea-mono">Token</span><code><?= esc((string) ($view['deviceTokenPreview'] ?? '')) ?></code></div>
                </div>
                <div class="ea-tech-foot">
                    <span><strong>Dispositivo</strong> <?= esc((string) ($view['deviceName'] ?? '')) ?></span>
                    <span><strong>Último envío</strong> <?= esc((string) ($view['deviceLastSeen'] ?? '')) ?></span>
                    <span><strong>Última consulta</strong> <?= esc((string) ($view['deviceLastSync'] ?? '')) ?></span>
                </div>
            </details>

        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
