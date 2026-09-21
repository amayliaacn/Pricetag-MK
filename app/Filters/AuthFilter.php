<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Cek Login
        if (!session()->get('logged_in')) {
            return redirect()->to('/login')->with('msg', 'Silakan login terlebih dahulu.');
        }

        if (session()->get('role') === 'user' && session()->get('outlet_id') === null) {
            session()->destroy();
            return redirect()->to('/login')->with('msg', 'Data outlet pengguna tidak valid. Silakan hubungi administrator.');
        }

        // 2. Cek Role (Jika ada parameter role yang dikirim dari Routes)
        if (!empty($arguments)) {
            $userRole = session()->get('role');

            if (!in_array($userRole, $arguments)) {
                return redirect()->to('/dashboard')->with('msg', 'Anda tidak memiliki hak akses ke halaman ini.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
