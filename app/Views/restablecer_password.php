<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => 'EdenAir | Restablecer Contraseña']) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle' => 'Nueva contraseña',
        'actions'  => '<a href="' . site_url('login') . '" class="ea-button ea-button-secondary">Volver al login</a>',
    ]) ?>

    <main class="ea-auth">
        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Restablecer acceso</p>
                    <h1>Creá una nueva contraseña.</h1>
                    <p class="ea-lede">Usá al menos 8 caracteres con una mayúscula, una minúscula y un número.</p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="ea-message ea-message--error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php $errores = session()->getFlashdata('errors') ?? []; ?>
                <?php if ($errores): ?>
                    <div class="ea-message ea-message--error">
                        <div>
                            <strong>Revisá los siguientes campos:</strong>
                            <ul>
                                <?php foreach ($errores as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('restablecer/' . $token) ?>" method="POST" class="ea-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="ea-field">
                        <label for="password">Nueva contraseña</label>
                        <div class="ea-password">
                            <input type="password" id="password" name="password"
                                   placeholder="Ingresá tu nueva contraseña"
                                   autocomplete="new-password" minlength="8" required>
                            <button type="button" class="ea-button ea-button-secondary" data-toggle-password="#password">Mostrar</button>
                        </div>
                    </div>

                    <div class="ea-field">
                        <label for="password_confirm">Confirmar contraseña</label>
                        <div class="ea-password">
                            <input type="password" id="password_confirm" name="password_confirm"
                                   placeholder="Repetí tu nueva contraseña"
                                   autocomplete="new-password" minlength="8" required>
                            <button type="button" class="ea-button ea-button-secondary" data-toggle-password="#password_confirm">Mostrar</button>
                        </div>
                    </div>

                    <p class="ea-hint">
                        Cuando confirmes el cambio, el token quedará invalidado y deberás iniciar sesión con la nueva contraseña.
                    </p>

                    <button type="submit" class="ea-button ea-button-primary ea-button-block">
                        Guardar nueva contraseña
                    </button>
                </form>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script>
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.querySelector(button.dataset.togglePassword);

        if (!input) {
            return;
        }

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.textContent = isPassword ? 'Ocultar' : 'Mostrar';
    });
});
</script>
</body>
</html>
