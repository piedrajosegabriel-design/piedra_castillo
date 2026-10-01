<?php

namespace App\Libraries;

use CodeIgniter\View\View;

/* ============================================================
   Vista
   QUÉ HACE: es el mismo dibujador de vistas de CodeIgniter, con
   una sola cosa agregada: cada vista carga SOLA su hoja de
   estilos, si la tiene.

   LA REGLA: la vista  app/Views/ambientes/index.php
             usa el CSS public/CSS/vistas/ambientes/index.css
   Mismo nombre, misma carpeta. Si el CSS no existe no pasa nada:
   no se agrega ningún <link> y no hay ningún 404.

   CÓMO FUNCIONA, PASO A PASO:
     1. Cada vez que se dibuja una vista (la pantalla, el layout,
        cada partial) se anota su nombre en $vistasUsadas.
     2. partials/head.php deja en el <head> una marca de texto:
        <!-- ESTILOS DE LAS VISTAS -->
     3. Cuando termina de armarse la página ENTERA, se cambia esa
        marca por un <link> por cada vista anotada que tenga CSS.

   Se hace al final y no en el momento porque el <head> se dibuja
   antes que el menú, la barra superior y el resto: recién al
   final se sabe qué vistas usó la página.

   ORDEN DE LOS <link>: de lo más general a lo más particular.
   Primero el layout, después los partials y al final la
   pantalla. En CSS, si dos reglas empatan gana la que se carga
   última: así la pantalla siempre puede ajustar lo de arriba.

   DÓNDE SE ACTIVA: app/Config/Services.php le dice a CodeIgniter
   que use esta clase en vez de la suya (función renderer()).
   ============================================================ */
class Vista extends View
{
    /** La marca que deja partials/head.php y que acá se reemplaza por los <link>. */
    public const MARCA_ESTILOS = '<!-- ESTILOS DE LAS VISTAS -->';

    /** Carpeta (dentro de public/) donde viven los CSS de las vistas. */
    private const CARPETA_CSS = 'CSS/vistas/';

    /** Nombres de las vistas dibujadas en esta página, en el orden en que se dibujaron. */
    private array $vistasUsadas = [];

    /**
     * Cuántas vistas hay "abiertas" en este momento. Una vista puede dibujar
     * otra adentro (el layout dibuja el menú, el menú dibuja el logo...).
     * Cuando vuelve a 0, la página terminó de armarse.
     */
    private int $abiertas = 0;

    /**
     * Dibuja una vista. Es el render() de CodeIgniter, con el anotado de
     * vistas antes y el reemplazo de la marca al final.
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $this->vistasUsadas[] = $view;
        $this->abiertas++;

        try {
            $html = parent::render($view, $options, $saveData);
        } finally {
            $this->abiertas--;
        }

        // Todavía estamos adentro de otra vista: la página no terminó.
        if ($this->abiertas > 0) {
            return $html;
        }

        // Terminó la página: se reemplaza la marca y se limpia la lista para
        // la próxima (por ejemplo, un mail que se arma en el mismo pedido).
        $vistas             = $this->vistasUsadas;
        $this->vistasUsadas = [];

        return str_replace(self::MARCA_ESTILOS, $this->linksDeEstilos($vistas), $html);
    }

    /**
     * Arma los <link> de las vistas que tienen CSS, en orden: layouts,
     * después partials y al final el resto (la pantalla).
     */
    private function linksDeEstilos(array $vistas): string
    {
        $layouts  = [];
        $partials = [];
        $resto    = [];

        foreach (array_unique($vistas) as $vista) {
            if (str_starts_with($vista, 'layouts/')) {
                $layouts[] = $vista;
            } elseif (str_starts_with($vista, 'partials/')) {
                $partials[] = $vista;
            } else {
                $resto[] = $vista;
            }
        }

        $links = '';

        foreach ([...$layouts, ...$partials, ...$resto] as $vista) {
            $css = self::CARPETA_CSS . $vista . '.css';

            // Sin archivo, sin <link>: así una vista sin CSS propio no tira 404.
            if (is_file(FCPATH . $css)) {
                $links .= '<link rel="stylesheet" href="' . esc($this->rutaSinHost(asset($css))) . '">' . "\n";
            }
        }

        return $links;
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
