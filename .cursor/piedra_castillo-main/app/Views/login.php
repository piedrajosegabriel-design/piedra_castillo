<?php
/**
 * LOGIN — entrada al panel.
 * Ruta: /login · Controlador: AccesoController::login / validarLogin
 * JS: public/JS/acceso.js (mostrar/ocultar contraseña, bloquear el botón al enviar)
 */
$this->setData([
    'tituloPagina' => 'EdenAir | Login',
    'navSubtitulo' => 'Ingreso al sistema',
    'navBoton'     => ['texto' => 'Crear cuenta', 'href' => site_url('registro')],
    'scripts'      => ['JS/acceso.js'],
    'encabezado'   => [
        'sobretitulo' => 'Ingreso',
        'titulo'      => 'Accedé a tu panel.',
        'bajada'      => 'Usá tu nombre de usuario o correo electrónico.',
    ],
]);
?>
<?= $this->extend('layouts/acceso') ?>
<?= $this->section('contenido') ?>

    <form action="<?= site_url('login') ?>" method="POST" class="ea-form" novalidate
          data-enviando="Validando…">
        <?= csrf_field() ?>

        <div class="ea-field">
            <label for="usuario">Usuario o correo</label>
            <input type="text" name="usuario" id="usuario" required
                   placeholder="usuario o correo@dominio.com"
                   value="<?= esc(old('usuario')) ?>"
                   autocomplete="username">
        </div>

        <div class="ea-field">
            <label for="loginPassword">Contraseña</label>
            <div class="ea-password">
                <input type="password" name="password" id="loginPassword" required
                       placeholder="Ingresá tu contraseña"
                       autocomplete="current-password">
                <?= view('partials/ojo_password', ['objetivo' => '#loginPassword']) ?>
            </div>
        </div>

        <p class="ea-hint">
            Las credenciales se validan en servidor. Al entrar vas directo
            a tu panel; si todavía no tenés un dispositivo, lo conectás
            escaneando un QR, sin códigos ni configuración manual.
        </p>

        <button type="submit" class="ea-button ea-button-primary ea-button-block">
            Entrar al panel
        </button>

        <div class="ea-auth-foot">
            <a href="<?= site_url('recuperar') ?>" class="ea-auth-link">Olvidé mi contraseña</a>
            <a href="<?= site_url('registro') ?>" class="ea-auth-link">No tengo cuenta todavía</a>
        </div>
    </form>

<?= $this->endSection() ?>
