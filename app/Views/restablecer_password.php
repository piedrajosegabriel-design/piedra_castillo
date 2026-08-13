<?php
/**
 * RESTABLECER CONTRASEÑA — la pantalla a la que lleva el mail de recuperación.
 * Ruta: /restablecer/{token} · Controlador: AccesoController::restablecer
 * Recibe: $token (el de un solo uso que viajó en el enlace)
 *
 * Al guardar, el token queda invalidado: el enlace no sirve dos veces.
 */
$this->setData([
    'tituloPagina' => 'EdenAir | Restablecer Contraseña',
    'navSubtitulo' => 'Nueva contraseña',
    'navBoton'     => ['texto' => 'Volver al login', 'href' => site_url('login')],
    'scripts'      => ['JS/acceso.js'],
    'encabezado'   => [
        'sobretitulo' => 'Restablecer acceso',
        'titulo'      => 'Creá una nueva contraseña.',
        'bajada'      => 'Usá al menos 8 caracteres con una mayúscula, una minúscula y un número.',
    ],
]);
?>
<?= $this->extend('layouts/acceso') ?>
<?= $this->section('contenido') ?>

    <form action="<?= site_url('restablecer/' . $token) ?>" method="POST" class="ea-form" novalidate
          data-enviando="Guardando…">
        <?= csrf_field() ?>

        <div class="ea-field">
            <label for="password">Nueva contraseña</label>
            <div class="ea-password">
                <input type="password" id="password" name="password" required
                       placeholder="Ingresá tu nueva contraseña"
                       autocomplete="new-password" minlength="8">
                <?= view('partials/ojo_password', ['objetivo' => '#password']) ?>
            </div>
        </div>

        <div class="ea-field">
            <label for="password_confirm">Confirmar contraseña</label>
            <div class="ea-password">
                <input type="password" id="password_confirm" name="password_confirm" required
                       placeholder="Repetí tu nueva contraseña"
                       autocomplete="new-password" minlength="8">
                <?= view('partials/ojo_password', ['objetivo' => '#password_confirm']) ?>
            </div>
        </div>

        <p class="ea-hint">
            Cuando confirmes el cambio, el token quedará invalidado y deberás
            iniciar sesión con la nueva contraseña.
        </p>

        <button type="submit" class="ea-button ea-button-primary ea-button-block">
            Guardar nueva contraseña
        </button>
    </form>

<?= $this->endSection() ?>
