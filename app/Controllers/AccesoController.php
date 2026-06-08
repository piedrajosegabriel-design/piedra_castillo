<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class AccesoController extends BaseController
{
    public function inicio(): string
    {
        return view('inicio');
    }

    public function login(): string
    {
        return view('login');
    }

    public function registro(): string
    {
        return view('registro');
    }

    public function recuperar(): string
    {
        return view('recuperar');
    }

    public function procesarRecuperacion(): RedirectResponse
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/recuperar')->withInput()->with('error', 'Por favor, ingresa un correo valido.');
        }

        $usuarios = new UserModel();
        $usuario  = $usuarios->buscarParaLogin($email);

        if ($usuario) {
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $enlace    = site_url('restablecer/' . $token);
            $nombre    = (string) ($usuario['nombre'] ?? 'Usuario');

            $usuarios->guardarToken((int) $usuario['id'], $token, $expiresAt);

            $emailService = \Config\Services::email();
            $emailService->setFrom(config('Email')->fromEmail, config('Email')->fromName);
            $emailService->setTo((string) $usuario['email']);
            $emailService->setSubject('EdenAir - Restablecer tu contrasena');
            $emailService->setMessage(view('emails/recuperar_password', [
                'nombre' => $nombre,
                'enlace' => $enlace,
                'minutos' => 15,
            ]));

            if (! $emailService->send()) {
                log_message('error', 'Fallo el envio de recuperacion para {email}. Debug: {debug}', [
                    'email' => (string) $usuario['email'],
                    'debug' => $emailService->printDebugger(['headers', 'subject']),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No pudimos enviar el correo de recuperacion. Revisa la configuracion SMTP de Gmail e intenta nuevamente.');
            }
        }

        return redirect()->to('/login')->with('success', 'Si el correo coincide con una cuenta registrada, recibiras un enlace de recuperacion en los proximos minutos.');
    }

    public function restablecer(string $token): string|RedirectResponse
    {
        $usuarios = new UserModel();
        $usuario  = $usuarios->buscarPorToken($token);

        if (! $usuario) {
            return redirect()->to('/login')->with('error', 'El enlace de recuperacion es invalido o ya ha expirado.');
        }

        return view('restablecer_password', ['token' => $token]);
    }

    public function guardarNuevaPassword(string $token): RedirectResponse
    {
        $usuarios = new UserModel();
        $usuario  = $usuarios->buscarPorToken($token);

        if (! $usuario) {
            return redirect()->to('/login')->with('error', 'El enlace de recuperacion ha expirado.');
        }

        $password        = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');
        $reglas          = [
            'password'         => 'required|min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (! $this->validateData([
            'password' => $password,
            'password_confirm' => $passwordConfirm,
        ], $reglas, $this->mensajesRegistro())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $usuarios->actualizarPasswordConToken((int) $usuario['id'], $password);

        return redirect()->to('/login')->with('success', 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.');
    }

    public function validarLogin()
    {
        $datos = $this->leerDatosLogin();

        if ($redirect = $this->validarFormularioLogin($datos)) {
            return $redirect;
        }

        $usuarios = new UserModel();
        $usuario  = $usuarios->buscarParaLogin($datos['usuario']);

        if ($redirect = $this->validarCredencialesLogin($usuario, $datos['password'])) {
            return $redirect;
        }

        $this->actualizarHashLoginSiHaceFalta($usuarios, $usuario, $datos['password']);
        $this->iniciarSesion($usuario);

        return $this->redirigirDespuesDelLogin((int) $usuario['id']);
    }

    public function guardarRegistro()
    {
        $datos = $this->leerDatosRegistro();

        if ($redirect = $this->validarFormularioRegistro($datos)) {
            return $redirect;
        }

        $usuarios = new UserModel();

        if ($redirect = $this->validarUsuarioDisponible($usuarios, $datos)) {
            return $redirect;
        }

        $usuarios->crearUsuario($datos);

        return redirect()->to('/login')
            ->with('success', 'Cuenta creada correctamente. Inicia sesion para entrar al panel.');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sesion cerrada correctamente.');
    }

    private function reglasLogin(): array
    {
        return [
            'usuario'  => 'required|min_length[3]|max_length[120]',
            'password' => 'required|max_length[255]',
        ];
    }

    private function reglasRegistro(): array
    {
        return [
            'nombre'           => 'required|min_length[3]|max_length[120]',
            'apellido'         => 'required|min_length[2]|max_length[120]',
            'email'            => 'required|valid_email|max_length[120]',
            'usuario'          => 'required|min_length[3]|max_length[80]|regex_match[/^[A-Za-z0-9._-]+$/]',
            'password'         => 'required|min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
            'password_confirm' => 'required|matches[password]',
        ];
    }

    private function mensajesRegistro(): array
    {
        return [
            'usuario' => [
                'regex_match' => 'El usuario solo puede contener letras, numeros, puntos, guiones y guion bajo.',
            ],
            'password' => [
                'regex_match' => 'La contrasena debe incluir al menos una letra mayuscula, una minuscula y un numero.',
            ],
            'password_confirm' => [
                'matches' => 'La confirmacion de contrasena no coincide.',
            ],
        ];
    }

    private function validarFormularioLogin(array $datos): ?RedirectResponse
    {
        if ($this->validateData($datos, $this->reglasLogin())) {
            return null;
        }

        return $this->redirigirConInputYDato('/login', 'error', 'Completa tu usuario o correo y la contraseña.');
    }

    private function validarCredencialesLogin(?array $usuario, string $password): ?RedirectResponse
    {
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            return null;
        }

        return $this->redirigirConInputYDato('/login', 'error', 'Las credenciales ingresadas no son validas.');
    }

    private function actualizarHashLoginSiHaceFalta(UserModel $usuarios, array $usuario, string $password): void
    {
        if (! password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            return;
        }

        $usuarios->actualizarHashContrasena((int) $usuario['id'], $password);
    }

    private function redirigirDespuesDelLogin(int $userId): RedirectResponse
    {
        // Nuevo flujo (Hito 2): el usuario entra directo al dashboard. La
        // bienvenida o el panel se decide según tenga o no dispositivos —
        // ya no se fuerza la selección de ambiente al loguearse.
        return redirect()->to('/panel');
    }

    private function validarFormularioRegistro(array $datos): ?RedirectResponse
    {
        if ($this->validateData($datos, $this->reglasRegistro(), $this->mensajesRegistro())) {
            return null;
        }

        return $this->redirigirConInputYDato('/registro', 'errors', $this->validator->getErrors());
    }

    private function validarUsuarioDisponible(UserModel $usuarios, array $datos): ?RedirectResponse
    {
        if (! $usuarios->existeCorreoOUsuario($datos['email'], $datos['usuario'])) {
            return null;
        }

        return $this->redirigirConInputYDato('/registro', 'errors', [
            'unique' => 'El correo o el nombre de usuario ya se encuentran registrados.',
        ]);
    }

    private function redirigirConInputYDato(string $ruta, string $clave, mixed $valor): RedirectResponse
    {
        return redirect()->to($ruta)
            ->withInput()
            ->with($clave, $valor);
    }

    private function leerDatosLogin(): array
    {
        return [
            'usuario'  => trim((string) $this->request->getPost('usuario')),
            'password' => (string) $this->request->getPost('password'),
        ];
    }

    private function leerDatosRegistro(): array
    {
        return [
            'nombre'           => trim((string) $this->request->getPost('nombre')),
            'apellido'         => trim((string) $this->request->getPost('apellido')),
            'email'            => strtolower(trim((string) $this->request->getPost('email'))),
            'usuario'          => trim((string) $this->request->getPost('usuario')),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
        ];
    }

    private function iniciarSesion(array $usuario): void
    {
        session()->regenerate(true);
        session()->set([
            'user_id'      => (int) $usuario['id'],
            'user_name'    => $usuario['nombre'],
            'is_logged_in' => true,
        ]);
    }
}
