<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => 'EdenAir | Recuperar Contraseña']) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle' => 'Recuperación de cuenta',
        'actions'  => '<a href="' . site_url('login') . '" class="ea-button ea-button-secondary">Iniciar Sesión</a>',
    ]) ?>

    <main class="ea-auth">
        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Seguridad</p>
                    <h1>¿Olvidaste tu contraseña?</h1>
                    <p class="ea-lede">Ingresá el correo electrónico asociado a tu cuenta.</p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="ea-message ea-message--error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= site_url('recuperar') ?>" method="POST" id="formRecuperar" class="ea-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="ea-field">
                        <label for="email">Correo electrónico</label>
                        <input type="email" name="email" id="email"
                               placeholder="correo@dominio.com"
                               value="<?= esc(old('email')) ?>"
                               autocomplete="email" required>
                    </div>

                    <p class="ea-hint">
                        Revisá tu bandeja de entrada (y la carpeta de spam) tras enviar la solicitud. El enlace adjunto tendrá una validez de 15 minutos.
                    </p>

                    <button type="submit" class="ea-button ea-button-primary ea-button-block" id="botonRecuperar">
                        Enviar enlace de recuperación
                    </button>

                    <div class="ea-auth-foot">
                        <a href="<?= site_url('login') ?>" class="ea-auth-link">Volver al login</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
</body>
</html>
