<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => 'EdenAir | Registro']) ?>
</head>
<body class="ea-body">
<div class="ea-shell">
    <?= view('partials/navbar', [
        'subtitle' => 'Crear cuenta',
        'actions'  => '<a href="' . site_url('login') . '" class="ea-button ea-button-secondary">Login</a>',
    ]) ?>

    <main class="ea-auth">
        <aside class="ea-auth-aside">
            <svg class="ea-auth-pattern" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <?php for ($i = 0; $i < 12; $i++):
                    $y = ($i + 1) * (100 / 13);
                    $phase = ($i % 3) * 8;
                    $amp = 4 + ($i % 4); ?>
                    <path d="M -4 <?= $y ?> C 20 <?= $y - $amp ?>, 40 <?= $y + $amp + $phase * 0.2 ?>, 60 <?= $y - $amp ?> C 80 <?= $y + $amp ?>, 100 <?= $y - $amp - $phase * 0.2 ?>, 104 <?= $y ?>"
                        fill="none" stroke="rgba(236,242,232,0.16)" stroke-width="0.6" />
                <?php endfor; ?>
            </svg>

            <div class="ea-auth-meta">
                <span>EdenAir / Alta de usuario</span>
                <span>v 1.0 · 2026</span>
            </div>

            <div>
                <?= view('partials/logo', ['tone' => 'cream', 'size' => 56, 'variant' => 'horizontal']) ?>
                <h1 class="ea-auth-title">Empezá tu<br><em>edén.</em></h1>
                <p class="ea-auth-lede">
                    Creá tu cuenta para empezar a medir el aire de tu espacio.
                    Después del primer ingreso vas a poder elegir el tipo de ambiente
                    y conectar tu módulo ESP32.
                </p>
            </div>

            <ul class="ea-auth-points">
                <li>
                    <span></span>
                    <span>
                        <strong>Formulario directo</strong>
                        Solo los datos necesarios para empezar.
                    </span>
                </li>
                <li>
                    <span></span>
                    <span>
                        <strong>Contraseña segura</strong>
                        Mínimo 8 caracteres, mayúsculas, minúsculas y números.
                    </span>
                </li>
                <li>
                    <span></span>
                    <span>
                        <strong>Ambiente al final</strong>
                        Después de entrar elegís hogar, oficina, aula o personalizable.
                    </span>
                </li>
            </ul>
        </aside>

        <section class="ea-auth-main">
            <div class="ea-auth-card">
                <div>
                    <p class="ea-eyebrow">Alta de usuario</p>
                    <h2>Creá tu acceso.</h2>
                    <p class="ea-lede">El ambiente se configura después del primer login.</p>
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

                <form action="<?= site_url('registro') ?>" method="POST" id="formRegistro" class="ea-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="ea-field-row">
                        <div class="ea-field">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                value="<?= esc(old('nombre')) ?>"
                                placeholder="Tu nombre"
                                autocomplete="given-name" required>
                        </div>

                        <div class="ea-field">
                            <label for="email">Correo electrónico</label>
                            <input type="email" id="email" name="email"
                                value="<?= esc(old('email')) ?>"
                                placeholder="correo@ejemplo.com"
                                autocomplete="email" required>
                        </div>
                    </div>

                    <div class="ea-field-row">
                        <div class="ea-field">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido"
                                value="<?= esc(old('apellido')) ?>"
                                placeholder="Tu apellido"
                                autocomplete="family-name" required>
                        </div>
                    </div>

                    <div class="ea-field">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario"
                            value="<?= esc(old('usuario')) ?>"
                            placeholder="usuario.personal"
                            autocomplete="username" required>
                        <p class="ea-hint">Letras, números, puntos, guiones o guion bajo.</p>
                    </div>

                    <div class="ea-field">
                        <label for="registroPassword">Contraseña</label>
                        <div class="ea-password">
                            <input type="password" id="registroPassword" name="password"
                                placeholder="Creá una contraseña"
                                autocomplete="new-password" minlength="8" required>
                            <button type="button" class="ea-button ea-button-secondary" id="verPasswordRegistro">Mostrar</button>
                        </div>
                        <p class="ea-hint">8+ caracteres, una mayúscula, una minúscula y un número.</p>
                    </div>

                    <div class="ea-strength">
                        <div class="ea-strength-track">
                            <span id="fuerzaBarra"></span>
                        </div>
                        <p id="fuerzaTexto" class="ea-hint">Seguridad pendiente.</p>
                    </div>

                    <div class="ea-field">
                        <label for="confirmPassword">Confirmar contraseña</label>
                        <input type="password" id="confirmPassword" name="password_confirm"
                            placeholder="Repetí la contraseña"
                            autocomplete="new-password" minlength="8" required>
                    </div>

                    <p id="coincideTexto" class="ea-hint">Esperando confirmación de contraseña.</p>

                    <div class="ea-card ea-card--cream" style="padding: 18px 22px;">
                        <p class="ea-eyebrow" style="margin-bottom: 6px;">Próximo paso</p>
                        <strong style="font-family: var(--ea-font-serif); font-size: 20px; letter-spacing: -0.01em;">
                            Elegís el ambiente después del login.
                        </strong>
                        <p class="ea-hint" style="margin-top: 6px;">
                            Vas a poder seleccionar entre hogar, oficina, aula, dormitorio
                            o personalizar tus propios rangos ideales.
                        </p>
                    </div>

                    <button type="submit" class="ea-button ea-button-primary ea-button-block" id="botonRegistro">
                        Crear cuenta
                    </button>

                    <div class="ea-auth-foot">
                        <a href="<?= site_url('login') ?>" class="ea-auth-link">Ya tengo una cuenta</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/registro.js') ?>"></script>
</body>
</html>
