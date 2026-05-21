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
<link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/branding/favicon.svg') ?>">
<link rel="alternate icon" href="<?= base_url('favicon.ico') ?>">
<link rel="mask-icon" href="<?= base_url('assets/img/branding/mark-mono-dark.svg') ?>" color="#1c4029">
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
<link rel="stylesheet" href="<?= base_url('CSS/eden-brand.css') ?>">
<?php foreach ($extraCss as $cssPath): ?>
    <link rel="stylesheet" href="<?= base_url($cssPath) ?>">
<?php endforeach; ?>
<?= $extraHead ?>
