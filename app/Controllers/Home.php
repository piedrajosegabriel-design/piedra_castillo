<?php

namespace App\Controllers;

// Este controlador es el ejemplo por defecto de CodeIgniter.
// Hoy no participa del flujo principal porque la app usa TesinaController
// como entrada para landing, login, registro y dashboard.
class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }
}
