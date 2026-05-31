<?php
$conSesion = (bool) session()->get('user_id');

// Acciones del navbar: el CTA de compra tiene más peso visual que el login.
ob_start(); ?>
    <?php if ($conSesion): ?>
        <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary">Entrar al dashboard</a>
        <a href="#comprar" class="ea-button ea-button-primary ea-button-buy" data-ea-buy-cta>Comprar</a>
    <?php else: ?>
        <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary">Iniciar sesión</a>
        <a href="#comprar" class="ea-button ea-button-primary ea-button-buy" data-ea-buy-cta>Comprar Eden&nbsp;Air</a>
    <?php endif; ?>
<?php $eaNavActions = ob_get_clean(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'Eden Air · Monitoreo y ambientación inteligente del aire',
        'extraCss' => ['CSS/inicio.css'],
        'extraHead' =>
              '<meta name="description" content="Eden Air es un sistema inteligente de monitoreo y ambientación automática para interiores: sensa temperatura, humedad, CO₂ y calidad del aire, decide y actúa con un dashboard propio.">'
            . '<meta name="keywords" content="Eden Air, calidad del aire, monitoreo ambiental, IoT, ESP32, automatización ambiental, sustentable, smart home">'
            . '<meta property="og:type" content="website">'
            . '<meta property="og:title" content="Eden Air · Monitoreo y ambientación inteligente del aire">'
            . '<meta property="og:description" content="Sensa, decide y actúa: dispositivo + dashboard propios para crear ambientes saludables, cómodos y sustentables.">'
            . '<meta property="og:image" content="' . base_url('assets/img/branding/mark.svg') . '">'
            . '<meta property="og:locale" content="es_AR">'
            . '<meta name="twitter:card" content="summary_large_image">'
            . '<meta name="twitter:title" content="Eden Air · Monitoreo y ambientación inteligente">'
            . '<meta name="twitter:description" content="Sensa, decide y actúa sobre el ambiente desde un dashboard propio.">',
    ]) ?>
