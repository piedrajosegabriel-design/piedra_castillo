<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => 'EdenAir | Login']) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle' => 'Ingreso al sistema',
        'actions'  => '<a href="' . site_url('registro') . '" class="ea-button ea-button-secondary">Crear cuenta</a>',
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
                <span>EdenAir / Acceso</span>
                <span>v 1.0 · 2026</span>
            </div>

            <div>
                <?= view('partials/logo', ['tone' => 'cream', 'size' => 56, 'variant' => 'horizontal']) ?>
                <h1 class="ea-auth-title">Bienvenido<br><em>de vuelta.</em></h1>
                <p class="ea-auth-lede">
                    Respirá mejor, viví más cómodo. Accedé al panel para ver el estado
                    del ambiente, controlar los actuadores y revisar el historial.
                </p>
            </div>

            <ul class="ea-auth-points">
                <li>
                    <span></span>
                    <span>
                        <strong>Autenticación segura</strong>
                        Hash de contraseña y sesión regenerada al ingresar.
                    </span>
                </li>
                <li>
                    <span></span>
                    <span>
                        <strong>Entrada simple</strong>
                        Usuario o correo, sin pasos de más.
                    </span>
                </li>
                <li>
                    <span></span>
                    <span>
                        <strong>Primer ingreso guiado</strong>
                        Si todavía no elegiste ambiente, el sistema te acompaña.
                    </span>
                </li>
            </ul>
        </aside>

        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Ingreso</p>
                    <h2>Accedé a tu panel.</h2>
                    <p class="ea-lede">Usá tu nombre de usuario o correo electrónico.</p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="ea-message ea-message--error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="ea-message ea-message--success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <form action="<?= site_url('login') ?>" method="POST" id="formLogin" class="ea-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="ea-field">
                        <label for="usuario">Usuario o correo</label>
                        <input type="text" name="usuario" id="usuario"
                               placeholder="usuario o correo@dominio.com"
                               value="<?= esc(old('usuario')) ?>"
                               autocomplete="username" required>
                    </div>

                    <div class="ea-field">
                        <label for="loginPassword">Contraseña</label>
                        <div class="ea-password">
                            <input type="password" name="password" id="loginPassword"
                                   placeholder="Ingresá tu contraseña"
                                   autocomplete="current-password" required>
                            <button type="button" class="ea-button ea-button-secondary" id="verPasswordLogin">Mostrar</button>
                        </div>
                    </div>

                    <p class="ea-hint">
                        Las credenciales se validan en servidor. Al entrar vas directo
                        a tu panel; si todavía no tenés un dispositivo, te guiamos para
                        vincularlo o probar la demo.
                    </p>

                    <button type="submit" class="ea-button ea-button-primary ea-button-block" id="botonLogin">
                        Entrar al panel
                    </button>

                    <div class="ea-auth-foot">
                      <a href="<?= site_url('recuperar') ?>" class="ea-auth-link">Olvidé mi contraseña   </a>
                      <a href="<?= site_url('registro') ?>" class="ea-auth-link">No tengo cuenta todavía</a>
                   </div>
                   
                </form>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/login.js') ?>"></script>
</body>
</html>
