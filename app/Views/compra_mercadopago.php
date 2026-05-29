<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'EdenAir | Compra',
        'extraCss' => ['CSS/dashboard.css'],
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body dashboard-loading">
<div class="ea-dashboard" data-dashboard-app>
    <aside class="ea-sidebar" id="dashboardSidebar" aria-label="Navegacion principal">
        <div class="ea-sidebar-brand">
            <span class="ea-sidebar-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M22.5 7.5C12.8 7.5 7 13.4 7 21c0 1.8.4 3.4 1 4.8C13.6 23 18.6 19 22 13" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M22.5 7.5c1.6 5-.2 11.4-4.3 14.7-2.7 2.1-5.8 2.7-8.7 1.9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="ea-sidebar-word"><b>Eden<em>Air</em></b><span>Compra</span></span>
        </div>
        <div class="ea-sidebar-section">Sistema</div>
        <a href="<?= site_url('panel') ?>" class="ea-sidebar-item"><span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="9" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="5" rx="1.6"/><rect x="13.5" y="11.5" width="7" height="9" rx="1.6"/><rect x="3.5" y="15.5" width="7" height="5" rx="1.6"/></svg></span><span class="ea-sidebar-label">Dashboard</span></a>
        <a href="<?= site_url('panel/perfil') ?>" class="ea-sidebar-item"><span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 10-16 0"/><circle cx="12" cy="7" r="4"/></svg></span><span class="ea-sidebar-label">Editar datos</span></a>
        <a href="<?= site_url('panel/compra') ?>" class="ea-sidebar-item is-active"><span class="ea-sidebar-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg></span><span class="ea-sidebar-label">Comprar</span></a>
        <div class="ea-sidebar-footer"><span class="ea-sidebar-dot"></span><span class="ea-sidebar-foot-label">Interfaz demo</span></div>
    </aside>

    <main class="ea-main">
        <header class="dashboard-header ea-header">
            <button type="button" class="ea-header-icon-btn" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M9 4.5v15"/></svg></button>
            <div class="ea-header-titles"><h1>Compra</h1><p>Interfaz visual de checkout con estilo Mercado Pago</p></div>
            <span class="ea-chip ea-chip-status status-success"><span class="ea-pulse"></span><span>Demo segura</span></span>
            <div class="ea-header-tools"><?= view('partials/theme_toggle') ?><a href="<?= site_url('logout') ?>" class="ea-header-icon-btn ea-header-logout" title="Cerrar sesion" aria-label="Cerrar sesion"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4"/><path d="M10 16l-4-4 4-4"/><path d="M6 12h12"/></svg></a></div>
        </header>

        <div class="ea-content">
            <div class="ea-sec"><h2>Checkout</h2><span class="ea-sec-right">solo interfaz · sin cobro real</span></div>

            <article class="ea-card ea-checkout-product">
                <div class="ea-checkout-device" aria-hidden="true">
                    <span></span>
                    <i></i>
                </div>
                <div class="ea-checkout-copy">
                    <span class="ea-badge tone-success"><span class="ea-dot"></span>Producto seleccionado</span>
                    <h2 class="ea-serif">EdenAir Kit Ambiental</h2>
                    <p>Sensor inteligente para monitorear temperatura, humedad, CO2 y calidad de aire desde el panel.</p>
                    <div class="ea-checkout-specs">
                        <span>ESP32</span>
                        <span>4 sensores</span>
                        <span>Panel web</span>
                    </div>
                </div>
            </article>

            <article class="ea-card ea-checkout-card">
                <div class="ea-card-head">
                    <h3>Resumen de compra</h3>
                    <span class="ea-mono ea-card-meta">MERCADO PAGO UI</span>
                </div>
                <div class="ea-checkout-line"><span>EdenAir Kit Ambiental</span><strong>$ 89.999</strong></div>
                <div class="ea-checkout-line"><span>Envio</span><strong>Gratis</strong></div>
                <div class="ea-checkout-total"><span>Total</span><strong>$ 89.999</strong></div>
                <button type="button" class="ea-mp-button">Pagar con Mercado Pago</button>
                <p class="ea-checkout-note">Boton de muestra. No se conecta con Mercado Pago ni procesa pagos.</p>
            </article>

            <article class="ea-card ea-checkout-card">
                <div class="ea-card-head">
                    <h3>Datos de entrega</h3>
                    <span class="ea-mono ea-card-meta">DEMO</span>
                </div>
                <div class="ea-account-form">
                    <label>Nombre y apellido<input type="text" value="" placeholder="Comprador demo"></label>
                    <label>Correo<input type="email" value="" placeholder="correo@gmail.com"></label>
                    <label class="ea-account-wide">Direccion<input type="text" value="" placeholder="Calle, numero, ciudad"></label>
                </div>
            </article>
        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
