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
/* =============================================================================
   VISTA: panel.php — DASHBOARD del usuario (ruta /panel, requiere sesión)
   CSS:  public/CSS/dashboard.css (+ eden-brand.css global)
   JS:   dashboard.js (loader, sidebar, ver más) · dashboard-gsap.js (scroll
         suave + reveals) · tema.js · ea-scrollbar.js

   ESTA VISTA NO CALCULA NADA: valores, tonos, textos y porcentajes vienen
   cocinados de App\Services\PanelService::obtenerVistaPanel() en $panel['view'].

   ESTRUCTURA (cada dato aparece UNA sola vez):
     loader → sidebar → header (navegación) → flashes
     1. HERO      · el diagnóstico: cómo está el ambiente ahora
     2. SENSORES  · los 4 valores medidos, con su rango ideal
     3. CONTROL   · actuadores + modo de operación + automatizaciones
     4. LECTURAS  · historial reciente
   ============================================================================= */
$panel  = (isset($panel) && is_array($panel)) ? $panel : [];
$view   = (isset($panel['view']) && is_array($panel['view'])) ? $panel['view'] : [];
$errors = session()->getFlashdata('errors') ?? [];

$tone       = (string) ($view['tono'] ?? 'success');
$modoManual = ! empty($view['modoManual']);

/** Etiqueta corta para un tono de sensor (el color ya lo pone la clase). */
$tonoLabel = static fn (string $t): string => match ($t) {
    'danger'  => 'Crítico',
    'warning' => 'Atención',
    'neutral' => 'Sin datos',
    default   => 'Normal',
};
?>

<!-- ===== Fallback SIN JavaScript =====
     Este <style> vive acá adentro a propósito: solo aplica si el navegador
     no ejecuta JS (<noscript>). Oculta el loader y muestra el dashboard
     directo. NO moverlo a dashboard.css: perdería la condición noscript. -->
<noscript>
    <style>
        .dashboard-loading .dashboard-loader { display: none; }
        .dashboard-loading .ea-dashboard { opacity: 1; transform: none; }
    </style>
</noscript>

<!-- ===== ESTRUCTURA: loader de entrada =====
     Pantalla de carga con el logo animado y 3 pasos. -->
<!-- ===== ANIMACIÓN (CSS + JS): los anillos/halo se animan por CSS
     (dashboard.css) y dashboard.js la oculta cuando la página está lista
     (saca la clase dashboard-loading del body). -->
<div class="ea-loader dashboard-loader" data-dashboard-loader role="status" aria-live="polite" aria-label="Preparando tu ambiente inteligente">
    <div class="ea-loader-pattern" aria-hidden="true"></div>
    <div class="ea-loader-grain" aria-hidden="true"></div>

    <div class="ea-loader-inner">
        <span class="ea-loader-eyebrow">
            <span class="ea-loader-dot" aria-hidden="true"></span>
            EdenAir · Núcleo ambiental
        </span>

        <div class="ea-loader-orbit" aria-hidden="true">
            <span class="ea-loader-halo"></span>
            <span class="ea-loader-ring ea-loader-ring--a"></span>
            <span class="ea-loader-ring ea-loader-ring--b"></span>
            <span class="ea-loader-ring ea-loader-ring--c"></span>
            <span class="ea-loader-logo ea-loader-logo--e">
                <svg viewBox="2 16 116 70" aria-hidden="true">
                    <defs>
                        <linearGradient id="ld-e" x1="0.08" y1="0.1" x2="0.92" y2="0.92">
                            <stop offset="0" stop-color="#F6F4EC"/>
                            <stop offset="0.5" stop-color="#BCE9DC"/>
                            <stop offset="1" stop-color="#5BE5B6"/>
                        </linearGradient>
                    </defs>
                    <path d="M15 39 C21 30 30 33 36 41 C45 51 60 51 80 52 C86 36 70 24 52 24 C34 24 22 38 24 52 C26 68 42 78 60 76 C76 74 90 70 104 60"
                          fill="none" stroke="url(#ld-e)" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>

        <div class="ea-loader-text">
            <strong class="ea-loader-name">Eden<em>Air</em></strong>
            <p class="ea-loader-msg">Preparando tu ambiente inteligente</p>
        </div>

        <div class="ea-loader-progress" aria-hidden="true"><span></span></div>
    </div>
