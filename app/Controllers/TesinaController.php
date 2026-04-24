<?php

namespace App\Controllers;

// Controlador minimo para sostener solo las rutas actuales mientras
// login, registro y base de datos todavia no estan implementados.
class TesinaController extends BaseController
{
    // Landing publica.
    public function home()
    {
        return view('home');
    }

    // Vista de login.
    public function inicio()
    {
        return view('login');
    }

    // POST de login temporal.
    // Por ahora solo vuelve a la misma vista porque todavia no hay autenticacion real.
    public function login()
    {
        return redirect()->to('/login');
    }

    // Vista de registro.
    public function register()
    {
        return view('register');
    }

    // POST de registro temporal.
    // Por ahora solo vuelve a la misma vista porque todavia no hay guardado real.
    public function registerStore()
    {
        return redirect()->to('/register');
    }

    // Dashboard de ejemplo.
    // Se deja accesible mientras no exista login real.
    public function dashboard()
    {
        return view('dashboard');
    }

    // Logout temporal.
    // Sin sesion real, solo redirige al login.
    public function logout()
    {
        return redirect()->to('/login');
    }
}
