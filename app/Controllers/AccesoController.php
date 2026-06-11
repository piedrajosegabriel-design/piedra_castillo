<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * AccesoController — todo lo "público" y de cuenta.
 *
 * Maneja: la landing, el login, el registro, la recuperación y el
 * restablecimiento de contraseña, y el logout. Es la puerta de entrada
 * del sistema: una vez logueado, el usuario pasa a PanelController.
 *
 * Organización del archivo:
 *   1) Vistas públicas (solo renderizan formularios)
 *   2) Login            (validarLogin + sus helpers privados)
 *   3) Registro         (guardarRegistro + sus helpers privados)
 *   4) Recuperación de contraseña (token por email)
 *   5) Logout
 *   6) Helpers compartidos (lectura de datos, redirecciones, sesión)
 */
class AccesoController extends BaseController
{
    // =========================================================================
    // 1) VISTAS PÚBLICAS
    // Métodos GET que solo muestran una pantalla. No procesan datos.
    // =========================================================================

    /** Landing pública del proyecto (página de inicio). */
    public function inicio(): string
    {
        return view('inicio');
    }

    /** Formulario de inicio de sesión. */
    public function login(): string
    {
        return view('login');
    }

    /** Formulario de creación de cuenta. */
    public function registro(): string
    {
        return view('registro');
    }

    /** Formulario "olvidé mi contraseña" (pide solo el email). */
    public function recuperar(): string
    {
        return view('recuperar');
    }

    // =========================================================================
    // 2) LOGIN
    // Flujo: leer datos → validar formulario → validar credenciales →
    // rehashear si hace falta → iniciar sesión → redirigir al panel.
    // Cada paso que puede fallar devuelve un RedirectResponse; si devuelve
    // null, el flujo continúa (patrón "guard clause").
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

    /** Lee usuario/correo y contraseña del POST (con trim al identificador). */
    private function leerDatosLogin(): array
    {
        return [
            'usuario'  => trim((string) $this->request->getPost('usuario')),
            'password' => (string) $this->request->getPost('password'),
        ];
    }

    /** Reglas de validación del formulario de login. */
    private function reglasLogin(): array
    {
        return [
            'usuario'  => 'required|min_length[3]|max_length[120]',
            'password' => 'required|max_length[255]',
        ];
    }

    /** Si el formulario no valida, vuelve al login con error; si valida, null. */
    private function validarFormularioLogin(array $datos): ?RedirectResponse
    {
        if ($this->validateData($datos, $this->reglasLogin())) {
            return null;
        }

        return $this->redirigirConInputYDato('/login', 'error', 'Completa tu usuario o correo y la contraseña.');
    }

    /**
     * Compara la contraseña ingresada contra el hash guardado (bcrypt).
     * password_verify() es la única forma correcta de comparar: nunca se
     * compara texto plano contra texto plano.
     */
    private function validarCredencialesLogin(?array $usuario, string $password): ?RedirectResponse
    {
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            return null;
        }

