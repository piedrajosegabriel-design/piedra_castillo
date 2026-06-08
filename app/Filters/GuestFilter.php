<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Si NO hay sesión, dejamos pasar (login/registro/recuperación son
        // pantallas para invitados).
        if (! session()->get('user_id')) {
            return null;
        }

        // Si YA hay sesión, el usuario no debería ver login/registro: lo
        // mandamos directo al dashboard. Es PanelController::index() quien
        // decide qué mostrar allí (bienvenida si no tiene dispositivos, o el
        // panel monitor si tiene al menos uno). Ya NO se fuerza la selección
        // de ambiente al loguearse (lógica nueva del Hito 2).
        return redirect()->to('/panel');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