</head>
<body class="ea-body ea-landing">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle'  => 'Monitoreo ambiental',
        'conSesion' => $conSesion,
        'actions'   => $eaNavActions,
        'navLinks'  => [
            ['href' => '#que-es',             'label' => 'Qué es'],
            ['href' => '#beneficios',         'label' => 'Beneficios'],
            ['href' => '#tecnologia-interna', 'label' => 'Tecnología'],
            ['href' => '#funcionamiento',     'label' => 'Funcionamiento'],
            ['href' => '#comprar',            'label' => 'Comprar'],
        ],
    ]) ?>

    <button type="button" class="ea-nav-toggle" data-ea-nav-toggle aria-expanded="false" aria-controls="ea-mobile-nav" aria-label="Abrir menú de navegación">
        <span class="ea-nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>

    <nav id="ea-mobile-nav" class="ea-mobile-nav" data-ea-mobile-nav aria-hidden="true">
        <ul>
            <li><a href="#inicio">Inicio</a></li>
            <li><a href="#que-es">Qué es</a></li>
            <li><a href="#beneficios">Beneficios</a></li>
            <li><a href="#tecnologia-interna">Tecnología interna</a></li>
            <li><a href="#funcionamiento">Funcionamiento</a></li>
            <li><a href="#sensores">Sensores</a></li>
            <li><a href="#automatizacion">Automatización</a></li>
            <li><a href="#comprar">Comprar</a></li>
            <li><a href="<?= site_url('portfolio') ?>">Portfolio →</a></li>
        </ul>
        <div class="ea-mobile-nav-actions">
            <div class="ea-mobile-nav-theme">
                <span class="ea-mobile-nav-theme-label">Tema</span>
                <?= view('partials/theme_toggle', ['unique' => '-mobile']) ?>
            </div>
            <a href="#comprar" class="ea-button ea-button-primary ea-button-block ea-button-buy">Comprar Eden Air</a>
            <?php if ($conSesion): ?>
                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary ea-button-block">Entrar al dashboard</a>
                <a href="<?= site_url('logout') ?>" class="ea-button ea-button-ghost ea-button-block">Cerrar sesión</a>
            <?php else: ?>
                <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary ea-button-block">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <section class="ea-hero" id="inicio">
            <span class="ea-hero-glow" aria-hidden="true"></span>
            <div class="ea-hero-orbits" aria-hidden="true">
                <span class="ea-hero-orbit ea-hero-orbit--a"></span>
                <span class="ea-hero-orbit ea-hero-orbit--b"></span>
                <span class="ea-hero-orbit ea-hero-orbit--c"></span>
            </div>

            <div class="ea-hero-leaves" aria-hidden="true">
                <svg class="ea-hero-leaf ea-hero-leaf--a" viewBox="0 0 64 64">
                    <path d="M 20 46 C 17 32, 25 18, 48 14 C 44 30, 34 42, 20 46 Z" fill="rgba(74,122,85,0.18)" stroke="rgba(74,122,85,0.55)" stroke-width="1.2" stroke-linejoin="round"/>
                    <path d="M 20 46 C 28 38, 38 26, 48 14" fill="none" stroke="rgba(74,122,85,0.75)" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <svg class="ea-hero-leaf ea-hero-leaf--b" viewBox="0 0 64 64">
                    <path d="M 20 46 C 17 32, 25 18, 48 14 C 44 30, 34 42, 20 46 Z" fill="rgba(201,216,112,0.18)" stroke="rgba(201,216,112,0.6)" stroke-width="1.2" stroke-linejoin="round"/>
                </svg>
                <svg class="ea-hero-leaf ea-hero-leaf--c" viewBox="0 0 64 64">
                    <path d="M 20 46 C 17 32, 25 18, 48 14 C 44 30, 34 42, 20 46 Z" fill="rgba(188,210,189,0.22)" stroke="rgba(188,210,189,0.55)" stroke-width="1.2" stroke-linejoin="round"/>
                </svg>
            </div>

            <svg class="ea-hero-pattern" viewBox="0 0 100 120" preserveAspectRatio="none" aria-hidden="true">
                <?php for ($i = 0; $i < 22; $i++):
                    $y = $i * 5 + 4;
                    $amp = 1 + ($i % 5) * 0.5; ?>
                    <path d="M 0 <?= $y ?> C 25 <?= $y - $amp ?>, 50 <?= $y + $amp ?>, 75 <?= $y - $amp ?> C 90 <?= $y + $amp * 0.5 ?>, 100 <?= $y ?>, 100 <?= $y ?>"
                          fill="none" stroke="rgba(20,32,26,0.08)" stroke-width="0.4" />
                <?php endfor; ?>
            </svg>

            <div class="ea-page ea-hero-grid">
                <div class="ea-hero-intro">
                    <span class="ea-hero-tagline">
                        <span class="ea-hero-tagline-dot" aria-hidden="true"></span>
                        Environmental Control System
                    </span>
                <h1 class="ea-hero-title">Respirá mejor,<br><em>viví más cómodo.</em></h1>
                    <p class="ea-hero-lede">
                        EdenAir sensa temperatura, humedad, CO₂ y calidad del aire.
                        Decide. Activa los módulos. Devuelve un ambiente equilibrado.
                    </p>
                    <div class="ea-hero-actions">
                        <?php if ($conSesion): ?>
                            <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary">Abrir panel</a>
                            <a href="#comprar" class="ea-button ea-button-secondary">Comprar dispositivo</a>
                        <?php else: ?>
                            <a href="#comprar" class="ea-button ea-button-primary ea-button-buy">Comprar Eden&nbsp;Air</a>
                            <a href="#que-es" class="ea-button ea-button-secondary">Conocer más</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ea-hero-core ea-hero-card-anim"
                     data-eden-core
                     data-eden-core-src="<?= base_url('assets/models/eden-air-core.glb') ?>"
                     data-eden-core-endpoint="<?= site_url('api/sensores') ?>">
                    <div class="ea-hero-core-stage" data-eden-core-stage>
                        <span class="ea-hero-core-glow" aria-hidden="true"></span>
                        <span class="ea-hero-core-shadow" aria-hidden="true"></span>
                        <canvas class="ea-hero-core-canvas" data-eden-core-canvas aria-hidden="true"></canvas>

                        <div class="ea-hero-core-fallback" data-eden-core-fallback aria-hidden="true">
                            <span class="ea-hero-core-fallback-orb" aria-hidden="true"></span>
                            <p class="ea-hero-core-fallback-msg">Cargando núcleo 3D…</p>
                        </div>
                    </div>

                    <div class="ea-hero-core-cards" data-eden-core-cards aria-hidden="true">
                        <div class="ea-hud-panel" data-position="tl" data-metric="temperatura">
                            <div class="ea-hud-data">
                                <span class="ea-hud-key">Temperatura</span>
                                <span class="ea-hud-val"><b data-value>22</b><span class="ea-hud-unit" data-unit>°C</span></span>
                            </div>
                            <span class="ea-hud-link" aria-hidden="true"></span>
                        </div>
                        <div class="ea-hud-panel" data-position="tr" data-metric="humedad">
                            <div class="ea-hud-data">
                                <span class="ea-hud-key">Humedad</span>
                                <span class="ea-hud-val"><b data-value>48</b><span class="ea-hud-unit" data-unit>%</span></span>
                            </div>
                            <span class="ea-hud-link" aria-hidden="true"></span>
                        </div>
                        <div class="ea-hud-panel" data-position="ml" data-metric="co2">
                            <div class="ea-hud-data">
                                <span class="ea-hud-key">CO₂</span>
                                <span class="ea-hud-val"><b data-value data-text>Normal</b></span>
                            </div>
                            <span class="ea-hud-link" aria-hidden="true"></span>
                        </div>
                        <div class="ea-hud-panel" data-position="mr" data-metric="calidad_aire">
                            <div class="ea-hud-data">
                                <span class="ea-hud-key">Calidad de aire</span>
                                <span class="ea-hud-val"><b data-value data-text>Excelente</b></span>
                            </div>
                            <span class="ea-hud-link" aria-hidden="true"></span>
                        </div>
                        <div class="ea-hud-panel" data-position="bl" data-metric="ventilador">
                            <div class="ea-hud-data">
                                <span class="ea-hud-key">Ventilador</span>
                                <span class="ea-hud-val"><b data-value data-text>Activo</b></span>
                            </div>
                            <span class="ea-hud-link" aria-hidden="true"></span>
                        </div>
                        <div class="ea-hud-panel" data-position="br" data-metric="humidificacion">
                            <div class="ea-hud-data">
                                <span class="ea-hud-key">Humidificación</span>
                                <span class="ea-hud-val"><b data-value data-text>Óptima</b></span>
                            </div>
                            <span class="ea-hud-link" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ea-bridge" aria-hidden="true" data-reveal>
            <span class="ea-bridge-grad" aria-hidden="true"></span>
            <span class="ea-bridge-glow" aria-hidden="true"></span>
            <div class="ea-page ea-bridge-inner">
                <span class="ea-bridge-eyebrow">La experiencia</span>
                <h2 class="ea-bridge-title">Mirá <em>cómo respira</em> el ambiente.</h2>
                <span class="ea-bridge-hint">
                    <span class="ea-bridge-hint-dot" aria-hidden="true"></span>
                    Scrolleá para entrar
                </span>
            </div>
            <span class="ea-bridge-fade" aria-hidden="true"></span>
        </section>

        <?php
            // Cache-bust automático según mtime de cada archivo
            $eaMp4Path  = FCPATH . 'videos/eden-air-scroll-optimized.mp4';
            $eaWebmPath = FCPATH . 'videos/eden-air-scroll-optimized.webm';
            $eaMp4Ver   = is_file($eaMp4Path)  ? filemtime($eaMp4Path)  : time();
            $eaWebmVer  = is_file($eaWebmPath) ? filemtime($eaWebmPath) : time();
            $eaMp4Url   = base_url('videos/eden-air-scroll-optimized.mp4')  . '?v=' . $eaMp4Ver;
            $eaWebmUrl  = base_url('videos/eden-air-scroll-optimized.webm') . '?v=' . $eaWebmVer;
            $eaPosterPath = FCPATH . 'videos/eden-air-poster.jpg';
        ?>
        <section class="ea-experience" id="experience" data-ea-experience aria-label="Eden Air en acción">
            <div class="ea-experience-stage">
                <div class="ea-experience-media">
                    <video
                        class="ea-experience-video"
                        data-ea-experience-video
                        data-ea-experience-src="<?= htmlspecialchars($eaMp4Url, ENT_QUOTES, 'UTF-8') ?>"
                        muted
                        playsinline
                        webkit-playsinline="true"
                        preload="auto"
                        disablepictureinpicture
                    >
                        <?php if (is_file($eaWebmPath)): ?>
                            <source src="<?= htmlspecialchars($eaWebmUrl, ENT_QUOTES, 'UTF-8') ?>" type="video/webm">
                        <?php endif; ?>
                        <source src="<?= htmlspecialchars($eaMp4Url, ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
                    </video>

                    <div class="ea-experience-fallback" data-ea-experience-fallback aria-hidden="true">
                        <span class="ea-experience-fallback-orb"></span>
                        <p>Cargando experiencia…</p>
                    </div>
                </div>

                <span class="ea-experience-overlay" aria-hidden="true"></span>
                <span class="ea-experience-glow ea-experience-glow--a" aria-hidden="true"></span>
                <span class="ea-experience-glow ea-experience-glow--b" aria-hidden="true"></span>
                <span class="ea-experience-vignette" aria-hidden="true"></span>

                <div class="ea-experience-layer" data-ea-experience-texts>
                    <p class="ea-experience-text ea-experience-text--tl" data-step="0">
                        <span class="ea-experience-line" aria-hidden="true"></span>
                        Ambiente <em>inestable</em> detectado
                    </p>

                    <p class="ea-experience-text ea-experience-text--mr" data-step="1">
                        Análisis ambiental<br>en <em>tiempo real</em>
                    </p>

                    <p class="ea-experience-text ea-experience-text--bl" data-step="2">
                        <span class="ea-experience-kicker">Sensores</span>
                        Temperatura · Humedad · CO₂ · Calidad del aire
                    </p>

                    <p class="ea-experience-text ea-experience-text--cr" data-step="3">
                        Regulación <em>automática</em>
                    </p>

                    <p class="ea-experience-text ea-experience-text--br" data-step="4">
                        Aire equilibrado.<br>
                        <em>Monitoreo continuo.</em>
                    </p>
                </div>
            </div>
        </section>

        <section class="ea-section" id="que-es" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <h2>Detecta el <em>ambiente invisible.</em></h2>
                    <p>Cuatro variables. Lectura continua. Cero esfuerzo.</p>
                </div>

                <div class="ea-feature-grid">
                    <article class="ea-feature-card" data-reveal-child>
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v4M5.5 5.5l2.8 2.8M21 12h-4M5.5 18.5l2.8-2.8M12 21v-4M18.5 18.5l-2.8-2.8M21 12h-4M18.5 5.5l-2.8 2.8"/>
                                <circle cx="12" cy="12" r="3.5"/>
                            </svg>
                        </span>
                        <h3>Sensado continuo</h3>
                        <p>Temperatura, humedad, CO₂ y calidad de aire medidos por el módulo ESP32 cada pocos segundos, presentados sin ruido visual.</p>
                    </article>

                    <article class="ea-feature-card" data-reveal-child>
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 12h4l2-7 4 14 2-7h4"/>
                            </svg>
                        </span>
                        <h3>Estados claros</h3>
                        <p>Cada variable se muestra con su valor, su rango ideal y un estado: normal, advertencia o crítico. Sin tener que interpretarlo.</p>
                    </article>

                    <article class="ea-feature-card" data-reveal-child>
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v18M3 12h18"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        </span>
                        <h3>Control de actuadores</h3>
                        <p>Ventilador, aromatizador, LED de alerta y humidificador en modo automático o manual desde el mismo panel.</p>
                    </article>

                    <article class="ea-feature-card" data-reveal-child>
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
                                <path d="M9 13h6M9 17h6M9 9h2"/>
                            </svg>
                        </span>
                        <h3>Historial accesible</h3>
                        <p>Las últimas lecturas quedan disponibles en tablas prolijas, listas para revisar tendencias o exportar para la tesina.</p>
                    </article>

                    <article class="ea-feature-card" data-reveal-child>
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2.5C8 2.5 5 5.5 5 9.5c0 5 7 12 7 12s7-7 7-12c0-4-3-7-7-7Z"/>
                                <circle cx="12" cy="9.5" r="2.5"/>
                            </svg>
                        </span>
                        <h3>Perfiles de ambiente</h3>
                        <p>Hogar, oficina, aula o un perfil personalizable definen los rangos ideales y el sistema los respeta en cada lectura.</p>
                    </article>

                    <article class="ea-feature-card" data-reveal-child>
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="M3 9h18M8 14l2 2 4-4"/>
                            </svg>
                        </span>
                        <h3>API preparada</h3>
                        <p>Endpoints REST listos para que el ESP32 publique mediciones y reciba comandos cuando el hardware esté integrado.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ea-core-section" id="beneficios" data-reveal>
            <span class="ea-core-bg" aria-hidden="true"></span>

            <div class="ea-page ea-core-page">
                <header class="ea-core-head">
                    <h2 class="ea-core-title">Un núcleo. <em>Siete módulos.</em><br>Un ambiente perfecto.</h2>
                    <p class="ea-core-lede">
                        El dispositivo lee el ambiente y decide qué módulo activar.
                        Sin paneles. Sin botones. Sin tu intervención.
                    </p>
                </header>

                <div class="ea-core" data-ea-core>
                    <!-- Anillos orbitales SVG -->
                    <svg class="ea-core-orbits" viewBox="-200 -200 400 400" aria-hidden="true" preserveAspectRatio="xMidYMid meet">
                        <defs>
                            <radialGradient id="eaCoreCenterGrad" cx="50%" cy="40%" r="60%">
                                <stop offset="0%"  stop-color="#b8d5d0" stop-opacity="0.95"/>
                                <stop offset="55%" stop-color="#4a7a55" stop-opacity="0.7"/>
                                <stop offset="100%" stop-color="#0a1310" stop-opacity="1"/>
                            </radialGradient>
                            <linearGradient id="eaCoreLine" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%"   stop-color="rgba(184,213,208,0)"/>
                                <stop offset="50%"  stop-color="rgba(184,213,208,0.6)"/>
                                <stop offset="100%" stop-color="rgba(184,213,208,0)"/>
                            </linearGradient>
                        </defs>
                        <!-- Anillos concéntricos -->
                        <circle cx="0" cy="0" r="92"  fill="none" stroke="rgba(184,213,208,0.20)" stroke-width="0.6"/>
                        <circle cx="0" cy="0" r="135" fill="none" stroke="rgba(184,213,208,0.12)" stroke-width="0.6" stroke-dasharray="2 6"/>
                        <circle cx="0" cy="0" r="178" fill="none" stroke="rgba(184,213,208,0.08)" stroke-width="0.6"/>

                        <!-- 7 spokes apuntando a los módulos (cada ~51.4° desde -90°) -->
                        <g class="ea-core-spokes" stroke="url(#eaCoreLine)" stroke-width="0.6" fill="none">
                            <line x1="0" y1="0" x2="0"      y2="-168"/>
                            <line x1="0" y1="0" x2="130.6"  y2="-105.8"/>
                            <line x1="0" y1="0" x2="163.7"  y2="37.8"/>
                            <line x1="0" y1="0" x2="73.0"   y2="151.3"/>
                            <line x1="0" y1="0" x2="-73.0"  y2="151.3"/>
                            <line x1="0" y1="0" x2="-163.7" y2="37.8"/>
                            <line x1="0" y1="0" x2="-130.6" y2="-105.8"/>
                        </g>

                        <!-- Núcleo central -->
                        <circle class="ea-core-pulse" cx="0" cy="0" r="68" fill="none" stroke="rgba(184,213,208,0.45)" stroke-width="0.8"/>
                        <circle cx="0" cy="0" r="52" fill="url(#eaCoreCenterGrad)"/>
                        <circle cx="0" cy="0" r="52" fill="none" stroke="rgba(184,213,208,0.6)" stroke-width="0.6"/>
                    </svg>

                    <!-- Logo / nombre al centro absoluto sobre el SVG -->
                    <div class="ea-core-center" aria-hidden="true">
                        <span class="ea-core-center-label">Eden</span>
                        <span class="ea-core-center-sub">AIR · CORE</span>
                    </div>

                    <!-- 7 módulos en órbita: ángulos distribuidos cada ~51° -->
                    <div class="ea-core-modules">
                        <article class="ea-core-mod" data-tone="warm" style="--ang:-90deg;  --r:42;">
                            <span class="ea-core-mod-key">Temperatura</span>
                            <span class="ea-core-mod-val">°C</span>
                        </article>
                        <article class="ea-core-mod" data-tone="cool" style="--ang:-39deg;  --r:42;">
                            <span class="ea-core-mod-key">Humedad</span>
                            <span class="ea-core-mod-val">%</span>
                        </article>
                        <article class="ea-core-mod" data-tone="leaf" style="--ang: 13deg;  --r:42;">
                            <span class="ea-core-mod-key">CO₂</span>
                            <span class="ea-core-mod-val">ppm</span>
                        </article>
                        <article class="ea-core-mod" data-tone="cool" style="--ang: 64deg;  --r:42;">
                            <span class="ea-core-mod-key">Calidad de aire</span>
                            <span class="ea-core-mod-val">índice</span>
                        </article>
                        <article class="ea-core-mod" data-tone="leaf" style="--ang: 115deg; --r:42;">
                            <span class="ea-core-mod-key">Ventilación</span>
                            <span class="ea-core-mod-val">auto</span>
                        </article>
                        <article class="ea-core-mod" data-tone="cool" style="--ang: 167deg; --r:42;">
                            <span class="ea-core-mod-key">Humidificación</span>
                            <span class="ea-core-mod-val">auto</span>
                        </article>
                        <article class="ea-core-mod" data-tone="leaf" style="--ang:-141deg; --r:42;">
                            <span class="ea-core-mod-key">Purificación</span>
                            <span class="ea-core-mod-val">auto</span>
                        </article>
                    </div>
                </div>

                <ul class="ea-core-facts" aria-label="Capacidades de Eden Air">
                    <li><span>4</span> variables monitoreadas</li>
                    <li><span>3</span> módulos regulados</li>
                    <li><span>24/7</span> aire optimizado</li>
                </ul>
            </div>
        </section>

        <?php
            // Video "exploded view" del interior del dispositivo (sin audio, optimizado).
            $eaTechMp4Path  = FCPATH . 'videos/eden-air-exploded.mp4';
            $eaTechWebmPath = FCPATH . 'videos/eden-air-exploded.webm';
            $eaTechPoster   = FCPATH . 'videos/eden-air-exploded-poster.jpg';
            $eaTechMp4Ver   = is_file($eaTechMp4Path)  ? filemtime($eaTechMp4Path)  : time();
            $eaTechWebmVer  = is_file($eaTechWebmPath) ? filemtime($eaTechWebmPath) : time();
            $eaTechMp4Url   = base_url('videos/eden-air-exploded.mp4')  . '?v=' . $eaTechMp4Ver;
            $eaTechWebmUrl  = base_url('videos/eden-air-exploded.webm') . '?v=' . $eaTechWebmVer;
            $eaTechPosterUrl = base_url('videos/eden-air-exploded-poster.jpg') . '?v=' . (is_file($eaTechPoster) ? filemtime($eaTechPoster) : time());
        ?>
        <section class="ea-tech" id="tecnologia-interna" data-reveal aria-labelledby="ea-tech-title">
            <div class="ea-page">
                <div class="ea-section-head ea-tech-head">
                    <h2 id="ea-tech-title">Tecnología interna diseñada para <em>cuidar tu ambiente.</em></h2>
                    <p>Eden Air combina sensores, procesamiento inteligente y automatización para interpretar el estado del ambiente y responder de forma precisa.</p>
                </div>

                <div class="ea-tech-stage" data-reveal-child>
                    <div class="ea-tech-media">
                        <video
                            class="ea-tech-video"
                            data-ea-tech-video
                            poster="<?= htmlspecialchars($eaTechPosterUrl, ENT_QUOTES, 'UTF-8') ?>"
                            muted
                            loop
                            playsinline
                            webkit-playsinline="true"
                            autoplay
                            preload="metadata"
                            disablepictureinpicture
                            aria-label="Vista interna animada del dispositivo Eden Air mostrando sus módulos"
                        >
                            <?php if (is_file($eaTechWebmPath)): ?>
                                <source src="<?= htmlspecialchars($eaTechWebmUrl, ENT_QUOTES, 'UTF-8') ?>" type="video/webm">
                            <?php endif; ?>
                            <source src="<?= htmlspecialchars($eaTechMp4Url, ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
                        </video>
                        <span class="ea-tech-glow" aria-hidden="true"></span>
                        <span class="ea-tech-vignette" aria-hidden="true"></span>
                    </div>

                    <ul class="ea-tech-cards" aria-label="Lo que hay dentro de Eden Air">
                        <li class="ea-tech-card ea-tech-card--tl">
                            <span class="ea-tech-card-dot" aria-hidden="true"></span>
                            <span class="ea-tech-card-key">Sensores ambientales</span>
                            <span class="ea-tech-card-val">Temperatura, humedad, CO₂ y calidad del aire</span>
                        </li>
                        <li class="ea-tech-card ea-tech-card--tr">
                            <span class="ea-tech-card-dot" aria-hidden="true"></span>
                            <span class="ea-tech-card-key">Control inteligente</span>
                            <span class="ea-tech-card-val">El ESP32 interpreta cada lectura en tiempo real</span>
                        </li>
                        <li class="ea-tech-card ea-tech-card--bl">
                            <span class="ea-tech-card-dot" aria-hidden="true"></span>
                            <span class="ea-tech-card-key">Automatización</span>
                            <span class="ea-tech-card-val">Activa ventilación, humidificación y aroma</span>
                        </li>
                        <li class="ea-tech-card ea-tech-card--br">
                            <span class="ea-tech-card-dot" aria-hidden="true"></span>
                            <span class="ea-tech-card-key">Diseño eficiente</span>
                            <span class="ea-tech-card-val">Bajo consumo y preparado para múltiples espacios</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="ea-section" id="funcionamiento" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <h2>Sensa. <em>Decide.</em> Actúa.</h2>
                    <p>Cada lectura recorre el mismo camino. Sin atajos. Sin demoras.</p>
                </div>

                <div class="ea-flow">
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Sensa</h4>
                        <p>El ESP32 mide cuatro variables clave cada pocos segundos.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Transmite</h4>
                        <p>Las lecturas viajan por la API REST hacia el núcleo.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Registra</h4>
                        <p>Cada dato queda guardado. Historial siempre disponible.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Decide</h4>
                        <p>Las reglas evalúan en milisegundos qué hacer.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Actúa</h4>
                        <p>Ventilación, humidificación y purificación responden solas.</p>
                    </div>
                </div>

                <p class="ea-flow-notice">
                    <span class="ea-badge ea-badge--info">Estado del hardware</span>
                    &nbsp;Integración con ESP32 preparada — próxima etapa del proyecto.
                </p>
            </div>
        </section>

        <section class="ea-section" id="sensores" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <h2>Lee el aire. <em>Mueve el ambiente.</em></h2>
                    <p>Cuatro sensores entran. Cuatro actuadores responden.</p>
                </div>

                <div class="ea-hardware">
                    <article class="ea-hardware-block" data-reveal-child>
                        <p class="ea-eyebrow">Sensores</p>
                        <h3>Qué se mide</h3>
                        <p class="ea-hardware-desc">Cuatro variables que definen el confort y la calidad del aire en un ambiente.</p>

                        <div class="ea-hardware-list">
                            <div class="ea-hardware-item tone-warning">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                                        <path d="M12 4a2 2 0 0 0-2 2v8.2a3.6 3.6 0 1 0 4 0V6a2 2 0 0 0-2-2Z"/>
                                        <circle cx="12" cy="16.5" r="1.6" fill="currentColor"/>
                                    </svg>
                                </span>
                                <div><strong>Temperatura</strong><small>°C · ambiente</small></div>
                            </div>
                            <div class="ea-hardware-item tone-info">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                                        <path d="M12 3.5c2.4 2.8 5.5 6 5.5 9.5a5.5 5.5 0 1 1-11 0c0-3.5 3.1-6.7 5.5-9.5Z"/>
                                    </svg>
                                </span>
                                <div><strong>Humedad</strong><small>% · vapor de agua</small></div>
                            </div>
                            <div class="ea-hardware-item tone-success">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <circle cx="12" cy="12" r="8"/>
                                        <text x="12" y="14.5" text-anchor="middle" font-size="7" font-family="DM Mono, monospace" fill="currentColor" stroke="none">CO₂</text>
                                    </svg>
                                </span>
                                <div><strong>CO₂</strong><small>ppm · ventilación</small></div>
                            </div>
                            <div class="ea-hardware-item tone-citrus">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                        <path d="M3 9h12a3 3 0 1 0-3-3"/>
                                        <path d="M3 14h15a3 3 0 1 1-3 3"/>
                                    </svg>
                                </span>
                                <div><strong>Calidad de aire</strong><small>0–100 · índice</small></div>
                            </div>
                        </div>
                    </article>

                    <article class="ea-hardware-block" data-reveal-child>
                        <p class="ea-eyebrow">Actuadores</p>
                        <h3>Qué se controla</h3>
                        <p class="ea-hardware-desc">Cuatro dispositivos que responden a las reglas o al control manual desde el panel.</p>

                        <div class="ea-hardware-list">
                            <div class="ea-hardware-item tone-info">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="2.2"/>
                                        <path d="M12 10c0-3 1-6 4-6 0 3-1.4 5-4 6Zm0 4c0 3-1 6-4 6 0-3 1.4-5 4-6Zm-2-2c-3 0-6-1-6-4 3 0 5 1.4 6 4Zm4 0c3 0 6 1 6 4-3 0-5-1.4-6-4Z"/>
                                    </svg>
                                </span>
                                <div><strong>Ventilador</strong><small>Renueva el aire</small></div>
                            </div>
                            <div class="ea-hardware-item tone-citrus">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <path d="M12 3c2 3 4 6 4 9a4 4 0 1 1-8 0c0-3 2-6 4-9Z"/>
                                        <path d="M9 14c-1 1.5-1 3 0 4M15 14c1 1.5 1 3 0 4"/>
                                    </svg>
                                </span>
                                <div><strong>Aromatizador</strong><small>Neutraliza olores</small></div>
                            </div>
                            <div class="ea-hardware-item tone-warning">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <path d="M9 3h6l2 5a6 6 0 1 1-10 0Z"/>
                                        <path d="M10 21h4"/>
                                    </svg>
                                </span>
                                <div><strong>LED de alerta</strong><small>Aviso visual</small></div>
                            </div>
                            <div class="ea-hardware-item tone-success">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <path d="M12 3.5c2.4 2.8 5.5 6 5.5 9.5a5.5 5.5 0 1 1-11 0c0-3.5 3.1-6.7 5.5-9.5Z"/>
                                        <path d="M8.5 13.5c.6 1.6 2 2.7 3.5 2.7"/>
                                    </svg>
                                </span>
                                <div><strong>Humidificador</strong><small>Regula humedad</small></div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="ea-section" id="automatizacion" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <h2>Cuatro reglas. <em>Cero supervisión.</em></h2>
                    <p>Cada regla es transparente, editable y siempre activa.</p>
                </div>

                <div class="ea-rules">
                    <article class="ea-rule" data-reveal-child>
                        <header class="ea-rule-head">
                            <span class="ea-rule-num">01</span>
                            <span class="ea-badge ea-badge--danger">Aire viciado</span>
                        </header>
                        <p class="ea-rule-cond"><span class="ea-rule-var">CO₂</span> <span class="ea-rule-op">&gt;</span> <span class="ea-rule-val">1000 ppm</span></p>
                        <p class="ea-rule-arrow" aria-hidden="true">↓</p>
                        <p class="ea-rule-action">Encender <strong>ventilador</strong> hasta renovar el aire.</p>
                    </article>

                    <article class="ea-rule" data-reveal-child>
                        <header class="ea-rule-head">
                            <span class="ea-rule-num">02</span>
                            <span class="ea-badge ea-badge--info">Aire seco</span>
                        </header>
                        <p class="ea-rule-cond"><span class="ea-rule-var">Humedad</span> <span class="ea-rule-op">&lt;</span> <span class="ea-rule-val">40 %</span></p>
                        <p class="ea-rule-arrow" aria-hidden="true">↓</p>
                        <p class="ea-rule-action">Activar <strong>humidificador</strong> hasta recuperar el confort.</p>
                    </article>

                    <article class="ea-rule" data-reveal-child>
                        <header class="ea-rule-head">
                            <span class="ea-rule-num">03</span>
                            <span class="ea-badge ea-badge--warning">Calor</span>
                        </header>
                        <p class="ea-rule-cond"><span class="ea-rule-var">Temperatura</span> <span class="ea-rule-op">&gt;</span> <span class="ea-rule-val">28 °C</span></p>
                        <p class="ea-rule-arrow" aria-hidden="true">↓</p>
                        <p class="ea-rule-action">Encender <strong>ventilador</strong> para refrescar el ambiente.</p>
                    </article>

                    <article class="ea-rule" data-reveal-child>
                        <header class="ea-rule-head">
                            <span class="ea-rule-num">04</span>
                            <span class="ea-badge ea-badge--success">Aire pesado</span>
                        </header>
                        <p class="ea-rule-cond"><span class="ea-rule-var">Calidad</span> <span class="ea-rule-op">&lt;</span> <span class="ea-rule-val">60 / 100</span></p>
                        <p class="ea-rule-arrow" aria-hidden="true">↓</p>
                        <p class="ea-rule-action">Activar <strong>aromatizador</strong> y mostrar <strong>LED</strong>.</p>
                    </article>
                </div>

                <p class="ea-rules-foot">
                    <span class="ea-badge ea-badge--neutral">Modo manual</span>
                    &nbsp;Cada actuador también se puede operar a mano desde el panel.
                </p>
            </div>
        </section>

        <section class="ea-section ea-buy-section" id="comprar" data-reveal aria-labelledby="ea-buy-title">
            <div class="ea-page">
                <div class="ea-section-head">
                    <h2 id="ea-buy-title">Llevá el control de tu ambiente <em>a casa.</em></h2>
                    <p>Un dispositivo, un dashboard y ambientes personalizados. Todo incluido, sin costos ocultos ni complementos caros.</p>
                </div>

                <div class="ea-buy-grid">
                    <article class="ea-buy-card" data-reveal-child>
                        <header class="ea-buy-card-head">
                            <span class="ea-buy-badge">
                                <span class="ea-buy-badge-dot" aria-hidden="true"></span>
                                Precio demo · presentación educativa
                            </span>
                            <h3 class="ea-buy-name">Eden Air Core</h3>
                            <p class="ea-buy-tagline">Dispositivo inteligente + acceso al dashboard + configuración personalizada de ambientes.</p>
                        </header>

                        <div class="ea-buy-price">
                            <span class="ea-buy-amount"><span class="ea-buy-cur">USD</span>5</span>
                            <span class="ea-buy-period">precio de prueba<br>para la demo de tesina</span>
                        </div>

                        <div class="ea-buy-actions">
                            <?php if ($conSesion): ?>
                                <a href="<?= site_url('panel/compra') ?>" class="ea-button ea-button-primary ea-button-buy ea-button-block">Comprar Eden Air</a>
                                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary ea-button-block">Ir al dashboard</a>
                            <?php else: ?>
                                <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary ea-button-buy ea-button-block">Comprar Eden Air</a>
                                <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary ea-button-block">Ya tengo cuenta</a>
                            <?php endif; ?>
                        </div>

                        <p class="ea-buy-note">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
                            Precio simulado para la presentación. No representa un valor comercial final.
                        </p>
                    </article>

                    <aside class="ea-buy-includes" data-reveal-child aria-label="Qué incluye Eden Air Core">
                        <h4 class="ea-buy-includes-title">Todo incluido en el plan</h4>
                        <ul class="ea-buy-list">
                            <li><span class="ea-buy-check" aria-hidden="true"></span> Configuración personalizada de ambientes incluida</li>
                            <li><span class="ea-buy-check" aria-hidden="true"></span> Acceso completo al dashboard de monitoreo</li>
                            <li><span class="ea-buy-check" aria-hidden="true"></span> Vinculación de múltiples dispositivos por cuenta</li>
                            <li><span class="ea-buy-check" aria-hidden="true"></span> Perfiles ambientales por espacio (dormitorio, aula, oficina…)</li>
                            <li><span class="ea-buy-check" aria-hidden="true"></span> Preparado para automatización ambiental</li>
                            <li><span class="ea-buy-check" aria-hidden="true"></span> Enfoque sustentable y de bajo consumo</li>
                        </ul>
                        <p class="ea-buy-includes-foot">
                            <span class="ea-badge ea-badge--neutral">Incluido</span>
                            El ambiente personalizado no se cobra aparte: es parte del producto.
                        </p>
                    </aside>
                </div>
            </div>
        </section>

        <section class="ea-section" data-reveal>
            <div class="ea-page">
                <div class="ea-cta">
                    <h2>Aire optimizado. <em>Empezá ahora.</em></h2>
                    <p>Monitoreo continuo. Control automático. Sin curva de aprendizaje.</p>
                    <div class="ea-hero-actions">
                        <?php if ($conSesion): ?>
                            <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary">Ir al panel</a>
                            <a href="<?= site_url('logout') ?>" class="ea-button ea-button-secondary">Cerrar sesión</a>
                        <?php else: ?>
                            <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary">Crear cuenta</a>
                            <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary">Ya tengo cuenta</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?= view('partials/footer') ?>
