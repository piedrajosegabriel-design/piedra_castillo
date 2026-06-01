<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'EdenAir · Perfil',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="description" content="Editá tus datos personales y tu contraseña en Eden Air.">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-ready">
<?php
$usuario = (isset($usuario) && is_array($usuario)) ? $usuario : [];
$errors  = session()->getFlashdata('errors') ?? [];
$nombreCompleto = trim((string) ($usuario['nombre'] ?? '') . ' ' . (string) ($usuario['apellido'] ?? ''));
?>

<div class="ea-dashboard" data-dashboard-app>
    <?= view('partials/dashboard_sidebar', ['active' => 'perfil']) ?>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>
            <div class="ea-header-titles">
                <h1>Perfil</h1>
                <p>Tus datos personales y tu contraseña</p>
            </div>
            <span class="ea-chip ea-chip-status status-success" title="Sesión protegida">
                <span class="ea-pulse"></span>
                <span>Sesión segura</span>
            </span>
            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>
            </div>
            <div class="ea-header-user" title="<?= esc($nombreCompleto) ?>">
                <span class="ea-header-avatar"><?= esc(strtoupper(substr((string) ($usuario['nombre'] ?? 'U'), 0, 1))) ?></span>
                <span class="ea-header-name"><?= esc($nombreCompleto ?: 'Usuario') ?><small>Cuenta</small></span>
            </div>
        </header>

        <div class="ea-content">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="ea-flash ea-flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="ea-flash ea-flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="ea-flash ea-flash-danger"><ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <section class="ea-account-intro" aria-labelledby="cuentaTitulo">
                <span class="ea-badge tone-info"><span class="ea-dot"></span>Cuenta protegida</span>
                <h2 id="cuentaTitulo" class="ea-serif ea-account-title">Tus datos, en tu control.</h2>
                <p class="ea-account-lede">Actualizá tu nombre y tu contraseña cuando lo necesites. Los cambios se verifican con tu contraseña actual.</p>
            </section>

            <div class="ea-account-grid">
                <article class="ea-card ea-account-card" aria-labelledby="datosTitulo">
                    <div class="ea-card-head">
                        <h3 id="datosTitulo">Datos personales</h3>
                        <span class="ea-mono ea-card-meta">Nombre · Apellido</span>
                    </div>
                    <form action="<?= site_url('panel/perfil') ?>" method="POST" class="ea-account-form" data-confirm-form data-confirm-changes data-confirm-message="Vas a actualizar tus datos personales. Para continuar se verificará tu contraseña actual. ¿Confirmás este cambio?">
                        <?= csrf_field() ?>
                        <label>
                            <span>Nombre</span>
                            <input type="text" name="nombre" value="<?= esc(old('nombre', (string) ($usuario['nombre'] ?? ''))) ?>" data-confirm-label="tu nombre" data-confirm-current="<?= esc((string) ($usuario['nombre'] ?? '')) ?>" autocomplete="given-name" required>
                        </label>
                        <label>
                            <span>Apellido</span>
                            <input type="text" name="apellido" value="<?= esc(old('apellido', (string) ($usuario['apellido'] ?? ''))) ?>" data-confirm-label="tu apellido" data-confirm-current="<?= esc((string) ($usuario['apellido'] ?? '')) ?>" autocomplete="family-name">
                        </label>
                        <?php if (! empty($usuario['usuario'])): ?>
                            <label>
                                <span>Nombre de usuario</span>
                                <input type="text" name="usuario" value="<?= esc(old('usuario', (string) ($usuario['usuario'] ?? ''))) ?>" data-confirm-label="tu nombre de usuario" data-confirm-current="<?= esc((string) ($usuario['usuario'] ?? '')) ?>" autocomplete="username" required>
                            </label>
                        <?php endif; ?>
                        <?php if (! empty($usuario['email'])): ?>
                            <label>
                                <span>Email <small>(no editable)</small></span>
                                <input type="email" value="<?= esc((string) $usuario['email']) ?>" autocomplete="email" readonly aria-readonly="true">
                            </label>
                        <?php endif; ?>
                        <label class="ea-account-wide ea-account-secure">
                            <span>Contraseña actual <small>(verificación)</small></span>
                            <input type="password" name="current_password" autocomplete="current-password" placeholder="Ingresá tu contraseña para confirmar" required>
                        </label>
                        <div class="ea-account-actions">
                            <a href="<?= site_url('panel') ?>" class="ea-kbtn">Cancelar</a>
                            <button type="submit" class="ea-kbtn ea-kbtn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true"><path d="M5 12.5l4 4 10-10"/></svg>
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </article>

                <article class="ea-card ea-account-card" aria-labelledby="passTitulo">
                    <div class="ea-card-head">
                        <h3 id="passTitulo">Cambiar contraseña</h3>
                        <span class="ea-mono ea-card-meta">Actual · Nueva · Confirmación</span>
                    </div>
                    <form action="<?= site_url('panel/password') ?>" method="POST" class="ea-account-form" data-confirm-form data-confirm-message="Vas a cambiar tu contraseña de acceso. Ingresá la actual, la nueva y la confirmación.">
                        <?= csrf_field() ?>
                        <label class="ea-account-wide">
                            <span>Contraseña actual</span>
                            <input type="password" name="current_password" autocomplete="current-password" required>
                        </label>
                        <label>
                            <span>Nueva contraseña</span>
                            <input type="password" name="password" autocomplete="new-password" minlength="6" required>
                        </label>
                        <label>
                            <span>Confirmar contraseña</span>
                            <input type="password" name="password_confirm" autocomplete="new-password" minlength="6" required>
                        </label>
                        <p class="ea-account-hint">Mínimo 6 caracteres. Te recomendamos combinar letras y números.</p>
                        <div class="ea-account-actions ea-account-wide">
                            <button type="submit" class="ea-kbtn ea-kbtn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                                Actualizar contraseña
                            </button>
                        </div>
                    </form>
                </article>
            </div>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
