<?php
/**
 * COMPRAR EDENAIR — la pantalla del producto.
 * Ruta: /panel/compra · Controlador: CompraController::index
 * Recibe: $producto (CompraService::producto) y $compras (CompraService::listarDeUsuario)
 *
 * El cobro es real, con Mercado Pago (Checkout Pro): el botón es el submit de
 * un formulario POST a /panel/compra/pagar, que genera el link de pago y manda
 * al usuario a la página de Mercado Pago. Al terminar, Mercado Pago lo devuelve
 * acá con el resultado como mensaje flash. compra.js solo maneja el estado
 * "cargando" del botón.
 */
$producto = $producto ?? ['nombre' => 'EdenAir Core', 'precio' => '', 'moneda' => 'ARS', 'configurado' => false];
$compras  = $compras ?? [];

$beneficios = [
    'Dispositivo EdenAir (módulo ESP32) listo para usar',
    'Acceso completo al dashboard en tiempo real',
    'Temperatura, humedad, CO₂ y calidad de aire',
    'Historial de mediciones y recomendaciones automáticas',
    'Automatización de aire acondicionado y humidificación',
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
                        <h2 id="compraTitulo" class="ea-plan-title ea-serif"><?= esc($producto['nombre']) ?></h2>
                        <p class="ea-plan-desc">
                            El dispositivo inteligente más el acceso completo al dashboard
                            para monitorear y mejorar la calidad del ambiente.
                        </p>
                    </header>

                    <div class="ea-plan-price">
                        <span class="ea-plan-currency">$</span>
                        <span class="ea-plan-amount"><?= esc($producto['precio']) ?></span>
                        <span class="ea-plan-period"><?= esc($producto['moneda']) ?> · pago único<br>compra del producto</span>
                    </div>

                    <!-- El monto NO viaja en el formulario: lo pone el servidor
                         (CompraService::PRECIO). Solo va el token CSRF. -->
                    <form class="ea-plan-cta" action="<?= site_url('panel/compra/pagar') ?>" method="post" data-plan-form>
                        <?= csrf_field() ?>
                        <button type="submit" class="ea-plan-btn" data-plan-buy
                                <?= $producto['configurado'] ? '' : 'disabled' ?>>
                            <span class="ea-plan-btn-spinner" aria-hidden="true"></span>
                            <?= icono('carrito', 16) ?>
                            <span data-plan-buy-text>Comprar EdenAir</span>
                        </button>
                        <p class="ea-plan-note">
                            <?= icono('candado', 12) ?>
                            <?php if ($producto['configurado']): ?>
                                Pago seguro · Mercado Pago
                            <?php else: ?>
                                Pagos no disponibles · falta configurar Mercado Pago
                            <?php endif; ?>
                        </p>
                    </form>
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

    <?php if ($compras !== []): ?>
        <!-- ===== Tus compras (estado según Mercado Pago) ===== -->
        <section class="ea-plan-history" aria-labelledby="comprasTitulo">
            <h3 id="comprasTitulo" class="ea-plan-history-title">Tus compras</h3>
            <ul class="ea-plan-history-list">
                <?php foreach ($compras as $c): ?>
                    <li class="ea-plan-history-item">
                        <div class="ea-plan-history-main">
                            <span class="ea-plan-history-name"><?= esc($c['producto']) ?></span>
                            <span class="ea-plan-history-meta ea-mono">
                                <?= esc(date('d/m/Y H:i', strtotime($c['fecha']))) ?> · <?= esc($c['referencia']) ?>
                            </span>
                        </div>
                        <div class="ea-plan-history-side">
                            <span class="ea-plan-history-amount">$ <?= esc($c['monto']) ?> <small><?= esc($c['moneda']) ?></small></span>
                            <span class="ea-badge tone-<?= esc($c['estado_tono']) ?>">
                                <span class="ea-dot"></span><?= esc($c['estado_label']) ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

<?= $this->endSection() ?>
