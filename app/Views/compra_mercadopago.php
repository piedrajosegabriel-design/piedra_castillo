<?php
/**
 * COMPRAR EDENAIR — la pantalla del producto.
 * Ruta: /panel/compra · Controlador: PanelController::compra
 *
 * La compra todavía es simulada: el botón no cobra nada, solo muestra el aviso
 * de abajo (lo maneja public/JS/compra.js). Cuando se integre el pago real,
 * el <button data-plan-buy> pasa a ser el submit de un formulario.
 */
$beneficios = [
    'Dispositivo EdenAir (módulo ESP32) listo para usar',
    'Acceso completo al dashboard en tiempo real',
    'Temperatura, humedad, CO₂ y calidad de aire',
    'Historial de mediciones y recomendaciones automáticas',
    'Automatización de ventilación, humidificación y aroma',
    'Configuración de ambientes y multi-dispositivo',
];

$this->setData([
    'tituloPagina'   => 'EdenAir · Comprar producto',
    'descripcion'    => 'Comprá EdenAir: el dispositivo inteligente y el dashboard para monitorear y mejorar la calidad del ambiente.',
    'sidebarActivo'  => 'compra',
    'claseContenido' => 'ea-plan-content',
    'scripts'        => ['JS/compra.js'],
    'cabecera'       => [
        'titulo'  => 'Comprar EdenAir',
        'bajada'  => 'Dispositivo + dashboard · compra del producto',
        'usuario' => false,
        'extra'   => '<span class="ea-chip ea-chip-status status-success" title="Estado del checkout">'
                   . '<span class="ea-pulse"></span><span>Compra simulada</span></span>',
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- ===== La tarjeta del producto ===== -->
    <section class="ea-plan-stage" aria-label="Comprar EdenAir">
        <article class="ea-plan-card" aria-labelledby="compraTitulo">
            <span class="ea-plan-glow" aria-hidden="true"></span>

            <div class="ea-plan-grid">

                <!-- Izquierda: qué es, cuánto sale y el botón -->
                <div class="ea-plan-left">
                    <header class="ea-plan-head">
                        <span class="ea-plan-tag">
                            <?= icono('carrito', 13) ?>
                            Comprá tu dispositivo
                        </span>
                        <h2 id="compraTitulo" class="ea-plan-title ea-serif">EdenAir Core</h2>
                        <p class="ea-plan-desc">
                            El dispositivo inteligente más el acceso completo al dashboard
                            para monitorear y mejorar la calidad del ambiente.
                        </p>
                    </header>

                    <div class="ea-plan-price">
                        <span class="ea-plan-currency">$</span>
                        <span class="ea-plan-amount">450.000</span>
                        <span class="ea-plan-period">ARS · pago único<br>compra del producto</span>
                    </div>

                    <div class="ea-plan-cta">
                        <button type="button" class="ea-plan-btn" data-plan-buy>
                            <?= icono('carrito', 16) ?>
                            <span>Comprar EdenAir</span>
                        </button>
                        <p class="ea-plan-note">
                            <?= icono('candado', 12) ?>
                            Compra simulada · sin integración de pago todavía
                        </p>
                    </div>
                </div>

                <!-- Derecha: qué incluye (la lista sale del array $beneficios) -->
                <div class="ea-plan-right">
                    <span class="ea-plan-benefits-label">Incluye</span>
                    <ul class="ea-plan-benefits" aria-label="Beneficios incluidos">
                        <?php foreach ($beneficios as $beneficio): ?>
                            <li>
                                <span class="ea-plan-check" aria-hidden="true"><?= icono('check', 12) ?></span>
                                <span><?= esc($beneficio) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>
        </article>
    </section>

    <!-- ===== Aviso de compra simulada (lo muestra compra.js al apretar) ===== -->
    <div class="ea-plan-toast" data-plan-toast role="status" aria-live="polite">
        <span class="ea-plan-toast-ico" aria-hidden="true"><?= icono('check', 14) ?></span>
        <span>¡Compra simulada exitosa! Pronto activaremos el cobro real.</span>
    </div>

<?= $this->endSection() ?>
