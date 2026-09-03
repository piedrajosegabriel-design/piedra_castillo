<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter — protege el área privada (grupo 'panel', logout y dashboard).
 *
 * Corre ANTES del controller: si no hay sesión, corta el paso. Distingue
 * entre navegación normal (redirige al login) y llamadas AJAX/JSON
 * (responde 401 en JSON, que el JavaScript sí sabe interpretar).
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Con sesión iniciada (user_id presente) se deja pasar.
        if (session()->get('user_id')) {
            return null;
        }

        // Sin sesión: si la request vino de JavaScript (AJAX o pide JSON),
        // devolver un HTML de login no serviría — respondemos 401 en JSON.
        $accept = strtolower($request->getHeaderLine('Accept'));

        if ($request->isAJAX() || str_contains($accept, 'application/json')) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Debes iniciar sesión para continuar.',
                ]);
        }

        // Navegación normal sin sesión → al login con mensaje.
        return redirect()->to('/login')->with('error', 'Primero debes iniciar sesión.');
    }

    /** No hace nada después del controller (la interfaz obliga a declararlo). */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO
   - before()           → corre ANTES del controller; null = dejar pasar,
                          cualquier Response = cortar y responder eso
   - after()            → corre DESPUÉS del controller (acá no se usa)
   - isAJAX()           → true si la request trae el header X-Requested-With
   - getHeaderLine()    → lee un header HTTP como texto
   - str_contains()     → (PHP) ¿el texto contiene tal substring?
   - HTTP_UNAUTHORIZED  → constante para el código 401 (no autenticado)
   - Se registra con el alias 'auth' en app/Config/Filters.php
   ============================================================================ */
