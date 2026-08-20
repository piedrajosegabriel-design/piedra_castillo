<?php

/**
 * EdenAir — helper de vistas. Se carga solo en todas las vistas
 * (app/Config/Autoload.php → $helpers = ['eden']).
 *
 * Existe para una cosa: que el HTML de las vistas se pueda LEER.
 * Antes cada ícono era un bloque de 200-400 caracteres de coordenadas SVG
 * metido entre el título de una tarjeta y su botón. Ahora es icono('temp').
 */

if (! function_exists('icono')) {
    /**
     * Devuelve un ícono SVG del set de EdenAir.
     *
     *   <?= icono('carrito') ?>            → tamaño lo decide el CSS
     *   <?= icono('carrito', 16) ?>        → 16x16 px
     *   <?= icono('chevron', 14, 'mi-clase') ?>
     *
     * Todos son de trazo (no relleno) y heredan el color del texto
     * (stroke="currentColor"), así que se pintan solos según el contexto.
     * Si el nombre no existe devuelve string vacío: nunca rompe la página.
     *
     * @param string $nombre Clave del catálogo de abajo.
     * @param int    $tam    Lado en px. 0 = sin width/height (lo dimensiona el CSS).
     * @param string $clase  Clase CSS opcional para el <svg>.
     */
    function icono(string $nombre, int $tam = 0, string $clase = ''): string
    {
        // [trazado, grosor de línea, viewBox]
        static $catalogo = [
            // --- Navegación (sidebar y menús) ---
            'casa'           => ['<path d="M3 11l9-7 9 7"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/>', 1.6],
            'dispositivo'    => ['<rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/>', 1.6],
            'dispositivo-ok' => ['<rect x="4" y="3" width="16" height="13" rx="2"/><path d="M8 20h8M12 16v4"/><path d="M9 8.5l2.3 2.3L15 7"/>', 1.6],
            'ambiente'       => ['<path d="M4 21V10l8-6 8 6v11"/><path d="M10 21v-7h4v7"/>', 1.6],
            'automatizacion' => ['<path d="M4 7h7"/><path d="M4 17h11"/><circle cx="14" cy="7" r="2.2"/><circle cx="18" cy="17" r="2.2"/>', 1.6],
            'perfil'         => ['<path d="M20 21a8 8 0 10-16 0"/><circle cx="12" cy="7" r="4"/>', 1.6],
            'carrito'        => ['<path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>', 1.7],
            'salir'          => ['<path d="M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4"/><path d="M10 16l-4-4 4-4"/><path d="M6 12h12"/>', 1.6],

            // --- Sensores (tarjetas del dashboard) ---
            'temp' => ['<path d="M10 14V5a2 2 0 014 0v9a4 4 0 11-4 0z"/><path d="M12 8v6"/>', 1.6],
            'hum'  => ['<path d="M12 3.5c3.5 4 6 7.2 6 10.5a6 6 0 11-12 0c0-3.3 2.5-6.5 6-10.5z"/><path d="M9 14a3 3 0 003 3"/>', 1.6],
            'aire' => ['<path d="M3 9h11a3 3 0 100-6"/><path d="M3 14h14a3 3 0 110 6"/><path d="M3 19h5a2 2 0 100-4"/>', 1.6],
            'co2'  => ['<circle cx="8" cy="12" r="3"/><path d="M14 9.5a3 3 0 110 5"/><path d="M19 14.5a2 2 0 110 3"/>', 1.6],

            // --- Actuadores ---
            'ventilador'   => ['<circle cx="12" cy="12" r="1.6"/><path d="M12 10.4c0-3 1.2-5.4 3.5-5.4S18 7 16.5 9.4c-1 1.5-3 2-4.5 1"/><path d="M13.6 12c3 0 5.4 1.2 5.4 3.5S17 18 14.6 16.5c-1.5-1-2-3-1-4.5"/><path d="M12 13.6c0 3-1.2 5.4-3.5 5.4S6 17 7.5 14.6c1-1.5 3-2 4.5-1"/><path d="M10.4 12c-3 0-5.4-1.2-5.4-3.5S7 6 9.4 7.5c1.5 1 2 3 1 4.5"/>', 1.6],
            'aromatizador' => ['<rect x="8" y="9" width="8" height="12" rx="1.6"/><path d="M10 9V6h4v3"/><path d="M18 6h2M18 9h3M18 12h2"/>', 1.6],
            'led'          => ['<path d="M9 18h6M10 21h4"/><path d="M7 13a5 5 0 1110 0c0 2-1 3-2 4H9c-1-1-2-2-2-4z"/><path d="M12 5V3"/>', 1.6],

            // --- Modo de operación ---
            'modo-auto'   => ['<path d="M12 3v3"/><path d="M5.6 5.6l2.1 2.1"/><path d="M3 12h3"/><path d="M5.6 18.4l2.1-2.1"/><circle cx="12" cy="12" r="3.2"/>', 1.8],
            'modo-manual' => ['<path d="M9 11V6a2 2 0 114 0v7"/><path d="M13 8a2 2 0 114 0v6"/><path d="M17 10a2 2 0 114 0v6a5 5 0 01-5 5h-3a5 5 0 01-5-5l-3-5a2 2 0 113-2l2 3"/>', 1.8],

            // --- Interfaz ---
            'check'       => ['<path d="M5 12.5l4 4 10-10"/>', 2],
            'check-grande'=> ['<path d="M20 6L9 17l-5-5"/>', 2.2],
            'candado'     => ['<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>', 1.8],
            'mas'         => ['<path d="M12 5v14M5 12h14"/>', 2],
            'chevron'     => ['<path d="M6 9l6 6 6-6"/>', 1.7],
            'alerta'      => ['<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.01"/>', 2.2],
            'ayuda'       => ['<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.8.4-1 1-1 1.7M12 17h.01"/>', 1.8],
            // Flechita chica de los desplegables: viewBox propio, más apretado.
            'caret'       => ['<path d="M2 4.5 6 8.5 10 4.5"/>', 1.6, '0 0 12 12'],
        ];

        if (! isset($catalogo[$nombre])) {
            return '';
        }

        [$trazado, $grosor] = $catalogo[$nombre];
        $viewBox = $catalogo[$nombre][2] ?? '0 0 24 24';

        $medidas = $tam > 0 ? ' width="' . $tam . '" height="' . $tam . '"' : '';
        $css     = $clase !== '' ? ' class="' . esc($clase) . '"' : '';

        return '<svg' . $css . ' viewBox="' . $viewBox . '"' . $medidas
             . ' fill="none" stroke="currentColor" stroke-width="' . $grosor . '"'
             . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
             . $trazado
             . '</svg>';
    }
}

