<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'EdenAir · Panel del ambiente',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="description" content="Panel EdenAir: monitoreo en tiempo real de temperatura, humedad, CO₂ y calidad del aire, con control de actuadores y automatizaciones del ambiente.">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="color-scheme" content="light dark">',
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

$tone      = (string) ($view['generalTone'] ?? 'success');
$modoManual = ! empty($view['modoManual']);

/** Etiqueta + tono legibles para un estado de sensor. */
$statusMeta = static function (string $s): array {
    return match ($s) {
        'danger'  => ['Crítico', 'danger'],
        'warning' => ['Atención', 'warning'],
        default   => ['Normal', 'success'],
    };
};
?>

<noscript>
    <style>
        .dashboard-loading .dashboard-loader { display: none; }
        .dashboard-loading .ea-dashboard { opacity: 1; transform: none; }
    </style>
</noscript>

<div class="ea-loader dashboard-loader" data-dashboard-loader role="status" aria-live="polite" aria-label="Preparando tu ambiente inteligente">
    <div class="ea-loader-pattern" aria-hidden="true"></div>

    <div class="ea-loader-inner">
        <div class="ea-loader-orbit" aria-hidden="true">
            <span class="ea-loader-ring ea-loader-ring--a"></span>
            <span class="ea-loader-ring ea-loader-ring--b"></span>
            <span class="ea-loader-ring ea-loader-ring--c"></span>
            <span class="ea-loader-particle ea-loader-particle--1"></span>
            <span class="ea-loader-particle ea-loader-particle--2"></span>
            <span class="ea-loader-particle ea-loader-particle--3"></span>
            <span class="ea-loader-logo">
                <svg viewBox="0 0 64 64" aria-hidden="true">
                    <path d="M 20 46 C 17 32, 25 18, 48 14 C 44 30, 34 42, 20 46 Z" fill="rgba(201,216,112,0.28)" stroke="#ecf2e8" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M 20 46 C 28 38, 38 26, 48 14" fill="none" stroke="#ecf2e8" stroke-width="1.6" stroke-linecap="round"/>
                    <circle cx="20" cy="46" r="2.8" fill="#c9d870"/>
                </svg>
            </span>
        </div>

        <div class="ea-loader-text">
            <strong class="ea-loader-name">Eden<em>Air</em></strong>
            <p class="ea-loader-msg">Preparando tu ambiente inteligente…</p>
        </div>

        <div class="ea-loader-progress" aria-hidden="true"><span></span></div>
    </div>
</div>

