<?php
/**
 * EdenAir — navbar superior para páginas públicas (inicio / login / registro).
 *
 * Variables opcionales:
 *   $subtitle   string  Línea pequeña bajo "EdenAir".
 *   $actions    string  HTML a inyectar en la zona derecha (botones, links).
 *   $conSesion  bool    Si es true muestra accesos a panel/logout, si false a login/registro.
 */
$subtitle  = $subtitle  ?? 'Monitoreo ambiental';
$actions   = $actions   ?? null;
$conSesion = $conSesion ?? false;
?>
<header class="ea-navbar">
    <div class="ea-page ea-navbar-inner">
        <?= view('partials/logo', [
            'href'     => site_url('/'),
            'size'     => 36,
            'subtitle' => $subtitle,
            'variant'  => 'horizontal',
        ]) ?>

        <div class="ea-nav-actions">
            <?= view('partials/theme_toggle') ?>

            <?php if ($actions !== null): ?>
                <?= $actions ?>
            <?php elseif ($conSesion): ?>
                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary">Ir al panel</a>
                <a href="<?= site_url('logout') ?>" class="ea-button ea-button-primary">Cerrar sesión</a>
            <?php else: ?>
                <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary">Login</a>
                <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</header>