        return $this->redirigirConInputYDato('/login', 'error', 'Las credenciales ingresadas no son validas.');
    }

    /**
     * Si PHP cambió el algoritmo de hash por defecto, aprovecha este login
     * (donde tenemos la contraseña en claro) para regenerar el hash.
     */
    private function actualizarHashLoginSiHaceFalta(UserModel $usuarios, array $usuario, string $password): void
    {
        if (! password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            return;
        }

        $usuarios->actualizarHashContrasena((int) $usuario['id'], $password);
    }

    /**
     * Guarda los datos mínimos del usuario en sesión.
     * regenerate(true) cambia el ID de sesión: evita ataques de
     * "fijación de sesión" (que alguien fuerce un ID conocido).
     */
    private function iniciarSesion(array $usuario): void
    {
        session()->regenerate(true);
        session()->set([
            'user_id'      => (int) $usuario['id'],
            'user_name'    => $usuario['nombre'],
            'is_logged_in' => true,
        ]);
    }

    private function redirigirDespuesDelLogin(int $userId): RedirectResponse
    {
        // Nuevo flujo (Hito 2): el usuario entra directo al dashboard. La
        // bienvenida o el panel se decide según tenga o no dispositivos —
        // ya no se fuerza la selección de ambiente al loguearse.
        return redirect()->to('/panel');
    }

    // =========================================================================
    // 3) REGISTRO
    // Flujo: leer datos → validar formulario → verificar que email/usuario
    // no estén ocupados → crear el usuario → mandar al login.
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
            ->with('success', 'Cuenta creada correctamente. Inicia sesion para entrar al panel.');
    }

    /** Lee y normaliza los campos del registro (trim + email en minúsculas). */
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

    /** Reglas del registro: la contraseña exige mayúscula + minúscula + número. */
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

    /** Mensajes personalizados (en español) para las reglas con regex. */
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

    /** Si el formulario no valida, vuelve al registro con los errores. */
    private function validarFormularioRegistro(array $datos): ?RedirectResponse
    {
        if ($this->validateData($datos, $this->reglasRegistro(), $this->mensajesRegistro())) {
            return null;
        }

        return $this->redirigirConInputYDato('/registro', 'errors', $this->validator->getErrors());
    }

    /** Verifica que el email y el nombre de usuario no estén ya registrados. */
    private function validarUsuarioDisponible(UserModel $usuarios, array $datos): ?RedirectResponse
    {
        if (! $usuarios->existeCorreoOUsuario($datos['email'], $datos['usuario'])) {
            return null;
        }

        return $this->redirigirConInputYDato('/registro', 'errors', [
            'unique' => 'El correo o el nombre de usuario ya se encuentran registrados.',
        ]);
    }

    // =========================================================================
    // 4) RECUPERACIÓN DE CONTRASEÑA
    // Flujo en 3 pasos:
    //   a) procesarRecuperacion(): genera un token, lo guarda hasheado con
    //      vencimiento (15 min) y envía un email con el enlace.
    //   b) restablecer($token): si el token es válido, muestra el formulario.
    //   c) guardarNuevaPassword($token): valida y guarda la nueva contraseña.
    // =========================================================================

    public function procesarRecuperacion(): RedirectResponse
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/recuperar')->withInput()->with('error', 'Por favor, ingresa un correo valido.');
        }

        $usuarios = new UserModel();
        $usuario  = $usuarios->buscarParaLogin($email);

        if ($usuario) {
            $token     = bin2hex(random_bytes(32)); // Token Seguro
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Vence en 15 min
            $enlace    = site_url('restablecer/' . $token); //Link enviado por email
            $nombre    = (string) ($usuario['nombre'] ?? 'Usuario'); // Nombre o valor por defecto

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

        // Mensaje neutro a propósito: no revela si el correo existe o no
        // (evita que un atacante descubra qué cuentas están registradas).
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
        // Se vuelve a validar el token: entre que el usuario abrió el form
        // y lo envió pudo haber expirado.
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

    // =========================================================================
    // 5) LOGOUT
    // =========================================================================

    /** Destruye toda la sesión y vuelve al login. */
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sesion cerrada correctamente.');
    }

    // =========================================================================
    // 6) HELPERS COMPARTIDOS
    // =========================================================================

    /**
     * Redirección estándar tras un error: vuelve a la ruta indicada,
     * conserva lo tipeado (withInput) y deja un mensaje flash.
     */
    private function redirigirConInputYDato(string $ruta, string $clave, mixed $valor): RedirectResponse
    {
        return redirect()->to($ruta)
            ->withInput()
            ->with($clave, $valor);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Métodos públicos (responden a rutas):
   - inicio()                  → muestra la landing pública (vista inicio.php)
   - login()/registro()/recuperar() → muestran sus formularios (solo GET)
   - validarLogin()            → procesa el POST del login (valida y crea sesión)
   - guardarRegistro()         → procesa el POST del registro (crea el usuario)
   - procesarRecuperacion()    → genera token + envía email de recuperación
   - restablecer($token)       → muestra el form de nueva contraseña si el token vale
   - guardarNuevaPassword($token) → guarda la nueva contraseña y limpia el token
   - logout()                  → destruye la sesión

   Helpers privados:
   - leerDatosLogin()/leerDatosRegistro() → leen y normalizan el POST
   - reglasLogin()/reglasRegistro()       → devuelven las reglas de validación CI4
   - mensajesRegistro()                   → mensajes de error personalizados
   - validarFormularioLogin()/Registro()  → corren la validación; null = OK
   - validarCredencialesLogin()           → password_verify contra el hash
   - validarUsuarioDisponible()           → email/usuario únicos
   - actualizarHashLoginSiHaceFalta()     → rehash si cambió el algoritmo
   - iniciarSesion()                      → regenera sesión + guarda user_id
   - redirigirDespuesDelLogin()           → siempre a /panel (flujo Hito 2)
   - redirigirConInputYDato()             → redirect + withInput + flash

   Funciones del framework (CI4) usadas acá:
   - view('nombre', $datos)    → renderiza app/Views/nombre.php y devuelve el HTML
   - redirect()->to('/ruta')   → respuesta de redirección HTTP
   - ->withInput()             → conserva lo que el usuario tipeó (repobla el form)
   - ->with('clave', $valor)   → mensaje "flash" (vive un solo request)
   - session()                 → acceso a la sesión (get/set/destroy/regenerate)
   - $this->request->getPost() → lee un campo enviado por POST
   - $this->validateData()     → valida un array contra reglas; errores en $this->validator
   - log_message()             → escribe en writable/logs/

   Funciones de PHP usadas acá:
   - password_verify()         → compara contraseña en claro vs hash (bcrypt)
   - password_needs_rehash()   → true si el hash quedó viejo y conviene regenerarlo
   - bin2hex(random_bytes(32)) → token aleatorio criptográficamente seguro (64 chars)
   - filter_var(..., FILTER_VALIDATE_EMAIL) → chequea formato de email
   ============================================================================ */
