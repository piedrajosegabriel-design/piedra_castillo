<?php
/**
 * REGISTRO — alta de una cuenta nueva.
 * Ruta: /registro · Controlador: AccesoController::registro / guardarRegistro
 * JS: public/JS/registro.js (medidor de seguridad, coincidencia de contraseñas)
 */
$this->setData([
    'tituloPagina' => 'EdenAir | Registro',
    'navSubtitulo' => 'Crear cuenta',
    'navBoton'     => ['texto' => 'Login', 'href' => site_url('login')],
    'scripts'      => ['JS/acceso.js', 'JS/registro.js'],
    'encabezado'   => [
        'sobretitulo' => 'Alta de usuario',
        'titulo'      => 'Creá tu acceso.',
        'bajada'      => 'Completá el formulario y entrás directo al dashboard.',
    ],
]);
?>
<?= $this->extend('layouts/acceso') ?>
<?= $this->section('contenido') ?>

    <form action="<?= site_url('registro') ?>" method="POST" class="ea-form" novalidate
          data-enviando="Creando cuenta…">
        <?= csrf_field() ?>

        <div class="ea-field-row">
            <div class="ea-field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required
                       value="<?= esc(old('nombre')) ?>"
                       placeholder="Tu nombre" autocomplete="given-name">
            </div>

            <div class="ea-field">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required
                       value="<?= esc(old('email')) ?>"
                       placeholder="correo@ejemplo.com" autocomplete="email">
            </div>
        </div>

        <div class="ea-field-row">
            <div class="ea-field">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" required
                       value="<?= esc(old('apellido')) ?>"
                       placeholder="Tu apellido" autocomplete="family-name">
            </div>
        </div>

        <div class="ea-field">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required
                   value="<?= esc(old('usuario')) ?>"
                   placeholder="usuario.personal" autocomplete="username">
            <p class="ea-hint">Letras, números, puntos, guiones o guion bajo.</p>
        </div>

        <div class="ea-field">
            <label for="registroPassword">Contraseña</label>
            <div class="ea-password">
                <input type="password" id="registroPassword" name="password" required
                       placeholder="Creá una contraseña"
                       autocomplete="new-password" minlength="8">
                <button type="button" class="ea-button ea-button-secondary" data-ver-password="#registroPassword, #confirmPassword">Mostrar</button>
            </div>
            <p class="ea-hint">8+ caracteres, una mayúscula, una minúscula y un número.</p>
        </div>

        <?php /* La barra y los textos los va llenando registro.js mientras escribís. */ ?>
        <div class="ea-strength">
            <div class="ea-strength-track"><span id="fuerzaBarra"></span></div>
            <p id="fuerzaTexto" class="ea-hint">Seguridad pendiente.</p>
        </div>

        <div class="ea-field">
            <label for="confirmPassword">Confirmar contraseña</label>
            <input type="password" id="confirmPassword" name="password_confirm" required
                   placeholder="Repetí la contraseña"
                   autocomplete="new-password" minlength="8">
        </div>

        <p id="coincideTexto" class="ea-hint">Esperando confirmación de contraseña.</p>

        <button type="submit" class="ea-button ea-button-primary ea-button-block">
            Crear cuenta
        </button>

        <div class="ea-auth-foot">
            <a href="<?= site_url('login') ?>" class="ea-auth-link">Ya tengo una cuenta</a>
        </div>
    </form>

<?= $this->endSection() ?>
