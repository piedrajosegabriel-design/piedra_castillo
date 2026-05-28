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
    public function index(): string
    {
        return view('portfolio');
    }
}
