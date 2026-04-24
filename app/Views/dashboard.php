<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos basicos de la vista privada. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Dashboard</title>

    <!-- CSS global que unifica todo el aspecto de la aplicacion. -->
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="home-body dashboard-body">
    <!-- Fondo decorativo fijo compartido con la landing. -->
    <div class="fx-bg">
        <span class="orb orb-1"></span>
        <span class="orb orb-2"></span>
        <span class="orb orb-3"></span>
    </div>

    <!-- Header del area privada con saludo y salida. -->
    <header class="home-header">
        <!-- Marca y acceso rapido de vuelta a la landing. -->
        <a class="home-logo" href="<?= site_url('/') ?>">
            <span class="logo-mark">EA</span>
            <span>EdenAir</span>
        </a>

        <!-- Saludo de sesion y logout. -->
        <nav class="home-nav">
            <span>Hola, <?= esc((string) session()->get('user_name')) ?></span>
            <a class="nav-link" href="<?= site_url('logout') ?>">Cerrar sesi&oacute;n</a>
        </nav>
    </header>

    <main class="home-main">
        <!-- Hero del dashboard: resume el estado general del sistema. -->
        <section class="home-hero dashboard-hero">
            <!-- Resumen textual del estado actual del sistema. -->
            <div class="hero-copy">
                <p class="eyebrow">Panel operativo</p>
                <h1>Resumen en tiempo real del estado ambiental.</h1>
                <p class="hero-lead">
                    Esta versi&oacute;n del dashboard prioriza lectura r&aacute;pida, contraste visual y una estructura
                    lista para crecer cuando conectes la l&oacute;gica real del ESP32 y m&aacute;s ambientes.
                </p>

                <div class="hero-stats">
                    <!-- Mini resumen del estado de la sesion y del panel. -->
                    <div class="hero-stat">
                        <strong>Activo</strong>
                        <span>sesi&oacute;n iniciada</span>
                    </div>
                    <div class="hero-stat">
                        <strong>3 m&eacute;tricas</strong>
                        <span>resumen inicial</span>
                    </div>
                    <div class="hero-stat">
                        <strong>Listo</strong>
                        <span>para integrar sensores</span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta lateral con estado general y datos sinteticos. -->
            <aside class="hero-panel tilt-card">
                <div class="panel-chip">Sistema activo</div>

                <div class="panel-reading">
                    <span>Estado general</span>
                    <strong class="panel-value">Estable</strong>
                    <small>Las variables del entorno se mantienen dentro del rango esperado.</small>
                </div>

                <div class="panel-grid">
                    <div class="panel-item">
                        <span class="panel-label">Ultima lectura</span>
                        <strong>Hace 3 s</strong>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">Latencia</span>
                        <strong>Normal</strong>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">Zona activa</span>
                        <strong>Principal</strong>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">Alertas</span>
                        <strong>0 cr&iacute;ticas</strong>
                    </div>
                </div>
            </aside>
        </section>

        <!-- Metricas principales visibles apenas entra el usuario. -->
        <section class="metrics-grid">
            <!-- Cada tarjeta representa una metrica clave del ambiente. -->
            <article class="metric-card tilt-card">
                <div class="metric-head">
                    <span class="metric-label">Temperatura</span>
                    <span class="metric-badge status-good">Estable</span>
                </div>
                <p class="metric-value" id="tempMetric">--.- &deg;C</p>
                <p class="metric-copy">Rango sugerido para confort: entre 22 &deg;C y 25 &deg;C.</p>
            </article>

            <article class="metric-card tilt-card">
                <div class="metric-head">
                    <span class="metric-label">Humedad</span>
                    <span class="metric-badge status-info">Controlada</span>
                </div>
                <p class="metric-value" id="humMetric">--%</p>
                <p class="metric-copy">Ideal para mantener sensaci&oacute;n t&eacute;rmica agradable y estable.</p>
            </article>

            <article class="metric-card tilt-card">
                <div class="metric-head">
                    <span class="metric-label">Calidad de aire</span>
                    <span class="metric-badge status-watch">Revision</span>
                </div>
                <p class="metric-value" id="airMetric">--</p>
                <p class="metric-copy">Visual listo para futuras reglas autom&aacute;ticas y alertas.</p>
            </article>
        </section>

        <!-- Paneles secundarios con estados por ambiente y proximos pasos. -->
        <section class="dashboard-grid">
            <!-- Columna con lectura por ambiente. -->
            <article class="status-panel">
                <div class="section-heading">
                    <p class="section-kicker">Ambientes</p>
                    <h2>Lectura rapida por zona.</h2>
                </div>

                <div class="status-list">
                    <div class="status-item">
                        <span class="status-dot status-good"></span>
                        <div>
                            <strong>Aula principal</strong>
                            <p>Condiciones equilibradas para mantener confort y concentraci&oacute;n.</p>
                        </div>
                        <span class="status-pill status-good">Normal</span>
                    </div>

                    <div class="status-item">
                        <span class="status-dot status-info"></span>
                        <div>
                            <strong>Oficina</strong>
                            <p>Lecturas consistentes y espacio listo para agregar hist&oacute;ricos.</p>
                        </div>
                        <span class="status-pill status-info">Monitoreo</span>
                    </div>

                    <div class="status-item">
                        <span class="status-dot status-watch"></span>
                        <div>
                            <strong>Hogar</strong>
                            <p>Sin desv&iacute;o cr&iacute;tico, pero listo para reglas de automatizaci&oacute;n.</p>
                        </div>
                        <span class="status-pill status-watch">Atento</span>
                    </div>
                </div>
            </article>

            <!-- Columna con proximos pasos de evolucion del proyecto. -->
            <article class="status-panel">
                <div class="section-heading">
                    <p class="section-kicker">Siguientes pasos</p>
                    <h2>Base lista para crecer.</h2>
                </div>

                <div class="status-list">
                    <div class="status-item">
                        <span class="status-dot status-good"></span>
                        <div>
                            <strong>Integrar lecturas reales</strong>
                            <p>Conecta el ESP32 para reemplazar los valores de demostraci&oacute;n del panel.</p>
                        </div>
                        <span class="status-pill status-good">Listo</span>
                    </div>

                    <div class="status-item">
                        <span class="status-dot status-info"></span>
                        <div>
                            <strong>Agregar historicos</strong>
                            <p>Incorpora registros por ambiente para comparar cambios a lo largo del tiempo.</p>
                        </div>
                        <span class="status-pill status-info">Proximo</span>
                    </div>
                </div>

                <div class="dashboard-note">
                    El dashboard ya tiene mejor jerarqu&iacute;a visual para sumar gr&aacute;ficos,
                    alertas y decisiones autom&aacute;ticas sin rehacer toda la interfaz.
                </div>
            </article>
        </section>
    </main>

    <!-- Footer del dashboard. -->
    <footer class="home-footer">
        <p>EdenAir - Dashboard base para monitoreo ambiental con ESP32.</p>
    </footer>

    <!-- Script que alimenta el dashboard con datos demo y microinteracciones. -->
    <script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
