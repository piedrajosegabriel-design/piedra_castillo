<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EdenAir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= base_url('CSS/dashboard.css') ?>">
</head>

<body>
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
            <span class="update-time" id="updateTime">Actualizado recien</span>
            <span class="user-name">Hola, <?= esc(session()->get('user_name') ?? 'Usuario') ?></span>
            <a href="<?= site_url('logout') ?>" class="logout-link">Cerrar sesion</a>
        </div>
    </header>

    <aside class="sidebar">
        <nav class="sidebar-menu">
            <a href="#" class="active">
                <span class="menu-icon">Inicio</span>
                <span class="menu-text">Resumen</span>
            </a>

            <a href="#">
                <span class="menu-icon">Zona</span>
                <span class="menu-text">Ambientes</span>
            </a>

            <a href="#">
                <span class="menu-icon">Aviso</span>
                <span class="menu-text">Alertas</span>
            </a>

            <a href="#">
                <span class="menu-icon">Datos</span>
                <span class="menu-text">Historial</span>
            </a>
        </nav>
    </aside>

    <main class="main">
        <section class="overview">
            <div class="overview-copy">
                <p class="eyebrow">Resumen general</p>
                <h1>Un dashboard mas claro para leer el estado del ambiente.</h1>
                <p>
                    EdenAir prioriza lo importante: estado general, ambientes activos,
                    alertas visibles y proximos pasos para decidir rapido.
                </p>
            </div>

            <div class="overview-card">
                <span class="overview-label">Estado del sistema</span>
                <strong>Estable</strong>
                <p>Las mediciones generales se mantienen en rango y los dispositivos estan en linea.</p>

                <div class="overview-tags">
                    <span class="tag good">3 ambientes activos</span>
                    <span class="tag info">Lectura cada 3 s</span>
                </div>
            </div>
        </section>

        <section class="metrics">
            <article class="metric-card temperature-card">
                <p class="metric-title">Temperatura promedio</p>
                <h2 id="tempMetric">24.6 °C</h2>
                <small>Confort termico general</small>
            </article>

            <article class="metric-card humidity-card">
                <p class="metric-title">Humedad promedio</p>
                <h2 id="humMetric">58%</h2>
                <small>Nivel aceptable</small>
            </article>

            <article class="metric-card comfort-card">
                <p class="metric-title">Calidad del ambiente</p>
                <h2 id="airMetric">Buena</h2>
                <small>Lectura simple para el usuario</small>
            </article>

            <article class="metric-card alert-card">
                <p class="metric-title">Alertas activas</p>
                <h2>2</h2>
                <small>Solo una necesita accion inmediata</small>
            </article>
        </section>

        <section class="content-grid">
            <article class="panel">
                <div class="panel-title">
                    <h2>Ambientes</h2>
                    <span>Lectura actual</span>
                </div>

                <div class="environment-list">
                    <div class="environment">
                        <div class="environment-main">
                            <strong>Aula principal</strong>
                            <p>Temperatura 23.8 °C · Humedad 55%</p>
                        </div>
                        <span class="status good">Estable</span>
                    </div>

                    <div class="environment">
                        <div class="environment-main">
                            <strong>Oficina</strong>
                            <p>Temperatura 24.9 °C · Humedad 60%</p>
                        </div>
                        <span class="status good">Normal</span>
                    </div>

                    <div class="environment">
                        <div class="environment-main">
                            <strong>Hogar</strong>
                            <p>Temperatura 26.1 °C · Humedad 62%</p>
                        </div>
                        <span class="status warning">Atento</span>
                    </div>
                </div>
            </article>

            <article class="panel">
                <div class="panel-title">
                    <h2>Alertas y acciones</h2>
                    <span>Prioridad del dia</span>
                </div>

                <div class="alert-list">
                    <div class="alert danger">
                        <strong>Hogar con temperatura alta</strong>
                        <p>Conviene ventilar o bajar la carga termica del ambiente.</p>
                    </div>

                    <div class="alert warning">
                        <strong>Humedad en subida</strong>
                        <p>La zona hogar esta algo por encima del rango recomendado.</p>
                    </div>

                    <div class="alert success">
                        <strong>Aula y oficina sin novedades</strong>
                        <p>Sus lecturas estan dentro del rango esperado por ahora.</p>
                    </div>
                </div>
            </article>
        </section>

        <section class="bottom-grid">
            <article class="panel">
                <div class="panel-title">
                    <h2>Tendencia diaria</h2>
                    <span>Ultimas horas</span>
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

                <p class="chart-caption">Vista rapida para detectar cambios sin sobrecargar la pantalla.</p>
            </article>

            <article class="panel">
                <div class="panel-title">
                    <h2>Dispositivos</h2>
                    <span>Estado actual</span>
                </div>

                <ul class="device-list">
                    <li><span>ESP32 Aula</span><strong>En linea</strong></li>
                    <li><span>ESP32 Oficina</span><strong>En linea</strong></li>
                    <li><span>ESP32 Hogar</span><strong>En linea</strong></li>
                </ul>

                <div class="next-step">
                    <strong>Siguiente paso</strong>
                    <p>Cuando integres datos reales, este panel puede mostrar ultima lectura y bateria.</p>
                </div>
            </article>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
