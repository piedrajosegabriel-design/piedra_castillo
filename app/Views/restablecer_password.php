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
        <aside class="ea-auth-aside">
            <svg class="ea-auth-pattern" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <?php for ($i = 1; $i <= 14; $i++):
                    $r = $i * 4 + 4;
                    $wobble = 1 + ($i % 3) * 0.4; ?>
                    <path d="M <?= 50 - $r ?> 55 C <?= 50 - $r ?> <?= 55 - $r * $wobble ?>, <?= 50 + $r ?> <?= 55 - $r * $wobble * 0.9 ?>, <?= 50 + $r ?> 55 C <?= 50 + $r ?> <?= 55 + $r * 0.9 ?>, <?= 50 - $r ?> <?= 55 + $r ?>, <?= 50 - $r ?> 55 Z"
                          fill="none" stroke="rgba(236,242,232,0.18)" stroke-width="0.4" />
                <?php endfor; ?>
            </svg>

            <div class="ea-auth-meta">
                <span>EdenAir / Seguridad</span>
                <span>v 1.0 - 2026</span>
            </div>

            <div>
                <?= view('partials/logo', ['tone' => 'cream', 'size' => 56, 'variant' => 'horizontal']) ?>
                <h1 class="ea-auth-title">Elegi una<br><em>nueva clave.</em></h1>
                <p class="ea-auth-lede">
                    Estas a un paso de recuperar tu cuenta. Ingresa una contraseña nueva y segura para volver a entrar a tu panel.
                </p>
            </div>

            <ul class="ea-auth-points">
                <li>
                    <span></span>
                    <span>
                        <strong>Token temporal</strong>
                        Este enlace solo es valído durante 15 minutos y se anula después de usarlo.
                    </span>
                </li>
                <li>
                    <span></span>
                    <span>
                        <strong>Nueva protección</strong>
                        Tu contraseña debe incluir mayúsculas, minúsculas y números.
                    </span>
                </li>
            </ul>
        </aside>

        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Restablecer acceso</p>
                    <h2>Crea una nueva contraseña.</h2>
                    <p class="ea-lede">Usa al menos 8 caracteres con una mayúscula, una minúscula y un número.</p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="ea-message ea-message--error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php $errores = session()->getFlashdata('errors') ?? []; ?>
                <?php if ($errores): ?>
                    <div class="ea-message ea-message--error">
                        <div>
                            <strong>Revisa los siguientes campos:</strong>
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
                                   placeholder="Ingresa tu nueva contrasena"
                                   autocomplete="new-password" minlength="8" required>
                            <button type="button" class="ea-button ea-button-secondary" data-toggle-password="#password">Mostrar</button>
                        </div>
                    </div>

                    <div class="ea-field">
                        <label for="password_confirm">Confirmar contraseña</label>
                        <div class="ea-password">
                            <input type="password" id="password_confirm" name="password_confirm"
                                   placeholder="Repite tu nueva contrasena"
                                   autocomplete="new-password" minlength="8" required>
                            <button type="button" class="ea-button ea-button-secondary" data-toggle-password="#password_confirm">Mostrar</button>
                        </div>
                    </div>

                    <p class="ea-hint">
                        Cuando confirmes el cambio, el token quedara invalídado y deberas iniciar sesión con la nueva contraseña.
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