</div>

<div class="ea-dashboard" data-dashboard-app data-url-datos="<?= site_url('panel/datos') ?>">

    <!-- =========================== SIDEBAR (compartido) =========================== -->
    <?= view('partials/dashboard_sidebar', [
        'active'       => 'inicio',
        'devicesCount' => count($panel['devices_list'] ?? []),
    ]) ?>

    <!-- =========================== HEADER =====================================
         Solo navegación e identidad: qué ambiente estoy viendo, con qué
         dispositivo, y quién soy. El estado del ambiente NO va acá: es el
         titular del hero y repetirlo dos veces cansa la lectura. -->
    <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>

            <div class="ea-header-titles">
                <h1><?= esc((string) ($view['spaceName'] ?? 'Mi ambiente')) ?></h1>
                <p><?= esc((string) ($view['spaceLabel'] ?? '')) ?></p>
            </div>

            <?php
            $devicesList = (array) ($panel['devices_list'] ?? []);
            $activeDeviceName = (string) ($view['deviceName'] ?? '');
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

            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>
            </div>

            <div class="ea-header-user" title="<?= esc((string) ($view['userName'] ?? '')) ?>">
                <span class="ea-header-avatar"><?= esc((string) ($view['userInitial'] ?? 'U')) ?></span>
                <span class="ea-header-name">
                    <?= esc((string) ($view['userName'] ?? '')) ?>
                    <small>Cuenta Eden Air</small>
                </span>
            </div>
    </header>

    <!-- ===== ANIMACIÓN (GSAP/ScrollSmoother): scroll suave =====
         dashboard-gsap.js envuelve el contenido en #smooth-wrapper/#smooth-content
         para el scroll suave. El header y el sidebar quedan FUERA (son fixed). -->
    <div id="smooth-wrapper">
        <div id="smooth-content">
            <main class="ea-main">

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

            <!-- ===== 1. HERO · el diagnóstico ===================================
                 Una sola frase que responde "¿cómo está mi ambiente?", más la
                 tendencia de temperatura. Los valores medidos NO se repiten
                 acá: viven en las tarjetas de sensores, justo abajo. -->
            <section class="ea-hero ea-reveal tone-<?= esc($tone) ?>" id="dashboard" data-vivo-tono>
                <div class="ea-hero-glow" aria-hidden="true"></div>

                <div class="ea-hero-main">
                    <div class="ea-hero-top">
                        <span class="ea-badge tone-<?= esc($tone) ?> ea-hero-status" data-vivo-tono><span class="ea-dot"></span><span data-vivo="estadoLabel"><?= esc((string) ($view['estadoLabel'] ?? '')) ?></span></span>
                        <span class="ea-hero-mode ea-mode-tag <?= $modoManual ? 'is-manual' : 'is-auto' ?>">
                            <span class="ea-mode-tag-dot" aria-hidden="true"></span>
                            Modo <?= $modoManual ? 'manual' : 'automático' ?>
                        </span>
                    </div>

                    <p class="ea-hero-eyebrow">Hola, <?= esc((string) ($view['userName'] ?? 'bienvenido')) ?></p>
                    <h2 class="ea-serif ea-hero-title" data-vivo="estadoTitulo"><?= esc((string) ($view['estadoTitulo'] ?? '')) ?></h2>
                    <p class="ea-hero-diag" data-vivo="estadoDetalle"><?= esc((string) ($view['estadoDetalle'] ?? '')) ?></p>

                    <div class="ea-hero-foot">
                        <span class="ea-hero-foot-item">
                            <span class="ea-hero-foot-label">Última lectura</span>
                            <span class="ea-mono ea-hero-foot-val" data-vivo="ultimaLectura"><?= esc((string) ($view['ultimaLectura'] ?? '—')) ?></span>
                        </span>
                        <span class="ea-hero-foot-item">
                            <span class="ea-hero-foot-label">Dispositivo</span>
                            <span class="ea-hero-foot-val"><?= esc((string) ($view['deviceName'] ?? '—')) ?></span>
                        </span>
                        <span class="ea-hero-foot-item ea-hero-foot-conn">
                            <span class="ea-conn-dot"></span>
                            <span class="ea-hero-foot-label">Identificador</span>
                            <span class="ea-mono ea-hero-foot-val"><?= esc((string) ($view['deviceUid'] ?? '')) ?></span>
                        </span>
                    </div>
                </div>

                <div class="ea-hero-side">
                    <div class="ea-hero-trend">
                        <span class="ea-mono ea-hero-trend-label">Temperatura · últimas <?= esc((string) count($view['historial'] ?? [])) ?> lecturas</span>
                        <svg viewBox="0 0 220 60" class="ea-hero-spark" preserveAspectRatio="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="ea-spark-grad" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0" stop-color="var(--eden-500)" stop-opacity=".30"/>
                                    <stop offset="1" stop-color="var(--eden-500)" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?> L 220 60 L 0 60 Z" fill="url(#ea-spark-grad)" data-vivo-spark="relleno"/>
                            <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?>" fill="none" stroke="var(--eden-500)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-vivo-spark="linea"/>
                        </svg>
                    </div>
                </div>
            </section>

            <!-- ===== 2. SENSORES ================================================
                 El único lugar donde aparecen los valores medidos. La banda
                 verde del medidor es el rango ideal del ambiente; el pin, la
                 lectura actual. -->
            <div class="ea-sec" id="sensores">
                <h2>Sensores</h2>
                <span class="ea-sec-right">Comparados con el rango de <?= esc((string) ($view['spaceName'] ?? 'tu ambiente')) ?></span>
            </div>

            <div class="ea-sensor-grid">
                <?php foreach (($view['sensores'] ?? []) as $sensor):
                    $sTono    = (string) ($sensor['tono'] ?? 'success');
                    $bandLow  = (float) ($sensor['bandLow'] ?? 0);
                    $bandHigh = (float) ($sensor['bandHigh'] ?? 100);
                    $bandW    = max(0.0, $bandHigh - $bandLow);
                    $pin      = max(0.0, min(100.0, (float) ($sensor['pct'] ?? 0)));
                ?>
                    <article class="ea-sensor-card accent-<?= esc((string) ($sensor['accent'] ?? 'eden')) ?>" data-vivo-sensor="<?= esc((string) ($sensor['icono'] ?? ''), 'attr') ?>">
                        <div class="ea-sensor-head">
                            <span class="ea-sensor-icon" aria-hidden="true">
                                <?php switch ($sensor['icono'] ?? 'temp'):
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
                            <span class="ea-badge tone-<?= esc($sTono) ?> ea-sensor-badge" data-vivo-tono><span class="ea-dot"></span><span data-vivo="badge"><?= esc($tonoLabel($sTono)) ?></span></span>
                        </div>

                        <div class="ea-sensor-value">
                            <span class="ea-sensor-num" data-vivo="valor"><?= esc((string) ($sensor['valor'] ?? '--')) ?></span>
                            <span class="ea-mono ea-sensor-unit"><?= esc((string) ($sensor['unidad'] ?? '')) ?></span>
                        </div>

                        <div class="ea-sensor-foot">
                            <div class="ea-gauge" role="img" aria-label="Lectura comparada con el rango ideal">
                                <span class="ea-gauge-band" data-vivo="band" style="left: <?= esc((string) round($bandLow, 1)) ?>%; width: <?= esc((string) round($bandW, 1)) ?>%;"></span>
                                <span class="ea-gauge-pin tone-<?= esc($sTono) ?>" data-vivo="pin" data-vivo-tono style="left: <?= esc((string) round($pin, 1)) ?>%;"></span>
                            </div>
                            <div class="ea-sensor-hint">
                                <span data-vivo="rango"><?= esc((string) ($sensor['rango'] ?? '')) ?></span>
                                <span class="ea-gauge-legend"><i></i>zona ideal</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- ===== 3. CONTROL =================================================
                 Actuadores (con el selector de modo en su cabecera, porque es
                 lo que decide si podés tocarlos) + las reglas que los mueven
                 solos. Antes esto estaba repartido en tres tarjetas. -->
            <div class="ea-sec" id="configuracion">
                <h2>Control</h2>
                <span class="ea-sec-right">Qué está encendido y con qué criterio</span>
            </div>

            <div class="ea-ops-grid">
                <article class="ea-card ea-actuators-card">
                    <div class="ea-card-head">
                        <h3>Actuadores</h3>
                        <span class="ea-mono ea-card-meta"><?= esc((string) (int) ($view['actuadoresActivos'] ?? 0)) ?> de <?= esc((string) count($view['actuadores'] ?? [])) ?> encendidos</span>
                    </div>

                    <!-- Selector de modo: en automático manda el sistema, en
                         manual mandás vos (y recién ahí aparecen los switches). -->
                    <form action="<?= site_url('panel/modo') ?>" method="POST" data-preserve-scroll class="ea-mode-switch <?= $modoManual ? 'is-manual' : '' ?>" role="group" aria-label="Modo de operación">
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

                    <ul class="ea-actuators-list">
                        <?php foreach (($view['actuadores'] ?? []) as $act):
                            $on  = ! empty($act['encendido']);
                            $key = (string) ($act['clave'] ?? 'fan');
                        ?>
                            <li class="ea-actuator-row">
                                <span class="ea-actuator-icon <?= $on ? 'is-on' : '' ?>" aria-hidden="true">
                                    <?php switch ($key):
                                        case 'fan': ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1.6"/><path d="M12 10.4c0-3 1.2-5.4 3.5-5.4S18 7 16.5 9.4c-1 1.5-3 2-4.5 1"/><path d="M13.6 12c3 0 5.4 1.2 5.4 3.5S17 18 14.6 16.5c-1.5-1-2-3-1-4.5"/><path d="M12 13.6c0 3-1.2 5.4-3.5 5.4S6 17 7.5 14.6c1-1.5 3-2 4.5-1"/><path d="M10.4 12c-3 0-5.4-1.2-5.4-3.5S7 6 9.4 7.5c1.5 1 2 3 1 4.5"/></svg>
                                    <?php break; case 'aromatizer': ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="9" width="8" height="12" rx="1.6"/><path d="M10 9V6h4v3"/><path d="M18 6h2M18 9h3M18 12h2"/></svg>
                                    <?php break; default: ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 21h4"/><path d="M7 13a5 5 0 1110 0c0 2-1 3-2 4H9c-1-1-2-2-2-4z"/><path d="M12 5V3"/></svg>
                                    <?php break; endswitch; ?>
                                </span>
                                <div class="ea-actuator-body">
                                    <strong class="ea-actuator-name"><?= esc((string) ($act['titulo'] ?? 'Actuador')) ?></strong>
                                    <span class="ea-actuator-reason"><?= esc((string) ($act['detalle'] ?? '')) ?></span>
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
                </article>

                <article class="ea-card ea-rules-card" id="automatizaciones">
                    <div class="ea-card-head">
                        <h3>Automatizaciones</h3>
                        <span class="ea-mono ea-card-meta"><?= esc((string) (int) ($view['reglasActivas'] ?? 0)) ?> de <?= esc((string) count($view['reglas'] ?? [])) ?> aplicándose</span>
                    </div>

                    <ul class="ea-rules-list">
                        <?php foreach (($view['reglas'] ?? []) as $regla):
                            $activa = ! empty($regla['activa']);
                        ?>
                            <li class="ea-rule">
                                <span class="ea-rule-state tone-<?= $activa ? 'success' : 'neutral' ?>" aria-hidden="true"></span>
                                <div class="ea-rule-body">
                                    <p class="ea-rule-text">
                                        Cuando <strong><?= esc((string) ($regla['cuando'] ?? '')) ?></strong>,
                                        <span><?= esc(mb_strtolower((string) ($regla['accion'] ?? ''))) ?>.</span>
                                    </p>
                                </div>
                                <span class="ea-badge tone-<?= $activa ? 'success' : 'neutral' ?> ea-rule-badge"><span class="ea-dot"></span><?= $activa ? 'Aplicándose' : 'En espera' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p class="ea-actuators-note">
                        <?= $modoManual
                            ? 'En modo manual las reglas quedan en pausa: los actuadores hacen lo que vos indiques.'
                            : 'En modo automático estas reglas encienden y apagan los actuadores solas.' ?>
                    </p>
                </article>
            </div>

            <!-- ===== 4. LECTURAS ================================================
                 Historial reciente. Se muestran 3 filas y el resto se despliega
                 con "Ver más" (dashboard.js). -->
            <div class="ea-sec" id="historial">
                <h2>Lecturas</h2>
                <span class="ea-sec-right"><span class="ea-mono"><?= esc((string) count($view['historial'] ?? [])) ?> registros recientes</span></span>
            </div>

            <?php
            $filas       = (array) ($view['historial'] ?? []);
            $visibleRows = 3;
            ?>
            <article class="ea-card ea-readings-card" data-readings>
                <div class="ea-readings-wrap">
                    <table class="ea-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Origen</th>
                                <th class="ea-num">Temp.</th>
                                <th class="ea-num">Humedad</th>
                                <th>Calidad</th>
                                <th class="ea-num">CO₂</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($filas === []): ?>
                                <tr><td colspan="7" class="ea-table-empty">
                                    <div class="ea-empty">
                                        <strong>Sin lecturas registradas todavía.</strong>
                                        <p>Cuando el dispositivo envíe datos, las lecturas aparecerán acá.</p>
                                    </div>
                                </td></tr>
                            <?php else: foreach ($filas as $i => $fila): ?>
                                <tr class="<?= $i >= $visibleRows ? 'is-extra' : '' ?>">
                                    <td class="ea-mono ea-table-time"><?= esc((string) ($fila['fecha'] ?? '--')) ?></td>
                                    <td>
                                        <span class="ea-table-dev">
                                            <span class="ea-table-dev-dot"></span>
                                            <span class="ea-mono"><?= esc((string) ($fila['origen'] ?? '--')) ?></span>
                                        </span>
                                    </td>
                                    <td class="ea-num ea-mono"><?= esc((string) ($fila['temperatura'] ?? '--')) ?></td>
                                    <td class="ea-num ea-mono"><?= esc((string) ($fila['humedad'] ?? '--')) ?></td>
                                    <td><?= esc((string) ($fila['aire'] ?? '--')) ?></td>
                                    <td class="ea-num ea-mono"><?= esc((string) ($fila['co2'] ?? '--')) ?></td>
                                    <td><span class="ea-badge tone-<?= esc((string) ($fila['tono'] ?? 'success')) ?>"><span class="ea-dot"></span><?= esc($tonoLabel((string) ($fila['tono'] ?? 'success'))) ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($filas) > $visibleRows): ?>
                    <div class="ea-readings-foot">
                        <button type="button" class="ea-kbtn ea-kbtn-primary ea-readings-more" data-readings-toggle data-less="Ver menos" data-more="Ver <?= esc((string) (count($filas) - $visibleRows)) ?> más" aria-expanded="false" aria-controls="historial">
                            <svg class="ea-readings-more-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                            <span data-readings-label>Ver <?= esc((string) (count($filas) - $visibleRows)) ?> más</span>
                        </button>
                    </div>
                <?php endif; ?>
            </article>

        </div>
            </main>
        </div><!-- /#smooth-content -->
    </div><!-- /#smooth-wrapper -->

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<!-- ===== SCRIPTS DE LA PÁGINA =====
     tema.js (claro/oscuro) → dashboard.js (loader, sidebar, ver más,
     preserve-scroll) → GSAP (CDN) → dashboard-gsap.js (scroll suave +
     reveals) → ea-scrollbar.js (barra flotante). -->
<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
<!-- GSAP · ScrollSmoother (scroll suave) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollTrigger.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollSmoother.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="<?= base_url('JS/dashboard-gsap.js') ?>"></script>
<!-- Barra de scroll moderna flotante (misma que la landing) -->
<script src="<?= base_url('JS/ea-scrollbar.js') ?>"></script>
<!-- Refresco en vivo: trae las mediciones nuevas sin recargar la página -->
<script src="<?= base_url('JS/panel-vivo.js') ?>"></script>
</body>
</html>
