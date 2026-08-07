<?php
/**
 * ============================================================================
 * LAYOUT DEL PANEL — el esqueleto de todas las pantallas internas.
 * ============================================================================
 * Vistas que lo usan: panel, bienvenida, dispositivos, conectar, ambientes,
 * editar ambiente, perfil y compra.
 *
 * Acá vive TODO lo que se repite: <head>, menú lateral, barra superior,
 * mensajes flash y scripts. Una vista solo escribe su propio contenido.
 *
 * ---------------------------------------------------------------------------
 * CÓMO SE USA (así arranca cada vista interna)
 * ---------------------------------------------------------------------------
 *   <?php $this->setData([
 *       'tituloPagina'  => 'EdenAir · Mis dispositivos',
 *       'sidebarActivo' => 'dispositivos',
 *       'cabecera'      => ['titulo' => 'Mis dispositivos', 'bajada' => '2 vinculados'],
 *   ]) ?>
 *   <?= $this->extend('layouts/panel') ?>
 *   <?= $this->section('contenido') ?>
 *       ... solo el HTML de ESTA pantalla ...
 *   <?= $this->endSection() ?>
 *
 * OJO: lo que escribas FUERA de section('contenido') no se muestra. Es cómo
 * funcionan los layouts de CodeIgniter, no es un error de la página.
 *
 * ---------------------------------------------------------------------------
 * OPCIONES (todas opcionales, con valor por defecto)
 * ---------------------------------------------------------------------------
 *   tituloPagina    string  Texto de la pestaña del navegador.
 *   descripcion     string  <meta name="description">.
 *   sidebarActivo   string  Ítem marcado del menú: inicio | dispositivos |
 *                           ambientes | perfil | compra.
 *   cantidadEquipos int     Número del globito de "Mis dispositivos".
 *   cabecera        array   Config de la barra superior. Ver partials/panel_header.php:
 *                           titulo, bajada, botones, extra, usuario.
 *   claseContenido  string  Clase extra del contenedor (ej: 'ea-welcome').
 *   attrsApp        string  Atributos sueltos del div .ea-dashboard.
 *   conLoader       bool    Pantalla de carga de entrada. Default false.
 *   conScrollSuave  bool    Scroll suave con GSAP ScrollSmoother. Default false.
 *   scripts         array   JS extra de la pantalla, ej: ['JS/conectar.js'].
 */
$tituloPagina    = $tituloPagina    ?? 'EdenAir · Panel';
$descripcion     = $descripcion     ?? '';
$sidebarActivo   = $sidebarActivo   ?? '';
$cantidadEquipos = $cantidadEquipos ?? null;
$claseContenido  = $claseContenido  ?? '';
$attrsApp        = $attrsApp        ?? '';
$conLoader       = $conLoader       ?? false;
$conScrollSuave  = $conScrollSuave  ?? false;
$cabecera        = is_array($cabecera ?? null) ? $cabecera : [];
$scripts         = is_array($scripts  ?? null) ? $scripts  : [];

// Con loader el body arranca "cargando" y dashboard.js lo destapa al terminar.
$estadoBody = $conLoader ? 'dashboard-loading' : 'dashboard-ready';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => $tituloPagina,
        'extraCss'  => ['CSS/dashboard.css'],
        'extraHead' => ($descripcion !== '' ? '<meta name="description" content="' . esc($descripcion) . '">' : '')
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="color-scheme" content="light dark">',
    ]) ?>
</head>
<body class="dashboard-body ea-body ea-dashboard-body <?= $estadoBody ?>">

<?php if ($conLoader): ?>
    <?= view('partials/panel_loader') ?>
<?php endif; ?>

<div class="ea-dashboard" data-dashboard-app <?= $attrsApp ?>>

    <?= view('partials/panel_sidebar', [
        'active'       => $sidebarActivo,
        'devicesCount' => $cantidadEquipos,
    ]) ?>

    <?= view('partials/panel_header', ['cabecera' => $cabecera]) ?>

    <?php /* El scroll suave envuelve SOLO el contenido: el menú lateral y la
             barra superior son fixed y GSAP los rompería si quedaran adentro. */ ?>
    <?php if ($conScrollSuave): ?><div id="smooth-wrapper"><div id="smooth-content"><?php endif; ?>

        <main class="ea-main">
            <div class="ea-content <?= esc($claseContenido) ?>">
                <?= view('partials/flashes') ?>
                <?= $this->renderSection('contenido') ?>
            </div>
        </main>

    <?php if ($conScrollSuave): ?></div></div><?php endif; ?>

    <div class="ea-sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<!-- =========================== SCRIPTS ===================================
     Orden fijo: tema (claro/oscuro) → dashboard (menú, loader, diálogos)
     → [GSAP si hay scroll suave] → los de la pantalla. -->
<script src="<?= asset('JS/tema.js') ?>"></script>
<script src="<?= asset('JS/dashboard.js') ?>"></script>

<?php if ($conScrollSuave): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollTrigger.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollSmoother.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="<?= asset('JS/dashboard-gsap.js') ?>"></script>
    <script src="<?= asset('JS/ea-scrollbar.js') ?>"></script>
<?php endif; ?>

<?php foreach ($scripts as $script): ?>
    <script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>
