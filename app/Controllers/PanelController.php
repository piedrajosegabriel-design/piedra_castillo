<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AutomationService;
use App\Services\CommandService;
use App\Services\DeviceProvisioningService;
use App\Services\PanelService;
use App\Services\SimulationService;
use CodeIgniter\HTTP\RedirectResponse;

class PanelController extends BaseController
{
    // =========================================================================
    // LISTAS BLANCAS
    // =========================================================================
    private const MODOS = ['automatic', 'manual'];
    private const ACTUADORES = ['fan', 'aromatizer', 'alert_led'];
    private const VALORES_ACTUADOR = ['on', 'off'];

    // =========================================================================
    // PANEL PRINCIPAL
    // =========================================================================
    public function index(): string|RedirectResponse
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $userId = $this->usuarioActual();
        (new DeviceProvisioningService())->ensureUserSetup($userId, [], false);

        return view('panel', [
            'panel' => (new PanelService())->obtenerVistaPanel($userId),
        ]);
    }

    public function perfil(): string|RedirectResponse
    {
        $usuario = (new UserModel())->obtenerPorId($this->usuarioActual());

        if (! $usuario) {
            session()->destroy();

            return redirect()->to('/login')->with('error', 'Tu sesion expiro. Inicia sesion nuevamente.');
        }

        return view('perfil_usuario', [
            'usuario' => $usuario,
        ]);
    }

    public function actualizarPerfil(): RedirectResponse
    {
        $datos = $this->leerDatosPerfil();

        if ($redirect = $this->validarFormularioPerfil($datos)) {
            return $redirect;
        }

        $usuarios = new UserModel();
        $usuario  = $usuarios->obtenerPorId($this->usuarioActual());

        if ($redirect = $this->validarAutenticacionPerfil($usuario, $datos['current_password'])) {
            return $redirect;
        }

        if ($usuarios->existeCorreoOUsuarioExcepto((int) $usuario['id'], $datos['email'], $datos['usuario'])) {
            return $this->redirigirConInputYDato('/panel/perfil', 'errors', [
                'unique' => 'El correo o el nombre de usuario ya pertenecen a otra cuenta.',
            ]);
        }

        $usuarios->actualizarPerfil((int) $usuario['id'], $datos);
        session()->set('user_name', trim($datos['nombre'] . ' ' . $datos['apellido']));

        return redirect()->to('/panel/perfil')->with('success', 'Datos actualizados correctamente.');
    }

    public function actualizarPassword(): RedirectResponse
    {
        $datos = $this->leerDatosPassword();

        if ($redirect = $this->validarFormularioPassword($datos)) {
            return $redirect;
        }

        $usuarios = new UserModel();
        $usuario  = $usuarios->obtenerPorId($this->usuarioActual());

        if ($redirect = $this->validarAutenticacionPerfil($usuario, $datos['current_password'])) {
            return $redirect;
        }

        $usuarios->actualizarHashContrasena((int) $usuario['id'], $datos['password']);

        return redirect()->to('/panel/perfil')->with('success', 'Contrasena actualizada correctamente.');
    }

    public function compra(): string
    {
        return view('compra_mercadopago');
    }

    // =========================================================================
    // MEDICION MANUAL
    // =========================================================================
    public function guardarMedicion()
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $datos = $this->leerDatosMedicion();

        if ($redirect = $this->validarFormularioMedicion($datos)) {
            return $redirect;
        }

        ['device' => $device, 'space' => $space] = $this->obtenerContexto();

        $resultado = (new SimulationService())->createMeasurement($device, $space, 'web', $datos);

        return $this->redirigirAlPanelConExito(
            'Medición registrada correctamente. ' . $resultado['automation']['summary']
        );
    }

    // =========================================================================
    // CAMBIO DE MODO
    // =========================================================================
    public function cambiarModo()
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $modo = (string) $this->request->getPost('mode');

        if (! $this->modoValido($modo)) {
            return $this->redirigirAlPanelConError('El modo seleccionado no es válido.');
        }

        ['device' => $device, 'space' => $space] = $this->obtenerContexto();

        (new CommandService())->changeOperatingMode((int) $device['id'], $modo, $this->usuarioActual());

        return $this->redirigirAlPanelConExito($this->crearMensajeCambioModo($modo, $device, $space));
    }

    // =========================================================================
    // CONTROL DE ACTUADORES
    // =========================================================================
    public function cambiarActuador()
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $actuador = (string) $this->request->getPost('actuator');
        $valor    = (string) $this->request->getPost('value');

        if (! $this->accionActuadorValida($actuador, $valor)) {
            return $this->redirigirAlPanelConError('La acción seleccionada no es válida.');
        }

        ['device' => $device] = $this->obtenerContexto();

        if (! $this->estaEnModoManual((int) $device['id'])) {
            return $this->redirigirAlPanelConError('Activa el modo manual para controlar actuadores.');
        }

        (new CommandService())->queueAndExecuteManualCommand(
            (int) $device['id'],
            $actuador,
            $valor,
            $this->usuarioActual()
        );

        return $this->redirigirAlPanelConExito('Acción aplicada correctamente.');
    }

    // =========================================================================
    // ARMADO DEL PANEL
    // =========================================================================
    private function crearPanel(): array
    {
        $userId = $this->usuarioActual();
        (new DeviceProvisioningService())->ensureUserSetup($userId, [], false);

        return (new PanelService())->obtenerDatos($userId);
    }

    private function obtenerContexto(): array
    {
        $panel = $this->crearPanel();

        return [
            'device' => $panel['device_raw'],
            'space'  => $panel['space_raw'],
        ];
    }

    // =========================================================================
    // DATOS Y VALIDACION
    // =========================================================================
    private function leerDatosMedicion(): array
    {
        return $this->request->getPost();
    }

    private function reglasMedicion(): array
    {
        return [
            'temperature'       => 'permit_empty|decimal',
            'humidity'          => 'permit_empty|decimal',
            'co2_ppm'           => 'permit_empty|integer',
            'air_quality_index' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'notes'             => 'permit_empty|max_length[255]',
        ];
    }

    private function validarFormularioMedicion(array $datos): ?RedirectResponse
    {
        if ($this->validateData($datos, $this->reglasMedicion())) {
            return null;
        }

        return redirect()->to('/panel')
            ->withInput()
            ->with('error', implode(' ', array_values($this->validator->getErrors())));
    }

    private function leerDatosPerfil(): array
    {
        return [
            'nombre'           => trim((string) $this->request->getPost('nombre')),
            'apellido'         => trim((string) $this->request->getPost('apellido')),
            'email'            => strtolower(trim((string) $this->request->getPost('email'))),
            'usuario'          => trim((string) $this->request->getPost('usuario')),
            'current_password' => (string) $this->request->getPost('current_password'),
        ];
    }

    private function leerDatosPassword(): array
    {
        return [
            'current_password' => (string) $this->request->getPost('current_password'),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
        ];
    }

    private function validarFormularioPerfil(array $datos): ?RedirectResponse
    {
        $reglas = [
            'nombre'           => 'required|min_length[2]|max_length[120]',
            'apellido'         => 'permit_empty|max_length[120]',
            'email'            => 'required|valid_email|max_length[120]',
            'usuario'          => 'required|min_length[3]|max_length[80]|regex_match[/^[A-Za-z0-9._-]+$/]',
            'current_password' => 'required|max_length[255]',
        ];

        $mensajes = [
            'usuario' => [
                'regex_match' => 'El usuario solo puede contener letras, numeros, puntos, guiones y guion bajo.',
            ],
        ];

        if ($this->validateData($datos, $reglas, $mensajes)) {
            return null;
        }

        return $this->redirigirConInputYDato('/panel/perfil', 'errors', $this->validator->getErrors());
    }

    private function validarFormularioPassword(array $datos): ?RedirectResponse
    {
        $reglas = [
            'current_password' => 'required|max_length[255]',
            'password'         => 'required|min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
            'password_confirm' => 'required|matches[password]',
        ];

        $mensajes = [
            'password' => [
                'regex_match' => 'La contrasena debe incluir al menos una letra mayuscula, una minuscula y un numero.',
            ],
            'password_confirm' => [
                'matches' => 'La confirmacion de contrasena no coincide.',
            ],
        ];

        if ($this->validateData($datos, $reglas, $mensajes)) {
            return null;
        }

        return $this->redirigirConInputYDato('/panel/perfil', 'errors', $this->validator->getErrors());
    }

    private function validarAutenticacionPerfil(?array $usuario, string $password): ?RedirectResponse
    {
        if ($usuario && password_verify($password, (string) $usuario['password_hash'])) {
            return null;
        }

        return $this->redirigirConInputYDato('/panel/perfil', 'error', 'No pudimos autenticarte. Ingresa tu contrasena actual para confirmar el cambio.');
    }

    private function redirigirConInputYDato(string $ruta, string $clave, mixed $valor): RedirectResponse
    {
        return redirect()->to($ruta)
            ->withInput()
            ->with($clave, $valor);
    }

    // =========================================================================
    // VALIDADORES DE MODO Y ACTUADOR
    // =========================================================================
    private function modoValido(string $modo): bool
    {
        return in_array($modo, self::MODOS, true);
    }

    private function accionActuadorValida(string $actuador, string $valor): bool
    {
        return in_array($actuador, self::ACTUADORES, true)
            && in_array($valor, self::VALORES_ACTUADOR, true);
    }

    private function estaEnModoManual(int $deviceId): bool
    {
        $estado = (new CommandService())->getStateByDeviceId($deviceId);

        return ($estado['operating_mode'] ?? 'automatic') === 'manual';
    }

    private function crearMensajeCambioModo(string $modo, array $device, array $space): string
    {
        if ($modo === 'manual') {
            return 'Modo manual activado.';
        }

        $automatico = (new AutomationService())->processLatestMeasurement($device, $space);

        return 'Modo automático activado. ' . $automatico['summary'];
    }

    // =========================================================================
    // RESPUESTAS
    // =========================================================================
    private function redirigirAlPanelConError(string $mensaje): RedirectResponse
    {
        return redirect()->to('/panel')->with('error', $mensaje);
    }

    private function redirigirAlPanelConExito(string $mensaje): RedirectResponse
    {
        return redirect()->to('/panel')->with('success', $mensaje);
    }

    // =========================================================================
    // SESION
    // =========================================================================
    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }

    // =========================================================================
    // PROTECCION DE ACCESO
    // =========================================================================
    private function redireccionarSiFaltaAmbiente(): ?RedirectResponse
    {
        if ((new DeviceProvisioningService())->hasConfiguredSpace($this->usuarioActual())) {
            return null;
        }

        return redirect()->to('/panel/ambiente')
            ->with('error', 'Primero elige el ambiente que deseas monitorear.');
    }
}
