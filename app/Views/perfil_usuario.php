<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'EdenAir | Editar datos',
        'extraCss' => ['CSS/dashboard.css'],
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-loading">
<?php
$usuario = (isset($usuario) && is_array($usuario)) ? $usuario : [];
$errors  = session()->getFlashdata('errors') ?? [];
$nombreCompleto = trim((string) ($usuario['nombre'] ?? '') . ' ' . (string) ($usuario['apellido'] ?? ''));
?>

<div class="ea-dashboard" data-dashboard-app>
    <aside class="ea-sidebar" id="dashboardSidebar" aria-label="Navegacion principal">
        <div class="ea-sidebar-brand">
            <span class="ea-sidebar-mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none"><path d="M22.5 7.5C12.8 7.5 7 13.4 7 21c0 1.8.4 3.4 1 4.8C13.6 23 18.6 19 22 13" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M22.5 7.5c1.6 5-.2 11.4-4.3 14.7-2.7 2.1-5.8 2.7-8.7 1.9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="ea-sidebar-word"><b>Eden<em>Air</em></b><span>Cuenta</span></span>
        </div>

        <div class="ea-sidebar-section">Sistema</div>
        <a href="<?= site_url('panel') ?>" class="ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="9" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="5" rx="1.6"/><rect x="13.5" y="11.5" width="7" height="9" rx="1.6"/><rect x="3.5" y="15.5" width="7" height="5" rx="1.6"/></svg></span>
            <span class="ea-sidebar-label">Dashboard</span>
        </a>
        <a href="<?= site_url('panel/perfil') ?>" class="ea-sidebar-item is-active">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 10-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="ea-sidebar-label">Editar datos</span>
        </a>
        <a href="<?= site_url('panel/compra') ?>" class="ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg></span>
            <span class="ea-sidebar-label">Comprar</span>
        </a>
        <div class="ea-sidebar-footer"><span class="ea-sidebar-dot"></span><span class="ea-sidebar-foot-label">Sesion protegida</span></div>
    </aside>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-header-icon-btn" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M9 4.5v15"/></svg>
            </button>
            <div class="ea-header-titles">
                <h1>Editar datos</h1>
                <p>Cada cambio exige tu contrasena actual y una confirmacion explicita</p>
            </div>
            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>
                <a href="<?= site_url('logout') ?>" class="ea-header-icon-btn ea-header-logout" title="Cerrar sesion" aria-label="Cerrar sesion">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4"/><path d="M10 16l-4-4 4-4"/><path d="M6 12h12"/></svg>
                </a>
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

            <div class="ea-sec">
                <h2>Perfil</h2>
                <span class="ea-sec-right">verificacion obligatoria</span>
            </div>

            <article class="ea-card ea-account-card">
                <div class="ea-card-head">
                    <h3>Datos personales</h3>
                    <span class="ea-mono ea-card-meta">NOMBRE · APELLIDO · USUARIO · EMAIL</span>
                </div>
                <form action="<?= site_url('panel/perfil') ?>" method="POST" class="ea-account-form" data-confirm-form data-confirm-changes data-confirm-message="Vas a modificar datos importantes de tu cuenta. Para continuar se verificara tu contrasena actual. ¿Confirmas este cambio?">
                    <?= csrf_field() ?>
                    <label>Nombre<input type="text" name="nombre" value="<?= esc(old('nombre', (string) ($usuario['nombre'] ?? ''))) ?>" data-confirm-label="tu nombre" data-confirm-current="<?= esc((string) ($usuario['nombre'] ?? '')) ?>" autocomplete="given-name" required></label>
                    <label>Apellido<input type="text" name="apellido" value="<?= esc(old('apellido', (string) ($usuario['apellido'] ?? ''))) ?>" data-confirm-label="tu apellido" data-confirm-current="<?= esc((string) ($usuario['apellido'] ?? '')) ?>" autocomplete="family-name"></label>
                    <label>Nombre de usuario<input type="text" name="usuario" value="<?= esc(old('usuario', (string) ($usuario['usuario'] ?? ''))) ?>" data-confirm-label="tu nombre de usuario" data-confirm-current="<?= esc((string) ($usuario['usuario'] ?? '')) ?>" autocomplete="username" required></label>
                    <label>Gmail / correo<input type="email" name="email" value="<?= esc(old('email', (string) ($usuario['email'] ?? ''))) ?>" data-confirm-label="tu gmail/correo" data-confirm-current="<?= esc((string) ($usuario['email'] ?? '')) ?>" autocomplete="email" required></label>
                    <label class="ea-account-wide">Contrasena actual<input type="password" name="current_password" autocomplete="current-password" required></label>
                    <div class="ea-account-actions">
                        <a href="<?= site_url('panel') ?>" class="ea-kbtn">Volver</a>
                        <button type="submit" class="ea-kbtn ea-kbtn-primary">Guardar datos</button>
                    </div>
                </form>
            </article>

            <article class="ea-card ea-account-card">
                <div class="ea-card-head">
                    <h3>Cambiar contrasena</h3>
                    <span class="ea-mono ea-card-meta">ACTUAL · NUEVA · CONFIRMACION</span>
                </div>
                <form action="<?= site_url('panel/password') ?>" method="POST" class="ea-account-form" data-confirm-form data-confirm-message="Vas a cambiar la contrasena de acceso a tu cuenta. Ingresa la actual, la nueva y la confirmacion. ¿Confirmas este cambio?">
                    <?= csrf_field() ?>
                    <label>Contrasena actual<input type="password" name="current_password" autocomplete="current-password" required></label>
                    <label>Nueva contrasena<input type="password" name="password" autocomplete="new-password" required></label>
                    <label>Repetir nueva contrasena<input type="password" name="password_confirm" autocomplete="new-password" required></label>
                    <div class="ea-account-actions ea-account-wide">
                        <button type="submit" class="ea-kbtn ea-kbtn-primary">Actualizar contrasena</button>
                    </div>
                </form>
            </article>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
