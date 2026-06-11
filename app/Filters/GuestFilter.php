<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * GuestFilter — protege las pantallas de invitados (login, registro,
 * recuperación). Es el espejo de AuthFilter: acá el que NO puede entrar
 * es el usuario que YA tiene sesión.
 */
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

    /** No hace nada después del controller (la interfaz obliga a declararlo). */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO
   - before()  → null = invitado, puede ver login/registro;
                 redirect a /panel = ya está logueado, afuera
   - after()   → sin uso acá
   - Se registra con el alias 'guest' en app/Config/Filters.php
   - Cambio del Hito 2: antes consultaba SpaceModel para decidir destino;
     ahora no toca la base — /panel decide todo
   ============================================================================ */
