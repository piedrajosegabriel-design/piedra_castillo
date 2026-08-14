<?php
/**
 * PERFIL — datos personales y cambio de contraseña.
 * Ruta: /panel/perfil · Controlador: PanelController::perfil
 * Recibe: $usuario
 *
 * Los dos formularios piden la contraseña actual: es la verificación de que
 * quien está sentado adelante es el dueño de la cuenta.
 *
 * data-confirm-form / data-confirm-changes → dashboard.js muestra un diálogo
 * de confirmación antes de enviar, listando qué cambió.
 */
$usuario = (isset($usuario) && is_array($usuario)) ? $usuario : [];

$this->setData([
    'tituloPagina'  => 'EdenAir · Perfil',
    'descripcion'   => 'Editá tus datos personales y tu contraseña en Eden Air.',
    'sidebarActivo' => 'perfil',
    // acceso.js trae el ojo para ver la contraseña (partials/ojo_password.php).
    'scripts'       => ['JS/acceso.js'],
    'cabecera'      => [
        'titulo' => 'Perfil',
        'bajada' => 'Tus datos personales y tu contraseña',
        'extra'  => '<span class="ea-chip ea-chip-status status-success" title="Sesión protegida">'
                  . '<span class="ea-pulse"></span><span>Sesión segura</span></span>',
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- ===== Presentación ===== -->
    <section class="ea-account-intro" aria-labelledby="cuentaTitulo">
        <span class="ea-badge tone-info"><span class="ea-dot"></span>Cuenta protegida</span>
        <h2 id="cuentaTitulo" class="ea-serif ea-account-title">Tus datos, en tu control.</h2>
        <p class="ea-account-lede">
            Actualizá tu nombre y tu contraseña cuando lo necesites.
            Los cambios se verifican con tu contraseña actual.
        </p>
    </section>

    <div class="ea-account-grid">

        <!-- ===== FORMULARIO 1: datos personales =====
             POST /panel/perfil → PanelController::actualizarPerfil -->
        <article class="ea-card ea-account-card" aria-labelledby="datosTitulo">
            <div class="ea-card-head">
                <h3 id="datosTitulo">Datos personales</h3>
                <span class="ea-mono ea-card-meta">Nombre · Apellido</span>
            </div>

            <form action="<?= site_url('panel/perfil') ?>" method="POST" class="ea-account-form"
                  data-confirm-form data-confirm-changes
                  data-confirm-message="Vas a actualizar tus datos personales. Para continuar se verificará tu contraseña actual. ¿Confirmás este cambio?">
                <?= csrf_field() ?>

                <label>
                    <span>Nombre</span>
                    <input type="text" name="nombre" autocomplete="given-name" required
                           value="<?= esc(old('nombre', (string) ($usuario['nombre'] ?? ''))) ?>"
                           data-confirm-label="tu nombre"
                           data-confirm-current="<?= esc((string) ($usuario['nombre'] ?? '')) ?>">
                </label>

                <label>
                    <span>Apellido</span>
                    <input type="text" name="apellido" autocomplete="family-name"
                           value="<?= esc(old('apellido', (string) ($usuario['apellido'] ?? ''))) ?>"
                           data-confirm-label="tu apellido"
                           data-confirm-current="<?= esc((string) ($usuario['apellido'] ?? '')) ?>">
                </label>

                <?php if (! empty($usuario['usuario'])): ?>
                    <label>
                        <span>Nombre de usuario</span>
                        <input type="text" name="usuario" autocomplete="username" required
                               value="<?= esc(old('usuario', (string) ($usuario['usuario'] ?? ''))) ?>"
                               data-confirm-label="tu nombre de usuario"
                               data-confirm-current="<?= esc((string) ($usuario['usuario'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php /* Este input va A PROPÓSITO sin name=: es solo para que el
                         usuario vea con qué mail entra, no se manda ni se guarda.
                         El controlador toma el email de la base. No le agregues
                         name="email": readonly se saca desde el navegador, y ahí
                         cualquiera podría cambiarse el mail de la cuenta. */ ?>
                <?php if (! empty($usuario['email'])): ?>
                    <label>
                        <span>Email <small>(no editable)</small></span>
                        <input type="email" value="<?= esc((string) $usuario['email']) ?>" autocomplete="email" readonly aria-readonly="true">
                    </label>
                <?php endif; ?>

                <label class="ea-account-wide ea-account-secure">
                    <span>Contraseña actual <small>(verificación)</small></span>
                    <div class="ea-password">
                        <input type="password" id="perfilDatosPassword" name="current_password"
                               autocomplete="current-password" required
                               placeholder="Ingresá tu contraseña para confirmar">
                        <?= view('partials/ojo_password', ['objetivo' => '#perfilDatosPassword']) ?>
                    </div>
                </label>

                <div class="ea-account-actions">
                    <a href="<?= site_url('panel') ?>" class="ea-kbtn">Cancelar</a>
                    <button type="submit" class="ea-kbtn ea-kbtn-primary">
                        <?= icono('check', 13) ?>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </article>

        <!-- ===== FORMULARIO 2: cambiar contraseña =====
             POST /panel/password → PanelController::actualizarPassword -->
        <article class="ea-card ea-account-card" aria-labelledby="passTitulo">
            <div class="ea-card-head">
                <h3 id="passTitulo">Cambiar contraseña</h3>
                <span class="ea-mono ea-card-meta">Actual · Nueva · Confirmación</span>
            </div>

            <form action="<?= site_url('panel/password') ?>" method="POST" class="ea-account-form"
                  data-confirm-form
                  data-confirm-message="Vas a cambiar tu contraseña de acceso. Ingresá la actual, la nueva y la confirmación.">
                <?= csrf_field() ?>

                <label class="ea-account-wide">
                    <span>Contraseña actual</span>
                    <div class="ea-password">
                        <input type="password" id="perfilPasswordActual" name="current_password"
                               autocomplete="current-password" required>
                        <?= view('partials/ojo_password', ['objetivo' => '#perfilPasswordActual']) ?>
                    </div>
                </label>

                <label>
                    <span>Nueva contraseña</span>
                    <div class="ea-password">
                        <input type="password" id="perfilPasswordNueva" name="password"
                               autocomplete="new-password" minlength="6" required>
                        <?= view('partials/ojo_password', ['objetivo' => '#perfilPasswordNueva, #perfilPasswordConfirmar']) ?>
                    </div>
                </label>

                <label>
                    <span>Confirmar contraseña</span>
                    <div class="ea-password">
                        <input type="password" id="perfilPasswordConfirmar" name="password_confirm"
                               autocomplete="new-password" minlength="6" required>
                        <?= view('partials/ojo_password', ['objetivo' => '#perfilPasswordConfirmar']) ?>
                    </div>
                </label>

                <p class="ea-account-hint">Mínimo 6 caracteres. Te recomendamos combinar letras y números.</p>

                <div class="ea-account-actions ea-account-wide">
                    <button type="submit" class="ea-kbtn ea-kbtn-primary">
                        <?= icono('candado', 13) ?>
                        Actualizar contraseña
                    </button>
                </div>
            </form>
        </article>

    </div>

<?= $this->endSection() ?>
