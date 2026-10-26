<?php
/**
 * ============================================================================
 * BARRA SUPERIOR PÚBLICA — landing, login, registro, recuperación.
 * ============================================================================
 * (La del panel es otra: app/Views/partials/panel_header.php)
 *
 * ---------------------------------------------------------------------------
 * PARÁMETROS (todos opcionales)
 * ---------------------------------------------------------------------------
 *   $subtitle        string  Línea chica bajo "EdenAir".
 *   $actions         string  HTML de la zona derecha. Si no se pasa, se usan
 *                            los botones por defecto según $conSesion.
 *   $conSesion       bool    true → "Ir al panel" y "Cerrar sesión".
 *                            false → "Login" y "Crear cuenta".
 *   $navLinks        array   Accesos rápidos: [['href' => '#x', 'label' => 'X']]
 */
$subtitle  = $subtitle  ?? 'Monitoreo ambiental';
$actions   = $actions   ?? null;
$conSesion = $conSesion ?? false;
$navLinks  = isset($navLinks) && is_array($navLinks) ? $navLinks : [];
?>
<header class="ea-navbar">
    <div class="ea-page ea-navbar-inner">

        <?= view('partials/logo', ['logo' => [
            'href'     => site_url('/'),
            'size'     => 36,
            'subtitle' => $subtitle,
            'variant'  => 'horizontal',
        ]]) ?>

        <?php if ($navLinks !== []): ?>
            <ul class="ea-nav-links" aria-label="Secciones">

                <!-- Accesos rápidos que pasó la página -->
                <?php foreach ($navLinks as $link): ?>
                    <li><a href="<?= esc($link['href']) ?>"><?= esc($link['label']) ?></a></li>
                <?php endforeach; ?>

            </ul>
        <?php endif; ?>

        <!-- Zona derecha: tema + botones -->
        <div class="ea-nav-actions">
            <?= view('partials/theme_toggle', ['toggle' => []]) ?>

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
