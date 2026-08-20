<?php
/* =============================================================================
   VISTA: portfolio.php — PORTFOLIO PÚBLICO del proyecto (ruta "/portfolio")
   CSS:  public/CSS/portfolio.css (+ eden-brand.css global)
   JS:   portfolio.js (menú, scrollspy, gráficos Chart.js) ·
         portfolio-gsap.js (animaciones de scroll) · ea-scrollbar.js
   CÓMO LEER ESTA VISTA:
   · ESTRUCTURA → las secciones ya vienen numeradas con banners
     "NN · Nombre" (00 hero, 01 imagen corporativa ... 06 plan operativo).
     Los arrays de acá abajo ($landingLinks, $internalSections, $sitemap)
     alimentan menú, scrollspy y mapa del sitio: tocás acá y se actualiza todo.
   · ANIMACIÓN (GSAP/ScrollTrigger) → todo elemento con data-reveal aparece
     al entrar en pantalla y data-reveal-child entra en cascada; lo define
     portfolio-gsap.js. Los gráficos de la sección 04 los dibuja Chart.js
     desde portfolio.js.
   ============================================================================= */
$conSesion = (bool) session()->get('user_id');

// Precio de venta del equipo: sale del análisis de costos (sección 07) y es el
// mismo número que muestra la landing. Se escribe una sola vez, acá.
$precioVenta    = 450000;
$precioVentaTxt = '$' . number_format($precioVenta, 0, ',', '.');

$landingLinks = [
    ['href' => site_url('/') . '#inicio',         'label' => 'Inicio'],
    ['href' => site_url('/') . '#que-es',         'label' => 'Qué es'],
    ['href' => site_url('/') . '#beneficios',     'label' => 'Beneficios'],
    ['href' => site_url('/') . '#funcionamiento', 'label' => 'Funcionamiento'],
    ['href' => site_url('/') . '#sensores',       'label' => 'Sensores'],
    ['href' => site_url('/') . '#automatizacion', 'label' => 'Automatización'],
];

$internalSections = [
    ['anchor' => 'pagina-principal',     'label' => 'Página principal'],
    ['anchor' => 'imagen-corporativa',   'label' => 'Imagen corporativa'],
    ['anchor' => 'quienes-somos',        'label' => 'Quiénes somos'],
    ['anchor' => 'acerca-de-eden-air',   'label' => 'Acerca de Eden Air'],
    ['anchor' => 'analisis-mercado',     'label' => 'Análisis de mercado'],
    ['anchor' => 'analisis-competencia', 'label' => 'Análisis de la competencia'],
    ['anchor' => 'plan-operativo',       'label' => 'Plan operativo'],
    ['anchor' => 'analisis-costos',      'label' => 'Análisis de costos'],
];

$sitemap = [
    ['n' => '01', 'anchor' => 'imagen-corporativa',   'title' => 'Imagen corporativa',          'lede' => 'Logo, paleta, tipografía y principios visuales.',  'status' => 'is-ready',    'status_label' => 'Preparado'],
    ['n' => '02', 'anchor' => 'quienes-somos',        'title' => 'Quiénes somos',               'lede' => 'El equipo estudiantil detrás del proyecto.',        'status' => 'is-ready',    'status_label' => 'Preparado'],
    ['n' => '03', 'anchor' => 'acerca-de-eden-air',   'title' => 'Acerca de Eden Air',          'lede' => 'Producto, propósito y propuesta de valor.',         'status' => 'is-active',   'status_label' => 'Desarrollado'],
    ['n' => '04', 'anchor' => 'analisis-mercado',     'title' => 'Análisis de mercado',         'lede' => 'TP Nº 2 · Investigación, encuesta y reflexión.',    'status' => 'is-focus',    'status_label' => 'Prioritario'],
    ['n' => '05', 'anchor' => 'analisis-competencia', 'title' => 'Análisis de la competencia',  'lede' => 'Comparativa frente a otros productos del rubro.',   'status' => 'is-research', 'status_label' => 'En investigación'],
    ['n' => '06', 'anchor' => 'plan-operativo',       'title' => 'Plan operativo',              'lede' => 'Etapas, recursos y línea de tiempo del proyecto.',  'status' => 'is-progress', 'status_label' => 'En curso'],
    ['n' => '07', 'anchor' => 'analisis-costos',      'title' => 'Análisis de costos',         'lede' => 'TP Nº 5 · Costos, precio de venta y punto muerto.', 'status' => 'is-active',   'status_label' => 'Desarrollado'],
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'EdenAir | Portfolio',
        'extraCss' => ['CSS/portfolio.css'],
    ]) ?>
