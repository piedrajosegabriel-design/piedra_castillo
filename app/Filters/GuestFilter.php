<?php

namespace App\Filters;

use App\Models\SpaceModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('user_id')) {
            return null;
        }

        $spaceExists = (new SpaceModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->countAllResults() > 0;

        return redirect()->to($spaceExists ? '/panel' : '/panel/ambiente');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
