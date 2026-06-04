<?php
/**
 * EdenAir — Logo / wordmark partial.
 *
 * Variables esperadas (todas opcionales):
 *   $size       int       Tamaño del símbolo en px (default 40).
 *   $tone       string    "ink" | "cream" | "moss" (default "ink").
 *                         ink: se adapta a claro/oscuro automáticamente.
 *                         cream: pensado para fondos oscuros.
 *                         moss: variante verde para casos especiales.
 *   $variant    string    "horizontal" | "stacked" | "mark" | "wordmark" (default "horizontal").
 *   $showSlogan bool      Mostrar slogan (default false).
 *   $href       string|null  Si se pasa, el logo es un link.
 *   $subtitle   string|null  Texto pequeño bajo el wordmark.
 */
$size       = isset($size) && (int) $size > 0 ? (int) $size : 40;
$tone       = isset($tone) && in_array($tone, ['ink', 'cream', 'moss'], true) ? $tone : 'ink';
$variant    = isset($variant) && in_array($variant, ['horizontal', 'stacked', 'mark', 'wordmark'], true) ? $variant : 'horizontal';
$showSlogan = $showSlogan ?? false;
$href       = $href       ?? null;
$subtitle   = $subtitle   ?? null;

$wordmarkSize = max(18, (int) round($size * 0.55));

/**
 * Marca oficial "e" — la inicial de EdenAir dibujada como una corriente de
 * aire: un solo trazo redondeado con degradado verde → aqua (el aire que se
 * hace visible). El color del trazo se resuelve por tono (ink / cream / moss)
 * y modo oscuro vía variables CSS --ea-elogo-* (ver eden-brand.css), por lo
 * que el mismo SVG se adapta a cualquier superficie de la página.
 */
$u       = 'e' . bin2hex(random_bytes(3));
$ePath   = 'M15 39 C21 30 30 33 36 41 C45 51 60 51 80 52 C86 36 70 24 52 24 '
         . 'C34 24 22 38 24 52 C26 68 42 78 60 76 C76 74 90 70 104 60';
$markW   = (int) round($size * 116 / 70); // conserva la proporción del viewBox
$markSvg = ''
    . '<svg viewBox="2 16 116 70" width="' . $markW . '" height="' . $size . '" role="img" aria-label="EdenAir" class="ea-logo-mark ea-logo-mark--e">'
    . '<defs>'
    . '<linearGradient id="' . $u . '" x1="0.08" y1="0.1" x2="0.92" y2="0.92">'
    . '<stop offset="0" stop-color="var(--ea-elogo-a)"/>'
    . '<stop offset="0.55" stop-color="var(--ea-elogo-b)"/>'
    . '<stop offset="1" stop-color="var(--ea-elogo-c)"/>'
    . '</linearGradient>'
    . '</defs>'
    . '<path d="' . $ePath . '" fill="none" stroke="url(#' . $u . ')" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>'
    . '</svg>';

$wordmark = '<span class="ea-logo-word" style="font-size:' . $wordmarkSize . 'px;">'
          . 'Eden<em>Air</em>'
          . '</span>';

$classVariant = 'ea-logo--' . esc($variant);
$classTone    = 'ea-logo--tone-' . esc($tone);

if ($variant === 'mark') {
    $inner = $markSvg;
} elseif ($variant === 'wordmark') {
    $inner = $wordmark;
} elseif ($variant === 'stacked') {
    $inner = $markSvg . $wordmark;
} else {
    $textBlock = '<span class="ea-logo-text">' . $wordmark;
    if ($subtitle) {
        $textBlock .= '<small class="ea-logo-sub">' . esc($subtitle) . '</small>';
    }
    $textBlock .= '</span>';
    $inner = $markSvg . $textBlock;
}

$tag = $href !== null ? 'a' : 'span';
$attrs = 'class="ea-logo ' . $classVariant . ' ' . $classTone . '"';
if ($href !== null) {
    $attrs .= ' href="' . esc($href) . '"';
}
?>
<<?= $tag ?> <?= $attrs ?>>
    <?= $inner ?>
</<?= $tag ?>>

<?php if ($showSlogan): ?>
    <p class="ea-slogan">Respirá mejor, viví más cómodo.</p>
<?php endif; ?>
