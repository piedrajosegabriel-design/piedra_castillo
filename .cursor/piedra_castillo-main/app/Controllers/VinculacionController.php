<?php

namespace App\Controllers;

use App\Services\DevicePairingService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * VinculacionController — la vuelta del celular, después de configurar el WiFi.
 *
 * POR QUÉ ESTE CONTROLADOR ES PÚBLICO
 * El QR no puede llevarle datos a la ESP32: la web dibuja el QR sin poder
 * hablarle a la placa, que en ese momento todavía no está en ninguna red. Así
 * que el dato viaja en sentido contrario:
 *
 *   1. La ESP32 inventa un código de sesión cuando abre su portal.
 *   2. Al terminar de configurar el WiFi, el portal le ofrece al celular un
 *      botón hacia  /vinculacion/seguir?s=CODIGO
 *   3. La placa manda ese mismo código en POST /api/devices/pair.
 *   4. Esta pantalla, sondeando, ve que el equipo llegó y lo muestra.
 *
 * En ese teléfono nadie inició sesión —puede ser el celular de cualquiera—, así
 * que exigir login acá cortaría el flujo justo al final. La credencial es el
 * código: 16 bytes aleatorios que genera el equipo, de un solo uso, y que solo
 * sirve mientras la ventana está viva.
 *
 * QUÉ SE EXPONE: si la vinculación terminó y el nombre del equipo. Nada de la
 * cuenta. Para ver mediciones hay que iniciar sesión como siempre.
 *
 * RUTAS (ver app/Config/Routes.php, sección pública):
 *   GET vinculacion/seguir         -> seguir()  (pantalla)
 *   GET vinculacion/seguir/estado  -> estado()  (JSON, sondeo cada 2 s)
 */
class VinculacionController extends BaseController
{
    /** Pantalla que ve el celular al volver del portal del equipo. */
    public function seguir(): string
    {
        return view('vinculacion/seguir', [
            'sesion' => (string) ($this->request->getGet('s') ?? ''),
        ]);
    }

    /**
     * Sondeo: ¿ya llegó el equipo que configuré?
     *
     * Siempre 200, incluso cuando el código no existe: es una pantalla de
     * espera, no un formulario. Un 404 acá haría que el celular mostrara un
     * error justo cuando lo único que pasa es que el equipo todavía no
     * terminó de conectarse.
     */
    public function estado(): ResponseInterface
    {
        $sesion = trim((string) ($this->request->getGet('s') ?? ''));

        if ($sesion === '') {
            return $this->response->setJSON([
                'estado'  => 'desconocida',
                'mensaje' => 'No recibimos el código de esta vinculación.',
                'device'  => null,
            ]);
        }

        return $this->response->setJSON(
            (new DevicePairingService())->estadoPorSesion($sesion)
        );
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - seguir()   → dibuja la pantalla de espera del celular
   - estado()   → JSON del sondeo: esperando | vinculado | expirado |
                  cancelado | desconocida
   - getGet('s')            → (CI4) lee el parámetro ?s= de la URL
   - response->setJSON()    → (CI4) responde JSON con el Content-Type correcto
   ============================================================================ */
