<?php
/**
 * ============================================================================
 * LAYOUT DE ACCESO — login, registro, recuperar y restablecer contraseña.
 * ============================================================================
 * Las cuatro pantallas son la misma tarjeta centrada con un formulario adentro.
 * Acá vive el esqueleto; cada vista solo escribe sus campos.
 *
 * ---------------------------------------------------------------------------
 * CÓMO SE USA
 * ---------------------------------------------------------------------------
 *   <?php $this->setData([
 *       'tituloPagina' => 'EdenAir | Login',
 *       'navSubtitulo' => 'Ingreso al sistema',
 *       'navBoton'     => ['texto' => 'Crear cuenta', 'href' => site_url('registro')],
 *       'encabezado'   => [
 *           'sobretitulo' => 'Ingreso',
 *           'titulo'      => 'Accedé a tu panel.',
 *           'bajada'      => 'Usá tu nombre de usuario o correo electrónico.',
 *       ],
 *       'scripts'      => ['JS/login.js'],
 *   ]) ?>
 *   <?= $this->extend('layouts/acceso') ?>
 *   <?= $this->section('contenido') ?>
 *       ... el <form> y nada más ...
 *   <?= $this->endSection() ?>
 *
 * OJO: lo que escribas FUERA de section('contenido') no se muestra.
 *
 * ---------------------------------------------------------------------------
 * OPCIONES
 * ---------------------------------------------------------------------------
 *   tituloPagina  string  Pestaña del navegador.
 *   navSubtitulo  string  Línea chica al lado del logo, arriba.
 *   navBoton      array   ['texto', 'href'] del botón de la barra superior.
 *   encabezado    array   ['sobretitulo', 'titulo', 'bajada'] de la tarjeta.
 *   scripts       array   JS de la pantalla, ej: ['JS/registro.js'].
 */
$tituloPagina = $tituloPagina ?? 'EdenAir';
$navSubtitulo = $navSubtitulo ?? 'Acceso';
$navBoton     = is_array($navBoton   ?? null) ? $navBoton   : [];
$encabezado   = is_array($encabezado ?? null) ? $encabezado : [];
$scripts      = is_array($scripts    ?? null) ? $scripts    : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', ['title' => $tituloPagina]) ?>
</head>
<body class="ea-body">
<div class="ea-shell">

    <?= view('partials/navbar', [
        'subtitle' => $navSubtitulo,
        'actions'  => isset($navBoton['texto'])
            ? '<a href="' . esc((string) $navBoton['href']) . '" class="ea-button ea-button-secondary">' . esc((string) $navBoton['texto']) . '</a>'
            : null,
    ]) ?>

    <main class="ea-auth">
        <section class="ea-auth-main">
            <div class="ea-auth-card">

                <!-- Título de la pantalla -->
                <div>
                    <p class="ea-eyebrow"><?= esc((string) ($encabezado['sobretitulo'] ?? '')) ?></p>
                    <h1><?= esc((string) ($encabezado['titulo'] ?? '')) ?></h1>
                    <p class="ea-lede"><?= esc((string) ($encabezado['bajada'] ?? '')) ?></p>
                </div>

                <!-- Avisos de error / éxito del último envío -->
                <?= view('partials/mensajes_acceso') ?>

                <!-- El formulario de cada pantalla -->
                <?= $this->renderSection('contenido') ?>

            </div>
        </section>
    </main>

</div>

<script src="<?= asset('JS/tema.js') ?>"></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
