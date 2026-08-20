<?php
/**
 * Pantalla de carga de entrada al panel: la marca con los anillos girando
 * mientras el dashboard termina de armarse.
 *
 * Estructura: solo el HTML de abajo.
 * Animación:  los anillos y el halo giran por CSS (dashboard.css, sección
 *             LOADER). Quien la saca de pantalla es dashboard.js, que le quita
 *             la clase 'dashboard-loading' al <body> cuando la página cargó.
 *
 * Se activa con 'conLoader' => true en la config de la vista.
 */
?>
<!-- Sin JavaScript no hay quien apague el loader: lo escondemos de entrada.
     Este <style> tiene que quedar acá adentro porque depende de <noscript>;
     movido a dashboard.css perdería esa condición y aplicaría siempre. -->
<noscript>
    <style>
        .dashboard-loading .dashboard-loader { display: none; }
        .dashboard-loading .ea-dashboard { opacity: 1; transform: none; }
    </style>
</noscript>

<div class="ea-loader dashboard-loader" data-dashboard-loader role="status" aria-live="polite" aria-label="Preparando tu ambiente inteligente">
    <div class="ea-loader-pattern" aria-hidden="true"></div>
    <div class="ea-loader-grain" aria-hidden="true"></div>

    <div class="ea-loader-inner">
        <span class="ea-loader-eyebrow">
            <span class="ea-loader-dot" aria-hidden="true"></span>
            EdenAir
        </span>

        <div class="ea-loader-orbit" aria-hidden="true">
            <span class="ea-loader-halo"></span>
            <span class="ea-loader-ring ea-loader-ring--a"></span>
            <span class="ea-loader-ring ea-loader-ring--b"></span>
            <span class="ea-loader-ring ea-loader-ring--c"></span>
            <?php /* Colores fijos, no variables de tema: el loader siempre va
                     sobre fondo oscuro, en modo claro y en modo oscuro. */ ?>
            <span class="ea-loader-logo ea-loader-logo--e">
                <?= marca_e('ld-e', ['#F6F4EC', '#BCE9DC', '#5BE5B6']) ?>
            </span>
        </div>

        <div class="ea-loader-text">
            <strong class="ea-loader-name">Eden<em>Air</em></strong>
            <p class="ea-loader-msg">Preparando tu ambiente inteligente</p>
        </div>

        <div class="ea-loader-progress" aria-hidden="true"><span></span></div>
    </div>
</div>
