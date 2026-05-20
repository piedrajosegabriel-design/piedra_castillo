<?php $conSesion = (bool) session()->get('user_id'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => 'EdenAir | Inicio']) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle'  => 'Monitoreo ambiental',
        'conSesion' => $conSesion,
    ]) ?>

    <main>
        <section class="ea-hero">
            <svg class="ea-hero-pattern" viewBox="0 0 100 120" preserveAspectRatio="none" aria-hidden="true">
                <?php for ($i = 0; $i < 22; $i++):
                    $y = $i * 5 + 4;
                    $amp = 1 + ($i % 5) * 0.5; ?>
                    <path d="M 0 <?= $y ?> C 25 <?= $y - $amp ?>, 50 <?= $y + $amp ?>, 75 <?= $y - $amp ?> C 90 <?= $y + $amp * 0.5 ?>, 100 <?= $y ?>, 100 <?= $y ?>"
                          fill="none" stroke="rgba(20,32,26,0.08)" stroke-width="0.4" />
                <?php endfor; ?>
            </svg>

            <div class="ea-page ea-hero-grid">
                <div>
                    <p class="ea-eyebrow">EdenAir / Plataforma de monitoreo</p>
                    <h1 class="ea-hero-title">Respirá <em>mejor,</em><br>viví más cómodo.</h1>
                    <p class="ea-hero-lede">
                        EdenAir mide y cuida el aire de cada espacio. Conectada a un módulo
                        ESP32, transforma cada habitación en su propio pequeño edén: datos
                        claros, control simple y un ambiente que responde a quien lo habita.
                    </p>
                    <div class="ea-hero-actions">
                        <?php if ($conSesion): ?>
                            <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary">Abrir panel</a>
                            <a href="<?= site_url('logout') ?>" class="ea-button ea-button-secondary">Cerrar sesión</a>
                        <?php else: ?>
                            <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary">Crear cuenta</a>
                            <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary">Ya tengo cuenta</a>
                        <?php endif; ?>
                    </div>
                </div>

                <article class="ea-card ea-card--ink" style="padding: 28px;">
                    <p class="ea-eyebrow" style="color: rgba(236,242,232,0.7);">Sala — lectura en vivo</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin: 22px 0;">
                        <div>
                            <p class="ea-mono" style="color: var(--ea-citrus); margin-bottom: 4px;">● CO₂</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">612</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">ppm</span>
                        </div>
                        <div>
                            <p class="ea-mono" style="color: var(--ea-citrus); margin-bottom: 4px;">● Humedad</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">48</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">%</span>
                        </div>
                        <div>
                            <p class="ea-mono" style="color: var(--ea-citrus); margin-bottom: 4px;">● Temperatura</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">22.4</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">°C</span>
                        </div>
                        <div>
                            <p class="ea-mono" style="color: var(--ea-clay); margin-bottom: 4px;">● VOC</p>
                            <strong class="ea-serif" style="font-size: 40px; line-height: 1; color: var(--eden-100);">0.31</strong>
                            <span class="ea-mono" style="color: rgba(236,242,232,0.6); margin-left: 4px;">mg</span>
                        </div>
                    </div>

                    <div class="ea-spread" style="border-top: 1px solid rgba(236,242,232,0.12); padding-top: 16px; font-family: var(--ea-font-mono); font-size: 11.5px; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(236,242,232,0.6);">
                        <span>EA-ENV-01 · ESP32</span>
                        <span style="color: var(--ea-citrus);">● Aire limpio</span>
                    </div>
                </article>
            </div>
        </section>

        <section class="ea-section">
            <div class="ea-page">
                <div class="ea-section-head">
                    <p class="ea-eyebrow">01 / Qué hace EdenAir</p>
                    <h2>Una vista clara del <em>aire</em> que respirás.</h2>
                </div>

                <div class="ea-feature-grid">
                    <article class="ea-feature-card">
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v4M5.5 5.5l2.8 2.8M21 12h-4M5.5 18.5l2.8-2.8M12 21v-4M18.5 18.5l-2.8-2.8M21 12h-4M18.5 5.5l-2.8 2.8"/>
                                <circle cx="12" cy="12" r="3.5"/>
                            </svg>
                        </span>
                        <h3>Sensado continuo</h3>
                        <p>Temperatura, humedad, CO₂ y calidad de aire medidos por el módulo ESP32 cada pocos segundos, presentados sin ruido visual.</p>
                    </article>

                    <article class="ea-feature-card">
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 12h4l2-7 4 14 2-7h4"/>
                            </svg>
                        </span>
                        <h3>Estados claros</h3>
                        <p>Cada variable se muestra con su valor, su rango ideal y un estado: normal, advertencia o crítico. Sin tener que interpretarlo.</p>
                    </article>

                    <article class="ea-feature-card">
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v18M3 12h18"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        </span>
                        <h3>Control de actuadores</h3>
                        <p>Aire, aromatizador, LED de alerta y humidificador en modo automático o manual desde el mismo panel, sin perder de vista el ambiente.</p>
                    </article>

                    <article class="ea-feature-card">
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
                                <path d="M9 13h6M9 17h6M9 9h2"/>
                            </svg>
                        </span>
                        <h3>Historial accesible</h3>
                        <p>Las últimas lecturas quedan disponibles en tablas prolijas, listas para revisar tendencias o exportar para la tesina.</p>
                    </article>

                    <article class="ea-feature-card">
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2.5C8 2.5 5 5.5 5 9.5c0 5 7 12 7 12s7-7 7-12c0-4-3-7-7-7Z"/>
                                <circle cx="12" cy="9.5" r="2.5"/>
                            </svg>
                        </span>
                        <h3>Perfiles de ambiente</h3>
                        <p>Hogar, oficina, aula o un perfil personalizable definen los rangos ideales y el sistema los respeta en cada lectura.</p>
                    </article>

                    <article class="ea-feature-card">
                        <span class="ea-feature-icon">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="M3 9h18M8 14l2 2 4-4"/>
                            </svg>
                        </span>
                        <h3>API conectada</h3>
                        <p>El ESP32 publica mediciones y recibe comandos por endpoints REST documentados. Listo para crecer con nuevos sensores.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ea-section">
            <div class="ea-page">
                <div class="ea-section-head">
                    <p class="ea-eyebrow">02 / Tu pequeño edén</p>
                    <h2>Tu casa, <em>un edén.</em></h2>
                </div>

                <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--ea-gap-lg);">
                    <article class="ea-card ea-card--cream" style="padding: 36px;">
                        <p class="ea-eyebrow">Manifesto</p>
                        <p class="ea-serif" style="font-size: clamp(22px, 2.4vw, 30px); line-height: 1.25; margin: 18px 0 0; font-style: italic; color: var(--ea-ink);">
                            "Creemos que cada casa merece tener su pequeño jardín — uno
                            que no se ve, pero que se siente con cada respiración."
                        </p>
                        <div class="ea-spread" style="margin-top: 28px;">
                            <span class="ea-mono ea-mute">EdenAir · 2026</span>
                            <span class="ea-mono" style="color: var(--eden-500);">● Tesina</span>
                        </div>
                    </article>

                    <article class="ea-card" style="display: flex; flex-direction: column; gap: 18px;">
                        <p class="ea-eyebrow">Mensajes cortos</p>
                        <ul class="ea-stack" style="gap: 14px;">
                            <li class="ea-serif" style="font-size: 22px;">El aire <em style="color: var(--eden-500);">nunca</em> miente.</li>
                            <li class="ea-serif" style="font-size: 22px;">Datos que <em style="color: var(--eden-500);">florecen.</em></li>
                            <li class="ea-serif" style="font-size: 22px;">Medí lo que <em style="color: var(--eden-500);">respirás.</em></li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <?= view('partials/footer') ?>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
</body>
</html>
