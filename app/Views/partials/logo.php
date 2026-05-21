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
 * Sistema visual del isotipo "EdenAir Leaf"
 *  - Anillo abierto    → aire en circulación / ambiente / contenedor orgánico
 *  - Hoja estilizada   → naturaleza / vida / pureza
 *  - Vena central      → flujo de aire / trayectoria / respiración
 *  - Punto sensor      → medición / origen del dato / semilla
 *
 *  La punta de la hoja "respira" hacia afuera por la abertura del anillo (≈1h),
 *  y el punto sensor queda anclado en la base (≈7-8h). Composición diagonal
 *  ascendente que transmite movimiento, equilibrio y precisión técnica.
 */
$markSvg = ''
    . '<svg viewBox="0 0 64 64" width="' . $size . '" height="' . $size . '" '
    . 'role="img" aria-label="EdenAir" class="ea-logo-mark ea-logo-mark--' . esc($tone) . '">'
    . '<path d="M 41 7.5 A 26 26 0 1 0 54.5 45" fill="none" stroke-width="2.5" stroke-linecap="round" class="ea-logo-stroke" />'
    . '<path d="M 20 46 C 17 32, 25 18, 48 14 C 44 30, 34 42, 20 46 Z" stroke-width="1.5" stroke-linejoin="round" class="ea-logo-leaf" />'
    . '<path d="M 20 46 C 28 38, 38 26, 48 14" fill="none" stroke-width="1.5" stroke-linecap="round" class="ea-logo-vein" />'
    . '<circle cx="20" cy="46" r="2.6" class="ea-logo-dot" />'
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
