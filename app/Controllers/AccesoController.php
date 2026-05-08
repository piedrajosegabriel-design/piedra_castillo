<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\DeviceProvisioningService;
use App\Services\EnvironmentPresetService;

class AccesoController extends BaseController
{
    private const AMBIENTE_PERSONALIZADO = 'personalizable';

    public function inicio(): string
    {
        return view('inicio');
    }

    public function login(): string
    {
        return view('login');
    }

    public function validarLogin()
    {
        $datos = $this->leerDatosLogin();

        if (! $this->validateData($datos, $this->reglasLogin())) {
            return redirect()->to('/login')
                ->withInput()
                ->with('error', 'Completa tu usuario o correo y la contrasena.');
        }

        $usuarios = new UserModel();
        $usuario  = $usuarios->buscarParaLogin($datos['usuario']);

        if (! $usuario || ! password_verify($datos['password'], $usuario['password_hash'])) {
            return redirect()->to('/login')
                ->withInput()
                ->with('error', 'Las credenciales ingresadas no son validas.');
        }

        if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            $usuarios->actualizarHashContrasena((int) $usuario['id'], $datos['password']);
        }

        (new DeviceProvisioningService())->ensureUserSetup((int) $usuario['id']);
        $this->iniciarSesion($usuario);

        return redirect()->to('/panel');
    }

    public function registro(): string
    {
        return view('registro', [
            'presets' => (new EnvironmentPresetService())->getPresets(),
        ]);
    }

    public function guardarRegistro()
    {
        $datos = $this->leerDatosRegistro();

        if (! $this->validateData($datos, $this->reglasRegistro(), $this->mensajesRegistro())) {
            return redirect()->to('/registro')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $errores = $this->validarAmbientePersonalizado($datos);

        $usuarios = new UserModel();
        if ($usuarios->existeCorreoOUsuario($datos['email'], $datos['usuario'])) {
            $errores['unique'] = 'El correo o el nombre de usuario ya se encuentran registrados.';
        }

        if ($errores !== []) {
            return redirect()->to('/registro')
                ->withInput()
                ->with('errors', $errores);
        }

        $userId = $usuarios->crearUsuario($datos);

        (new DeviceProvisioningService())->ensureUserSetup($userId, [
            'environment_type' => $datos['environment_type'],
            'custom_name'      => $datos['custom_name'],
            'min_temperature'  => $datos['min_temperature'],
            'max_temperature'  => $datos['max_temperature'],
            'min_humidity'     => $datos['min_humidity'],
            'max_humidity'     => $datos['max_humidity'],
            'max_co2'          => $datos['max_co2'],
        ]);

        return redirect()->to('/login')
            ->with('success', 'Cuenta creada correctamente. Ya puedes iniciar sesion.');
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
            'email'            => 'required|valid_email|max_length[120]',
            'usuario'          => 'required|min_length[3]|max_length[80]|regex_match[/^[A-Za-z0-9._-]+$/]',
            'password'         => 'required|min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
            'password_confirm' => 'required|matches[password]',
            'environment_type' => 'required|in_list[oficina,aula,hogar,dormitorio,personalizable]',
            'custom_name'      => 'permit_empty|max_length[120]',
            'min_temperature'  => 'permit_empty|decimal',
            'max_temperature'  => 'permit_empty|decimal',
            'min_humidity'     => 'permit_empty|decimal',
            'max_humidity'     => 'permit_empty|decimal',
            'max_co2'          => 'permit_empty|integer',
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
            'email'            => strtolower(trim((string) $this->request->getPost('email'))),
            'usuario'          => trim((string) $this->request->getPost('usuario')),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
            'environment_type' => (string) $this->request->getPost('environment_type'),
            'custom_name'      => trim((string) $this->request->getPost('custom_name')),
            'min_temperature'  => trim((string) $this->request->getPost('min_temperature')),
            'max_temperature'  => trim((string) $this->request->getPost('max_temperature')),
            'min_humidity'     => trim((string) $this->request->getPost('min_humidity')),
            'max_humidity'     => trim((string) $this->request->getPost('max_humidity')),
            'max_co2'          => trim((string) $this->request->getPost('max_co2')),
        ];
    }

    private function validarAmbientePersonalizado(array $datos): array
    {
        if (($datos['environment_type'] ?? '') !== self::AMBIENTE_PERSONALIZADO) {
            return [];
        }

        $errores = [];

        if ($datos['custom_name'] === '') {
            $errores['custom_name'] = 'Indica un nombre para el ambiente personalizable.';
        }

        $this->validarRangoOpcional($datos, 'min_temperature', 'max_temperature', 'temperature_range', 'temperatura', $errores);
        $this->validarRangoOpcional($datos, 'min_humidity', 'max_humidity', 'humidity_range', 'humedad', $errores);

        if ($datos['max_co2'] !== '' && (int) $datos['max_co2'] <= 0) {
            $errores['max_co2'] = 'El limite de CO2 debe ser mayor que cero.';
        }

        return $errores;
    }

    private function validarRangoOpcional(
        array $datos,
        string $minKey,
        string $maxKey,
        string $errorKey,
        string $label,
        array &$errores
    ): void {
        $minimo = $datos[$minKey] ?? '';
        $maximo = $datos[$maxKey] ?? '';

        if ($minimo === '' && $maximo === '') {
            return;
        }

        if ($minimo === '' || $maximo === '') {
            $errores[$errorKey] = 'Completa el rango de ' . $label . ' o deja ambos valores vacios.';
            return;
        }

        if ((float) $minimo >= (float) $maximo) {
            $errores[$errorKey] = 'El valor minimo de ' . $label . ' debe ser menor que el maximo.';
        }
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
