<?php

namespace App\Libraries;

use CodeIgniter\View\View;

/* ============================================================
   Vista
   QUÉ HACE: es el mismo dibujador de vistas de CodeIgniter, con
   una sola cosa agregada: cada pantalla carga SOLA su hoja de
   estilos.

   LA REGLA: la pantalla app/Views/ambientes/index.php
             usa el CSS   public/CSS/ambientes/index.css
   Mismo nombre, misma carpeta. Ese archivo tiene TODO lo que se
   ve en esa pantalla (también el menú, la barra de arriba, los
   botones...): no se carga ningún otro CSS al lado.
   Si el CSS no existe no pasa nada: no se agrega ningún <link> y
   no hay ningún 404.

   CÓMO FUNCIONA, PASO A PASO:
     1. El controlador dibuja la pantalla (por ejemplo
        view('ambientes/index')). Esa es la primera vista que
        se dibuja; el layout y los partials se dibujan adentro.
     2. partials/head.php deja en el <head> una marca de texto:
        <!-- ESTILOS DE LAS VISTAS -->
     3. Cuando termina de armarse la página ENTERA, se cambia esa
        marca por el <link> al CSS de la pantalla.

   Se hace al final y no en el momento porque el <head> se dibuja
   adentro del layout, después de la pantalla: recién al final se
   tiene la página completa.

   DÓNDE SE ACTIVA: app/Config/Services.php le dice a CodeIgniter
   que use esta clase en vez de la suya (función renderer()).
   ============================================================ */
class Vista extends View
{
    /** La marca que deja partials/head.php y que acá se reemplaza por el <link>. */
    public const MARCA_ESTILOS = '<!-- ESTILOS DE LAS VISTAS -->';

    /** Carpeta (dentro de public/) donde viven los CSS de las pantallas. */
    private const CARPETA_CSS = 'CSS/';

    /**
     * Cuántas vistas hay "abiertas" en este momento. Una vista puede dibujar
     * otra adentro (la pantalla dibuja su layout, el layout dibuja el menú...).
     * Cuando vuelve a 0, la página terminó de armarse.
     */
    private int $abiertas = 0;

    /**
     * Dibuja una vista. Es el render() de CodeIgniter, con el reemplazo de la
     * marca al final, cuando termina la vista de más afuera (la pantalla).
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        // La primera vista que se abre es la pantalla: es la que da el nombre del CSS.
        $esPantalla = $this->abiertas === 0;
        $this->abiertas++;

        try {
            $html = parent::render($view, $options, $saveData);
        } finally {
            $this->abiertas--;
        }

        // Todavía estamos adentro de otra vista: la página no terminó.
        if (! $esPantalla) {
            return $html;
        }

        return str_replace(self::MARCA_ESTILOS, $this->linkDeEstilos($view), $html);
    }

    /** El <link> al CSS de la pantalla, o nada si esa pantalla no tiene CSS. */
    private function linkDeEstilos(string $pantalla): string
    {
        $css = self::CARPETA_CSS . $pantalla . '.css';

        // Sin archivo, sin <link>: así una vista sin CSS propio no tira 404.
        if (! is_file(FCPATH . $css)) {
            return '';
        }

        return '<link rel="stylesheet" href="' . esc($this->rutaSinHost(asset($css))) . '">' . "\n";
    }

    /**
     * Saca el "http://localhost:8080" de una URL y deja solo la ruta.
     *
     * La pantalla vinculacion/seguir se abre desde el CELULAR, entrando por
     * la IP de la PC: ahí "localhost" es el propio teléfono y la hoja de
     * estilos no cargaría. Sin host, el navegador la pide al mismo lugar de
     * donde sacó la página, sea cual sea (es lo mismo que hace esa vista con
     * sus links y su JS).
     */
    private function rutaSinHost(string $url): string
    {
        $ruta     = '/' . ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $consulta = (string) parse_url($url, PHP_URL_QUERY);

        return $consulta !== '' ? $ruta . '?' . $consulta : $ruta;
    }
}
