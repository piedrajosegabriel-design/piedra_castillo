<?php
/**
 * Sidebar **único** del dashboard. Se usa en todas las vistas internas
 * (Inicio/panel, Mis dispositivos, Ambientes, Automatizaciones, Compra, Perfil)
 * para garantizar consistencia.
 *
 * Variables:
 *   $active        string  Clave del ítem activo: inicio | dispositivos | ambientes | automatizaciones | compra | perfil
 *   $devicesCount  int     (opcional) cantidad de dispositivos del usuario para el badge
 */
$active       = isset($active) && is_string($active) ? $active : '';
$devicesCount = isset($devicesCount) ? (int) $devicesCount : null;

$cls = static function (string $key) use ($active): string {
    return $active === $key
        ? 'ea-sidebar-item is-active'
        : 'ea-sidebar-item';
};
$aria = static fn (string $key): string => $active === $key ? ' aria-current="page"' : '';
?>
<aside class="ea-sidebar" id="dashboardSidebar" aria-label="Navegación principal del dashboard">
    <div class="ea-sidebar-brand">
        <span class="ea-sidebar-mark" aria-hidden="true">
            <svg viewBox="0 0 32 32" fill="none">
                <path d="M22.5 7.5C12.8 7.5 7 13.4 7 21c0 1.8.4 3.4 1 4.8C13.6 23 18.6 19 22 13" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                <path d="M22.5 7.5c1.6 5-.2 11.4-4.3 14.7-2.7 2.1-5.8 2.7-8.7 1.9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="ea-sidebar-word">
            <b>Eden<em>Air</em></b>
            <span>Panel · v0.5</span>
        </span>
    </div>

    <nav class="ea-sidebar-nav" aria-label="Secciones">
        <a href="<?= site_url('panel') ?>" class="<?= $cls('inicio') ?>"<?= $aria('inicio') ?>>
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/></svg></span>
            <span class="ea-sidebar-label">Inicio</span>
        </a>
        <a href="<?= site_url('panel/dispositivos') ?>" class="<?= $cls('dispositivos') ?>"<?= $aria('dispositivos') ?>>
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/></svg></span>
            <span class="ea-sidebar-label">Mis dispositivos</span>
            <?php if ($devicesCount !== null): ?><span class="ea-sidebar-meta"><?= esc((string) $devicesCount) ?></span><?php endif; ?>
        </a>
        <a href="<?= site_url('panel/ambientes') ?>" class="<?= $cls('ambientes') ?>"<?= $aria('ambientes') ?>>
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M10 21v-7h4v7"/></svg></span>
            <span class="ea-sidebar-label">Ambientes</span>
        </a>
        <a href="<?= site_url('panel') ?>#automatizaciones" class="ea-sidebar-item">
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h7"/><path d="M4 17h11"/><circle cx="14" cy="7" r="2.2"/><circle cx="18" cy="17" r="2.2"/></svg></span>
            <span class="ea-sidebar-label">Automatizaciones</span>
        </a>

        <div class="ea-sidebar-section">Cuenta</div>
        <a href="<?= site_url('panel/perfil') ?>" class="<?= $cls('perfil') ?>"<?= $aria('perfil') ?>>
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 10-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="ea-sidebar-label">Perfil</span>
        </a>
        <a href="<?= site_url('panel/compra') ?>" class="<?= $cls('compra') ?> ea-sidebar-item--cta"<?= $aria('compra') ?> data-ea-buy-cta>
            <span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg></span>
            <span class="ea-sidebar-label">Plan / Comprar</span>
        </a>
    </nav>

    <div class="ea-sidebar-footer">
        <div class="ea-sidebar-status">
            <span class="ea-sidebar-dot tone-success"></span>
            <span class="ea-sidebar-foot-label">Sistema en línea · ESP32 preparada</span>
        </div>
        <a href="<?= site_url('logout') ?>" class="ea-sidebar-logout" title="Cerrar sesión">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4"/><path d="M10 16l-4-4 4-4"/><path d="M6 12h12"/></svg>
            <span class="ea-sidebar-label">Cerrar sesión</span>
        </a>
    </div>
</aside>
