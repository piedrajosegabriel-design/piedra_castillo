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

$markSvg = ''
    . '<svg viewBox="0 0 100 100" width="' . $size . '" height="' . $size . '" '
    . 'role="img" aria-label="EdenAir" class="ea-logo-mark ea-logo-mark--' . esc($tone) . '">'
    . '<circle cx="50" cy="50" r="44" fill="none" stroke-width="2" class="ea-logo-stroke" />'
    . '<path d="M 18 70 C 30 35, 60 25, 82 30" fill="none" stroke-width="2" stroke-linecap="round" class="ea-logo-stroke" />'
    . '<path d="M 18 70 C 40 60, 65 55, 82 30" fill="none" stroke-width="2" stroke-linecap="round" class="ea-logo-accent" />'
    . '<circle cx="50" cy="50" r="2.4" class="ea-logo-dot" />'
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