<div class="ea-dashboard" data-dashboard-app>

    <!-- =========================== SIDEBAR (compartido) =========================== -->
    <?= view('partials/dashboard_sidebar', [
        'active'       => 'inicio',
        'devicesCount' => count($panel['devices_list'] ?? []),
    ]) ?>

    <!-- =========================== MAIN =========================== -->
    <main class="ea-main">

        <!-- =========================== HEADER =========================== -->
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>

            <div class="ea-header-titles">
                <h1>Resumen</h1>
                <p><?= esc((string) ($view['spaceName'] ?? '')) ?> · <?= esc((string) ($view['spaceLabel'] ?? '')) ?></p>
            </div>

            <?php
            $devicesList = (array) ($panel['devices_list'] ?? []);
            $activeDeviceName = '';
            foreach ($devicesList as $_d) {
                if (! empty($_d['is_active'])) { $activeDeviceName = (string) $_d['name']; break; }
            }
            ?>
            <?php if (count($devicesList) > 1): ?>
                <form method="post" action="<?= site_url('panel/dispositivo-activo') ?>" class="ea-device-switcher" data-preserve-scroll>
                    <?= csrf_field() ?>
                    <label for="ea-device-select" class="ea-device-switcher-label">Dispositivo</label>
                    <div class="ea-device-switcher-control">
                        <select id="ea-device-select" name="device_id" onchange="this.form.submit()" aria-label="Cambiar de dispositivo">
                            <?php foreach ($devicesList as $_d): ?>
                                <option value="<?= esc((string) $_d['id'], 'attr') ?>" <?= ! empty($_d['is_active']) ? 'selected' : '' ?>>
                                    <?= esc($_d['name']) ?> · <?= esc($_d['space']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="ea-device-switcher-caret" viewBox="0 0 12 12" width="10" height="10" aria-hidden="true"><path d="M2 4.5 6 8.5 10 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <noscript><button type="submit" class="ea-button ea-button-sm ea-button-secondary">Cambiar</button></noscript>
                </form>
            <?php elseif ($activeDeviceName !== ''): ?>
                <span class="ea-chip ea-chip-device" title="Dispositivo activo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="12" height="12" aria-hidden="true"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                    <span><?= esc($activeDeviceName) ?></span>
                </span>
            <?php endif; ?>

            <span class="ea-chip ea-chip-status status-<?= esc($tone) ?>" title="Estado general del ambiente">
                <span class="ea-pulse"></span>
                <span><?= esc((string) ($view['generalLabel'] ?? '')) ?></span>
            </span>

            <span class="ea-chip ea-chip-update" title="Última actualización">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M20 12a8 8 0 11-2.5-5.8"/><path d="M20 4v4h-4"/></svg>
                <span class="ea-mono"><?= esc((string) ($view['lastUpdate'] ?? 'Hoy')) ?></span>
            </span>

            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>
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

            <?php
            // Si el usuario solo tiene el dispositivo simulado auto-creado, lo
            // invitamos a vincular su Eden Air real. No bloquea: es informativo.
            $deviceRaw       = (array) ($panel['device_raw'] ?? []);
            $totalDispositivos = count((array) ($panel['devices_list'] ?? []));
            $esSoloSimulado  = $totalDispositivos === 1
                && (int) ($deviceRaw['is_simulated'] ?? 0) === 1
                && empty($deviceRaw['activation_code']);
            ?>
            <?php if ($esSoloSimulado): ?>
                <section class="ea-claim-banner" aria-label="Vinculá tu dispositivo Eden Air">
                    <div class="ea-claim-banner-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/><path d="M9 8.5l2.3 2.3L15 7"/></svg>
                    </div>
                    <div class="ea-claim-banner-text">
                        <strong>¿Ya tenés tu Eden Air?</strong>
                        <span>Vinculá tu dispositivo real con su código de activación y administrá uno o varios desde tu cuenta.</span>
                    </div>
                    <a href="<?= site_url('panel/dispositivos/agregar') ?>" class="ea-button ea-button-primary ea-button-buy ea-button-sm">Vincular dispositivo</a>
                </section>
            <?php endif; ?>

            <!-- ============== HERO · Resumen del ambiente ============== -->
            <section class="ea-hero ea-reveal tone-<?= esc($tone) ?>" id="dashboard">
                <div class="ea-hero-glow" aria-hidden="true"></div>

                <div class="ea-hero-main">
                    <div class="ea-hero-top">
                        <span class="ea-badge tone-<?= esc($tone) ?> ea-hero-status"><span class="ea-dot"></span><?= esc((string) ($view['generalLabel'] ?? '')) ?></span>
                        <span class="ea-hero-mode ea-mode-tag <?= $modoManual ? 'is-manual' : 'is-auto' ?>">
                            <span class="ea-mode-tag-dot" aria-hidden="true"></span>
                            <?= $modoManual ? 'Modo manual' : 'Modo automático' ?>
                        </span>
                    </div>

                    <p class="ea-hero-eyebrow">Hola, <?= esc((string) ($view['userName'] ?? 'bienvenido')) ?></p>
                    <h2 class="ea-serif ea-hero-title"><?= esc((string) ($view['generalTitle'] ?? '')) ?></h2>
                    <p class="ea-hero-diag"><?= esc((string) ($view['generalDetail'] ?? '')) ?></p>

                    <div class="ea-hero-foot">
                        <span class="ea-hero-foot-item">
                            <span class="ea-hero-foot-label">Actualizado</span>
                            <span class="ea-mono ea-hero-foot-val"><?= esc((string) ($view['lastUpdate'] ?? 'Hoy')) ?></span>
                        </span>
                        <span class="ea-hero-foot-item">
                            <span class="ea-hero-foot-label">Actuadores activos</span>
                            <span class="ea-hero-foot-val"><?= esc((string) (int) ($view['activeActuators'] ?? 0)) ?></span>
                        </span>
                        <span class="ea-hero-foot-item">
                            <span class="ea-hero-foot-label">Reglas activas</span>
                            <span class="ea-hero-foot-val"><?= esc((string) (int) ($view['automationActiveCount'] ?? 0)) ?><small>/<?= esc((string) count($view['automationRules'] ?? [])) ?></small></span>
                        </span>
                        <span class="ea-hero-foot-item ea-hero-foot-conn">
                            <span class="ea-conn-dot"></span>
                            <span class="ea-hero-foot-label">En línea</span>
                            <span class="ea-mono ea-hero-foot-val"><?= esc((string) ($view['deviceUid'] ?? '')) ?></span>
                        </span>
                    </div>
                </div>

                <div class="ea-hero-side">
                    <?php
                    $heroMetrics = [
                        ['Temperatura', number_format((float) ($view['currentTemp'] ?? 0), 1), '°C',  (string) ($view['sensorCards'][0]['estado'] ?? 'success')],
                        ['Humedad',     (string) (int) ($view['currentHumidity'] ?? 0),         '%',   (string) ($view['sensorCards'][1]['estado'] ?? 'success')],
                        ['CO₂',         (string) (int) ($view['currentCo2'] ?? 0),              'ppm', (string) ($view['sensorCards'][3]['estado'] ?? 'success')],
                        ['Calidad',     (string) ($view['airStateLabel'] ?? '—'),               'AQI ' . (int) ($view['currentAir'] ?? 0), (string) ($view['airTone'] ?? 'success')],
                    ];
                    ?>
                    <div class="ea-hero-metrics">
                        <?php foreach ($heroMetrics as [$mLabel, $mVal, $mUnit, $mTone]): ?>
                            <div class="ea-hero-metric tone-<?= esc($mTone) ?>">
                                <span class="ea-hero-metric-label"><span class="ea-dot"></span><?= esc($mLabel) ?></span>
                                <span class="ea-hero-metric-val"><?= esc($mVal) ?> <small><?= esc($mUnit) ?></small></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ea-hero-trend">
                        <span class="ea-mono ea-hero-trend-label">Tendencia · 24 h</span>
                        <svg viewBox="0 0 220 60" class="ea-hero-spark" preserveAspectRatio="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="ea-spark-grad" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0" stop-color="var(--eden-500)" stop-opacity=".30"/>
                                    <stop offset="1" stop-color="var(--eden-500)" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?> L 220 60 L 0 60 Z" fill="url(#ea-spark-grad)"/>
                            <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?>" fill="none" stroke="var(--eden-500)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </section>

            <!-- ============== Sensores ============== -->
            <div class="ea-sec" id="sensores">
                <h2>Sensores</h2>
                <span class="ea-sec-right">4 activos · lecturas en tiempo real</span>
            </div>

            <div class="ea-sensor-grid">
                <?php foreach (($view['sensorCards'] ?? []) as $sensor):
                    $sStatus = (string) ($sensor['estado'] ?? 'success');
                    [$sLabel, $sTone] = $statusMeta($sStatus);
                    $bandLow  = (float) ($sensor['bandLow'] ?? 0);
                    $bandHigh = (float) ($sensor['bandHigh'] ?? 100);
                    $bandW    = max(0.0, $bandHigh - $bandLow);
                    $pin      = max(0.0, min(100.0, (float) ($sensor['pct'] ?? 0)));
                ?>
                    <article class="ea-sensor-card accent-<?= esc((string) ($sensor['accent'] ?? 'eden')) ?>">
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
                            <span class="ea-badge tone-<?= esc($sTone) ?> ea-sensor-badge"><span class="ea-dot"></span><?= esc($sLabel) ?></span>
                        </div>

                        <div class="ea-sensor-value">
                            <span class="ea-sensor-num"><?= esc((string) ($sensor['valor'] ?? '')) ?></span>
                            <span class="ea-mono ea-sensor-unit"><?= esc((string) ($sensor['unidad'] ?? '')) ?></span>
                        </div>

                        <div class="ea-sensor-foot">
                            <div class="ea-gauge" role="img" aria-label="Lectura comparada con el rango ideal">
                                <span class="ea-gauge-band" style="left: <?= esc((string) round($bandLow, 1)) ?>%; width: <?= esc((string) round($bandW, 1)) ?>%;"></span>
                                <span class="ea-gauge-pin tone-<?= esc($sTone) ?>" style="left: <?= esc((string) round($pin, 1)) ?>%;"></span>
                            </div>
                            <div class="ea-sensor-hint">
                                <span><?= esc((string) ($sensor['detalle'] ?? '')) ?></span>
                                <span class="ea-gauge-legend"><i></i>zona ideal</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- ============== Estado del sistema ============== -->
            <div class="ea-sec" id="configuracion">
                <h2>Estado del sistema</h2>
                <span class="ea-sec-right">ESP32 · <span class="ea-mono">integración preparada</span></span>
            </div>

            <article class="ea-card ea-system-card">
                <div class="ea-system-stats">
                    <div class="ea-system-stat">
                        <span class="ea-system-ico is-ok" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4 4 10-10"/></svg></span>
                        <div><strong>Sistema en línea</strong><small>Servicios operativos</small></div>
                    </div>
                    <div class="ea-system-stat">
                        <span class="ea-system-ico is-ok" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="3"/><path d="M9 9h6v6H9z"/><path d="M5 9H2M5 15H2M22 9h-3M22 15h-3M9 5V2M15 5V2M9 22v-3M15 22v-3"/></svg></span>
                        <div><strong>ESP32 preparada</strong><small>Último envío <?= esc((string) ($view['deviceLastSeen'] ?? '—')) ?></small></div>
                    </div>
                    <div class="ea-system-stat">
                        <span class="ea-system-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg></span>
                        <div><strong><?= esc((string) (int) ($view['activeActuators'] ?? 0)) ?> actuadores activos</strong><small><?= esc((string) (count($view['actuators'] ?? []) - (int) ($view['activeActuators'] ?? 0))) ?> en espera</small></div>
                    </div>
                    <div class="ea-system-stat">
                        <span class="ea-system-ico tone-<?= esc($tone) ?>" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg></span>
                        <div><strong><?= esc((string) (int) ($view['criticalCount'] ?? 0)) ?> alertas</strong><small><?= ((int) ($view['criticalCount'] ?? 0)) > 0 ? 'Requieren revisión' : 'Todo bajo control' ?></small></div>
                    </div>
                </div>

                <div class="ea-mode-panel <?= $modoManual ? 'is-manual' : 'is-auto' ?>">
                    <div class="ea-mode-copy">
                        <span class="ea-mode-eyebrow">Modo de operación</span>
                        <strong><?= $modoManual ? 'Manual' : 'Automático' ?></strong>
                        <small><?= $modoManual ? 'Vos decidís: usá los interruptores para encender o apagar cada actuador.' : 'El sistema enciende los actuadores cuando una variable sale del rango.' ?></small>
                    </div>
                    <form action="<?= site_url('panel/modo') ?>" method="POST" data-preserve-scroll class="ea-mode-switch" role="group" aria-label="Modo de operación">
                        <?= csrf_field() ?>
                        <button type="submit" name="mode" value="automatic" class="ea-mode-opt <?= $modoManual ? '' : 'is-active' ?>" <?= $modoManual ? '' : 'aria-current="true"' ?>>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><path d="M12 3v3"/><path d="M5.6 5.6l2.1 2.1"/><path d="M3 12h3"/><path d="M5.6 18.4l2.1-2.1"/><circle cx="12" cy="12" r="3.2"/></svg>
                            Automático
                        </button>
                        <button type="submit" name="mode" value="manual" class="ea-mode-opt <?= $modoManual ? 'is-active' : '' ?>" <?= $modoManual ? 'aria-current="true"' : '' ?>>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><path d="M9 11V6a2 2 0 114 0v7"/><path d="M13 8a2 2 0 114 0v6"/><path d="M17 10a2 2 0 114 0v6a5 5 0 01-5 5h-3a5 5 0 01-5-5l-3-5a2 2 0 113-2l2 3"/></svg>
                            Manual
                        </button>
                    </form>
                </div>
            </article>

            <div class="ea-ops-grid">
                <!-- Actuadores -->
                <article class="ea-card ea-actuators-card">
                    <div class="ea-card-head">
                        <h3>Actuadores</h3>
                        <span class="ea-mono ea-card-meta"><?= esc((string) (int) ($view['activeActuators'] ?? 0)) ?> ON · <?= esc((string) (count($view['actuators'] ?? []) - (int) ($view['activeActuators'] ?? 0))) ?> OFF</span>
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
                                    <?php if ($modoManual): ?>
                                        <form action="<?= site_url('panel/actuador') ?>" method="POST" data-preserve-scroll class="ea-actuator-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="actuator" value="<?= esc($key) ?>">
                                            <input type="hidden" name="value" value="<?= $on ? 'off' : 'on' ?>">
                                            <button type="submit" class="ea-actuator-toggle <?= $on ? 'is-on' : '' ?>" aria-label="<?= $on ? 'Apagar' : 'Encender' ?> <?= esc((string) ($act['titulo'] ?? '')) ?>">
                                                <span class="ea-actuator-toggle-thumb"></span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="ea-badge <?= $on ? 'tone-success' : 'tone-neutral' ?> ea-actuator-badge"><span class="ea-dot"></span><?= $on ? 'ON' : 'OFF' ?></span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p class="ea-actuators-note">
                        <?= $modoManual
                            ? 'Modo manual: usá los interruptores para forzar cada actuador.'
                            : 'Modo automático: el sistema enciende cada actuador según las reglas. Pasá a manual para forzarlos.' ?>
                    </p>
                </article>

                <!-- Automatizaciones -->
                <article class="ea-card ea-rules-card" id="automatizaciones">
                    <div class="ea-card-head">
                        <h3>Automatizaciones</h3>
                        <span class="ea-mono ea-card-meta"><?= esc((string) (int) ($view['automationActiveCount'] ?? 0)) ?>/<?= esc((string) count($view['automationRules'] ?? [])) ?> activas</span>
                    </div>

                    <ul class="ea-rules-list">
                        <?php foreach (($view['automationRules'] ?? []) as $rule):
                            $active  = ! empty($rule['active']);
                            $pending = ! empty($rule['pending']);
                            $isAlert = ($rule['icon'] ?? '') === 'alert';

                            if ($active && $isAlert) {
                                $rState = 'danger'; $rLabel = 'Requiere atención';
                            } elseif ($active) {
                                $rState = 'success'; $rLabel = 'Activa';
                            } elseif ($pending) {
                                $rState = 'info'; $rLabel = 'Preparada';
                            } else {
                                $rState = 'neutral'; $rLabel = 'En espera';
                            }
                        ?>
                            <li class="ea-rule">
                                <span class="ea-rule-state tone-<?= esc($rState) ?>" aria-hidden="true"></span>
                                <div class="ea-rule-body">
                                    <p class="ea-rule-text">
                                        Cuando <strong><?= esc((string) ($rule['when'] ?? '')) ?></strong>,
                                        <span><?= esc(mb_strtolower((string) ($rule['then'] ?? ''))) ?>.</span>
                                    </p>
                                </div>
                                <span class="ea-badge tone-<?= esc($rState) ?> ea-rule-badge"><span class="ea-dot"></span><?= esc($rLabel) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </div>

            <!-- ============== Lecturas ============== -->
            <div class="ea-sec" id="historial">
                <h2>Lecturas</h2>
                <span class="ea-sec-right"><span class="ea-mono"><?= esc((string) count($view['historyRows'] ?? [])) ?> registros recientes</span></span>
            </div>

            <?php
            $historyRows = $view['historyRows'] ?? [];
            $maxCo2Prof  = isset($panel['space']['perfil']['max_co2']) ? (int) $panel['space']['perfil']['max_co2'] : 900;
            $visibleRows = 3;
            ?>
            <article class="ea-card ea-readings-card" data-readings>
                <div class="ea-card-head">
                    <h3>Últimas lecturas</h3>
                    <span class="ea-badge tone-<?= ! empty($view['historyIsSample']) ? 'warning' : 'success' ?> ea-card-meta-badge"><span class="ea-dot"></span><?= ! empty($view['historyIsSample']) ? 'Datos de ejemplo' : 'Datos reales' ?></span>
                    <span class="ea-kbtn ea-card-head-action" aria-disabled="true" title="Disponible próximamente">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5"/><path d="M5 20h14"/></svg>
                        Exportar
                    </span>
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
                            <?php if ($historyRows === []): ?>
                                <tr><td colspan="7" class="ea-table-empty">
                                    <div class="ea-empty">
                                        <strong>Sin lecturas registradas todavía.</strong>
                                        <p>Cuando el dispositivo principal envíe datos, las lecturas aparecerán acá.</p>
                                    </div>
                                </td></tr>
                            <?php else:
                                foreach ($historyRows as $i => $row):
                                    $rowCo2Raw = (string) ($row['co2'] ?? '');
                                    preg_match('/-?\d+(?:[.,]\d+)?/', $rowCo2Raw, $m);
                                    $rowCo2   = isset($m[0]) ? (float) str_replace(',', '.', $m[0]) : 0.0;
                                    $rowState = $rowCo2 > $maxCo2Prof + 200 ? 'danger' : ($rowCo2 > $maxCo2Prof ? 'warning' : 'success');
                                    [$rowLabel] = $statusMeta($rowState);
                            ?>
                                <tr class="<?= $i >= $visibleRows ? 'is-extra' : '' ?>">
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
                    <?php if (count($historyRows) > $visibleRows): ?>
                        <button type="button" class="ea-kbtn ea-kbtn-primary ea-readings-more" data-readings-toggle data-less="Ver menos" data-more="Ver <?= esc((string) (count($historyRows) - $visibleRows)) ?> más" aria-expanded="false" aria-controls="historial">
                            <svg class="ea-readings-more-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                            <span data-readings-label>Ver <?= esc((string) (count($historyRows) - $visibleRows)) ?> más</span>
                        </button>
                    <?php else: ?>
                        <span class="ea-mono">Mostrando <?= esc((string) count($historyRows)) ?> registros</span>
                    <?php endif; ?>
                    <span class="ea-readings-note <?= ! empty($view['latestIsSample']) ? 'is-sample' : '' ?>">
                        <?= ! empty($view['latestIsSample']) ? 'Datos de ejemplo' : 'Última: ' . esc((string) ($view['latest']['fecha'] ?? '')) ?>
                    </span>
                </div>
            </article>

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
