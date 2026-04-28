<?php

namespace App\Controllers;

use App\Models\UserModel;

// Controlador principal del flujo de acceso de EdenAir.
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

    // Procesa el login real usando la tabla users.
    public function login()
    {
        $userModel = new UserModel();
        $usuario = (string) $this->request->getPost('usuario');
        $password = (string) $this->request->getPost('password');

        $user = $userModel->where('usuario', $usuario)->first();
        $isValid = $user && password_verify($password, $user['password_hash']);

        if (!$isValid) {
            return redirect()->to('/login')->with('error', "Usuario o contrase\u{00f1}a inv\u{00e1}lidos.");
        }

        session()->set([
            'user_id' => $user['id'],
            'user_name' => $user['nombre'],
            'is_logged_in' => true,
        ]);

        return redirect()->to('/dashboard');
    }

    // Vista de registro.
    public function register()
    {
        return view('register');
    }

    // Crea un usuario nuevo si los datos son validos.
    public function registerStore()
    {
        $userModel = new UserModel();
        $nombre = (string) $this->request->getPost('nombre');
        $email = (string) $this->request->getPost('email');
        $usuario = (string) $this->request->getPost('usuario');
        $password = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if ($password !== $passwordConfirm) {
            return redirect()->to('/register')->with('error', "Las contrase\u{00f1}as no coinciden.");
        }

        $exists = $userModel
            ->groupStart()
            ->where('email', $email)
            ->orWhere('usuario', $usuario)
            ->groupEnd()
            ->first();

        if ($exists) {
            return redirect()->to('/register')->with('error', 'El correo o usuario ya existe.');
        }

        $userModel->insert([
            'nombre' => $nombre,
            'email' => $email,
            'usuario' => $usuario,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/login')->with('success', "Cuenta creada correctamente. Ahora inicia sesi\u{00f3}n.");
    }

    // Dashboard privado: requiere sesion iniciada.
    public function dashboard()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/login')->with('error', "Primero debes iniciar sesi\u{00f3}n.");
        }

        return view('dashboard');
    }

    // Cierra la sesion actual y vuelve al login.
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', "Sesi\u{00f3}n cerrada.");
    }
}
