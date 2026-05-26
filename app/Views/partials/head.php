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
<?php
    // Cache-bust automático de CSS para evitar versiones cacheadas durante dev
    $eaCssBust = function (string $relativePath): string {
        $abs = FCPATH . $relativePath;
        $v   = is_file($abs) ? filemtime($abs) : time();
        return base_url($relativePath) . '?v=' . $v;
    };
?>
<link rel="stylesheet" href="<?= htmlspecialchars($eaCssBust('CSS/eden-brand.css'), ENT_QUOTES, 'UTF-8') ?>">
<?php foreach ($extraCss as $cssPath): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($eaCssBust($cssPath), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
<?= $extraHead ?>
