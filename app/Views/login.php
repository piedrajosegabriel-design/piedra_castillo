<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos basicos de la vista de autenticacion. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Login</title>

    <!-- Estilos globales compartidos por todo el front. -->
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="auth-body">
    <!-- Fondo decorativo fijo para dar profundidad sin afectar el formulario. -->
    <div class="fx-bg">
        <span class="orb orb-1"></span>
        <span class="orb orb-2"></span>
        <span class="orb orb-3"></span>
    </div>

    <main class="auth-shell">
        <!-- Columna izquierda: explica el producto y acompana visualmente el acceso. -->
        <section class="auth-side">
            <p class="eyebrow">EdenAir</p>
            <h1>Entr&aacute; a tu panel y segu&iacute; el estado de cada ambiente.</h1>
            <p class="auth-copy">
                Accede a una vista m&aacute;s ordenada para revisar lecturas, comparar condiciones
                y detectar r&aacute;pidamente cuando el confort ambiental sale del rango esperado.
            </p>

            <div class="auth-stat-grid">
                <!-- Datos rapidos para contextualizar la pantalla. -->
                <div class="auth-stat">
                    <strong>3 paneles</strong>
                    <span>zonas iniciales</span>
                </div>
                <div class="auth-stat">
                    <strong>Alertas</strong>
                    <span>visuales y r&aacute;pidas</span>
                </div>
                <div class="auth-stat">
                    <strong>Sesiones</strong>
                    <span>seguras</span>
                </div>
            </div>

            <div class="auth-feature-list">
                <!-- Bloques cortos que explican beneficios del sistema. -->
                <article class="auth-feature">
                    <span class="feature-index">01</span>
                    <div>
                        <h3>Lecturas claras</h3>
                        <p>Informaci&oacute;n principal al frente, sin ruido y con mejor jerarqu&iacute;a visual.</p>
                    </div>
                </article>

                <article class="auth-feature">
                    <span class="feature-index">02</span>
                    <div>
                        <h3>Mejor seguimiento</h3>
                        <p>Una interfaz lista para crecer con m&aacute;s sensores, ambientes y alertas.</p>
                    </div>
                </article>
            </div>

            <p class="auth-note">Usa tu usuario y contrase&ntilde;a para ingresar al dashboard de EdenAir.</p>
        </section>

        <!-- Columna derecha: formulario real de login. -->
        <section class="login-box auth-card">
            <p class="card-kicker">Acceso</p>
            <h2>Iniciar sesion</h2>
            <p class="form-subtitle">Entr&aacute; a tu cuenta para continuar con el monitoreo del sistema.</p>

            <!-- Flashdata de error enviado desde el controlador luego de un redirect. -->
            <?php if (session()->getFlashdata('error')): ?>
                <p class="form-alert error"><?= esc(session()->getFlashdata('error')) ?></p>
            <?php endif; ?>

            <!-- Flashdata de exito, por ejemplo despues de registrarse. -->
            <?php if (session()->getFlashdata('success')): ?>
                <p class="form-alert success"><?= esc(session()->getFlashdata('success')) ?></p>
            <?php endif; ?>

            <!-- Enviamos el formulario al POST /login definido en Routes.php. -->
            <form action="<?= site_url('login') ?>" method="POST">
                <!-- Token CSRF requerido por CodeIgniter para aceptar el POST. -->
                <?= csrf_field() ?>

                <!-- Campo de usuario usado para buscar el registro en la tabla users. -->
                <div class="input-box">
                    <label for="usuario">Usuario</label>
                    <input type="text" name="usuario" id="usuario" placeholder="Ingresa tu usuario" required>
                </div>

                <!-- Campo de contrasena con boton para alternar visibilidad. -->
                <div class="input-box">
                    <label for="password">Contrase&ntilde;a</label>
                    <div class="password-box">
                        <input type="password" name="password" id="password" placeholder="Ingresa tu contrase&ntilde;a" required>
                        <button type="button" id="togglePassword" aria-label="Mostrar contrase&ntilde;a">Ver</button>
                    </div>
                </div>

                <button type="submit">Entrar al dashboard</button>

                <!-- Enlace secundario hacia la pantalla de registro. -->
                <a href="<?= site_url('register') ?>" id="goRegisterLink" class="forgot">
                    &iquest;No tienes cuenta? Reg&iacute;strate
                </a>
            </form>
        </section>
    </main>

    <!-- Script especifico de interacciones del login. -->
    <script src="<?= base_url('JS/login.js') ?>"></script>
</body>
</html>
