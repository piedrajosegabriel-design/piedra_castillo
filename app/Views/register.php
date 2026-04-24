<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos basicos de la vista de alta de usuario. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Registro</title>

    <!-- CSS global compartido con login, home y dashboard. -->
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="auth-body">
    <!-- Fondo decorativo compartido con login para mantener coherencia visual. -->
    <div class="fx-bg">
        <span class="orb orb-1"></span>
        <span class="orb orb-2"></span>
        <span class="orb orb-3"></span>
    </div>

    <main class="auth-shell">
        <!-- Columna informativa: explica para que sirve crear la cuenta. -->
        <section class="auth-side">
            <p class="eyebrow">Nuevo acceso</p>
            <h1>Crea tu cuenta y empez&aacute; a ordenar mejor el monitoreo.</h1>
            <p class="auth-copy">
                Registra un usuario para centralizar las lecturas del proyecto, revisar estados
                r&aacute;pidamente y dejar lista la base del dashboard para futuras automatizaciones.
            </p>

            <div class="auth-feature-list">
                <!-- Lista de beneficios o senales que anticipan el uso del formulario. -->
                <article class="auth-feature">
                    <span class="feature-index">01</span>
                    <div>
                        <h3>Alta simple</h3>
                        <p>Formulario compacto, claro y listo para empezar a usar sin pasos innecesarios.</p>
                    </div>
                </article>

                <article class="auth-feature">
                    <span class="feature-index">02</span>
                    <div>
                        <h3>Senales utiles</h3>
                        <p>Indicadores visuales para revisar seguridad de la contrase&ntilde;a y coincidencia.</p>
                    </div>
                </article>

                <article class="auth-feature">
                    <span class="feature-index">03</span>
                    <div>
                        <h3>Base escalable</h3>
                        <p>Preparado para seguir creciendo con m&aacute;s ambientes, reportes y alertas.</p>
                    </div>
                </article>
            </div>
        </section>

        <!-- Columna del formulario: alta del usuario. -->
        <section class="register-box auth-card">
            <p class="card-kicker">Registro</p>
            <h2>Crear cuenta</h2>
            <p class="form-subtitle">Completa tus datos y deja listo el acceso a EdenAir.</p>

            <!-- Mensaje de error de validacion enviado por el controlador. -->
            <?php if (session()->getFlashdata('error')): ?>
                <p class="form-alert error"><?= esc(session()->getFlashdata('error')) ?></p>
            <?php endif; ?>

            <!-- Enviamos el formulario al POST /register definido en rutas. -->
            <form action="<?= site_url('register') ?>" method="POST" id="registerForm">
                <!-- Token CSRF requerido por CodeIgniter para aceptar el POST. -->
                <?= csrf_field() ?>

                <!-- Datos personales basicos del nuevo usuario. -->
                <div class="input-grid">
                    <div class="input-box">
                        <label for="nombre">Nombre</label>
                        <input type="text" name="nombre" id="nombre" placeholder="Tu nombre" required>
                    </div>

                    <div class="input-box">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" placeholder="correo@ejemplo.com" required>
                    </div>
                </div>

                <!-- Nombre con el que el usuario va a iniciar sesion. -->
                <div class="input-box">
                    <label for="usuario">Usuario</label>
                    <input type="text" name="usuario" id="usuario" placeholder="Elige un nombre de usuario" required>
                </div>

                <!-- Clave principal y boton para mostrarla/ocultarla. -->
                <div class="input-box">
                    <label for="registerPassword">Contrase&ntilde;a</label>
                    <div class="password-box">
                        <input type="password" name="password" id="registerPassword" placeholder="Crea una contrase&ntilde;a" required>
                        <button type="button" id="toggleRegisterPassword" aria-label="Mostrar contrase&ntilde;a">Ver</button>
                    </div>
                </div>

                <!-- Indicador visual de seguridad calculado en register.js. -->
                <div class="strength-meter">
                    <div class="strength-bar-track">
                        <span id="strengthBar" class="strength-bar"></span>
                    </div>
                    <p id="strengthText" class="helper-text">Seguridad: pendiente</p>
                </div>

                <!-- Confirmacion de contrasena para evitar errores de tipeo. -->
                <div class="input-box">
                    <label for="confirmPassword">Confirmar contrase&ntilde;a</label>
                    <input type="password" name="password_confirm" id="confirmPassword" placeholder="Repite la contrase&ntilde;a" required>
                </div>

                <!-- Texto auxiliar que indica si ambas claves coinciden. -->
                <p id="matchText" class="helper-text">A&uacute;n no verificadas</p>

                <button type="submit" id="registerSubmit">Registrarme</button>

                <a href="<?= site_url('login') ?>" class="forgot">
                    Ya tengo cuenta
                </a>
            </form>
        </section>
    </main>

    <!-- Script con validaciones visuales del formulario de alta. -->
    <script src="<?= base_url('JS/register.js') ?>"></script>
</body>
</html>
