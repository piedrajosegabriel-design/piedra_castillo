<?php
/**
 * EdenAir — encabezado HTML compartido para páginas públicas.
 *
 * Variables esperadas (opcionales):
 *   $title       string  Título de la pestaña.
 *   $extraCss    array   Listado de paths adicionales a CSS (relativos a /public).
 *   $extraHead   string  HTML adicional a inyectar antes de </head>.
 */
$title     = $title     ?? 'EdenAir';
$extraCss  = is_array($extraCss ?? null) ? $extraCss : [];
$extraHead = $extraHead ?? '';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1c4029">
<title><?= esc($title) ?></title>
<?php /* asset() en vez de base_url(): el favicon es de lo que más cachea el
         navegador, y sin el ?v=<fecha> se sigue viendo el icono viejo aunque
         el archivo haya cambiado. El .ico es el que usan los navegadores que
         ignoran el SVG; tiene que ser la marca de EdenAir, no el de CI4. */ ?>
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/branding/favicon.svg') ?>">
<link rel="alternate icon" type="image/x-icon" href="<?= asset('favicon.ico') ?>">
<link rel="mask-icon" href="<?= asset('assets/img/branding/mark-mono-dark.svg') ?>" color="#1c4029">
<script>
    (function () {
        try {
            var tema = localStorage.getItem('tema');
            if (tema === 'light' || tema === 'dark') {
                document.documentElement.setAttribute('data-theme', tema);
            }
        } catch (e) { /* ignore */ }
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php /* asset() agrega ?v=<fecha del archivo> para que el navegador no siga
         mostrando el CSS viejo después de un cambio. Ver app/Helpers/eden_helper.php */ ?>
<link rel="stylesheet" href="<?= asset('CSS/eden-brand.css') ?>">
<?php foreach ($extraCss as $cssPath): ?>
    <link rel="stylesheet" href="<?= asset($cssPath) ?>">
<?php endforeach; ?>
<?php /* Red de seguridad: .ea-reveal arranca en opacity:0 y lo destapa
         dashboard.js al entrar en pantalla. Si el JS no llega a correr
         (error de red, bloqueador, navegador viejo), sin esto la página se
         vería COMPLETAMENTE EN BLANCO. El contenido nunca debería depender
         del JS para ser visible. */ ?>
<noscript>
    <style>.ea-reveal { opacity: 1 !important; transform: none !important; }</style>
</noscript>
<?= $extraHead ?>
