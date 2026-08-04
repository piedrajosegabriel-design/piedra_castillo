<?php

namespace App\Controllers;

use App\Services\DevicePairingService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gestión de dispositivos del usuario: listado ("Mis dispositivos") y conexión
 * de un equipo nuevo por QR.
 *
 * No hay código de activación ni formulario de alta: el usuario aprieta
 * "Conectar", la web abre una ventana de vinculación y muestra el QR generado
 * en el momento. El equipo que se presente durante esa ventana queda dado de
 * alta solo (ver DevicePairingService).
 *
 * Rutas (grupo panel, filtro auth):
 *   GET  panel/dispositivos                  -> index()
 *   GET  panel/dispositivos/conectar         -> conectar()
 *   POST panel/dispositivos/conectar         -> iniciar()   (JSON: abre la ventana)
 *   GET  panel/dispositivos/conectar/estado  -> estadoVinculacion() (JSON, sondeo)
 *   POST panel/dispositivos/conectar/cancelar-> cancelar()   (JSON)
 */
class DispositivosController extends BaseController
{
    // =========================================================================
    // LISTADO — "Mis dispositivos"
    // =========================================================================

    /** Lista los dispositivos del usuario con su estado legible. */
    public function index(): string
    {
        $servicio = new DevicePairingService();

        return view('dispositivos/index', [
            'dispositivos' => $servicio->listarDeUsuario($this->usuarioActual()),
        ]);
    }

    // =========================================================================
    // CONEXIÓN POR QR
    // conectar() dibuja la pantalla; iniciar() abre la ventana y devuelve el QR;
    // estadoVinculacion() es el sondeo que espera al equipo.
    // =========================================================================

    /** Pantalla "Conectá tu Eden Air": un botón y, al apretarlo, el QR. */
    public function conectar(): string
    {
        return view('dispositivos/conectar', [
            'ssid'    => DevicePairingService::ssid(),
            'minutos' => DevicePairingService::MINUTOS_VENTANA,
        ]);
    }

    /**
     * Abre la ventana de vinculación y devuelve el QR ya dibujado.
     * Es lo que corre al hacer clic en "Conectar".
     */
    public function iniciar(): ResponseInterface
    {
        $paquete = (new DevicePairingService())->abrirVentana(
            $this->usuarioActual(),
            $this->request->getIPAddress()
        );

        // csrf_hash() es el token NUEVO: el proyecto lo regenera en cada
        // request, así que el JS lo guarda para el próximo POST.
        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()] + $paquete);
    }

    /**
     * Sondeo de la página mientras espera al equipo. GET → exento de CSRF.
     */
    public function estadoVinculacion(): ResponseInterface
    {
        $token  = (string) $this->request->getGet('token');
        $estado = (new DevicePairingService())->estado($token, $this->usuarioActual());

        return $this->response->setJSON([
            'estado'    => $estado['estado'],
            'mensaje'   => $estado['mensaje'],
            'expira_en' => $estado['expira_en'],
            'device'    => $estado['device'] ? [
                'id'     => (int) $estado['device']['id'],
                'nombre' => (string) $estado['device']['name'],
                'uid'    => (string) $estado['device']['device_uid'],
            ] : null,
        ]);
    }

    /** Cierra la ventana cuando el usuario cancela o se va de la pantalla. */
    public function cancelar(): ResponseInterface
    {
        (new DevicePairingService())->cancelar(
            (string) $this->request->getPost('token'),
            $this->usuarioActual()
        );

        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Devuelve el user_id guardado en sesión por el login. */
    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Métodos públicos (responden a rutas):
   - index()              → lista "Mis dispositivos"
   - conectar()           → pantalla con el botón "Conectar"
   - iniciar()            → abre la ventana de vinculación y devuelve el SVG
                            del QR (JSON). Es el clic en "Conectar".
   - estadoVinculacion()  → sondeo: ¿ya apareció el equipo? (JSON, GET sin CSRF)
   - cancelar()           → cierra la ventana antes de tiempo (JSON)

   Helpers privados:
   - usuarioActual()      → user_id de la sesión

   Servicios y funciones usados acá:
   - DevicePairingService::abrirVentana()  → ventana + QR dibujado
   - DevicePairingService::estado()        → estado de la ventana
   - DevicePairingService::listarDeUsuario()→ datos para el listado
   - $this->request->getIPAddress()        → (CI4) IP del navegador; sirve para
                                             desempatar si hay varias ventanas
   - $this->response->setJSON()            → respuesta JSON para el fetch
   ============================================================================ */
