<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AutomationService;
use App\Services\CommandService;
use App\Services\DeviceProvisioningService;
use App\Services\PanelService;
use App\Services\SimulationService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * PanelController — el corazón del área privada (dashboard).
 *
 * Maneja: el panel principal (bienvenida o monitor según haya dispositivos),
 * la demo, el switcher de dispositivo activo, el perfil del usuario,
 * la carga manual de mediciones, el cambio de modo (automático/manual)
 * y el control de actuadores.
 *
 * Patrón general de los métodos POST: cada paso que puede fallar devuelve
 * un RedirectResponse (guard clause); si devuelve null, el flujo sigue.
 * La lógica pesada vive en los services (PanelService, CommandService,
 * SimulationService, AutomationService) — este controller solo orquesta.
 */
class PanelController extends BaseController
{
    // =========================================================================
    // LISTAS BLANCAS
    // Valores permitidos para modo y actuadores. Todo lo que llega por POST
    // se compara contra estas constantes: si no está acá, se rechaza.
    // =========================================================================
    private const MODOS = ['automatic', 'manual'];
    private const ACTUADORES = ['fan', 'aromatizer', 'alert_led'];
    private const VALORES_ACTUADOR = ['on', 'off'];

    // =========================================================================
    // PANEL PRINCIPAL
    // =========================================================================
    public function index(): string|RedirectResponse
    {
        $userId = $this->usuarioActual();

        // Si la cuenta aún no tiene dispositivos, mostramos la pantalla de
        // bienvenida (3 CTAs: agregar dispositivo, ver demo o comprar). No se
        // auto-crea nada en silencio: la demo es una acción explícita.
        $cantidadDispositivos = (new \App\Models\DeviceModel())
            ->where('user_id', $userId)
            ->countAllResults();

        if ($cantidadDispositivos === 0) {
            $usuario = (new UserModel())->obtenerPorId($userId);
            return view('panel/bienvenida', [
                'usuario' => $usuario ?? ['nombre' => 'usuario', 'apellido' => ''],
            ]);
        }

        $activeDeviceId = $this->dispositivoActivo($userId);

        return view('panel', [
            'panel' => (new PanelService())->obtenerVistaPanel($userId, $activeDeviceId),
        ]);
    }

    /**
     * Crea un dispositivo + ambiente simulados etiquetados como Demo y
     * redirige al panel. Es la acción explícita detrás del CTA
     * "Ver demo del sistema" de la pantalla de bienvenida.
     */
    public function iniciarDemo(): RedirectResponse
    {
        $userId = $this->usuarioActual();

        $cantidad = (new \App\Models\DeviceModel())
            ->where('user_id', $userId)
            ->countAllResults();

        if ($cantidad === 0) {
            (new DeviceProvisioningService())->ensureUserSetup($userId, [], true);
        }

        return redirect()->to('/panel')->with('success', 'Cargamos un dispositivo de demostración para que veas el panel en acción.');
    }

    // =========================================================================
    // DISPOSITIVO ACTIVO (switcher del panel)
    // =========================================================================

    /**
     * Cambia el dispositivo activo del panel monitor. El id se valida contra
     * los dispositivos del usuario antes de guardarlo en sesión.
     */
    public function seleccionarDispositivo(): RedirectResponse
    {
        $deviceId = (int) $this->request->getPost('device_id');
        $userId   = $this->usuarioActual();

        if ($deviceId > 0) {
            $existe = (new \App\Models\DeviceModel())
                ->where('id', $deviceId)
                ->where('user_id', $userId)
                ->countAllResults();

            if ($existe > 0) {
                session()->set('active_device_id', $deviceId);
            }
        }

        return redirect()->to('/panel');
    }

    /** Devuelve el id de dispositivo activo en sesión si pertenece al usuario. */
    private function dispositivoActivo(int $userId): ?int
    {
        $candidato = (int) session()->get('active_device_id');

        if ($candidato <= 0) {
            return null;
        }

        $valido = (new \App\Models\DeviceModel())
            ->where('id', $candidato)
            ->where('user_id', $userId)
            ->countAllResults() > 0;

        if (! $valido) {
            session()->remove('active_device_id');
            return null;
        }

        return $candidato;
    }

