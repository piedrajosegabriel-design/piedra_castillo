<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos basicos de la pagina publica de inicio. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Inicio</title>

    <!-- Hoja de estilos global usada por todas las vistas principales. -->
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="home-body">
    <!-- Fondo decorativo global del sitio publico. -->
    <div class="fx-bg">
        <span class="orb orb-1"></span>
        <span class="orb orb-2"></span>
        <span class="orb orb-3"></span>
    </div>

    <!-- Header principal con accesos a login y registro. -->
    <header class="home-header">
        <!-- Marca del proyecto y enlace para volver a la landing. -->
        <a class="home-logo" href="<?= site_url('/') ?>">
            <span class="logo-mark">EA</span>
            <span>EdenAir</span>
        </a>

        <!-- Navegacion publica para entrar o crear cuenta. -->
        <nav class="home-nav">
            <a class="nav-link" href="<?= site_url('login') ?>">Login</a>
            <a class="nav-link nav-link--cta" href="<?= site_url('register') ?>">Crear cuenta</a>
        </nav>
    </header>

    <main class="home-main">
        <!-- Hero: presenta la idea del proyecto y sus accesos principales. -->
        <section class="home-hero">
            <!-- Columna izquierda: propuesta de valor y CTA. -->
            <div class="hero-copy">
                <p class="eyebrow">EdenAir &middot; Monitoreo ambiental</p>
                <h1>Un panel m&aacute;s claro para entender el clima de cada ambiente.</h1>
                <p class="hero-lead">
                    EdenAir integra sensores ESP32 para medir temperatura y humedad, compararlas
                    con rangos ideales y detectar r&aacute;pido cuando un aula, oficina, hogar o dormitorio
                    sale de confort.
                </p>

                <div class="hero-actions">
                    <a class="hero-btn primary" href="<?= site_url('register') ?>">Comenzar ahora</a>
                    <a class="hero-btn ghost" href="<?= site_url('login') ?>">Iniciar sesion</a>
                </div>

                <div class="hero-stats">
                    <!-- Estadisticas cortas para reforzar la idea del producto. -->
                    <div class="hero-stat">
                        <strong>24/7</strong>
                        <span>captura continua</span>
                    </div>
                    <div class="hero-stat">
                        <strong>4 zonas</strong>
                        <span>ambientes de prueba</span>
                    </div>
                    <div class="hero-stat">
                        <strong>Alertas</strong>
                        <span>seg&uacute;n rango ideal</span>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: panel visual de ejemplo con datos de demo. -->
            <aside class="hero-panel tilt-card">
                <div class="panel-chip">En tiempo real</div>

                <div class="panel-reading">
                    <span>Ambiente principal</span>
                    <strong class="panel-value">24.6 &deg;C</strong>
                    <small>Confort t&eacute;rmico estable y humedad dentro del rango esperado.</small>
                </div>

                <div class="panel-grid">
                    <div class="panel-item">
                        <span class="panel-label">Humedad</span>
                        <strong>52%</strong>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">Calidad</span>
                        <strong>&Oacute;ptima</strong>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">Respuesta</span>
                        <strong>1.8 s</strong>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">Estado</span>
                        <strong>Activo</strong>
                    </div>
                </div>
            </aside>
        </section>

        <div class="section-heading">
            <p class="section-kicker">Ambientes</p>
            <h2>Escenarios donde el monitoreo hace diferencia.</h2>
        </div>

        <!-- Tarjetas de ejemplo para mostrar escenarios de uso del monitoreo. -->
        <section class="home-cards">
            <!-- Cada card representa un ambiente posible del sistema. -->
            <article class="home-card tilt-card">
                <div class="card-visual card-visual--aula">
                    <span class="visual-pill">Aula</span>
                    <div class="visual-reading">
                        <strong>22.4 &deg;C</strong>
                        <span>Confort alto</span>
                    </div>
                </div>

                <div class="card-content">
                    <h3>Aulas con enfoque en concentraci&oacute;n</h3>
                    <p>
                        Seguimiento de temperatura y humedad para mantener condiciones
                        favorables durante jornadas largas de estudio.
                    </p>
                    <div class="card-tags">
                        <span>Temperatura</span>
                        <span>Humedad</span>
                        <span>Alertas</span>
                    </div>
                </div>
            </article>

            <article class="home-card tilt-card">
                <div class="card-visual card-visual--oficina">
                    <span class="visual-pill">Oficina</span>
                    <div class="visual-reading">
                        <strong>23.1 &deg;C</strong>
                        <span>Productividad estable</span>
                    </div>
                </div>

                <div class="card-content">
                    <h3>Oficinas con mejor lectura del entorno</h3>
                    <p>
                        Datos &uacute;tiles para detectar incomodidad t&eacute;rmica antes de que afecte
                        el bienestar y el rendimiento del equipo.
                    </p>
                    <div class="card-tags">
                        <span>Historico</span>
                        <span>Comparacion</span>
                        <span>Confort</span>
                    </div>
                </div>
            </article>

            <article class="home-card tilt-card">
                <div class="card-visual card-visual--hogar">
                    <span class="visual-pill">Hogar</span>
                    <div class="visual-reading">
                        <strong>24.0 &deg;C</strong>
                        <span>Rutina confortable</span>
                    </div>
                </div>

                <div class="card-content">
                    <h3>Hogares con control simple y visual</h3>
                    <p>
                        Una vista clara para entender si el ambiente est&aacute; en equilibrio
                        y actuar r&aacute;pido cuando las condiciones cambian.
                    </p>
                    <div class="card-tags">
                        <span>Bienestar</span>
                        <span>Domotica</span>
                        <span>ESP32</span>
                    </div>
                </div>
            </article>

            <article class="home-card tilt-card">
                <div class="card-visual card-visual--dormitorio">
                    <span class="visual-pill">Dormitorio</span>
                    <div class="visual-reading">
                        <strong>22.8 &deg;C</strong>
                        <span>Descanso estable</span>
                    </div>
                </div>

                <div class="card-content">
                    <h3>Dormitorios con confort pensado para el descanso</h3>
                    <p>
                        Lecturas simples para sostener un entorno equilibrado durante la noche
                        y anticipar cambios que afecten el descanso.
                    </p>
                    <div class="card-tags">
                        <span>Descanso</span>
                        <span>Ventilacion</span>
                        <span>Equilibrio</span>
                    </div>
                </div>
            </article>
        </section>

        <div class="section-heading">
            <p class="section-kicker">Stack</p>
            <h2>Tecnolog&iacute;as pensadas para un monitoreo simple y escalable.</h2>
        </div>

        <!-- Lista corta del stack o capacidades principales. -->
        <section class="tech-strip">
            <div class="tech-pill">ESP32</div>
            <div class="tech-pill">Sensores DHT</div>
            <div class="tech-pill">Comparaci&oacute;n autom&aacute;tica</div>
            <div class="tech-pill">Alertas inteligentes</div>
            <div class="tech-pill">Dashboard visual</div>
        </section>
    </main>

    <!-- Pie simple del proyecto. -->
    <footer class="home-footer">
        <p>EdenAir - Monitoreo ambiental con ESP32 y una interfaz m&aacute;s clara para cada ambiente.</p>
    </footer>

    <script src="<?= base_url('JS/home.js') ?>"></script>
</body>
</html>
