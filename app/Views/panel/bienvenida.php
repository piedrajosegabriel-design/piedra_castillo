<?php
/**
 * BIENVENIDA — lo primero que ve una cuenta que todavía no tiene dispositivos.
 * Ruta: /panel (cuando no hay equipos) · Controlador: PanelController::index
 * Recibe: $usuario
 *
 * ES UN ESTADO VACÍO, Y SE MANTIENE CORTO A PROPÓSITO
 * Dos bloques y nada más:
 *   1. Saludo + LA acción principal → sin hacer scroll para encontrarla.
 *   2. Los tres pasos, una línea cada uno → para llegar sabiendo qué pasa.
 *
 * Un solo botón primario en toda la pantalla. El camino de compra queda como
 * link secundario al lado, no como tarjeta del mismo peso: quien ya tiene el
 * equipo no debería tener que elegir entre dos opciones iguales.
 *
 * Se probó con más cosas (vista previa de las métricas, notas de ayuda, tira
 * de compra) y sobraba: en una pantalla de bienvenida, cada bloque extra es
 * una decisión más antes de la única que importa.
 *
 * JERARQUÍA DE TÍTULOS: el <h1> lo pone partials/panel_header.php, así que acá
 * se arranca en <h2> y se baja de a un nivel.
 *
 * ANIMACIÓN: la utilidad .ea-reveal (dashboard.css), que dashboard.js destapa
 * al entrar en pantalla y respeta prefers-reduced-motion sola.
 */
$usuario = isset($usuario) && is_array($usuario) ? $usuario : [];
$nombre  = trim((string) ($usuario['nombre'] ?? ''));

$pasos = [
    'Enchufá el equipo y esperá un minuto.',
    'Escaneá el QR con la cámara del celular.',
    'Elegí el WiFi de tu casa y listo.',
];

$this->setData([
    'tituloPagina'    => 'Eden Air · Bienvenido',
    'descripcion'     => 'Bienvenido a Eden Air. Conectá tu dispositivo y mirá la temperatura, la humedad y el CO₂ de tu espacio en tiempo real.',
    'sidebarActivo'   => 'inicio',
    'cantidadEquipos' => 0,
    'claseContenido'  => 'ea-welcome',
    'cabecera'        => [
        'titulo' => 'Inicio',
        'bajada' => 'Configurá tu primer dispositivo Eden Air',
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- ===== 1 · Saludo + la acción principal ===== -->
    <section class="ea-welcome-hero ea-reveal" aria-labelledby="ea-welcome-title">
        <span class="ea-welcome-badge">
            <?= icono('check', 13) ?>
            Tu cuenta está lista
        </span>

        <h2 id="ea-welcome-title" class="ea-welcome-title">
            <?php if ($nombre !== ''): ?>
                Hola, <?= esc($nombre) ?>.<br>
            <?php endif; ?>
            Bienvenido a <em>Eden&nbsp;Air</em>.
        </h2>

        <p class="ea-welcome-lede">
            Falta un solo paso para ver el aire de tu espacio en vivo. Se hace
            todo desde el celular. ¿Todavía no tenés tu equipo? Podés conseguirlo
            acá mismo.
        </p>

        <div class="ea-welcome-cta">
            <a href="<?= site_url('panel/dispositivos/conectar') ?>"
               class="ea-button ea-button-primary ea-button-buy">
                <?= icono('dispositivo-ok', 16) ?>
                Conectar mi Eden Air
            </a>
            <?php /* Botón fantasma, no link de texto: como link se perdía y el
                     camino de compra quedaba invisible. Sigue siendo secundario
                     por el estilo, no por esconderlo. */ ?>
            <a href="<?= site_url('panel/compra') ?>" class="ea-button ea-button-ghost">
                <?= icono('carrito', 16) ?>
                Comprar un Eden Air
            </a>
        </div>
    </section>

    <!-- ===== 2 · Los tres pasos, en una línea cada uno ===== -->
    <section class="ea-welcome-pasos ea-reveal" style="transition-delay:.08s"
             aria-labelledby="ea-pasos-title">
        <h3 id="ea-pasos-title" class="ea-welcome-subtitulo">Cómo se conecta</h3>

        <ol class="ea-pasos-lista">
            <?php foreach ($pasos as $i => $texto): ?>
                <li class="ea-paso">
                    <span class="ea-paso-num"><?= $i + 1 ?></span>
                    <span><?= esc($texto) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

<?= $this->endSection() ?>
