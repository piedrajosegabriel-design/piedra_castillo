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
 * Marca oficial "Corriente" (ráfagas de aire en circulación).
 * App-icon squircle: fondo verde gradiente + glifo de corriente blanco
 * + punto de acento cítrico (la medición). Es el mismo símbolo en claro y
 * oscuro porque lleva su propio fondo: lee siempre premium sobre cualquier
 * superficie. Fuente: edenair-brandmark.js del brand kit.
 */
$u       = 'co' . bin2hex(random_bytes(3));
$markSvg = ''
    . '<svg viewBox="0 0 64 64" width="' . $size . '" height="' . $size . '" role="img" aria-label="EdenAir" class="ea-logo-mark ea-logo-mark--corriente">'
    . '<defs>'
    . '<linearGradient id="' . $u . '-p" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#DCF1EA"/></linearGradient>'
    . '<linearGradient id="' . $u . '-acc" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#DDEE93"/><stop offset="1" stop-color="#C9D870"/></linearGradient>'
    . '<linearGradient id="' . $u . '-bg" x1="0" y1="0" x2="0.25" y2="1"><stop offset="0" stop-color="#48946F"/><stop offset="1" stop-color="#163829"/></linearGradient>'
    . '<linearGradient id="' . $u . '-gloss" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff" stop-opacity=".26"/><stop offset=".55" stop-color="#ffffff" stop-opacity="0"/></linearGradient>'
    . '<radialGradient id="' . $u . '-glow" cx="50%" cy="42%" r="60%"><stop offset="0" stop-color="#8FD6C8" stop-opacity=".55"/><stop offset="1" stop-color="#8FD6C8" stop-opacity="0"/></radialGradient>'
    . '<filter id="' . $u . '-sh" x="-45%" y="-45%" width="190%" height="190%"><feDropShadow dx="0" dy="2.2" stdDeviation="1.9" flood-color="rgba(8,28,20,.30)" flood-opacity="1"/></filter>'
    . '<filter id="' . $u . '-isz" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="3.2" stdDeviation="3.4" flood-color="rgba(16,40,28,.45)" flood-opacity="1"/></filter>'
    . '</defs>'
    . '<g filter="url(#' . $u . '-isz)"><rect x="4" y="4" width="56" height="56" rx="17" fill="url(#' . $u . '-bg)"/></g>'
    . '<circle cx="32" cy="30" r="22" fill="url(#' . $u . '-glow)"/>'
    . '<rect x="4" y="4" width="56" height="56" rx="17" fill="url(#' . $u . '-gloss)"/>'
    . '<rect x="4.8" y="4.8" width="54.4" height="54.4" rx="16.2" fill="none" stroke="#ffffff" stroke-opacity=".18" stroke-width="1.1"/>'
    . '<g transform="translate(32 33) scale(0.62) translate(-32 -32)">'
    . '<g filter="url(#' . $u . '-sh)" fill="none" stroke="url(#' . $u . '-p)" stroke-width="5.2" stroke-linecap="round">'
    . '<path d="M13 20 H39 a5.5 5.5 0 1 0 -5 -5.4"/>'
    . '<path d="M13 32 H47 a6 6 0 1 1 -6 6"/>'
    . '<path d="M13 44 H33 a5 5 0 1 0 -4.4 5"/>'
    . '</g>'
    . '<circle cx="50" cy="20" r="3.6" fill="url(#' . $u . '-acc)" filter="url(#' . $u . '-sh)"/>'
    . '</g>'
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
