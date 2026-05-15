<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\DeviceProvisioningService;
use App\Services\EnvironmentPresetService;
use CodeIgniter\HTTP\RedirectResponse;

class AccesoController extends BaseController
{
    // =========================================================================
    // CONSTANTE DE AMBIENTE
    // =========================================================================
    private const AMBIENTE_PERSONALIZADO = 'personalizable';

    // =========================================================================
    // VISTAS PUBLICAS
    // =========================================================================
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

    // =========================================================================
    // LOGIN
    // =========================================================================
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

    // =========================================================================
    // SELECCION DE AMBIENTE
    // =========================================================================
    public function seleccionAmbiente(): string|RedirectResponse
    {
        if ((new DeviceProvisioningService())->hasConfiguredSpace($this->usuarioActual())) {
            return redirect()->to('/panel');
        }

        return view('seleccion_ambiente', [
            'presets' => (new EnvironmentPresetService())->getPresets(),
        ]);
    }

    // =========================================================================
    // REGISTRO
    // =========================================================================
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
            ->with('success', 'Cuenta creada correctamente. Inicia sesión y elige tu ambiente.');
    }

    // =========================================================================
    // GUARDAR AMBIENTE
    // =========================================================================
    public function guardarAmbiente()
    {
        $userId = $this->usuarioActual();
        $provisioningService = new DeviceProvisioningService();

        if ($provisioningService->hasConfiguredSpace($userId)) {
            return redirect()->to('/panel');
        }

        $datos = $this->leerDatosAmbiente();

        if ($redirect = $this->validarFormularioAmbiente($datos)) {
            return $redirect;
        }

        $provisioningService->ensureUserSetup($userId, $datos);

        return redirect()->to('/panel')
            ->with('success', 'Ambiente configurado correctamente.');
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sesión cerrada correctamente.');
    }

    // =========================================================================
    // REGLAS DE VALIDACION
    // =========================================================================
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
        ];
    }

    private function reglasAmbiente(): array
    {
        return [
            'environment_type' => 'required|in_list[oficina,aula,hogar,dormitorio,personalizable]',
            'custom_name'      => 'permit_empty|max_length[120]',
            'min_temperature'  => 'permit_empty|decimal',
            'max_temperature'  => 'permit_empty|decimal',
            'min_humidity'     => 'permit_empty|decimal',
            'max_humidity'     => 'permit_empty|decimal',
            'max_co2'          => 'permit_empty|integer',
        ];
    }

    // =========================================================================
    // MENSAJES DE VALIDACION
    // =========================================================================
    private function mensajesRegistro(): array
    {
        return [
            'usuario' => [
                'regex_match' => 'El usuario solo puede contener letras, números, puntos, guiones y guion bajo.',
            ],
            'password' => [
                'regex_match' => 'La contraseña debe incluir al menos una letra mayúscula, una minúscula y un número.',
            ],
            'password_confirm' => [
                'matches' => 'La confirmación de contraseña no coincide.',
            ],
        ];
    }

    // =========================================================================
    // HELPERS DE LOGIN Y REGISTRO
    // =========================================================================
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

        return $this->redirigirConInputYDato('/login', 'error', 'Las credenciales ingresadas no son válidas.');
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
        $provisioningService = new DeviceProvisioningService();

        if (! $provisioningService->hasConfiguredSpace($userId)) {
            return $this->redirigirConDato('/panel/ambiente', 'success', 'Inicio de sesión correcto. Ahora elige el ambiente que deseas monitorear.');
        }

        $provisioningService->ensureUserSetup($userId, [], false);

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

    private function validarFormularioAmbiente(array $datos): ?RedirectResponse
    {
        if (! $this->validateData($datos, $this->reglasAmbiente())) {
            return $this->redirigirConInputYDato('/panel/ambiente', 'errors', $this->validator->getErrors());
        }

        $errores = $this->validarAmbientePersonalizado($datos);

        if ($errores === []) {
            return null;
        }

        return $this->redirigirConInputYDato('/panel/ambiente', 'errors', $errores);
    }

    // =========================================================================
    // HELPERS DE REDIRECCION
    // =========================================================================
    private function redirigirConInputYDato(string $ruta, string $clave, $valor): RedirectResponse
    {
        return redirect()->to($ruta)
            ->withInput()
            ->with($clave, $valor);
    }

    private function redirigirConDato(string $ruta, string $clave, $valor): RedirectResponse
    {
        return redirect()->to($ruta)->with($clave, $valor);
    }

    // =========================================================================
    // LECTURA DE FORMULARIOS
    // =========================================================================
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
        ];
    }

    private function leerDatosAmbiente(): array
    {
        $datos = [
            'environment_type' => (string) $this->request->getPost('environment_type'),
            'custom_name'      => trim((string) $this->request->getPost('custom_name')),
            'min_temperature'  => trim((string) $this->request->getPost('min_temperature')),
            'max_temperature'  => trim((string) $this->request->getPost('max_temperature')),
            'min_humidity'     => trim((string) $this->request->getPost('min_humidity')),
            'max_humidity'     => trim((string) $this->request->getPost('max_humidity')),
            'max_co2'          => trim((string) $this->request->getPost('max_co2')),
        ];

        if (($datos['environment_type'] ?? '') !== self::AMBIENTE_PERSONALIZADO) {
            $datos['custom_name'] = '';
            $datos['min_temperature'] = '';
            $datos['max_temperature'] = '';
            $datos['min_humidity'] = '';
            $datos['max_humidity'] = '';
            $datos['max_co2'] = '';
        }

        return $datos;
    }

    // =========================================================================
    // VALIDACION EXTRA DE AMBIENTE
    // =========================================================================
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
            $errores['max_co2'] = 'El límite de CO2 debe ser mayor que cero.';
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
            $errores[$errorKey] = 'Completa el rango de ' . $label . ' o deja ambos valores vacíos.';
            return;
        }

        if ((float) $minimo >= (float) $maximo) {
            $errores[$errorKey] = 'El valor mínimo de ' . $label . ' debe ser menor que el máximo.';
        }
    }

    // =========================================================================
    // SESION
    // =========================================================================
    private function iniciarSesion(array $usuario): void
    {
        session()->regenerate(true);
        session()->set([
            'user_id'      => (int) $usuario['id'],
            'user_name'    => $usuario['nombre'],
            'is_logged_in' => true,
        ]);
    }

    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }
}
