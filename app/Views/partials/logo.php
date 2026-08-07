<?php
/**
 * ============================================================================
 * LOGO / MARCA DE EDENAIR
 * ============================================================================
 *
 *   <?= view('partials/logo', ['logo' => ['href' => site_url('/'), 'size' => 36]]) ?>
 *
 * OJO CON EL PARÁMETRO: va TODO adentro de una clave 'logo'. No es capricho.
 * CodeIgniter comparte los datos entre los view() de una misma página, así que
 * si el navbar dibuja el logo con 'href' y después el pie lo dibuja sin él, el
 * segundo se queda con el href del primero (el pie terminaba siendo un link,
 * con el subtítulo del navbar colgado). Agrupando todo en una clave, la que no
 * se pasa toma su valor por defecto y no hay herencia posible.
 *
 * ---------------------------------------------------------------------------
 * CLAVES DE 'logo' (todas opcionales)
 * ---------------------------------------------------------------------------
 *   size       int     Alto del símbolo en px (default 40).
 *   tone       string  ink | cream | moss (default ink).
 *                      ink   → se adapta solo a claro/oscuro
 *                      cream → para fondos oscuros (el pie)
 *                      moss  → verde monocromo
 *   variant    string  horizontal | stacked | mark | wordmark (default horizontal).
 *   href       string  Si se pasa, el logo es un enlace.
 *   subtitle   string  Línea chica bajo el nombre.
 *   showSlogan bool    Agrega el slogan debajo (default false).
 */
$logo = is_array($logo ?? null) ? $logo : [];

$size       = isset($logo['size']) && (int) $logo['size'] > 0 ? (int) $logo['size'] : 40;
$tone       = in_array($logo['tone']    ?? '', ['ink', 'cream', 'moss'], true) ? $logo['tone'] : 'ink';
$variant    = in_array($logo['variant'] ?? '', ['horizontal', 'stacked', 'mark', 'wordmark'], true) ? $logo['variant'] : 'horizontal';
$href       = $logo['href']       ?? null;
$subtitle   = $logo['subtitle']   ?? null;
$showSlogan = $logo['showSlogan'] ?? false;

// El nombre escrito acompaña al símbolo: poco más de la mitad de su alto.
$wordmarkSize = max(18, (int) round($size * 0.55));

// El id del degradado tiene que ser único en la página: si el navbar y el pie
// usaran el mismo, el segundo pisaría los colores del primero.
$marca    = marca_e('e' . bin2hex(random_bytes(3)), null, $size);
$wordmark = '<span class="ea-logo-word" style="font-size:' . $wordmarkSize . 'px;">Eden<em>Air</em></span>';

$contenido = match ($variant) {
    'mark'     => $marca,
    'wordmark' => $wordmark,
    'stacked'  => $marca . $wordmark,
    default    => $marca . '<span class="ea-logo-text">' . $wordmark
                . ($subtitle ? '<small class="ea-logo-sub">' . esc($subtitle) . '</small>' : '')
                . '</span>',
};

$etiqueta  = $href !== null ? 'a' : 'span';
$atributos = 'class="ea-logo ea-logo--' . esc($variant) . ' ea-logo--tone-' . esc($tone) . '"'
           . ($href !== null ? ' href="' . esc($href) . '"' : '');
?>
<<?= $etiqueta ?> <?= $atributos ?>>
    <?= $contenido ?>
</<?= $etiqueta ?>>

<?php if ($showSlogan): ?>
    <p class="ea-slogan">Respirá mejor, viví más cómodo.</p>
<?php endif; ?>