</head>
<body class="ea-body ea-landing ea-portfolio" data-ea-portfolio>
    <?= view('partials/navbar', [
        'subtitle'        => 'Portfolio del proyecto',
        'conSesion'       => $conSesion,
        'navLinks'        => $landingLinks,
        'portfolioMenu'   => true,
        'activePortfolio' => true,
    ]) ?>

    <button type="button" class="ea-nav-toggle" data-ea-nav-toggle aria-expanded="false" aria-controls="ea-mobile-nav" aria-label="Abrir menú de navegación">
        <span class="ea-nav-toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>

    <nav id="ea-mobile-nav" class="ea-mobile-nav" data-ea-mobile-nav aria-hidden="true">
        <ul data-ea-portfolio-spy>
            <?php foreach ($internalSections as $section): ?>
                <li><a href="#<?= esc($section['anchor']) ?>"><?= esc($section['label']) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="ea-mobile-nav-section">
            <span class="ea-mobile-nav-section-title">Landing</span>
            <ul>
                <?php foreach ($landingLinks as $link): ?>
                    <li><a href="<?= esc($link['href']) ?>"><?= esc($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="ea-mobile-nav-actions">
            <div class="ea-mobile-nav-theme">
                <span class="ea-mobile-nav-theme-label">Tema</span>
                <?= view('partials/theme_toggle', ['unique' => '-mobile']) ?>
            </div>
            <?php if ($conSesion): ?>
                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary ea-button-block">Ir al panel</a>
                <a href="<?= site_url('logout') ?>" class="ea-button ea-button-secondary ea-button-block">Cerrar sesión</a>
            <?php else: ?>
                <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary ea-button-block">Crear cuenta</a>
                <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary ea-button-block">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </nav>

<!-- Scroll suave (ScrollSmoother): el navbar y los menús fixed quedan FUERA
     del #smooth-wrapper. La barra de scroll moderna la inyecta
     JS/ea-scrollbar.js, también fuera del wrapper. -->
<div id="smooth-wrapper">
<div id="smooth-content">
<div class="ea-shell">
    <main class="ea-pf">
        <!-- ============================================================
             00 · Página principal — Hero inmersivo
             ============================================================ -->
        <section class="ea-pf-hero" id="pagina-principal">
            <span class="ea-pf-hero__bg" aria-hidden="true"></span>

            <div class="ea-page ea-pf-hero__grid">
                <div class="ea-pf-hero__intro">
                    <span class="ea-pf-eyebrow">Portfolio · Eden Air</span>
                    <h1 class="ea-pf-h ea-pf-h1 ea-pf-hero__title">Eden Air,<br><em>paso a paso.</em></h1>
                    <p class="ea-pf-lede">
                        Recorrido digital del proyecto: sistema inteligente de monitoreo y ambientación automática
                        de espacios interiores. Esta vista reúne identidad, producto, análisis de mercado y plan operativo.
                    </p>

                    <span class="ea-pf-hero__current">
                        Trabajo Práctico Nº 5 ·&nbsp;<strong>Análisis de Costos</strong>
                    </span>

                    <div class="ea-pf-hero__actions">
                        <a href="#analisis-mercado" class="ea-button ea-button-primary">Ver análisis de mercado</a>
                        <a href="#acerca-de-eden-air" class="ea-button ea-button-secondary">Conocer el proyecto</a>
                    </div>
                </div>

                <div class="ea-pf-hero__art" aria-hidden="true">
                    <span class="ea-pf-hero__rings"></span>
                    <div class="ea-pf-hero__core">
                        <div>
                            <span class="ea-pf-hero__core-mark">Eden<em>Air</em></span>
                            <span class="ea-pf-hero__core-tag">Core · 2026</span>
                        </div>
                    </div>
                    <div class="ea-pf-hero__chips">
                        <span class="ea-pf-chip ea-pf-chip--temp">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4a2 2 0 0 0-2 2v8.2a3.6 3.6 0 1 0 4 0V6a2 2 0 0 0-2-2Z"/></svg>
                            Temperatura · <strong>22 °C</strong>
                        </span>
                        <span class="ea-pf-chip ea-pf-chip--humedad">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 3.5c2.4 2.8 5.5 6 5.5 9.5a5.5 5.5 0 1 1-11 0c0-3.5 3.1-6.7 5.5-9.5Z"/></svg>
                            Humedad · <strong>48 %</strong>
                        </span>
                        <span class="ea-pf-chip ea-pf-chip--co2">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="8"/></svg>
                            CO₂ · <strong>Normal</strong>
                        </span>
                        <span class="ea-pf-chip ea-pf-chip--calidad">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12a3 3 0 1 0-3-3"/><path d="M3 14h15a3 3 0 1 1-3 3"/></svg>
                            Calidad · <strong>Excelente</strong>
                        </span>
                    </div>
                </div>
            </div>

            <span class="ea-pf-hero__cue" aria-hidden="true">
                <span>Recorrer</span>
                <span class="ea-pf-hero__cue-bar"></span>
            </span>
        </section>

        <!-- ============================================================
             Sitemap — Mapa visual del recorrido
             ============================================================ -->
        <section class="ea-pf-sitemap" aria-labelledby="ea-pf-sitemap-title" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-sitemap__head">
                    <div>
                        <span class="ea-pf-eyebrow">Recorrido</span>
                        <h2 class="ea-pf-h ea-pf-h2" id="ea-pf-sitemap-title">Mapa del <em>portfolio</em>.</h2>
                    </div>
                    <p class="ea-pf-lede" style="max-width: 38ch;">
                        Siete paradas para entender, validar y proyectar Eden Air. Toca cualquier card para saltar a esa sección.
                    </p>
                </header>

                <div class="ea-pf-sitemap__grid">
                    <?php foreach ($sitemap as $tile): ?>
                        <a href="#<?= esc($tile['anchor']) ?>" class="ea-pf-tile" data-reveal-child>
                            <div class="ea-pf-tile__head">
                                <span class="ea-pf-tile__num"><?= esc($tile['n']) ?></span>
                                <span class="ea-pf-status <?= esc($tile['status']) ?>"><?= esc($tile['status_label']) ?></span>
                            </div>
                            <h3 class="ea-pf-tile__title"><?= esc($tile['title']) ?></h3>
                            <p class="ea-pf-tile__lede"><?= esc($tile['lede']) ?></p>
                            <span class="ea-pf-tile__arrow">Abrir →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ============================================================
             01 · Imagen corporativa — Bento
             ============================================================ -->
        <section class="ea-pf-section" id="imagen-corporativa" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">01</span>
                        <span class="ea-pf-eyebrow">Identidad</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Imagen <em>corporativa</em>.</h2>
                        <p class="ea-pf-lede">Sistema visual de Eden Air: marca, paleta, tipografía y aplicaciones futuras.</p>
                    </div>
                </header>

                <div class="ea-pf-bento">
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Marca</span>
                        <div class="ea-pf-logo-stage">
                            <?= view('partials/logo', ['logo' => ['variant' => 'horizontal', 'size' => 60, 'subtitle' => 'Monitoreo ambiental']]) ?>
                        </div>
                        <h3 class="ea-pf-card__title">Eden Air · isotipo + wordmark</h3>
                        <p class="ea-pf-card__text">
                            Anillo abierto que sugiere aire en circulación, hoja estilizada para la naturaleza y
                            un punto sensor que ancla la medición. La marca respira al mismo tiempo que el ambiente.
                        </p>
                        <!-- TODO: Cargar logo definitivo si la marca cambia -->
                    </article>

                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Paleta</span>
                        <div class="ea-pf-palette">
                            <div class="ea-pf-swatch" style="background: var(--eden-700);"><span class="ea-pf-swatch__hex">#1C4029</span></div>
                            <div class="ea-pf-swatch" style="background: var(--eden-500);"><span class="ea-pf-swatch__hex">#4A7A55</span></div>
                            <div class="ea-pf-swatch" style="background: var(--eden-300);"><span class="ea-pf-swatch__hex">#BCD2BD</span></div>
                            <div class="ea-pf-swatch" style="background: var(--ea-citrus);"><span class="ea-pf-swatch__hex">#C9D870</span></div>
                            <div class="ea-pf-swatch" style="background: var(--ea-breath);"><span class="ea-pf-swatch__hex">#B8D5D0</span></div>
                        </div>
                        <h3 class="ea-pf-card__title">Color institucional</h3>
                        <p class="ea-pf-card__text">Verdes profundos para sustento, citrus y breath como acentos vivos.</p>
                    </article>

                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Tipografía</span>
                        <div class="ea-pf-type">
                            <span class="ea-pf-type__serif">Eden Air</span>
                            <span class="ea-pf-type__sans">DM Sans · cuerpo del texto</span>
                            <span class="ea-pf-type__mono">DM Mono · etiquetas técnicas</span>
                        </div>
                        <h3 class="ea-pf-card__title">Sistema tipográfico</h3>
                        <p class="ea-pf-card__text">Serif editorial, sans humanista y mono para datos.</p>
                    </article>

                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Principios visuales</span>
                        <div class="ea-pf-chip-row">
                            <span class="ea-pf-tag">Glassmorphism</span>
                            <span class="ea-pf-tag">Sombras sutiles</span>
                            <span class="ea-pf-tag">Bordes suaves</span>
                            <span class="ea-pf-tag">Transiciones limpias</span>
                            <span class="ea-pf-tag">Modo claro · oscuro</span>
                            <span class="ea-pf-tag">Responsive nativo</span>
                        </div>
                        <h3 class="ea-pf-card__title">Lenguaje visual</h3>
                        <p class="ea-pf-card__text">Tecnología, ambiente y precisión en cada interacción.</p>
                    </article>

                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Aplicaciones</span>
                        <div class="ea-pf-mockups">
                            <div class="ea-pf-mockup">Web</div>
                            <div class="ea-pf-mockup">Dispositivo</div>
                            <div class="ea-pf-mockup">Folletería</div>
                            <div class="ea-pf-mockup">Banner QR</div>
                            <div class="ea-pf-mockup">Presentación</div>
                            <div class="ea-pf-mockup">Stand expo</div>
                        </div>
                        <h3 class="ea-pf-card__title">Superficies de marca</h3>
                        <p class="ea-pf-card__text">Espacios reservados para previsualizar la marca aplicada a piezas reales.</p>
                        <!-- TODO: Cargar mockups reales de cada superficie cuando estén disponibles -->
                    </article>
                </div>
            </div>
        </section>

        <!-- ============================================================
             02 · Quiénes somos
             ============================================================ -->
        <section class="ea-pf-section" id="quienes-somos" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">02</span>
                        <span class="ea-pf-eyebrow">Equipo</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Quiénes <em>somos</em>.</h2>
                        <p class="ea-pf-lede">
                            Estudiantes de 7º año desarrollando una tesina técnica que integra programación,
                            electrónica, IoT, base de datos, diseño web y emprendimiento.
                        </p>
                    </div>
                </header>

                <div class="ea-pf-team">
                    <!-- TODO: Cargar integrantes reales (nombre, foto, descripción) -->
                    <article class="ea-pf-card ea-pf-team__card">
                        <div class="ea-pf-avatar" aria-hidden="true">01</div>
                        <span class="ea-pf-team__role">Desarrollo web & backend</span>
                        <h3 class="ea-pf-card__title">Integrante por confirmar</h3>
                        <p class="ea-pf-card__text">Plataforma en CodeIgniter 4, base de datos MySQL y API REST.</p>
                    </article>
                    <article class="ea-pf-card ea-pf-team__card">
                        <div class="ea-pf-avatar" aria-hidden="true">02</div>
                        <span class="ea-pf-team__role">Electrónica & ESP32</span>
                        <h3 class="ea-pf-card__title">Integrante por confirmar</h3>
                        <p class="ea-pf-card__text">Sensores, actuadores, firmware y conexión con la web.</p>
                    </article>
                    <article class="ea-pf-card ea-pf-team__card">
                        <div class="ea-pf-avatar" aria-hidden="true">03</div>
                        <span class="ea-pf-team__role">Diseño visual & presentación</span>
                        <h3 class="ea-pf-card__title">Integrante por confirmar</h3>
                        <p class="ea-pf-card__text">Identidad de marca, comunicación y materiales de exposición.</p>
                    </article>
                    <article class="ea-pf-card ea-pf-team__card">
                        <div class="ea-pf-avatar" aria-hidden="true">04</div>
                        <span class="ea-pf-team__role">Investigación de mercado</span>
                        <h3 class="ea-pf-card__title">Integrante por confirmar</h3>
                        <p class="ea-pf-card__text">Validación del producto, encuesta y reflexión sustentable.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ============================================================
             03 · Acerca de Eden Air — Storytelling
             ============================================================ -->
        <section class="ea-pf-section" id="acerca-de-eden-air" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">03</span>
                        <span class="ea-pf-eyebrow">Producto</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Acerca de <em>Eden Air</em>.</h2>
                        <p class="ea-pf-lede">
                            Sistema inteligente de monitoreo y ambientación automática para espacios interiores.
                            Hardware con ESP32, sensores, actuadores y una plataforma web que respira con el ambiente.
                        </p>
                    </div>
                </header>

                <div class="ea-pf-story">
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Propósito</span>
                        <h3 class="ea-pf-card__title">Mejorar el confort interior</h3>
                        <p class="ea-pf-card__text">
                            Medir, comparar y automatizar para que cada ambiente se mantenga en su rango ideal sin
                            intervención constante del usuario.
                        </p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Problema que resuelve</span>
                        <h3 class="ea-pf-card__title">Control ambiental impreciso</h3>
                        <p class="ea-pf-card__text">
                            Pocos lugares controlan temperatura, humedad, calidad del aire y ambientación con datos reales.
                        </p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Propuesta de valor</span>
                        <h3 class="ea-pf-card__title">Una sola experiencia</h3>
                        <p class="ea-pf-card__text">
                            Unifica monitoreo, automatización y visualización en una plataforma simple, visual y adaptable.
                        </p>
                    </article>
                </div>

                <h3 class="ea-pf-h ea-pf-h3" style="margin-top: 12px;">Cómo funciona, en cinco pasos.</h3>
                <div class="ea-pf-journey">
                    <article class="ea-pf-journey__step">
                        <h4>Problema detectado</h4>
                        <p>El usuario quiere un ambiente más cómodo y saludable, sin tener que ajustarlo a mano.</p>
                    </article>
                    <article class="ea-pf-journey__step">
                        <h4>Medición del ambiente</h4>
                        <p>El dispositivo ESP32 mide temperatura, humedad, CO₂ y calidad del aire en tiempo real.</p>
                    </article>
                    <article class="ea-pf-journey__step">
                        <h4>Comparación de datos</h4>
                        <p>La web compara las lecturas con los valores recomendados según el ambiente elegido.</p>
                    </article>
                    <article class="ea-pf-journey__step">
                        <h4>Acción automática</h4>
                        <p>Se activan o representan actuadores: aire por IR, aromatizador, humidificación y LED.</p>
                    </article>
                    <article class="ea-pf-journey__step">
                        <h4>Mejora del confort</h4>
                        <p>El ambiente vuelve al rango ideal y los datos quedan registrados para análisis posterior.</p>
                    </article>
                </div>

                <h3 class="ea-pf-h ea-pf-h3" style="margin-top: 28px;">Stack del sistema</h3>
                <div class="ea-pf-stack">
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>ESP32</span>
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>Sensores ambientales</span>
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>Actuadores físicos</span>
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>MySQL</span>
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>CodeIgniter 4</span>
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>Dashboard web</span>
                    <span class="ea-pf-stack__item"><span class="ea-pf-stack__dot"></span>Google Forms · Sheets</span>
                </div>

                <div class="ea-pf-metrics">
                    <div class="ea-pf-metric">
                        <span class="ea-pf-metric__num">4</span>
                        <span class="ea-pf-metric__label">Variables monitoreadas</span>
                        <span class="ea-pf-metric__hint">Temperatura · Humedad · CO₂ · Calidad</span>
                    </div>
                    <div class="ea-pf-metric">
                        <span class="ea-pf-metric__num">3+</span>
                        <span class="ea-pf-metric__label">Actuadores integrados</span>
                        <span class="ea-pf-metric__hint">Aire IR · Aromatizador · LED</span>
                    </div>
                    <div class="ea-pf-metric">
                        <span class="ea-pf-metric__num">24/7</span>
                        <span class="ea-pf-metric__label">Monitoreo continuo</span>
                        <span class="ea-pf-metric__hint">Lecturas cada pocos segundos</span>
                    </div>
                    <div class="ea-pf-metric">
                        <span class="ea-pf-metric__num">∞</span>
                        <span class="ea-pf-metric__label">Ambientes posibles</span>
                        <span class="ea-pf-metric__hint">Hogar · Aula · Oficina · Custom</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             04 · Análisis de mercado — Research dashboard (PRIORITARIO)
             ============================================================ -->
        <section class="ea-pf-section" id="analisis-mercado" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">04</span>
                        <span class="ea-pf-eyebrow">TP Nº 2 · Emprendimientos</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Análisis de <em>mercado</em>.</h2>
                        <p class="ea-pf-lede">
                            Validar decisiones del producto Eden Air con datos reales: utilidad, diseño,
                            aceptación, decisión de uso o compra y enfoque sustentable.
                        </p>
                    </div>
                </header>

                <!-- Status row dashboard -->
                <div class="ea-pf-dash__row" style="margin-bottom: clamp(20px,3vw,28px);">
                    <div class="ea-pf-stat ea-pf-stat--accent">
                        <span class="ea-pf-stat__label">Tipo de encuesta</span>
                        <span class="ea-pf-stat__value">Abierta</span>
                        <span class="ea-pf-stat__sub">Google Forms · enlace público</span>
                    </div>
                    <div class="ea-pf-stat">
                        <span class="ea-pf-stat__label">Inicio</span>
                        <span class="ea-pf-stat__value">Miércoles 6 de mayo</span>
                        <span class="ea-pf-stat__sub">Día de apertura</span>
                    </div>
                    <div class="ea-pf-stat">
                        <span class="ea-pf-stat__label">Cierre</span>
                        <span class="ea-pf-stat__value">Miércoles 13 de mayo</span>
                        <span class="ea-pf-stat__sub">Día de corte</span>
                    </div>
                    <div class="ea-pf-stat">
                        <span class="ea-pf-stat__label">Mínimo esperado</span>
                        <span class="ea-pf-stat__value">10 respuestas</span>
                        <span class="ea-pf-stat__sub">Umbral de la consigna</span>
                    </div>
                </div>

                <div class="ea-pf-dash">
                    <!-- A · Objetivo -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">A</span>
                            <h3 class="ea-pf-block__title">Objetivo de la encuesta</h3>
                        </div>
                        <article class="ea-pf-card">
                            <p class="ea-pf-card__text">
                                Obtener información útil para mejorar el <strong>diseño</strong>, la
                                <strong>utilidad percibida</strong>, la <strong>aceptación</strong>, la posible
                                <strong>decisión de uso o compra</strong> y el <strong>enfoque sustentable</strong>
                                de Eden Air.
                            </p>
                        </article>
                    </div>

                    <!-- B · Producto evaluado -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">B</span>
                            <h3 class="ea-pf-block__title">Producto evaluado</h3>
                        </div>
                        <article class="ea-pf-card">
                            <p class="ea-pf-card__text">
                                El producto evaluado es <strong>Eden Air</strong>, un sistema inteligente de monitoreo
                                y ambientación automática para espacios interiores. Su objetivo es mejorar el confort
                                ambiental mediante sensores, actuadores físicos y una plataforma web que permite
                                visualizar datos, comparar condiciones actuales con valores recomendados y representar
                                acciones automáticas.
                            </p>
                        </article>
                    </div>

                    <!-- C · Público consultado y segmentación -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">C</span>
                            <h3 class="ea-pf-block__title">Público y segmentación</h3>
                        </div>
                        <div class="ea-pf-grid-3">
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Cantidad de respuestas</span>
                                <h3 class="ea-pf-card__title">17 respuestas</h3>
                                <p class="ea-pf-card__text">Se obtuvieron 17 respuestas en total, lo que permite realizar una primera lectura sobre el interés, las necesidades y las preferencias de los posibles usuarios de Eden Air.</p>
                            </article>
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Tipo de público</span>
                                <h3 class="ea-pf-card__title">Comunidad escolar + entorno</h3>
                                <p class="ea-pf-card__text">Estudiantes, docentes, familia, vecinos y contactos del equipo.</p>
                            </article>
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Segmentación prevista</span>
                                <h3 class="ea-pf-card__title">Edad · pertenencia · interés</h3>
                                <p class="ea-pf-card__text">
                                    Edad, relación con la escuela, interés en tecnología, ambiente donde usaría
                                    Eden Air y nivel de importancia del confort ambiental.
                                </p>
                            </article>
                        </div>
                    </div>

                    <!-- D · Cuestionario -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">D</span>
                            <h3 class="ea-pf-block__title">Cuestionario</h3>
                        </div>
                        <div>
                            <p class="ea-pf-lede" style="margin-bottom: 12px;">
                                Estructura preparada para cargar las preguntas reales del Google Forms.
                            </p>
                            <!-- TODO: Cargar preguntas reales de la encuesta -->
                            <ol class="ea-pf-questions">
                                <li>Pregunta 1 · pendiente de cargar.</li>
                                <li>Pregunta 2 · pendiente de cargar.</li>
                                <li>Pregunta 3 · pendiente de cargar.</li>
                                <li>Pregunta vinculada a sustentabilidad · pendiente de cargar.</li>
                            </ol>
                        </div>
                    </div>

                    <!-- E · Sustainability spotlight -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">E</span>
                            <h3 class="ea-pf-block__title">Sustentabilidad</h3>
                        </div>
                        <div class="ea-pf-sustain">
                            <header class="ea-pf-sustain__head">
                                <span class="ea-pf-sustain__mark" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 3c4 4 8 7 8 12a8 8 0 1 1-16 0c0-5 4-8 8-12Z"/><path d="M8 14c2 2 4 2 6 0"/></svg>
                                </span>
                                <div>
                                    <span class="ea-pf-eyebrow" style="background: rgba(255,255,255,0.55);">Eje ambiental</span>
                                    <h3 class="ea-pf-h ea-pf-h3" style="margin-top: 4px;">La encuesta también pregunta por sustentabilidad.</h3>
                                </div>
                            </header>
                            <p class="ea-pf-lede" style="color: var(--ea-ink-2);">
                                Buscamos saber qué grado de importancia le asignan los encuestados al impacto
                                ambiental y cómo influye en su decisión de uso o compra. A partir de los resultados
                                podremos tomar decisiones concretas sobre Eden Air:
                            </p>
                            <ul class="ea-pf-sustain__list">
                                <li>Optimizar consumo energético del dispositivo.</li>
                                <li>Promover el uso eficiente del aire acondicionado.</li>
                                <li>Diseñar una carcasa durable y reparable.</li>
                                <li>Reducir materiales innecesarios en el prototipo.</li>
                                <li>Comunicar el beneficio ambiental del monitoreo inteligente.</li>
                                <li>Evaluar materiales reciclables si el diseño lo permite.</li>
                            </ul>
                            <p style="font-size: 12.5px; color: var(--ea-mute); margin: 14px 0 0;">
                                Estas decisiones se confirman recién con respuestas reales — no se afirma nada antes.
                            </p>
                        </div>
                    </div>

                    <!-- F · Recolección -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">F</span>
                            <h3 class="ea-pf-block__title">Fechas de recolección</h3>
                        </div>
                        <div>
                            <div class="ea-pf-dates">
                                <div class="ea-pf-date">
                                    <span class="ea-pf-date__label">Inicio</span>
                                    <div class="ea-pf-date__value">Miércoles 6 de mayo</div>
                                </div>
                                <div class="ea-pf-date ea-pf-date--end">
                                    <span class="ea-pf-date__label">Cierre</span>
                                    <div class="ea-pf-date__value">Miércoles 13 de mayo</div>
                                </div>
                            </div>
                            <p style="font-size: 13px; color: var(--ea-ink-2); margin-top: 12px;">
                                La etapa de recolección de datos se encuentra finalizada. A partir de las 17 respuestas obtenidas, se realiza el análisis de mercado para validar el interés, la utilidad, la aceptación y los aspectos sustentables del producto. Estado: <span class="ea-pf-status is-ready">Finalizada</span>
                            </p>
                        </div>
                    </div>

                    <!-- G · Link + pipeline técnico -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">G</span>
                            <h3 class="ea-pf-block__title">Integración técnica</h3>
                        </div>
                        <div>
                            <div class="ea-pf-form-link">
                                <span class="ea-pf-card__eyebrow">Enlace público del Google Forms</span>
                                <a href="https://docs.google.com/forms/d/e/1FAIpQLSfP5dDLkh80dF5tSIptzy2ySpS2VcnGI5H2iy8lT40c1n5HaQ/viewform?usp=sharing&amp;ouid=101966553140147513526"
                                   target="_blank" rel="noopener noreferrer"
                                   class="ea-button ea-button-secondary" style="margin-top: 10px; display: inline-flex;">
                                    Ver encuesta en Google Forms
                                </a>
                            </div>

                            <h4 class="ea-pf-h ea-pf-h3" style="margin-top: 22px; margin-bottom: 4px;">Pipeline de datos</h4>
                            <p style="font-size: 13.5px; color: var(--ea-ink-2); margin: 0 0 6px;">
                                Forms recolecta respuestas. Sheets las almacena. Un importador protegido las trae
                                a MySQL y los endpoints alimentan los gráficos del portfolio con Chart.js.
                            </p>
                            <div class="ea-pf-pipeline">
                                <div class="ea-pf-pipeline__node"><span class="ea-pf-pipeline__num">01</span><span class="ea-pf-pipeline__name">Google Forms</span></div>
                                <div class="ea-pf-pipeline__node"><span class="ea-pf-pipeline__num">02</span><span class="ea-pf-pipeline__name">Google Sheets</span></div>
                                <div class="ea-pf-pipeline__node"><span class="ea-pf-pipeline__num">03</span><span class="ea-pf-pipeline__name">CI4 · importador</span></div>
                                <div class="ea-pf-pipeline__node"><span class="ea-pf-pipeline__num">04</span><span class="ea-pf-pipeline__name">MySQL · form_answers</span></div>
                                <div class="ea-pf-pipeline__node"><span class="ea-pf-pipeline__num">05</span><span class="ea-pf-pipeline__name">Chart.js · gráficos</span></div>
                            </div>
                            <!-- TODO: Definir columnas reales de form_answers -->
                            <!-- TODO: Conectar Google Sheets / MySQL / Chart.js cuando estén definidas las columnas reales de form_answers -->
                            <p style="font-size: 12px; color: var(--ea-mute); margin-top: 12px;">
                                Privacidad: los endpoints públicos solo devuelven métricas agregadas — no exponen emails ni nombres.
                            </p>
                        </div>
                    </div>

                    <!-- H · Gráficos -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">H</span>
                            <h3 class="ea-pf-block__title">Gráficos de respuestas</h3>
                        </div>
                        <div>
                            <!-- TODO: Reemplazar estos datos manuales por datos importados desde Google Sheets/MySQL cuando esté lista la integración -->
                            <div class="ea-pf-charts">

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Tipo de espacio habitual</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:200px;">
                                        <canvas id="ea-chart-1"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">La mayoría pasa gran parte de su tiempo en el hogar, seguido por espacios educativos. Eden Air puede orientarse principalmente a ambientes cotidianos.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Rango de edad</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:180px;">
                                        <canvas id="ea-chart-2"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">El segmento predominante (70.6%) es de 15 a 20 años, vinculado al entorno escolar y cotidiano.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Incomodidad por temperatura</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:200px;">
                                        <canvas id="ea-chart-3"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">El 88.2% manifiesta sentir incomodidad por la temperatura al menos algunas veces, validando la problemática que Eden Air busca resolver.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Importancia del confort automático</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas · opción duplicada en el formulario</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:220px;">
                                        <canvas id="ea-chart-4"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">Todas las respuestas expresan valoración positiva. Nota: el formulario original contiene una opción "Muy importante" duplicada; ambas se combinaron en el análisis (11 de 17).</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Interés en automatización</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:220px;">
                                        <canvas id="ea-chart-5"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">La mayoría acepta un sistema automático; el resto muestra postura abierta. No se registraron respuestas negativas.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Prioridades en el producto</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:220px;">
                                        <canvas id="ea-chart-6"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">El aspecto más valorado es que el producto sea amable con el medio ambiente, seguido por funciones y utilidad.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Ideas de sustentabilidad</h4>
                                        <span class="ea-pf-chart__meta">7 respuestas abiertas · categorizado</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="padding:18px;align-items:flex-start;">
                                        <div class="ea-pf-chip-row">
                                            <span class="ea-pf-tag">Bajo consumo energético</span>
                                            <span class="ea-pf-tag">Energía renovable · solar</span>
                                            <span class="ea-pf-tag">Reutilización de materiales</span>
                                            <span class="ea-pf-tag">Reducción de gases</span>
                                            <span class="ea-pf-tag">Monitoreo de uso excesivo</span>
                                            <span class="ea-pf-tag">Filtrado · calidad del aire</span>
                                        </div>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">Las ideas más repetidas se relacionan con bajo consumo eléctrico, energías renovables, reutilización de componentes y reducción del impacto ambiental.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Utilidad de la personalización</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:220px;">
                                        <canvas id="ea-chart-8"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">El 94.1% considera útil o muy útil un apartado de personalización, reforzando la necesidad de opciones configurables en la plataforma.</p>
                                </article>

                                <article class="ea-pf-chart">
                                    <header class="ea-pf-chart__head">
                                        <h4>Disposición de precio</h4>
                                        <span class="ea-pf-chart__meta">17 respuestas</span>
                                    </header>
                                    <div class="ea-pf-chart__stage" style="display:block;height:180px;">
                                        <canvas id="ea-chart-9"></canvas>
                                    </div>
                                    <p style="font-size:12.5px;color:var(--ea-mute);margin-top:8px;">La mayoría se ubica entre $40.000 y $80.000. Sirve como referencia para evaluar costos y percepción de valor del producto.</p>
                                </article>

                            </div>
                        </div>
                    </div>

                    <!-- I · Tabulación -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">I</span>
                            <h3 class="ea-pf-block__title">Tabulación de resultados</h3>
                        </div>
                        <div>
                            <div class="ea-pf-table-wrap">
                                <table class="ea-pf-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Segmento</th>
                                            <th>Respuesta resumida</th>
                                            <th>Categoría</th>
                                            <th>Observaciones</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="ea-pf-table-empty">
                                                Todavía no hay respuestas importadas para tabular.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- J · Análisis -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">J</span>
                            <h3 class="ea-pf-block__title">Análisis de resultados</h3>
                        </div>
                        <div class="ea-pf-grid-3">
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Hallazgos</span>
                                <h3 class="ea-pf-card__title">Perfil de los encuestados</h3>
                                <p class="ea-pf-card__text">
                                    La mayoría pasa gran parte de su tiempo en el hogar y en espacios educativos, coincidiendo con los ambientes donde Eden Air tendría mayor utilidad. El grupo etario predominante es de 15 a 20 años, por lo que los resultados reflejan principalmente la opinión de usuarios jóvenes vinculados al entorno escolar y cotidiano.
                                </p>
                            </article>
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Confort y automatización</span>
                                <h3 class="ea-pf-card__title">Necesidad real confirmada</h3>
                                <p class="ea-pf-card__text">
                                    El 88.2% manifestó sentir incomodidad por la temperatura al menos algunas veces, validando la problemática que Eden Air aborda. Además, el 64.7% respondió que sí le gustaría un sistema automático y el 35.3% respondió tal vez. No se registraron respuestas negativas.
                                </p>
                            </article>
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Personalización</span>
                                <h3 class="ea-pf-card__title">Configurabilidad valorada</h3>
                                <p class="ea-pf-card__text">
                                    El 94.1% considera útil o muy útil contar con un apartado de personalización. Esto valida la necesidad de que la plataforma no solo muestre datos, sino que permita adaptar el funcionamiento según el tipo de ambiente y las preferencias del usuario.
                                </p>
                            </article>
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Prioridades · precio</span>
                                <h3 class="ea-pf-card__title">Sustentabilidad primero</h3>
                                <p class="ea-pf-card__text">
                                    El aspecto más elegido como prioridad fue que el producto sea amable con el medio ambiente, seguido por funciones y utilidad. En cuanto al precio, la mayoría se concentra entre $40.000 y $80.000, dato útil como referencia para evaluar costos y estrategia de precio.
                                </p>
                            </article>
                            <article class="ea-pf-card">
                                <span class="ea-pf-card__eyebrow">Sustentabilidad</span>
                                <h3 class="ea-pf-card__title">Impacto ambiental percibido</h3>
                                <p class="ea-pf-card__text">
                                    Las respuestas abiertas confirman que la sustentabilidad es relevante para los encuestados. Las ideas más mencionadas fueron bajo consumo eléctrico, energías renovables, reutilización de componentes y reducción del impacto ambiental, orientando decisiones concretas sobre el diseño del producto.
                                </p>
                            </article>
                        </div>
                    </div>

                    <!-- K · Conclusiones + decisiones -->
                    <div class="ea-pf-block">
                        <div class="ea-pf-block__label">
                            <span class="ea-pf-block__letter">K</span>
                            <h3 class="ea-pf-block__title">Conclusiones y decisiones</h3>
                        </div>
                        <div>
                            <article class="ea-pf-card" style="margin-bottom: clamp(16px, 2.5vw, 24px);">
                                <span class="ea-pf-card__eyebrow">Conclusión general</span>
                                <h3 class="ea-pf-card__title">Resultados y orientación del proyecto</h3>
                                <p class="ea-pf-card__text">
                                    A partir de los resultados, Eden Air tiene una buena aceptación inicial entre los encuestados. La incomodidad por la temperatura en espacios interiores aparece como problemática frecuente, y la mayoría muestra interés en soluciones automáticas que ayuden a mantener un ambiente cómodo y saludable.
                                </p>
                                <p class="ea-pf-card__text" style="margin-top: 8px;">
                                    Una de las principales decisiones es mantener el enfoque del producto en el confort ambiental, la automatización y la visualización clara de datos. También resulta conveniente fortalecer el apartado de personalización, ya que el 94.1% lo considera útil o muy útil.
                                </p>
                                <p class="ea-pf-card__text" style="margin-top: 8px;">
                                    La sustentabilidad debe ocupar un lugar importante dentro de la propuesta de valor. Las respuestas abiertas mencionan bajo consumo energético, energías renovables y reutilización de componentes. En cuanto al precio, los rangos más aceptados se encuentran entre $40.000 y $80.000, útil como referencia para evaluar costos y estrategia de precio.
                                </p>
                            </article>

                            <h4 class="ea-pf-h ea-pf-h3" style="margin-bottom: 14px;">Decisiones posibles</h4>
                            <div class="ea-pf-grid-2">
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Función core</span>
                                    <h3 class="ea-pf-card__title">Priorizar la medición ambiental</h3>
                                    <p class="ea-pf-card__text">Temperatura, humedad y calidad del aire como variables centrales del dispositivo.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Automatización</span>
                                    <h3 class="ea-pf-card__title">Mantener la automatización como función central</h3>
                                    <p class="ea-pf-card__text">La mayoría acepta sistemas automáticos; reforzar este diferencial en la comunicación del producto.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Plataforma web</span>
                                    <h3 class="ea-pf-card__title">Fortalecer la personalización</h3>
                                    <p class="ea-pf-card__text">El 94.1% considera útil o muy útil poder configurar parámetros según el ambiente y las preferencias del usuario.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Comunicación</span>
                                    <h3 class="ea-pf-card__title">Comunicar el beneficio sustentable</h3>
                                    <p class="ea-pf-card__text">El impacto ambiental fue la prioridad más elegida; debe incluirse en la propuesta de valor y los materiales de presentación.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Diseño físico</span>
                                    <h3 class="ea-pf-card__title">Evaluar consumo energético bajo</h3>
                                    <p class="ea-pf-card__text">Optimizar el firmware y hardware para minimizar el consumo eléctrico del dispositivo.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Hardware</span>
                                    <h3 class="ea-pf-card__title">Diseñar componentes reemplazables</h3>
                                    <p class="ea-pf-card__text">Facilitar la reparación y el reemplazo de piezas para extender la vida útil del dispositivo.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Precio de referencia</span>
                                    <h3 class="ea-pf-card__title">Rango $40.000 – $80.000</h3>
                                    <p class="ea-pf-card__text">Usar este rango como punto de partida para evaluar costos, margen y percepción de valor del producto.</p>
                                </article>
                                <article class="ea-pf-card">
                                    <span class="ea-pf-card__eyebrow">Foco del producto</span>
                                    <h3 class="ea-pf-card__title">Funcionalidad sobre estética</h3>
                                    <p class="ea-pf-card__text">La estética fue poco priorizada frente a la funcionalidad y la sustentabilidad; el diseño debe acompañar sin dominar.</p>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             05 · Análisis de la competencia
             ============================================================ -->
        <section class="ea-pf-section" id="analisis-competencia" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">05</span>
                        <span class="ea-pf-eyebrow">Competencia</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Análisis de la <em>competencia</em>.</h2>
                        <p class="ea-pf-lede">Cuadro comparativo de doble entrada frente a los principales referentes del rubro, seguido del análisis y las ventajas competitivas de Eden Air.</p>
                    </div>
                </header>

                <?php
                // --- Datos de la comparativa (una sola fuente para tabla y acordeón) ---
                // Los precios y datos de mercado son estimativos / de referencia.
                $compCriterios = [
                    'localizacion'    => 'Localización',
                    'productos'       => 'Productos y servicios',
                    'precio'          => 'Precio de venta *',
                    'pagos'           => 'Medios de pago',
                    'materia'         => 'Materia prima',
                    'marca'           => 'Marca',
                    'tecnologia'      => 'Tecnología',
                    'embalaje'        => 'Embalaje',
                    'clientes'        => 'Clientes',
                    'atencion'        => 'Atención al cliente',
                    'entrega'         => 'Cumplimiento de entrega',
                    'publicidad'      => 'Publicidad',
                    'fuertes'         => 'Puntos fuertes',
                    'debiles'         => 'Puntos débiles',
                    'sustentabilidad' => 'Sustentabilidad e impacto ambiental',
                    'vs_eden'         => 'Frente a Eden Air',
                ];

                $competidores = [
                    [
                        'name' => 'Xiaomi', 'model' => 'Smart Air Purifier 4', 'eden' => false,
                        'prestacion' => 'Purifica · no mide CO₂', 'precio_ref' => 200,
                        'vals' => [
                            'localizacion'    => 'China · venta global e importación',
                            'productos'       => 'Purificador con filtro HEPA y app Mi Home',
                            'precio'           => 'USD 150 – 250',
                            'pagos'            => 'Tarjeta, MercadoPago (revendedores), transferencia',
                            'materia'          => 'Plástico ABS, filtro HEPA, sensor láser PM2.5',
                            'marca'            => 'Reconocida y masiva',
                            'tecnologia'       => 'Sensor PM2.5, Wi-Fi, app, asistentes de voz',
                            'embalaje'         => 'Caja de cartón con plásticos de protección',
                            'clientes'         => 'Hogares y oficinas, público tecnológico',
                            'atencion'         => 'Vía revendedor / online, en español limitado',
                            'entrega'          => 'Depende del importador; demoras frecuentes',
                            'publicidad'       => 'Marketing digital masivo y marketplaces',
                            'fuertes'          => 'Buena relación precio/calidad y ecosistema',
                            'debiles'          => 'No mide CO₂; filtros de recambio costosos',
                            'sustentabilidad'  => 'Bajo consumo, pero filtros descartables generan residuo',
                            'vs_eden'          => 'Solo purifica: no integra monitoreo + actuadores + dashboard propio',
                        ],
                    ],
                    [
                        'name' => 'Dyson', 'model' => 'Purifier Cool', 'eden' => false,
                        'prestacion' => 'Purifica y mide gases', 'precio_ref' => 575,
                        'vals' => [
                            'localizacion'    => 'Reino Unido / Singapur · presencia oficial',
                            'productos'       => 'Purificador + ventilador con app Dyson Link',
                            'precio'           => 'USD 450 – 700',
                            'pagos'            => 'Tarjeta, cuotas, tienda oficial',
                            'materia'          => 'Policarbonato, filtros HEPA y carbón activado',
                            'marca'            => 'Premium, alto prestigio',
                            'tecnologia'       => 'Sensores de partículas y gases, pantalla, Wi-Fi',
                            'embalaje'         => 'Cartón rígido con espuma de protección',
                            'clientes'         => 'Segmento premium, alto poder adquisitivo',
                            'atencion'         => 'Soporte oficial robusto y garantía',
                            'entrega'          => 'Alto cumplimiento en mercados oficiales',
                            'publicidad'       => 'Branding premium, TV, influencers, retail',
                            'fuertes'          => 'Calidad de fabricación; mide gases; diseño icónico',
                            'debiles'          => 'Precio muy alto; filtros caros; foco solo en purificar',
                            'sustentabilidad'  => 'Eficiencia energética, pero filtros y repuestos caros',
                            'vs_eden'          => 'Más caro y enfocado en purificar; no ambienta por espacio',
                        ],
                    ],
                    [
                        'name' => 'Netatmo', 'model' => 'Healthy Home Coach', 'eden' => false,
                        'prestacion' => 'Solo mide e informa', 'precio_ref' => 115,
                        'vals' => [
                            'localizacion'    => 'Francia (grupo Legrand)',
                            'productos'       => 'Monitor ambiental (CO₂, temp, humedad, ruido)',
                            'precio'           => 'USD 100 – 130',
                            'pagos'            => 'Tarjeta, tienda oficial, marketplaces',
                            'materia'          => 'Aluminio y plástico, sensores ambientales',
                            'marca'            => 'Reconocida en domótica',
                            'tecnologia'       => 'Sensores CO₂/temp/humedad/ruido, Wi-Fi, HomeKit',
                            'embalaje'         => 'Cartón reciclable minimalista',
                            'clientes'         => 'Hogares conectados / smart home',
                            'atencion'         => 'Soporte oficial y comunidad',
                            'entrega'          => 'Buena en mercados con presencia',
                            'publicidad'       => 'Marketing digital, ecosistema Apple/Google',
                            'fuertes'          => 'Mide CO₂/temp/humedad e integra domótica',
                            'debiles'          => 'Solo monitorea: no controla actuadores',
                            'sustentabilidad'  => 'Bajo consumo, reciclable, sin filtros (poco residuo)',
                            'vs_eden'          => 'Solo informa; Eden además decide y acciona automáticamente',
                        ],
                    ],
                    [
                        'name' => 'Airthings', 'model' => 'View Plus', 'eden' => false,
                        'prestacion' => 'Solo mide e informa', 'precio_ref' => 275,
                        'vals' => [
                            'localizacion'    => 'Noruega · envío internacional',
                            'productos'       => 'Monitor de calidad de aire (radón, CO₂, PM, COV)',
                            'precio'           => 'USD 250 – 300',
                            'pagos'            => 'Tarjeta, tienda oficial',
                            'materia'          => 'Plástico (parcial reciclado), multisensor',
                            'marca'            => 'Especialista en calidad de aire',
                            'tecnologia'       => 'Multisensor (radón/COV/CO₂/PM2.5), Wi-Fi, e-ink',
                            'embalaje'         => 'Cartón reciclable',
                            'clientes'         => 'Hogares/oficinas con foco en salud',
                            'atencion'         => 'Soporte oficial y dashboards web',
                            'entrega'          => 'Buena (envío internacional)',
                            'publicidad'       => 'Nicho salud/aire, contenido técnico',
                            'fuertes'          => 'Medición muy completa (incluye radón y COV)',
                            'debiles'          => 'Solo monitorea; precio alto; no actúa',
                            'sustentabilidad'  => 'Batería de larga duración, reciclable, sin filtros',
                            'vs_eden'          => 'Mide mucho pero es pasivo; Eden cierra sensar→decidir→actuar',
                        ],
                    ],
                    [
                        'name' => 'Eden Air', 'model' => 'Eden Air Core', 'eden' => true,
                        'prestacion' => 'Mide, decide y actúa', 'precio_ref' => null,
                        'vals' => [
                            'localizacion'    => 'Río Tercero, Córdoba (Argentina) · proyecto local',
                            'productos'       => 'Monitoreo + ambientación automática (dispositivo + dashboard)',
                            'precio'           => $precioVentaTxt . ' ARS · pago único (todo incluido)',
                            'pagos'            => 'MercadoPago; preparado para tarjeta/transferencia',
                            'materia'          => 'ESP32, sensores, actuadores y carcasa reutilizable',
                            'marca'            => 'Identidad propia, tecnológica y sustentable',
                            'tecnologia'       => 'ESP32 + sensores + actuadores + web propia, multi-dispositivo, API',
                            'embalaje'         => 'Materiales reciclables y reutilizables',
                            'clientes'         => 'Hogares, aulas, oficinas y espacios locales',
                            'atencion'         => 'Cercana, en español y directa del equipo',
                            'entrega'          => 'Producción y entrega local (cercanía)',
                            'publicidad'       => 'Web propia, portfolio y enfoque educativo/sustentable',
                            'fuertes'          => 'Sensa, decide y actúa; dashboard propio; perfiles por ambiente; multi-dispositivo; accesible',
                            'debiles'          => 'Marca nueva; escala inicial; hardware en etapa de prototipo',
                            'sustentabilidad'  => 'Bajo consumo, componentes reutilizables y reparables, sin filtros descartables',
                            'vs_eden'          => '— (producto de referencia)',
                        ],
                    ],
                ];
                // Nota al pie del cuadro: va igual debajo de la tabla y del acordeón.
                $compNota = 'Precios de la competencia <strong>estimativos / de referencia</strong>, '
                    . 'en dólares (importación y tiendas oficiales). El de Eden Air es el precio de '
                    . 'venta local calculado en <a href="#analisis-costos">07 · Análisis de costos</a>.';
                ?>

                <!-- Tabla comparativa (scroll horizontal en pantallas chicas) -->
                <div class="ea-comp">
                    <div class="ea-comp-scroll" role="region" aria-label="Cuadro comparativo de competencia" tabindex="0">
                        <table class="ea-comp-table">
                            <caption class="ea-comp-caption">Cuadro comparativo de doble entrada — competidores vs. Eden Air</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="ea-comp-corner">Criterio</th>
                                    <?php foreach ($competidores as $c): ?>
                                        <th scope="col" class="<?= $c['eden'] ? 'ea-comp-eden' : '' ?>">
                                            <span class="ea-comp-brand"><?= esc($c['name']) ?></span>
                                            <span class="ea-comp-model"><?= esc($c['model']) ?></span>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compCriterios as $key => $label): ?>
                                    <tr>
                                        <th scope="row"><?= esc($label) ?></th>
                                        <?php foreach ($competidores as $c): ?>
                                            <td class="<?= $c['eden'] ? 'ea-comp-eden' : '' ?>"><?= esc($c['vals'][$key] ?? '—') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="ea-comp-note">* <?= $compNota ?></p>
                </div>

                <!-- Versión móvil: acordeón por competidor -->
                <div class="ea-comp-cards" aria-label="Comparativa por competidor">
                    <?php foreach ($competidores as $i => $c): ?>
                        <details class="ea-comp-acc <?= $c['eden'] ? 'is-eden' : '' ?>" <?= $c['eden'] ? 'open' : '' ?>>
                            <summary>
                                <span class="ea-comp-acc-brand"><?= esc($c['name']) ?></span>
                                <span class="ea-comp-acc-model"><?= esc($c['model']) ?></span>
                            </summary>
                            <dl class="ea-comp-acc-list">
                                <?php foreach ($compCriterios as $key => $label): ?>
                                    <div><dt><?= esc($label) ?></dt><dd><?= esc($c['vals'][$key] ?? '—') ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                        </details>
                    <?php endforeach; ?>
                    <p class="ea-comp-note">* <?= $compNota ?></p>
                </div>

                <!-- Análisis y conclusión (Parte B del TP) -->
                <div class="ea-pf-grid-2 ea-comp-analysis">
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Diferenciación</span>
                        <h3 class="ea-pf-card__title">Qué hace distinto a Eden Air</h3>
                        <p class="ea-pf-card__text">Mientras la competencia <em>solo purifica</em> o <em>solo monitorea</em>, Eden Air integra el ciclo completo: <strong>sensa, decide y actúa</strong> sobre el ambiente, y lo administra desde un dashboard propio con perfiles por espacio y soporte para varios dispositivos.</p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Razones de compra</span>
                        <h3 class="ea-pf-card__title">Por qué elegir Eden Air</h3>
                        <ul class="ea-pf-criteria">
                            <li>Sistema integral a un precio accesible</li>
                            <li>Atención cercana y en español</li>
                            <li>Configuración de ambientes incluida</li>
                            <li>Varios dispositivos en una sola cuenta</li>
                            <li>Enfoque sustentable y de bajo consumo</li>
                        </ul>
                    </article>
                </div>

                <div class="ea-comp-advantage">
                    <article class="ea-comp-advantage-card">
                        <span class="ea-pf-card__eyebrow">Ventajas competitivas</span>
                        <h3 class="ea-pf-card__title">Lo que hoy el cliente no tiene</h3>
                        <ul class="ea-comp-checks">
                            <li>Un único sistema que <strong>mide y además actúa</strong> sobre el ambiente.</li>
                            <li>Automatización ambiental por espacio (confort, ahorro, ventilación).</li>
                            <li>Perfiles personalizados por ambiente, <strong>sin costo extra</strong>.</li>
                            <li>Vinculación directa: se escanea un QR y el equipo se da de alta solo.</li>
                            <li>Arquitectura preparada para crecer (API y multi-dispositivo).</li>
                        </ul>
                    </article>
                    <article class="ea-comp-advantage-card ea-comp-sustain">
                        <span class="ea-pf-card__eyebrow">Sustentabilidad como ventaja</span>
                        <h3 class="ea-pf-card__title">Menor impacto, más eficiencia</h3>
                        <ul class="ea-comp-checks">
                            <li><strong>Ahorro energético:</strong> los actuadores trabajan solo cuando hacen falta.</li>
                            <li><strong>Reutilización:</strong> componentes modulares y reparables.</li>
                            <li><strong>Menos residuos:</strong> sin filtros descartables periódicos.</li>
                            <li><strong>Materiales reciclables</strong> en carcasa y embalaje.</li>
                            <li><strong>Uso eficiente</strong> de recursos según necesidad real del ambiente.</li>
                        </ul>
                    </article>
                </div>

                <div class="ea-comp-conclusion">
                    <p><strong>Conclusión.</strong> Frente a purificadores (Xiaomi, Dyson) y monitores ambientales (Netatmo, Airthings), Eden Air ocupa un espacio propio: combina <em>monitoreo + automatización + dashboard</em> en un sistema local, accesible y sustentable. Su ventaja competitiva surge de ofrecer, en un solo producto y a bajo costo, lo que hoy el cliente necesitaría resolver con varios dispositivos separados.</p>
                </div>
            </div>
        </section>

        <!-- ============================================================
             06 · Plan operativo
             ============================================================ -->
        <section class="ea-pf-section" id="plan-operativo" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">06</span>
                        <span class="ea-pf-eyebrow">Operación</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Plan <em>operativo</em>.</h2>
                        <p class="ea-pf-lede">Etapas, recursos y responsables. Estructura preparada para definir el cronograma final.</p>
                    </div>
                </header>

                <div class="ea-pf-grid-3">
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Objetivos</span>
                        <h3 class="ea-pf-card__title">Qué buscamos lograr</h3>
                        <p class="ea-pf-card__text">Diseñar, construir, validar y presentar Eden Air como tesina técnica y proyecto emprendedor.</p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Recursos</span>
                        <h3 class="ea-pf-card__title">Hardware y software</h3>
                        <p class="ea-pf-card__text">ESP32, sensores, actuadores, servidor local (XAMPP), MySQL, materiales del prototipo.</p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Responsables</span>
                        <h3 class="ea-pf-card__title">Equipo</h3>
                        <p class="ea-pf-card__text">Roles definidos en "Quiénes somos". Nombres reales pendientes de carga.</p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Materiales</span>
                        <h3 class="ea-pf-card__title">Prototipado</h3>
                        <p class="ea-pf-card__text">Componentes electrónicos, carcasa, cableado y herramientas de medición.</p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Pruebas</span>
                        <h3 class="ea-pf-card__title">Validación final</h3>
                        <p class="ea-pf-card__text">Pruebas en ambientes reales y control de calidad antes de la expo.</p>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Tiempos</span>
                        <h3 class="ea-pf-card__title">Cronograma definitivo</h3>
                        <p class="ea-pf-card__text">Se completará con la planificación final del año.</p>
                        <span class="ea-pf-todo">Pendiente</span>
                    </article>
                </div>

                <!-- TODO: Completar plan operativo con cronograma definitivo -->
                <div class="ea-pf-stages">
                    <article class="ea-pf-stage">
                        <span class="ea-pf-stage__num">Etapa 01</span>
                        <div>
                            <h4 class="ea-pf-stage__title">Investigación</h4>
                            <p class="ea-pf-stage__lede">Análisis de mercado, competencia y validación inicial con encuesta.</p>
                        </div>
                        <span class="ea-pf-status is-progress">En curso</span>
                    </article>
                    <article class="ea-pf-stage">
                        <span class="ea-pf-stage__num">Etapa 02</span>
                        <div>
                            <h4 class="ea-pf-stage__title">Diseño del sistema</h4>
                            <p class="ea-pf-stage__lede">Arquitectura web, esquema MySQL, planos del dispositivo y reglas de automatización.</p>
                        </div>
                        <span class="ea-pf-status is-active">Avanzada</span>
                    </article>
                    <article class="ea-pf-stage">
                        <span class="ea-pf-stage__num">Etapa 03</span>
                        <div>
                            <h4 class="ea-pf-stage__title">Desarrollo web</h4>
                            <p class="ea-pf-stage__lede">Plataforma en CodeIgniter 4, dashboard, login, panel y portfolio público.</p>
                        </div>
                        <span class="ea-pf-status is-active">En desarrollo</span>
                    </article>
                    <article class="ea-pf-stage">
                        <span class="ea-pf-stage__num">Etapa 04</span>
                        <div>
                            <h4 class="ea-pf-stage__title">Integración ESP32</h4>
                            <p class="ea-pf-stage__lede">Firmware, sensores, actuadores y comunicación con la API REST.</p>
                        </div>
                        <span class="ea-pf-status is-pending">Pendiente</span>
                    </article>
                    <article class="ea-pf-stage">
                        <span class="ea-pf-stage__num">Etapa 05</span>
                        <div>
                            <h4 class="ea-pf-stage__title">Pruebas</h4>
                            <p class="ea-pf-stage__lede">Validación en ambientes reales, control de calidad y ajustes finales.</p>
                        </div>
                        <span class="ea-pf-status is-pending">Pendiente</span>
                    </article>
                    <article class="ea-pf-stage">
                        <span class="ea-pf-stage__num">Etapa 06</span>
                        <div>
                            <h4 class="ea-pf-stage__title">Expo demostrativa</h4>
                            <p class="ea-pf-stage__lede">Presentación con QR a la profesora y posibles usuarios finales.</p>
                        </div>
                        <span class="ea-pf-status is-pending">Pendiente</span>
                    </article>
                </div>
            </div>
        </section>

        <!-- ============================================================
             07 · Análisis de costos · TP Nº 5
             Se lee como una historia: el precio, el laboratorio para jugar
             con los números, de qué está hecho, cómo se fijó. El desarrollo
             académico completo queda plegado al final (<details>) para no
             tapar la sección con tablas.
             ============================================================ -->
        <section class="ea-pf-section ea-cost" id="analisis-costos" data-reveal>
            <div class="ea-page">
                <header class="ea-pf-section-head">
                    <div class="ea-pf-section-head__meta">
                        <span class="ea-pf-section-num">07</span>
                        <span class="ea-pf-eyebrow">TP Nº 5 · Emprendimientos</span>
                    </div>
                    <div>
                        <h2 class="ea-pf-h ea-pf-h2">Análisis de <em>costos</em>.</h2>
                        <p class="ea-pf-lede">Qué cuesta fabricar un Eden Air, a cuánto se vende y desde qué unidad empieza a dar ganancia.</p>
                    </div>
                </header>

                <?php
                // --- Cifras base del análisis (única fuente de toda la sección) ---
                // Cambiás un número acá y se recalculan tablas, gráficos y textos.
                $costo = [
                    'unidades'     => 15,             // unidades a producir por mes
                    'horas_unidad' => 8,              // horas de producción por unidad
                    'valor_hora'   => 5000,           // $ de la hora de mano de obra
                    'mpd_unidad'   => 220000,         // $ de materia prima directa por unidad
                    'precio_venta' => $precioVenta,   // $ definido arriba de todo (lo comparte la landing)
                ];

                // Materia prima directa, agrupada por subsistema del equipo.
                $costoInsumos = [
                    'Cerebro y sensores' => [
                        [1, 'Placa ESP32 DevKitC-32 (módulo ESP32-WROOM-32)'],
                        [1, 'Sensor SCD41 de CO₂, temperatura y humedad'],
                        [1, 'Módulo sensor MQ-135 de calidad de aire'],
                        [1, 'Receptor infrarrojo VS1838B'],
                    ],
                    'Actuadores' => [
                        [1, 'Módulo relé optoacoplado de 2 canales, 5 V'],
                        [1, 'Mini ventilador de 5 V'],
                        [1, 'Módulo atomizador ultrasónico de 5 V con placa controladora'],
                        [1, 'Emisor infrarrojo KY-005'],
                        [3, 'LEDs de 5 mm (verde, rojo y azul)'],
                    ],
                    'Alimentación' => [
                        [1, 'Fuente switching regulada de 5 V y 3 A'],
                        [1, 'Adaptador jack hembra 5,5 × 2,1 mm a bornera'],
                        [1, 'Bloque distribuidor de alimentación de 2 polos'],
                        [1, 'Interruptor SPST para 5 V y 3 A'],
                        [2, 'Adaptadores USB-A hembra a bornera'],
                        [1, 'Cable USB de datos para programar la ESP32'],
                        [1, 'Cable USB para el atomizador (USB-A a USB-C)'],
                    ],
                    'Electrónica de soporte' => [
                        [1, 'Protoboard grande'],
                        [3, 'Kits de cables Dupont'],
                        [3, 'Resistencias de 220 Ω y 330 Ω para los LEDs'],
                        [1, 'Resistencia de 20 kΩ del divisor del MQ-135'],
                        [1, 'Resistencia de 10 kΩ del divisor del MQ-135'],
                        [1, 'Transistor NPN BC337 o 2N2222'],
                        [1, 'Resistencia de 1 kΩ para la base del transistor'],
                        [1, 'Resistencia de 220 Ω del emisor infrarrojo'],
                    ],
                    'Montaje' => [
                        [1, 'Base de MDF o acrílico'],
                        ['-',  'Cable flexible rojo y negro'],
                        ['-',  'Tubos termocontraíbles de varios diámetros'],
                        ['-',  'Separadores, tornillos y tuercas'],
                        ['-',  'Precintos y sujetacables'],
                    ],
                ];

                // Costos fijos del mes: agregás o sacás filas y el total se recalcula solo.
                $costoFijos = [
                    'Sueldo administrativo (part time)'           => 400000,
                    'Publicidad y gestión de redes'               => 80000,
                    'Energía eléctrica'                           => 60000,
                    'Servicio de internet'                        => 50000,
                    'Amortización de herramientas'                => 35000,
                    'Hosting y dominio del sistema web'           => 25000,
                ];

                // --- Todo lo demás se deriva: ningún número escrito dos veces ---
                $c = $costo;
                $c['mpd_total']    = $c['mpd_unidad'] * $c['unidades'];
                $c['horas_mes']    = $c['horas_unidad'] * $c['unidades'];
                $c['mod_unidad']   = $c['horas_unidad'] * $c['valor_hora'];
                $c['mod_total']    = $c['horas_mes'] * $c['valor_hora'];
                $c['var_total']    = $c['mpd_total'] + $c['mod_total'];
                $c['fijo_total']   = array_sum($costoFijos);
                $c['total']        = $c['var_total'] + $c['fijo_total'];
                $c['var_unit']     = $c['var_total'] / $c['unidades'];
                $c['fijo_unit']    = $c['fijo_total'] / $c['unidades'];
                $c['unit']         = $c['total'] / $c['unidades'];
                $c['ganancia']     = $c['precio_venta'] - $c['unit'];
                $c['margen_costo'] = $c['ganancia'] / $c['unit'] * 100;
                $c['margen_venta'] = $c['ganancia'] / $c['precio_venta'] * 100;
                $c['contrib']      = $c['precio_venta'] - $c['var_unit'];
                $c['punto']        = $c['fijo_total'] / $c['contrib'];
                $c['punto_ent']    = (int) ceil($c['punto']);
                $c['punto_pesos']  = $c['punto_ent'] * $c['precio_venta'];
                $c['punto_pct']    = $c['punto_ent'] / $c['unidades'] * 100;
                $c['ingresos']     = $c['unidades'] * $c['precio_venta'];
                $c['utilidad']     = $c['ingresos'] - $c['total'];
                $c['peso_var']     = $c['var_unit'] / $c['unit'] * 100;
                $c['peso_fijo']    = $c['fijo_unit'] / $c['unit'] * 100;
                $c['peso_mpd']     = $c['mpd_unidad'] / $c['unit'] * 100;
                $c['insumos_qty']  = array_sum(array_map('count', $costoInsumos));

                // Formato: $1.234,56 (decimales solo cuando la cifra no es redonda).
                $pesos = static function ($n, ?int $dec = null): string {
                    $n   = round((float) $n, 2);
                    $dec = $dec ?? (fmod($n, 1) === 0.0 ? 0 : 2);
                    return '$' . number_format($n, $dec, ',', '.');
                };
                $num = static fn ($n, int $dec = 2): string => number_format((float) $n, $dec, ',', '.');
                $pct = static fn ($n, int $dec = 1): string => number_format((float) $n, $dec, ',', '.') . ' %';

                // A dónde va cada peso del precio de venta (suma 100 %).
                $costoReparto = [
                    ['Materia prima', $c['mpd_unidad'], 'mpd'],
                    ['Mano de obra',  $c['mod_unidad'], 'mod'],
                    ['Costos fijos',  $c['fijo_unit'],  'fijo'],
                    ['Ganancia',      $c['ganancia'],   'gan'],
                ];

                // Relevamiento de precios: reusa los competidores de la sección 05.
                $costoRivales  = array_values(array_filter($competidores, static fn ($x) => ! $x['eden']));
                $costoPromedio = array_sum(array_column($costoRivales, 'precio_ref')) / max(1, count($costoRivales));

                // Semilla para el simulador: el JS arranca de acá y puede volver.
                $costoSemilla = json_encode([
                    'unidades'   => $c['unidades'],
                    'horas'      => $c['horas_unidad'],
                    'valorHora'  => $c['valor_hora'],
                    'mpd'        => $c['mpd_unidad'],
                    'precio'     => $c['precio_venta'],
                    'fijos'      => $c['fijo_total'],
                ], JSON_UNESCAPED_UNICODE);
                ?>

                <!-- 1 · El número, y a dónde va cada peso -->
                <div class="ea-cost-open" data-reveal-child>
                    <div class="ea-cost-open__main">
                        <span class="ea-pf-eyebrow">Precio de venta</span>
                        <p class="ea-cost-open__amount" data-cost-count="<?= esc($c['precio_venta']) ?>"><?= esc($pesos($c['precio_venta'])) ?></p>
                        <p class="ea-cost-open__sub">
                            Por equipo, pago único, con la plataforma web incluida. Cubre
                            <strong><?= esc($pesos($c['unit'])) ?></strong> de costo y deja
                            <strong><?= esc($pesos($c['ganancia'])) ?></strong> de ganancia.
                        </p>
                    </div>

                    <figure class="ea-cost-open__split">
                        <figcaption>A dónde va cada peso que paga el cliente</figcaption>
                        <div class="ea-cost-bar" role="img" aria-label="<?php
                            $partes = [];
                            foreach ($costoReparto as [$etiqueta, $importe]) {
                                $partes[] = $etiqueta . ' ' . $pct($importe / $c['precio_venta'] * 100);
                            }
                            echo esc(implode(', ', $partes), 'attr');
                        ?>">
                            <?php foreach ($costoReparto as [$etiqueta, $importe, $clave]): ?>
                                <span class="ea-cost-bar__seg is-<?= esc($clave) ?>"
                                      style="--w: <?= esc(round($importe / $c['precio_venta'] * 100, 3)) ?>%"></span>
                            <?php endforeach; ?>
                        </div>
                        <ul class="ea-cost-key">
                            <?php foreach ($costoReparto as [$etiqueta, $importe, $clave]): ?>
                                <li class="is-<?= esc($clave) ?>">
                                    <span class="ea-cost-key__name"><?= esc($etiqueta) ?></span>
                                    <span class="ea-cost-key__val"><?= esc($pesos($importe)) ?></span>
                                    <span class="ea-cost-key__pct"><?= esc($pct($importe / $c['precio_venta'] * 100)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </figure>
                </div>

                <!-- 2 · Laboratorio: el visitante mueve los números y ve qué pasa -->
                <div class="ea-cost-lab" data-cost-lab data-cost-seed='<?= esc($costoSemilla, 'attr') ?>' data-reveal-child>
                    <div class="ea-cost-lab__head">
                        <h3 class="ea-cost-lab__title">Probá vos.</h3>
                        <p class="ea-cost-lab__lede">Movés un valor y se recalculan el costo, la ganancia y el punto de equilibrio.</p>
                        <button type="button" class="ea-cost-reset" data-cost-reset>Volver a los valores del TP</button>
                    </div>

                    <div class="ea-cost-lab__grid">
                        <div class="ea-cost-controls">
                            <?php
                            // Cada control: id, etiqueta, unidad, min, max, paso y valor inicial.
                            $costoControles = [
                                ['unidades',  'Unidades por mes',    'u',  5,      40,     1,     $c['unidades']],
                                ['horas',     'Horas por unidad',    'h',  2,      16,     1,     $c['horas_unidad']],
                                ['valorHora', 'Valor de la hora',    '$',  2000,   12000,  500,   $c['valor_hora']],
                                ['mpd',       'Materiales por equipo', '$', 120000, 400000, 10000, $c['mpd_unidad']],
                                ['precio',    'Precio de venta',     '$',  300000, 800000, 10000, $c['precio_venta']],
                            ];
                            foreach ($costoControles as [$id, $etiqueta, $unidad, $min, $max, $paso, $valor]):
                                $texto = $unidad === '$' ? $pesos($valor) : $valor . ' ' . $unidad;
                            ?>
                                <div class="ea-cost-control">
                                    <label for="ea-cost-<?= esc($id) ?>"><?= esc($etiqueta) ?></label>
                                    <output id="ea-cost-<?= esc($id) ?>-out" for="ea-cost-<?= esc($id) ?>"><?= esc($texto) ?></output>
                                    <input type="range" id="ea-cost-<?= esc($id) ?>" data-cost-input="<?= esc($id) ?>"
                                           min="<?= esc($min) ?>" max="<?= esc($max) ?>" step="<?= esc($paso) ?>"
                                           value="<?= esc($valor) ?>" data-cost-unit="<?= esc($unidad) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="ea-cost-readout">
                            <div class="ea-cost-readout__row" data-cost-results aria-live="polite">
                                <div class="ea-cost-tile">
                                    <span class="ea-cost-tile__label">Costo por unidad</span>
                                    <span class="ea-cost-tile__value" data-cost-out="unit"><?= esc($pesos($c['unit'])) ?></span>
                                </div>
                                <div class="ea-cost-tile is-good" data-cost-profit>
                                    <span class="ea-cost-tile__label">Ganancia por unidad</span>
                                    <span class="ea-cost-tile__value" data-cost-out="ganancia"><?= esc($pesos($c['ganancia'])) ?></span>
                                    <span class="ea-cost-tile__sub" data-cost-out="margen"><?= esc($pct($c['margen_costo'], 2)) ?> sobre el costo</span>
                                </div>
                                <div class="ea-cost-tile">
                                    <span class="ea-cost-tile__label">Punto de equilibrio</span>
                                    <span class="ea-cost-tile__value" data-cost-out="punto"><?= esc($c['punto_ent']) ?> unidades</span>
                                    <span class="ea-cost-tile__sub" data-cost-out="puntoPct"><?= esc($pct($c['punto_pct'], 0)) ?> de la producción</span>
                                </div>
                                <div class="ea-cost-tile">
                                    <span class="ea-cost-tile__label">Utilidad del mes</span>
                                    <span class="ea-cost-tile__value" data-cost-out="utilidad"><?= esc($pesos($c['utilidad'])) ?></span>
                                    <span class="ea-cost-tile__sub">Si se vende toda la producción</span>
                                </div>
                            </div>

                            <p class="ea-cost-alert" data-cost-alert hidden>
                                A ese precio cada equipo se vende por debajo de lo que cuesta fabricarlo.
                            </p>

                            <figure class="ea-cost-chart">
                                <figcaption>Ingresos contra costos, según las unidades vendidas en el mes</figcaption>
                                <div class="ea-cost-chart__stage">
                                    <canvas id="ea-cost-chart" height="260" aria-label="Gráfico de punto de equilibrio: la línea de ingresos cruza a la de costos totales en la unidad de equilibrio. Los mismos datos están en la tabla del desarrollo completo, más abajo." role="img"></canvas>
                                </div>
                            </figure>
                        </div>
                    </div>
                </div>

                <!-- 3 · De qué está hecho: filtro por subsistema en vez de una lista de 29 filas -->
                <div class="ea-cost-parts" data-cost-parts data-reveal-child>
                    <div class="ea-cost-parts__head">
                        <h3 class="ea-cost-lab__title">De qué está hecho.</h3>
                        <p class="ea-cost-lab__lede"><?= esc($c['insumos_qty']) ?> insumos por equipo, <?= esc($pesos($c['mpd_unidad'])) ?> en materiales.</p>
                    </div>

                    <div class="ea-cost-filters" role="group" aria-label="Filtrar insumos por subsistema">
                        <button type="button" class="ea-cost-filter is-active" data-cost-filter="todos" aria-pressed="true">
                            Todos <span><?= esc($c['insumos_qty']) ?></span>
                        </button>
                        <?php foreach ($costoInsumos as $grupo => $items): ?>
                            <button type="button" class="ea-cost-filter" data-cost-filter="<?= esc(md5($grupo)) ?>" aria-pressed="false">
                                <?= esc($grupo) ?> <span><?= esc(count($items)) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <ul class="ea-cost-chips">
                        <?php foreach ($costoInsumos as $grupo => $items): ?>
                            <?php foreach ($items as [$cantidad, $insumo]): ?>
                                <li class="ea-cost-chip" data-cost-group="<?= esc(md5($grupo)) ?>">
                                    <span class="ea-cost-chip__qty"><?= esc($cantidad) ?></span>
                                    <span class="ea-cost-chip__name"><?= esc($insumo) ?></span>
                                    <span class="ea-cost-chip__group"><?= esc($grupo) ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- 4 · Costos fijos: barras proporcionales, no una tabla más -->
                <div class="ea-cost-fixed" data-reveal-child>
                    <div class="ea-cost-fixed__head">
                        <h3 class="ea-cost-lab__title">Lo que se paga igual.</h3>
                        <p class="ea-cost-lab__lede">
                            <?= esc($pesos($c['fijo_total'])) ?> por mes, se fabrique una unidad o veinte.
                            No hay alquiler: se trabaja en un espacio propio.
                        </p>
                    </div>
                    <ul class="ea-cost-fixed__list">
                        <?php $costoFijoTope = max($costoFijos); ?>
                        <?php foreach ($costoFijos as $descripcion => $importe): ?>
                            <li>
                                <span class="ea-cost-fixed__name"><?= esc($descripcion) ?></span>
                                <span class="ea-cost-fixed__track">
                                    <span class="ea-cost-fixed__fill" style="--w: <?= esc(round($importe / $costoFijoTope * 100, 2)) ?>%"></span>
                                </span>
                                <span class="ea-cost-fixed__val"><?= esc($pesos($importe)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- 5 · Los dos métodos con los que se fijó el precio -->
                <div class="ea-cost-methods" data-reveal-child>
                    <article class="ea-cost-method">
                        <h3 class="ea-cost-method__title">Según la competencia</h3>
                        <p class="ea-cost-method__text">
                            El cliente compara antes de comprar. Eden Air queda arriba de los equipos que
                            solo miden y abajo de los importados de gama alta.
                        </p>
                        <dl class="ea-cost-method__data">
                            <div>
                                <dt>Promedio de mercado relevado</dt>
                                <dd>USD <?= esc($num($costoPromedio, 0)) ?></dd>
                            </div>
                            <div>
                                <dt>Equipos comparados</dt>
                                <dd><?= esc(count($costoRivales)) ?> en la <a href="#analisis-competencia">sección 05</a></dd>
                            </div>
                        </dl>
                    </article>
                    <article class="ea-cost-method">
                        <h3 class="ea-cost-method__title">Según el valor percibido</h3>
                        <p class="ea-cost-method__text">
                            Eden Air no solo mide: ventila, humidifica y comanda el aire acondicionado,
                            con plataforma web sin abono mensual.
                        </p>
                        <dl class="ea-cost-method__data">
                            <div>
                                <dt>Margen sobre el costo</dt>
                                <dd><?= esc($pct($c['margen_costo'], 2)) ?></dd>
                            </div>
                            <div>
                                <dt>Margen sobre la venta</dt>
                                <dd><?= esc($pct($c['margen_venta'], 2)) ?></dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <!-- 6 · Las tres conclusiones, sin párrafo largo -->
                <ul class="ea-cost-takeaways" data-reveal-child>
                    <li>
                        <span class="ea-cost-takeaway__num" data-cost-count="<?= esc(round($c['peso_mpd'])) ?>" data-cost-suffix=" %"><?= esc($pct($c['peso_mpd'], 0)) ?></span>
                        <p>del costo unitario son componentes importados. Ahí está el riesgo y también el margen de mejora.</p>
                    </li>
                    <li>
                        <span class="ea-cost-takeaway__num" data-cost-count="<?= esc($c['punto_ent']) ?>"><?= esc($c['punto_ent']) ?></span>
                        <p>unidades de <?= esc($c['unidades']) ?> alcanzan para cubrir todos los costos del mes. El proyecto aguanta una demanda inicial baja.</p>
                    </li>
                    <li>
                        <span class="ea-cost-takeaway__num" data-cost-count="<?= esc($c['contrib']) ?>" data-cost-money="1"><?= esc($pesos($c['contrib'])) ?></span>
                        <p>aporta cada equipo vendido a partir de ahí. Comprar por volumen y pasar a placa impresa sube ese número.</p>
                    </li>
                </ul>

                <!-- 7 · El TP completo, plegado: tablas y fórmulas para quien las busque -->
                <details class="ea-cost-full">
                    <summary>
                        <span class="ea-cost-full__title">Ver el desarrollo completo del trabajo práctico</span>
                        <span class="ea-cost-full__hint">Supuestos, materia prima, mano de obra, costos unitarios y punto muerto</span>
                    </summary>

                    <div class="ea-cost-full__body">
                        <h4>Supuestos de trabajo</h4>
                        <p>
                            <?= esc($c['unidades']) ?> unidades por mes, <?= esc($c['horas_unidad']) ?> horas de
                            producción por unidad y la hora valorizada en <?= esc($pesos($c['valor_hora'])) ?>.
                            Produce el equipo de dos integrantes en un espacio propio, sin alquiler de taller.
                            La modalidad es de pago único: el acceso a la plataforma web va incluido, sin suscripción.
                        </p>

                        <h4>Tema 1 · Costos del producto</h4>
                        <div class="ea-pf-table-wrap">
                            <table class="ea-pf-table ea-cost-table ea-cost-table--money">
                                <caption class="ea-comp-caption">a, b y c · Costo variable, costo fijo y costo total del mes</caption>
                                <thead>
                                    <tr><th scope="col">Concepto</th><th scope="col">Cálculo</th><th scope="col">Importe</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Materia prima directa</td>
                                        <td><?= esc($pesos($c['mpd_unidad'])) ?> × <?= esc($c['unidades']) ?> unidades</td>
                                        <td><?= esc($pesos($c['mpd_total'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Mano de obra directa</td>
                                        <td><?= esc($c['horas_mes']) ?> h × <?= esc($pesos($c['valor_hora'])) ?></td>
                                        <td><?= esc($pesos($c['mod_total'])) ?></td>
                                    </tr>
                                    <tr class="ea-cost-row-total">
                                        <td>Costo variable total</td>
                                        <td><?= esc($pesos($c['mpd_total'])) ?> + <?= esc($pesos($c['mod_total'])) ?></td>
                                        <td><?= esc($pesos($c['var_total'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Costo fijo total mensual</td>
                                        <td>Suma de los <?= esc(count($costoFijos)) ?> conceptos fijos</td>
                                        <td><?= esc($pesos($c['fijo_total'])) ?></td>
                                    </tr>
                                    <tr class="ea-cost-row-total">
                                        <td>Costo total</td>
                                        <td><?= esc($pesos($c['var_total'])) ?> + <?= esc($pesos($c['fijo_total'])) ?></td>
                                        <td><?= esc($pesos($c['total'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="ea-pf-table-wrap">
                            <table class="ea-pf-table ea-cost-table ea-cost-table--money">
                                <caption class="ea-comp-caption">d · Costo unitario, por las dos vías vistas en clase</caption>
                                <thead>
                                    <tr><th scope="col">Vía</th><th scope="col">Cálculo</th><th scope="col">Resultado</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Costo total sobre unidades</td>
                                        <td><?= esc($pesos($c['total'])) ?> / <?= esc($c['unidades']) ?></td>
                                        <td><?= esc($pesos($c['unit'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Costo variable unitario</td>
                                        <td><?= esc($pesos($c['var_total'])) ?> / <?= esc($c['unidades']) ?></td>
                                        <td><?= esc($pesos($c['var_unit'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Costo fijo unitario</td>
                                        <td><?= esc($pesos($c['fijo_total'])) ?> / <?= esc($c['unidades']) ?></td>
                                        <td><?= esc($pesos($c['fijo_unit'])) ?></td>
                                    </tr>
                                    <tr class="ea-cost-row-total">
                                        <td>Costo total unitario</td>
                                        <td><?= esc($pesos($c['var_unit'])) ?> + <?= esc($pesos($c['fijo_unit'])) ?></td>
                                        <td><?= esc($pesos($c['unit'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="ea-comp-note">
                            Las dos vías dan el mismo resultado, lo que verifica el cálculo. El costo variable pesa
                            <?= esc($pct($c['peso_var'])) ?> del costo unitario y el fijo el <?= esc($pct($c['peso_fijo'])) ?>.
                        </p>

                        <h4>Tema 2 · Precio del producto</h4>
                        <div class="ea-pf-table-wrap">
                            <table class="ea-pf-table ea-cost-table ea-cost-table--money">
                                <caption class="ea-comp-caption">a y b · Método 1, relevamiento de la competencia</caption>
                                <thead>
                                    <tr><th scope="col">Producto</th><th scope="col">Prestaciones</th><th scope="col">Precio de referencia *</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($costoRivales as $rival): ?>
                                        <tr>
                                            <td><?= esc($rival['name'] . ' ' . $rival['model']) ?></td>
                                            <td><?= esc($rival['prestacion']) ?></td>
                                            <td><?= esc($rival['vals']['precio']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="ea-cost-row-total">
                                        <td>Precio promedio de mercado</td>
                                        <td>Valor medio de cada rango</td>
                                        <td>USD <?= esc($num($costoPromedio, 0)) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="ea-comp-note">
                            * Precios de la competencia estimativos, en dólares (importación y tiendas oficiales),
                            relevados en la sección 05. El de Eden Air es local y en pesos, así que la comparación
                            se hace por segmento y no por conversión directa. Este método siempre se verifica contra
                            el costo unitario: por debajo de <?= esc($pesos($c['unit'])) ?> se vendería a pérdida.
                        </p>

                        <div class="ea-pf-table-wrap">
                            <table class="ea-pf-table ea-cost-table ea-cost-table--money">
                                <caption class="ea-comp-caption">b · Método 2, precio según el valor percibido</caption>
                                <thead>
                                    <tr><th scope="col">Concepto</th><th scope="col">Importe</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td>Costo total unitario</td><td><?= esc($pesos($c['unit'])) ?></td></tr>
                                    <tr><td>Ganancia por unidad</td><td><?= esc($pesos($c['ganancia'])) ?></td></tr>
                                    <tr><td>Margen sobre el costo</td><td><?= esc($pct($c['margen_costo'], 2)) ?></td></tr>
                                    <tr><td>Margen sobre el precio de venta</td><td><?= esc($pct($c['margen_venta'], 2)) ?></td></tr>
                                    <tr class="ea-cost-row-total">
                                        <td>Precio de venta adoptado</td>
                                        <td><?= esc($pesos($c['precio_venta'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="ea-pf-table-wrap">
                            <table class="ea-pf-table ea-cost-table ea-cost-table--money">
                                <caption class="ea-comp-caption">c · Punto muerto = Costos fijos / (Precio de venta - Costo variable unitario)</caption>
                                <thead>
                                    <tr><th scope="col">Cálculo</th><th scope="col">Resultado</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Contribución marginal unitaria = <?= esc($pesos($c['precio_venta'])) ?> - <?= esc($pesos($c['var_unit'])) ?></td>
                                        <td><?= esc($pesos($c['contrib'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Punto muerto = <?= esc($pesos($c['fijo_total'])) ?> / <?= esc($pesos($c['contrib'])) ?></td>
                                        <td><?= esc($num($c['punto'])) ?> unidades</td>
                                    </tr>
                                    <tr>
                                        <td>Punto muerto en unidades enteras</td>
                                        <td><?= esc($c['punto_ent']) ?> unidades</td>
                                    </tr>
                                    <tr class="ea-cost-row-total">
                                        <td>Punto muerto en pesos = <?= esc($c['punto_ent']) ?> × <?= esc($pesos($c['precio_venta'])) ?></td>
                                        <td><?= esc($pesos($c['punto_pesos'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h4>Conclusión</h4>
                        <p>
                            El costo unitario es alto (<?= esc($pesos($c['unit'])) ?>) por el peso de los insumos
                            electrónicos importados, cerca del <?= esc($pct($c['peso_mpd'], 0)) ?> del total. La mano
                            de obra es el segundo componente y los costos fijos inciden poco, por trabajar en un
                            espacio propio. Con un punto muerto de <?= esc($c['punto_ent']) ?> unidades sobre
                            <?= esc($c['unidades']) ?>, el proyecto es viable incluso con demanda inicial reducida.
                            El riesgo principal es la variación del precio de los componentes importados, que golpea
                            directo al costo variable. Las mejoras propuestas: comprar por volumen, reemplazar la
                            protoboard y el cableado Dupont por una placa de circuito impreso propia, y producir un
                            gabinete estandarizado. Todas bajan la materia prima y las horas de armado por unidad.
                        </p>
                    </div>
                </details>

                <!-- Cierre presentación -->
                <div class="ea-pf-close ea-page" style="padding-left: clamp(28px,5vw,56px); padding-right: clamp(28px,5vw,56px);">
                    <span class="ea-pf-eyebrow">Recorrido completo</span>
                    <h2 class="ea-pf-h ea-pf-h2">Gracias por <em>recorrer Eden Air</em>.</h2>
                    <p class="ea-pf-lede" style="margin: 0 auto;">
                        Este portfolio es público y está pensado para mostrarse vía QR en la Expo Demostrativa.
                        El contenido se actualizará a medida que crezca el proyecto y se incorporen los datos reales de la encuesta.
                    </p>
                    <div class="ea-pf-close__actions">
                        <a href="<?= site_url('/') ?>" class="ea-button ea-button-secondary">Volver al inicio</a>
                        <a href="#pagina-principal" class="ea-button ea-button-primary">Volver al comienzo del recorrido</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?= view('partials/footer') ?>
</div><!-- /.ea-shell -->
</div><!-- /#smooth-content -->
</div><!-- /#smooth-wrapper -->

<!-- ===== SCRIPTS DE LA PÁGINA =====
     GSAP (CDN) → tema.js → Chart.js (CDN, gráficos de la encuesta) →
     portfolio.js (interacción) → portfolio-gsap.js (animaciones) →
     ea-scrollbar.js. asset() agrega ?v=mtime para que el navegador no
     sirva la versión vieja. -->
<!-- GSAP + ScrollTrigger + ScrollSmoother (CDN cdnjs) — todos libres desde GSAP 3.13 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollSmoother.min.js"></script>
<script src="<?= asset('JS/tema.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script src="<?= asset('JS/portfolio.js') ?>"></script>
<script src="<?= asset('JS/portfolio-costos.js') ?>"></script>
<script src="<?= asset('JS/portfolio-gsap.js') ?>"></script>
<script src="<?= asset('JS/ea-scrollbar.js') ?>"></script>
</body>
</html>
