<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user_id')) {
            return null;
        }

        $accept = strtolower($request->getHeaderLine('Accept'));

        if ($request->isAJAX() || str_contains($accept, 'application/json')) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Debes iniciar sesión para continuar.',
                ]);
        }

        return redirect()->to('/login')->with('error', 'Primero debes iniciar sesión.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