</div>

<?php
    $eaJsBust = function (string $relativePath): string {
        $abs = FCPATH . $relativePath;
        $v   = is_file($abs) ? filemtime($abs) : time();
        return base_url($relativePath) . '?v=' . $v;
    };
?>
<script src="<?= htmlspecialchars($eaJsBust('JS/tema.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($eaJsBust('JS/inicio.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
/* Video "Ingeniería interna": reproduce solo cuando está en pantalla.
   Ahorra CPU/batería y respeta prefers-reduced-motion. */
(function () {
    var video = document.querySelector("[data-ea-tech-video]");
    if (!video) return;

    var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduce) {
        // Sin movimiento: mostramos el póster estático y no autoreproducimos.
        video.removeAttribute("autoplay");
        video.removeAttribute("loop");
        try { video.pause(); } catch (e) {}
        return;
    }

    var intentarPlay = function () {
        var p = video.play();
        if (p && typeof p.catch === "function") { p.catch(function () {}); }
    };

    if ("IntersectionObserver" in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { intentarPlay(); }
                else { try { video.pause(); } catch (e) {} }
            });
        }, { threshold: 0.25 });
        io.observe(video);
    } else {
        intentarPlay();
    }
})();
</script>
<script type="importmap">
{
    "imports": {
        "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
    }
}
</script>
<script type="module" src="<?= htmlspecialchars($eaJsBust('JS/eden-core-3d.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
