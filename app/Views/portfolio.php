<?php
$conSesion = (bool) session()->get('user_id');

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
];

$sitemap = [
    ['n' => '01', 'anchor' => 'imagen-corporativa',   'title' => 'Imagen corporativa',          'lede' => 'Logo, paleta, tipografía y principios visuales.',  'status' => 'is-ready',    'status_label' => 'Preparado'],
    ['n' => '02', 'anchor' => 'quienes-somos',        'title' => 'Quiénes somos',               'lede' => 'El equipo estudiantil detrás del proyecto.',        'status' => 'is-ready',    'status_label' => 'Preparado'],
    ['n' => '03', 'anchor' => 'acerca-de-eden-air',   'title' => 'Acerca de Eden Air',          'lede' => 'Producto, propósito y propuesta de valor.',         'status' => 'is-active',   'status_label' => 'Desarrollado'],
    ['n' => '04', 'anchor' => 'analisis-mercado',     'title' => 'Análisis de mercado',         'lede' => 'TP Nº 2 · Investigación, encuesta y reflexión.',    'status' => 'is-focus',    'status_label' => 'Prioritario'],
    ['n' => '05', 'anchor' => 'analisis-competencia', 'title' => 'Análisis de la competencia',  'lede' => 'Comparativa frente a otros productos del rubro.',   'status' => 'is-research', 'status_label' => 'En investigación'],
    ['n' => '06', 'anchor' => 'plan-operativo',       'title' => 'Plan operativo',              'lede' => 'Etapas, recursos y línea de tiempo del proyecto.',  'status' => 'is-progress', 'status_label' => 'En curso'],
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
<div class="ea-shell">
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
                        Trabajo Práctico Nº 2 ·&nbsp;<strong>Análisis de Mercado</strong>
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
        <section class="ea-pf-sitemap" aria-labelledby="ea-pf-sitemap-title">
            <div class="ea-page">
                <header class="ea-pf-sitemap__head">
                    <div>
                        <span class="ea-pf-eyebrow">Recorrido</span>
                        <h2 class="ea-pf-h ea-pf-h2" id="ea-pf-sitemap-title">Mapa del <em>portfolio</em>.</h2>
                    </div>
                    <p class="ea-pf-lede" style="max-width: 38ch;">
                        Seis paradas para entender, validar y proyectar Eden Air. Toca cualquier card para saltar a esa sección.
                    </p>
                </header>

                <div class="ea-pf-sitemap__grid">
                    <?php foreach ($sitemap as $tile): ?>
                        <a href="#<?= esc($tile['anchor']) ?>" class="ea-pf-tile">
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
                            <?= view('partials/logo', ['variant' => 'horizontal', 'size' => 60, 'subtitle' => 'Monitoreo ambiental']) ?>
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
                        <p class="ea-pf-lede">Sección preparada para completar con investigación real de competidores.</p>
                    </div>
                </header>

                <!-- TODO: Completar análisis de competencia con investigación real -->
                <div class="ea-pf-grid-2" style="margin-bottom: clamp(20px,3vw,28px);">
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Criterios de comparación</span>
                        <h3 class="ea-pf-card__title">Sobre qué evaluar</h3>
                        <ul class="ea-pf-criteria">
                            <li>Monitoreo ambiental</li>
                            <li>Automatización</li>
                            <li>Integración web</li>
                            <li>Facilidad de uso</li>
                            <li>Costo estimado</li>
                            <li>Sustentabilidad</li>
                            <li>Diseño físico</li>
                            <li>Diferenciación frente a Eden Air</li>
                        </ul>
                    </article>
                    <article class="ea-pf-card">
                        <span class="ea-pf-card__eyebrow">Fortalezas y debilidades</span>
                        <h3 class="ea-pf-card__title">Comparativa</h3>
                        <p class="ea-pf-card__text">
                            Se completará al relevar competidores reales. El objetivo es identificar dónde Eden Air
                            aporta valor diferencial y dónde necesita ajustar.
                        </p>
                        <span class="ea-pf-todo">Pendiente de investigación</span>
                    </article>
                </div>

                <div class="ea-pf-empty">
                    <div class="ea-pf-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                    </div>
                    <h4>Investigación de competencia pendiente</h4>
                    <p>Este bloque será completado en próximas etapas del proyecto. No se incluyen datos sin verificar.</p>
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
</div>

<?php
    $eaJsBust = static function (string $relativePath): string {
        $abs = FCPATH . $relativePath;
        $v   = is_file($abs) ? filemtime($abs) : time();
        return base_url($relativePath) . '?v=' . $v;
    };
?>
<script src="<?= htmlspecialchars($eaJsBust('JS/tema.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script src="<?= htmlspecialchars($eaJsBust('JS/portfolio.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
