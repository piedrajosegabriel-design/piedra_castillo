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
        <span class="ea-sidebar-mark ea-sidebar-mark--corriente" aria-hidden="true">
            <svg viewBox="0 0 64 64" width="38" height="38" role="img" aria-label="EdenAir">
                <defs>
                    <linearGradient id="sb-p" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#DCF1EA"/></linearGradient>
                    <linearGradient id="sb-acc" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#DDEE93"/><stop offset="1" stop-color="#C9D870"/></linearGradient>
                    <linearGradient id="sb-bg" x1="0" y1="0" x2="0.25" y2="1"><stop offset="0" stop-color="#48946F"/><stop offset="1" stop-color="#163829"/></linearGradient>
                    <radialGradient id="sb-glow" cx="50%" cy="42%" r="60%"><stop offset="0" stop-color="#8FD6C8" stop-opacity=".55"/><stop offset="1" stop-color="#8FD6C8" stop-opacity="0"/></radialGradient>
                </defs>
                <rect x="4" y="4" width="56" height="56" rx="17" fill="url(#sb-bg)"/>
                <circle cx="32" cy="30" r="22" fill="url(#sb-glow)"/>
                <rect x="4.8" y="4.8" width="54.4" height="54.4" rx="16.2" fill="none" stroke="#ffffff" stroke-opacity=".18" stroke-width="1.1"/>
                <g transform="translate(32 33) scale(0.62) translate(-32 -32)">
                    <g fill="none" stroke="url(#sb-p)" stroke-width="5.2" stroke-linecap="round">
                        <path d="M13 20 H39 a5.5 5.5 0 1 0 -5 -5.4"/>
                        <path d="M13 32 H47 a6 6 0 1 1 -6 6"/>
                        <path d="M13 44 H33 a5 5 0 1 0 -4.4 5"/>
                    </g>
                    <circle cx="50" cy="20" r="3.6" fill="url(#sb-acc)"/>
                </g>
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
            <span class="ea-sidebar-label">Comprar EdenAir</span>
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
