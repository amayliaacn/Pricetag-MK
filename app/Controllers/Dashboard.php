<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $session = session();
        
        $data = [
            'title'    => 'Dashboard - Sistem Price Tag',
            'username' => $session->get('username'),
            'role'     => $session->get('role')
        ];

        // Pastikan $data ikut dikirimkan di dalam kurung ini:
        return view('dashboard/index', $data);
    }
}