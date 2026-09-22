<?php

namespace App\Controllers;

use App\Models\OutletModel;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        helper(['form']);
        return view('auth/login');
    }

    public function processLogin()
    {
        $session = session();
        $model = new UserModel();
        
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        
        $data = $model->where('username', $username)->first();
        
        if ($data) {
            if (isset($data['is_active']) && (int) $data['is_active'] !== 1) {
                $session->setFlashdata('msg', 'Akun Anda sedang nonaktif. Silakan hubungi administrator.');
                return redirect()->to('/login');
            }

            $pass = $data['password'];
            $verify_pass = password_verify($password, $pass);
            
            if ($verify_pass) {
                $session->regenerate();

                $outletId   = $data['outlet_id'] ?? null;
                $outletName = null;
                $outletCode = null;

                if ($outletId !== null) {
                    $outletModel = new OutletModel();
                    $outlet      = $outletModel->findById((int) $outletId);

                    if (! $outlet || (int) ($outlet['is_active'] ?? 1) !== 1) {
                        $session->setFlashdata('msg', 'Outlet akun Anda sedang nonaktif. Silakan hubungi administrator.');
                        return redirect()->to('/login');
                    }

                    $outletName  = $outlet['name'] ?? null;
                    $outletCode  = $outlet['code'] ?? null;
                }

                $ses_data = [
                    'id'          => $data['id'],
                    'username'    => $data['username'],
                    'role'        => $data['role'],
                    'outlet_id'   => $outletId,
                    'outlet_name' => $outletName,
                    'outlet_code' => $outletCode,
                    'logged_in'   => TRUE
                ];
                $session->set($ses_data);
                return redirect()->to('/dashboard'); // Akan diarahkan ke dashboard nanti
            } else {
                $session->setFlashdata('msg', 'Password salah.');
                return redirect()->to('/login');
            }
        } else {
            $session->setFlashdata('msg', 'Username tidak ditemukan.');
            return redirect()->to('/login');
        }
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/login');
    }

    // Fungsi rahasia untuk membuat akun super_admin pertama
    public function createSuperAdmin()
    {
        $model = new UserModel();
        
        $cek = $model->where('role', 'super_admin')->first();
        if ($cek) {
            return "Akun Super Admin sudah ada!";
        }

        $data = [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT), // Enkripsi password
            'role'     => 'super_admin'
        ];

        $model->insert($data);
        return "Akun Super Admin berhasil dibuat! Username: admin | Password: admin123";
    }
}
