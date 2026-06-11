<?php

namespace App\Controllers;

/**
 * Portfolio público de Eden Air.
 *
 * Esta vista es accesible sin login. Reúne el desarrollo del proyecto
 * y el Trabajo Práctico Nº 2 (Análisis de mercado · Emprendimientos).
 */
class PortfolioController extends BaseController
{
    /** Renderiza la vista del portfolio (app/Views/portfolio.php). */
    public function index(): string
    {
        return view('portfolio');
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - index()       → responde a GET /portfolio; solo muestra la vista
   - view('x')     → (CI4) renderiza app/Views/x.php y devuelve el HTML
   ============================================================================ */
