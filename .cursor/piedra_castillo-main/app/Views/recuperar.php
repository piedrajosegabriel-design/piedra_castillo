<?php
/**
 * RECUPERAR CONTRASEÑA — pide el mail y manda el enlace.
 * Ruta: /recuperar · Controlador: AccesoController::recuperar / procesarRecuperacion
 *
 * El enlace que se envía por mail lleva un token de 15 minutos y cae en
 * /restablecer/{token} (ver restablecer_password.php).
 */
$this->setData([
    'tituloPagina' => 'EdenAir | Recuperar Contraseña',
    'navSubtitulo' => 'Recuperación de cuenta',
    'navBoton'     => ['texto' => 'Iniciar Sesión', 'href' => site_url('login')],
    'scripts'      => ['JS/acceso.js'],
    'encabezado'   => [
        'sobretitulo' => 'Seguridad',
        'titulo'      => '¿Olvidaste tu contraseña?',
        'bajada'      => 'Ingresá el correo electrónico asociado a tu cuenta.',
    ],
]);
?>
<?= $this->extend('layouts/acceso') ?>
<?= $this->section('contenido') ?>

    <form action="<?= site_url('recuperar') ?>" method="POST" class="ea-form" novalidate
          data-enviando="Enviando…">
        <?= csrf_field() ?>

        <div class="ea-field">
            <label for="email">Correo electrónico</label>
            <input type="email" name="email" id="email" required
                   placeholder="correo@dominio.com"
                   value="<?= esc(old('email')) ?>"
                   autocomplete="email">
        </div>

        <p class="ea-hint">
            Revisá tu bandeja de entrada (y la carpeta de spam) tras enviar la solicitud.
            El enlace adjunto tendrá una validez de 15 minutos.
        </p>

        <button type="submit" class="ea-button ea-button-primary ea-button-block">
            Enviar enlace de recuperación
        </button>

        <div class="ea-auth-foot">
            <a href="<?= site_url('login') ?>" class="ea-auth-link">Volver al login</a>
        </div>
    </form>

<?= $this->endSection() ?>
