<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EdenAir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= base_url('CSS/dashboard.css') ?>">
</head>

<body class="dashboard-body">
<div class="dashboard" id="dashboard">

    <header class="header">
        <div class="header-left">
            <button class="menu-button" id="menuButton" type="button" aria-label="Abrir o cerrar menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a href="<?= site_url('/') ?>" class="brand">
                <div class="brand-icon">EA</div>
                <div>
                    <h2>EdenAir</h2>
                    <p>Panel ambiental</p>
                </div>
            </a>
        </div>

        <div class="header-user">
            <span class="update-time" id="updateTime">Actualizado hace instantes</span>
            <span class="user-name">Hola, <?= esc(session()->get('user_name') ?? 'Usuario') ?></span>
            <a href="<?= site_url('logout') ?>" class="logout-link">Cerrar sesi&oacute;n</a>
        </div>
    </header>

    <aside class="sidebar">
        <nav class="sidebar-menu">
            <a href="#" class="active">
                <span class="menu-icon">Inicio</span>
                <span class="menu-text">Panorama</span>
            </a>

            <a href="#">
                <span class="menu-icon">Zona</span>
                <span class="menu-text">Ambientes</span>
            </a>

            <a href="#">
                <span class="menu-icon">Aviso</span>
                <span class="menu-text">Incidencias</span>
            </a>

            <a href="#">
                <span class="menu-icon">Datos</span>
                <span class="menu-text">Tendencias</span>
            </a>
        </nav>
    </aside>

    <main class="main">
        <section class="overview">
            <div class="overview-copy">
                <p class="eyebrow">Panorama ejecutivo</p>
                <h1>Una lectura m&aacute;s clara, elegante y confiable del estado ambiental.</h1>
                <p>
                    EdenAir organiza la informaci&oacute;n esencial en una vista serena y precisa,
                    para facilitar la lectura del contexto general, distinguir prioridades
                    y acompa&ntilde;ar decisiones con mayor seguridad.
                </p>

                <div class="legend-row">
                    <span class="legend-item legend-good"><i></i>Condici&oacute;n &oacute;ptima</span>
                    <span class="legend-item legend-watch"><i></i>Seguimiento sugerido</span>
                    <span class="legend-item legend-danger"><i></i>Atenci&oacute;n prioritaria</span>
                </div>
            </div>

            <div class="overview-card">
                <span class="overview-label">Estado operativo</span>
                <strong>Operaci&oacute;n estable</strong>
                <p>Los indicadores principales se mantienen dentro del rango esperado y los equipos responden con normalidad.</p>

                <div class="signal-board">
                    <span class="signal-pill good"><i></i>4 ambientes supervisados</span>
                    <span class="signal-pill info"><i></i>Actualizaci&oacute;n continua</span>
                    <span class="signal-pill warning"><i></i>1 recomendaci&oacute;n abierta</span>
                </div>
            </div>
        </section>

        <section class="metrics">
            <article class="metric-card temperature-card">
                <div class="gauge" id="tempGauge" style="--gauge-fill: 55%;">
                    <div class="gauge-inner">
                        <span class="gauge-caption">Temperatura</span>
                        <h2 id="tempMetric">24.6 &deg;C</h2>
                    </div>
                </div>
                <p class="metric-title">Temperatura media</p>
                <div class="metric-scale">
                    <span>Inferior</span>
                    <span>&Oacute;ptimo</span>
                    <span>Elevado</span>
                </div>
                <small>Referencia t&eacute;rmica general del conjunto.</small>
            </article>

            <article class="metric-card humidity-card">
                <div class="gauge" id="humGauge" style="--gauge-fill: 58%;">
                    <div class="gauge-inner">
                        <span class="gauge-caption">Humedad</span>
                        <h2 id="humMetric">58%</h2>
                    </div>
                </div>
                <p class="metric-title">Humedad relativa</p>
                <div class="metric-scale">
                    <span>Baja</span>
                    <span>Equilibrada</span>
                    <span>Alta</span>
                </div>
                <small>Percepci&oacute;n ambiental resumida en un solo nivel.</small>
            </article>

            <article class="metric-card comfort-card">
                <div class="gauge" id="airGauge" style="--gauge-fill: 78%;">
                    <div class="gauge-inner">
                        <span class="gauge-caption">Calidad</span>
                        <h2 id="airMetric">Buena</h2>
                    </div>
                </div>
                <p class="metric-title">Confort ambiental</p>
                <div class="metric-scale">
                    <span>Inicial</span>
                    <span>Adecuada</span>
                    <span>Destacada</span>
                </div>
                <small>S&iacute;ntesis visual pensada para una lectura inmediata.</small>
            </article>

            <article class="metric-card alert-card">
                <p class="metric-title">Incidencias activas</p>
                <div class="traffic-visual" aria-hidden="true">
                    <span class="traffic-dot danger active"></span>
                    <span class="traffic-dot warning active"></span>
                    <span class="traffic-dot success"></span>
                </div>
                <h2>2</h2>
                <small>Rojo se&ntilde;ala prioridad inmediata. &Aacute;mbar sugiere observaci&oacute;n.</small>
            </article>
        </section>

        <section class="content-grid">
            <article class="panel">
                <div class="panel-title">
                    <h2>Ambientes supervisados</h2>
                    <span>Estado actual</span>
                </div>

                <div class="environment-list">
                    <div class="environment environment-good">
                        <span class="environment-icon" aria-hidden="true"></span>
                        <div class="environment-main">
                            <div class="environment-heading">
                                <strong>Aula principal</strong>
                                <span class="status good">En equilibrio</span>
                            </div>
                            <p>Temperatura 23.8 &deg;C &middot; Humedad 55%</p>
                            <div class="mini-meter good"><span style="width: 72%"></span></div>
                        </div>
                    </div>

                    <div class="environment environment-good">
                        <span class="environment-icon" aria-hidden="true"></span>
                        <div class="environment-main">
                            <div class="environment-heading">
                                <strong>Oficina</strong>
                                <span class="status good">Rango saludable</span>
                            </div>
                            <p>Temperatura 24.9 &deg;C &middot; Humedad 60%</p>
                            <div class="mini-meter good"><span style="width: 64%"></span></div>
                        </div>
                    </div>

                    <div class="environment environment-warning">
                        <span class="environment-icon" aria-hidden="true"></span>
                        <div class="environment-main">
                            <div class="environment-heading">
                                <strong>Hogar</strong>
                                <span class="status warning">Seguimiento</span>
                            </div>
                            <p>Temperatura 26.1 &deg;C &middot; Humedad 62%</p>
                            <div class="mini-meter warning"><span style="width: 82%"></span></div>
                        </div>
                    </div>

                    <div class="environment environment-good">
                        <span class="environment-icon" aria-hidden="true"></span>
                        <div class="environment-main">
                            <div class="environment-heading">
                                <strong>Dormitorio</strong>
                                <span class="status good">Descanso ideal</span>
                            </div>
                            <p>Temperatura 22.7 &deg;C &middot; Humedad 57%</p>
                            <div class="mini-meter good"><span style="width: 68%"></span></div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="panel">
                <div class="panel-title">
                    <h2>Incidencias y criterio</h2>
                    <span>Prioridades del d&iacute;a</span>
                </div>

                <div class="alert-list">
                    <div class="alert danger">
                        <span class="alert-icon" aria-hidden="true"></span>
                        <div class="alert-copy">
                            <span class="alert-level">Intervenci&oacute;n sugerida</span>
                            <strong>Hogar con temperatura por encima del nivel ideal</strong>
                            <p>Se recomienda favorecer la ventilaci&oacute;n o reducir la carga t&eacute;rmica del espacio.</p>
                        </div>
                    </div>

                    <div class="alert warning">
                        <span class="alert-icon" aria-hidden="true"></span>
                        <div class="alert-copy">
                            <span class="alert-level">Observaci&oacute;n preventiva</span>
                            <strong>Ascenso gradual de humedad</strong>
                            <p>El ambiente hogare&ntilde;o comienza a ubicarse por encima del rango recomendado.</p>
                        </div>
                    </div>

                    <div class="alert success">
                        <span class="alert-icon" aria-hidden="true"></span>
                        <div class="alert-copy">
                            <span class="alert-level">Comportamiento estable</span>
                            <strong>Aula, oficina y dormitorio conservan una condici&oacute;n favorable</strong>
                            <p>Los tres sectores presentan valores estables y sin se&ntilde;ales de desajuste.</p>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="bottom-grid">
            <article class="panel">
                <div class="panel-title">
                    <h2>Comportamiento diario</h2>
                    <span>&Uacute;ltimas horas</span>
                </div>

                <div class="chart-levels" aria-hidden="true">
                    <span>Alto</span>
                    <span>Medio</span>
                    <span>Bajo</span>
                </div>

                <div class="chart-box" aria-hidden="true">
                    <div class="bar" style="height: 42%"></div>
                    <div class="bar" style="height: 58%"></div>
                    <div class="bar" style="height: 50%"></div>
                    <div class="bar" style="height: 66%"></div>
                    <div class="bar" style="height: 54%"></div>
                    <div class="bar" style="height: 72%"></div>
                    <div class="bar" style="height: 60%"></div>
                </div>

                <p class="chart-caption">Una lectura resumida para advertir variaciones sin distraer la atenci&oacute;n principal.</p>
            </article>

            <article class="panel">
                <div class="panel-title">
                    <h2>Dispositivos conectados</h2>
                    <span>Disponibilidad actual</span>
                </div>

                <ul class="device-list">
                    <li>
                        <div class="device-main">
                            <span class="device-dot" aria-hidden="true"></span>
                            <span>ESP32 Aula</span>
                        </div>
                        <strong>Disponible</strong>
                    </li>
                    <li>
                        <div class="device-main">
                            <span class="device-dot" aria-hidden="true"></span>
                            <span>ESP32 Oficina</span>
                        </div>
                        <strong>Disponible</strong>
                    </li>
                    <li>
                        <div class="device-main">
                            <span class="device-dot" aria-hidden="true"></span>
                            <span>ESP32 Hogar</span>
                        </div>
                        <strong>Disponible</strong>
                    </li>
                    <li>
                        <div class="device-main">
                            <span class="device-dot" aria-hidden="true"></span>
                            <span>ESP32 Dormitorio</span>
                        </div>
                        <strong>Disponible</strong>
                    </li>
                </ul>

                <div class="next-step">
                    <strong>Proyecci&oacute;n pr&oacute;xima</strong>
                    <p>Al incorporar datos reales, este bloque puede reunir &uacute;ltima lectura, bater&iacute;a y calidad del enlace.</p>
                </div>
            </article>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
