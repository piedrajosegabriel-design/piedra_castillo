<?php $conSesion = (bool) session()->get('user_id'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'EdenAir | Inicio',
        'extraCss' => ['CSS/inicio.css'],
    ]) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle'  => 'Monitoreo ambiental',
        'conSesion' => $conSesion,
        'navLinks'  => [
            ['href' => '#inicio',         'label' => 'Inicio'],
            ['href' => '#que-es',         'label' => 'Qué es'],
            ['href' => '#beneficios',     'label' => 'Beneficios'],
            ['href' => '#funcionamiento', 'label' => 'Funcionamiento'],
            ['href' => '#sensores',       'label' => 'Sensores'],
        ],
    ]) ?>

    <main>
        <section class="ea-hero" id="inicio">
            <span class="ea-hero-glow" aria-hidden="true"></span>
            <div class="ea-hero-orbits" aria-hidden="true">
                <span class="ea-hero-orbit ea-hero-orbit--a"></span>
                <span class="ea-hero-orbit ea-hero-orbit--b"></span>
                <span class="ea-hero-orbit ea-hero-orbit--c"></span>
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
                        Plataforma de monitoreo ambiental
                    </span>
                    <h1 class="ea-hero-title">Respirá <em>mejor,</em><br>viví más cómodo.</h1>
                    <p class="ea-hero-lede">
                        EdenAir es una plataforma web que mide, controla y automatiza el ambiente
                        de cada espacio. Sensores, actuadores y reglas inteligentes, listos para
                        integrarse con ESP32.
                    </p>
                    <div class="ea-hero-actions">
                        <?php if ($conSesion): ?>
                            <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary">Abrir panel</a>
                            <a href="#que-es" class="ea-button ea-button-secondary">Conocer más</a>
                        <?php else: ?>
                            <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary">Crear cuenta</a>
                            <a href="#que-es" class="ea-button ea-button-secondary">Conocer más</a>
                        <?php endif; ?>
                    </div>
                </div>

                <article class="ea-card ea-card--ink ea-hero-card-anim" style="padding: 28px;">
                    <p class="ea-eyebrow" style="color: rgba(236,242,232,0.7);">Sala — lectura en vivo</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin: 22px 0;">
                        <div class="ea-hero-stat">
                            <p class="ea-mono" style="color: var(--ea-citrus); margin-bottom: 4px;">● CO₂</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">612</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">ppm</span>
                        </div>
                        <div class="ea-hero-stat">
                            <p class="ea-mono" style="color: var(--ea-citrus); margin-bottom: 4px;">● Humedad</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">48</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">%</span>
                        </div>
                        <div class="ea-hero-stat">
                            <p class="ea-mono" style="color: var(--ea-citrus); margin-bottom: 4px;">● Temperatura</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">22.4</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">°C</span>
                        </div>
                        <div class="ea-hero-stat">
                            <p class="ea-mono" style="color: var(--ea-clay); margin-bottom: 4px;">● Aire</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">82</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">/100</span>
                        </div>
                    </div>

                    <div class="ea-spread" style="border-top: 1px solid rgba(236,242,232,0.12); padding-top: 16px; font-family: var(--ea-font-mono); font-size: 11.5px; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(236,242,232,0.6);">
                        <span>EA-ENV-01 · ESP32</span>
                        <span style="color: var(--ea-citrus);">● Aire limpio</span>
                    </div>
                </article>
            </div>
        </section>

        <section class="ea-section" id="que-es" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <p class="ea-eyebrow">01 / Qué es EdenAir</p>
                    <h2>Una vista clara del <em>aire</em> que respirás.</h2>
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
                        <p>Aire acondicionado, aromatizador, LED de alerta y humidificador en modo automático o manual desde el mismo panel.</p>
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

        <section class="ea-section" id="beneficios" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <p class="ea-eyebrow">02 / Beneficios</p>
                    <h2>Comodidad que <em>florece.</em></h2>
                </div>

                <div class="ea-carousel" data-ea-carousel aria-label="Beneficios y módulos de EdenAir">
                    <button type="button" class="ea-carousel-arrow" data-ea-carousel-prev aria-label="Anterior">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14.5 6 9 12l5.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="ea-carousel-viewport" data-ea-carousel-viewport>
                        <div class="ea-carousel-track" data-ea-carousel-track>
                            <article class="ea-carousel-slide tone-success">
                                <span class="ea-carousel-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 18h16M6 14h12M8 10h8M10 6h4"/>
                                    </svg>
                                </span>
                                <p class="ea-carousel-eyebrow">01 · Monitoreo</p>
                                <h3>Lectura ambiental clara</h3>
                                <p>Temperatura, humedad, CO₂ y calidad del aire en valores fáciles de leer, con estado y rango ideal.</p>
                            </article>

                            <article class="ea-carousel-slide tone-info">
                                <span class="ea-carousel-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 8h4v8H6zM14 6h4v12h-4z"/>
                                        <path d="M8 5v3M8 16v3M16 4v2M16 18v2"/>
                                    </svg>
                                </span>
                                <p class="ea-carousel-eyebrow">02 · Control</p>
                                <h3>Actuadores a mano</h3>
                                <p>Encendé y apagá ventilador, aromatizador, LED y humidificador desde el panel, en modo manual.</p>
                            </article>

                            <article class="ea-carousel-slide tone-warning">
                                <span class="ea-carousel-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h4l2-4 4 8 2-4h2"/>
                                    </svg>
                                </span>
                                <p class="ea-carousel-eyebrow">03 · Automatización</p>
                                <h3>Reglas inteligentes</h3>
                                <p>EdenAir reacciona solo: si el CO₂ sube, ventila. Si el aire baja, aromatiza. Si algo falla, alerta.</p>
                            </article>

                            <article class="ea-carousel-slide tone-citrus">
                                <span class="ea-carousel-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="6" y="6" width="12" height="12" rx="2"/>
                                        <path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/>
                                    </svg>
                                </span>
                                <p class="ea-carousel-eyebrow">04 · Hardware</p>
                                <h3>Preparado para ESP32</h3>
                                <p>API REST documentada para que el microcontrolador envíe mediciones y reciba comandos sin fricción.</p>
                            </article>

                            <article class="ea-carousel-slide tone-info">
                                <span class="ea-carousel-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 12a9 9 0 1 0 3-6.7"/>
                                        <path d="M3 4v4.5h4.5"/>
                                        <path d="M12 7.5V12l3 2"/>
                                    </svg>
                                </span>
                                <p class="ea-carousel-eyebrow">05 · Historial</p>
                                <h3>Datos que persisten</h3>
                                <p>Cada lectura queda guardada en MySQL para reconstruir la evolución del ambiente cuando se necesite.</p>
                            </article>

                            <article class="ea-carousel-slide tone-success">
                                <span class="ea-carousel-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 4v16M4 12h16"/>
                                        <circle cx="12" cy="12" r="9"/>
                                    </svg>
                                </span>
                                <p class="ea-carousel-eyebrow">06 · Branding</p>
                                <h3>Interfaz cómoda</h3>
                                <p>Tipografía sobria, modo claro/oscuro y colores tomados del branding EdenAir. Sin ruido visual.</p>
                            </article>
                        </div>
                    </div>

                    <button type="button" class="ea-carousel-arrow" data-ea-carousel-next aria-label="Siguiente">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m9.5 6 5.5 6-5.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="ea-carousel-dots" data-ea-carousel-dots aria-hidden="true"></div>
                </div>
            </div>
        </section>

        <section class="ea-section" id="funcionamiento" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <p class="ea-eyebrow">03 / Cómo funciona</p>
                    <h2>Cinco pasos, <em>una sola</em> idea.</h2>
                </div>

                <div class="ea-flow">
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Sensores miden</h4>
                        <p>El módulo lee temperatura, humedad, CO₂ y calidad de aire.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>ESP32 envía</h4>
                        <p>El microcontrolador publica los datos por la API REST de EdenAir.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>EdenAir guarda</h4>
                        <p>Las lecturas quedan registradas en MySQL listas para mostrar.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Reglas deciden</h4>
                        <p>Las automatizaciones evalúan si algún actuador debe activarse.</p>
                    </div>
                    <div class="ea-flow-step" data-reveal-child>
                        <span class="ea-flow-num" aria-hidden="true"></span>
                        <h4>Actuadores responden</h4>
                        <p>Aire, aromatizador, LED y humidificador siguen las órdenes recibidas.</p>
                    </div>
                </div>

                <p style="margin-top: 28px; font-size: 13.5px; color: var(--ea-mute); text-align: center;">
                    <span class="ea-badge ea-badge--info">Estado del hardware</span>
                    &nbsp;Integración con ESP32 preparada — próxima etapa del proyecto.
                </p>
            </div>
        </section>

        <section class="ea-section" id="sensores" data-reveal>
            <div class="ea-page">
                <div class="ea-section-head">
                    <p class="ea-eyebrow">04 / Sensores y actuadores</p>
                    <h2>Lo que <em>mide</em>, lo que <em>controla.</em></h2>
                </div>

                <div class="ea-hardware">
                    <article class="ea-hardware-block" data-reveal-child>
                        <p class="ea-eyebrow">Sensores</p>
                        <h3>Qué se mide</h3>
                        <p style="color: var(--ea-ink-2); font-size: 14.5px; margin-top: 8px;">
                            Cuatro variables que definen el confort y la calidad del aire en un ambiente.
                        </p>

                        <div class="ea-hardware-list">
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                                        <path d="M12 4a2 2 0 0 0-2 2v8.2a3.6 3.6 0 1 0 4 0V6a2 2 0 0 0-2-2Z"/>
                                        <circle cx="12" cy="16.5" r="1.6" fill="currentColor"/>
                                    </svg>
                                </span>
                                <div><strong>Temperatura</strong><small>°C</small></div>
                            </div>
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                                        <path d="M12 3.5c2.4 2.8 5.5 6 5.5 9.5a5.5 5.5 0 1 1-11 0c0-3.5 3.1-6.7 5.5-9.5Z"/>
                                    </svg>
                                </span>
                                <div><strong>Humedad</strong><small>%</small></div>
                            </div>
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <circle cx="12" cy="12" r="8"/>
                                        <text x="12" y="14.5" text-anchor="middle" font-size="7" font-family="DM Mono, monospace" fill="currentColor" stroke="none">CO₂</text>
                                    </svg>
                                </span>
                                <div><strong>CO₂</strong><small>ppm</small></div>
                            </div>
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                        <path d="M3 9h12a3 3 0 1 0-3-3"/>
                                        <path d="M3 14h15a3 3 0 1 1-3 3"/>
                                    </svg>
                                </span>
                                <div><strong>Calidad de aire</strong><small>0–100</small></div>
                            </div>
                        </div>
                    </article>

                    <article class="ea-hardware-block" data-reveal-child>
                        <p class="ea-eyebrow">Actuadores</p>
                        <h3>Qué se controla</h3>
                        <p style="color: var(--ea-ink-2); font-size: 14.5px; margin-top: 8px;">
                            Cuatro dispositivos que responden a las reglas o al control manual desde el panel.
                        </p>

                        <div class="ea-hardware-list">
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="2.2"/>
                                        <path d="M12 10c0-3 1-6 4-6 0 3-1.4 5-4 6Zm0 4c0 3-1 6-4 6 0-3 1.4-5 4-6Zm-2-2c-3 0-6-1-6-4 3 0 5 1.4 6 4Zm4 0c3 0 6 1 6 4-3 0-5-1.4-6-4Z"/>
                                    </svg>
                                </span>
                                <div><strong>Aire / ventilador</strong><small>Refrigera</small></div>
                            </div>
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <path d="M12 3c2 3 4 6 4 9a4 4 0 1 1-8 0c0-3 2-6 4-9Z"/>
                                        <path d="M9 14c-1 1.5-1 3 0 4M15 14c1 1.5 1 3 0 4"/>
                                    </svg>
                                </span>
                                <div><strong>Aromatizador</strong><small>Mejora el aire</small></div>
                            </div>
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <path d="M9 3h6l2 5a6 6 0 1 1-10 0Z"/>
                                        <path d="M10 21h4"/>
                                    </svg>
                                </span>
                                <div><strong>LED de alerta</strong><small>Aviso visual</small></div>
                            </div>
                            <div class="ea-hardware-item">
                                <span class="ea-hardware-item-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
                                        <path d="M12 3.5c2.4 2.8 5.5 6 5.5 9.5a5.5 5.5 0 1 1-11 0c0-3.5 3.1-6.7 5.5-9.5Z"/>
                                        <path d="M8.5 13.5c.6 1.6 2 2.7 3.5 2.7"/>
                                    </svg>
                                </span>
                                <div><strong>Humidificador</strong><small>Preparado</small></div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="ea-section" data-reveal>
            <div class="ea-page">
                <div class="ea-cta">
                    <p class="ea-eyebrow" style="color: rgba(236,242,232,0.7); justify-content: center;">Listo cuando vos</p>
                    <h2>Entrá al panel y <em>sentí el aire.</em></h2>
                    <p>
                        Una interfaz cómoda, ordenada y pensada para que el control ambiental sea simple.
                        Crear cuenta toma menos de un minuto.
                    </p>
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

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/inicio.js') ?>"></script>
</body>
</html>
