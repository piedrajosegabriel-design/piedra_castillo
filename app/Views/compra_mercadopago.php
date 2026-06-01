<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'EdenAir · Plan Inicial',
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => '<meta name="description" content="Plan Inicial EdenAir: comenzá a monitorear y mejorar la calidad del ambiente desde solo US$5.">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body">
<div class="ea-dashboard" data-dashboard-app>

    <?= view('partials/dashboard_sidebar', ['active' => 'compra']) ?>

    <!-- =========================== MAIN =========================== -->
    <main class="ea-main">

        <header class="dashboard-header ea-header">
            <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
                <span></span><span></span><span></span>
            </button>

            <div class="ea-header-titles">
                <h1>Compra</h1>
                <p>Plan Inicial · acceso a EdenAir</p>
            </div>

            <span class="ea-chip ea-chip-status status-success" title="Estado del checkout">
                <span class="ea-pulse"></span>
                <span>Compra simulada</span>
            </span>

            <div class="ea-header-tools">
                <?= view('partials/theme_toggle') ?>
            </div>
        </header>

        <div class="ea-content ea-plan-content">

            <!-- ============== Plan card (sin scroll) ============== -->
            <section class="ea-plan-stage" aria-label="Plan disponible">
                <article class="ea-plan-card" aria-labelledby="planInicialTitulo">
                    <span class="ea-plan-glow" aria-hidden="true"></span>

                    <div class="ea-plan-grid">
                        <div class="ea-plan-left">
                            <header class="ea-plan-head">
                                <span class="ea-plan-tag">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true"><path d="M12 2.5l2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 8.8l6.1-.7z"/></svg>
                                    Recomendado para empezar
                                </span>
                                <h2 id="planInicialTitulo" class="ea-plan-title ea-serif">Plan Inicial</h2>
                                <p class="ea-plan-desc">Ideal para comenzar a monitorear y mejorar la calidad del ambiente.</p>
                            </header>

                            <div class="ea-plan-price">
                                <span class="ea-plan-currency">US$</span>
                                <span class="ea-plan-amount">5</span>
                                <span class="ea-plan-period">pago único</span>
                            </div>

                            <div class="ea-plan-cta">
                                <button type="button" class="ea-plan-btn" data-plan-buy>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                                    <span>Comprar plan</span>
                                </button>
                                <p class="ea-plan-note">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="12" height="12" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                                    Compra simulada · sin integración de pago todavía
                                </p>
                            </div>
                        </div>

                        <div class="ea-plan-right">
                            <span class="ea-plan-benefits-label">Incluye</span>
                            <ul class="ea-plan-benefits" aria-label="Beneficios incluidos">
                                <?php
                                $beneficios = [
                                    'Dashboard principal en tiempo real',
                                    'Temperatura, humedad, CO₂ y calidad de aire',
                                    'Historial de mediciones',
                                    'Recomendaciones automáticas',
                                    'Modo claro y oscuro',
                                    'Acceso multi-dispositivo',
                                ];
                                foreach ($beneficios as $b): ?>
                                    <li>
                                        <span class="ea-plan-check" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><path d="M5 12.5l4 4 10-10"/></svg>
                                        </span>
                                        <span><?= esc($b) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </article>
            </section>

            <!-- ============== Confirmación simulada ============== -->
            <div class="ea-plan-toast" data-plan-toast role="status" aria-live="polite">
                <span class="ea-plan-toast-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M5 12.5l4 4 10-10"/></svg>
                </span>
                <span>¡Compra simulada exitosa! Pronto activaremos el cobro real.</span>
            </div>

        </div>
    </main>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
<script>
    (function () {
        var btn   = document.querySelector('[data-plan-buy]');
        var toast = document.querySelector('[data-plan-toast]');
        if (!btn || !toast) return;
        var timer;
        btn.addEventListener('click', function () {
            toast.classList.add('is-visible');
            btn.classList.add('is-done');
            clearTimeout(timer);
            timer = setTimeout(function () {
                toast.classList.remove('is-visible');
                btn.classList.remove('is-done');
            }, 3200);
        });
    })();
</script>
</body>
</html>
