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
        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Ingreso</p>
                    <h1>Accedé a tu panel.</h1>
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
                        a tu panel; si todavía no tenés un dispositivo, lo conectás
                        escaneando un QR, sin códigos ni configuración manual.
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
