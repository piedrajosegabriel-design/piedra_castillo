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
                <span>EdenAir / Soporte</span>
                <span>v 1.0 · 2026</span>
            </div>

            <div>
                <?= view('partials/logo', ['tone' => 'cream', 'size' => 56, 'variant' => 'horizontal']) ?>
                <h1 class="ea-auth-title">Recuperá<br><em>tu acceso.</em></h1>
                <p class="ea-auth-lede">
                    No te preocupes, a cualquiera le pasa. Te enviaremos un correo con las instrucciones necesarias para restablecer tus credenciales de forma segura.
                </p>
            </div>

            <ul class="ea-auth-points">
                <li>
                    <span></span>
                    <span>
                        <strong>Proceso cifrado</strong>
                        Generamos un token de un solo uso que expira automáticamente para proteger tus datos.
                    </span>
                </li>
            </ul>
        </aside>

        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Seguridad</p>
                    <h2>¿Olvidaste tu contraseña?</h2>
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