    // =========================================================================
    // PERFIL DE USUARIO Y COMPRA
    // Ver/editar datos personales y contraseña. Ambos cambios exigen
    // confirmar la contraseña actual (validarAutenticacionPerfil).
    // =========================================================================

    /** Muestra el perfil; si el usuario ya no existe en la base, cierra sesión. */
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

    /** Página estática de compra (checkout simulado con MercadoPago). */
    public function compra(): string
    {
        return view('compra_mercadopago');
    }

    // =========================================================================
    // MEDICION MANUAL
    // =========================================================================
    public function guardarMedicion()
    {
        if ($redirect = $this->redireccionarSiFaltaDispositivo()) {
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
        if ($redirect = $this->redireccionarSiFaltaDispositivo()) {
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
        if ($redirect = $this->redireccionarSiFaltaDispositivo()) {
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
            'apellido'         => 'required|min_length[2]|max_length[120]',
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
    // Sólo permite ejecutar la acción si el usuario tiene al menos un
    // dispositivo. Si no, lo manda al panel para ver la pantalla de bienvenida.
    // =========================================================================
    private function redireccionarSiFaltaDispositivo(): ?RedirectResponse
    {
        $cantidad = (new \App\Models\DeviceModel())
            ->where('user_id', $this->usuarioActual())
            ->countAllResults();

        if ($cantidad > 0) {
            return null;
        }

        return redirect()->to('/panel')
            ->with('error', 'Primero vinculá un dispositivo para poder realizar esta acción.');
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Métodos públicos (responden a rutas):
   - index()                 → bienvenida (0 dispositivos) o panel monitor (≥1)
   - iniciarDemo()           → crea dispositivo+ambiente simulados (CTA "Ver demo")
   - seleccionarDispositivo()→ guarda en sesión el dispositivo activo del switcher
   - perfil()                → muestra los datos del usuario
   - actualizarPerfil()      → guarda nombre/apellido/email/usuario (pide contraseña)
   - actualizarPassword()    → cambia la contraseña (pide la actual)
   - compra()                → página de compra simulada
   - guardarMedicion()       → registra una medición manual y corre automatización
   - cambiarModo()           → cambia automatic/manual vía CommandService
   - cambiarActuador()       → prende/apaga fan/aromatizer/alert_led (solo en manual)

   Helpers privados:
   - dispositivoActivo()     → valida el active_device_id de sesión (pertenencia)
   - crearPanel()            → asegura setup del usuario y pide datos a PanelService
   - obtenerContexto()       → devuelve device_raw y space_raw del panel
   - leerDatos*()            → leen el POST (medición, perfil, password)
   - reglasMedicion()        → reglas de validación de la medición manual
   - validarFormulario*()    → corren la validación; null = OK, redirect = error
   - validarAutenticacionPerfil() → exige la contraseña actual para confirmar cambios
   - modoValido()/accionActuadorValida() → chequeo contra las listas blancas
   - estaEnModoManual()      → lee operating_mode del estado del dispositivo
   - crearMensajeCambioModo()→ arma el flash; en automático corre la automatización
   - redirigirAlPanelConError()/ConExito() → redirect a /panel con mensaje flash
   - redirigirConInputYDato()→ redirect + withInput + flash
   - usuarioActual()         → user_id guardado en sesión
   - redireccionarSiFaltaDispositivo() → guard: sin dispositivos no hay acciones

   Funciones del framework (CI4) usadas acá:
   - view() / redirect() / session() / $this->request->getPost()
   - countAllResults()       → (Model) cuenta filas que cumplen los where()
   - $this->validateData()   → valida un array contra reglas CI4
   - in_array($v, $lista, true) → (PHP) pertenencia estricta a la lista blanca
   ============================================================================ */