if (! function_exists('marca_e')) {
    /**
     * La "e" de EdenAir: un solo trazo con degradado, la corriente de aire
     * de la marca. Aparece en el logo, el sidebar y el loader del panel.
     *
     * Cada uso necesita su propio $id porque el degradado es un <defs> y dos
     * gradientes con el mismo id en la misma página se pisan.
     *
     * El SVG sale con la clase .ea-logo-mark--e y de ahí saca los colores
     * (eden-brand.css). Sin esa clase el degradado queda sin resolver y el
     * trazo se dibuja negro: es lo que le pasaba al logo de la landing.
     *
     * @param string        $id      Identificador único del degradado en la página.
     * @param string[]|null $colores 3 colores del degradado. Null = variables de
     *                               marca (--ea-elogo-a/b/c), que se adaptan al tema.
     * @param int           $alto    Alto en px. 0 = lo dimensiona el CSS.
     */
    function marca_e(string $id, ?array $colores = null, int $alto = 0): string
    {
        $colores ??= ['var(--ea-elogo-a)', 'var(--ea-elogo-b)', 'var(--ea-elogo-c)'];

        $trazo = 'M15 39 C21 30 30 33 36 41 C45 51 60 51 80 52 C86 36 70 24 52 24 '
               . 'C34 24 22 38 24 52 C26 68 42 78 60 76 C76 74 90 70 104 60';

        // El viewBox es 116x70: si nos piden alto, el ancho sale de esa proporción.
        $medidas = $alto > 0
            ? ' width="' . (int) round($alto * 116 / 70) . '" height="' . $alto . '"'
            : '';

        // Los colores son valores CSS (`#RRGGBB` o `var(--x)`) y el id va dentro
        // de un url(#...): escaparlos como texto HTML los rompería. Como los
        // escribe el propio código, alcanza con limpiar lo que no corresponde.
        $id      = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
        $colores = array_map(static fn ($c) => preg_replace('/[^A-Za-z0-9#(),.\- ]/', '', (string) $c), $colores);

        return '<svg class="ea-logo-mark ea-logo-mark--e" viewBox="2 16 116 70"' . $medidas . ' role="img" aria-label="EdenAir">'
             . '<defs><linearGradient id="' . $id . '" x1="0.08" y1="0.1" x2="0.92" y2="0.92">'
             . '<stop offset="0" stop-color="' . $colores[0] . '"/>'
             . '<stop offset="0.55" stop-color="' . $colores[1] . '"/>'
             . '<stop offset="1" stop-color="' . $colores[2] . '"/>'
             . '</linearGradient></defs>'
             . '<path d="' . $trazo . '" fill="none" stroke="url(#' . $id . ')"'
             . ' stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>'
             . '</svg>';
    }
}

if (! function_exists('asset')) {
    /**
     * URL de un archivo de /public con ?v=<fecha de modificación> pegado.
     *
     * Sin esto el navegador se queda con el CSS/JS viejo en caché y uno jura
     * que el cambio "no anduvo". Reemplaza a las tres copias de $eaCssBust /
     * $eaJsBust que había sueltas en las vistas.
     */
    function asset(string $ruta): string
    {
        $absoluta = FCPATH . $ruta;
        $version  = is_file($absoluta) ? filemtime($absoluta) : time();

        return base_url($ruta) . '?v=' . $version;
    }
}
