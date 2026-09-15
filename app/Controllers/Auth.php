<?php

namespace App\Controllers;

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
            $pass = $data['password'];
            $verify_pass = password_verify($password, $pass);
            
            if ($verify_pass) {
                $ses_data = [
                    'id'       => $data['id'],
                    'username' => $data['username'],
                    'role'     => $data['role'],
                    'logged_in'=> TRUE
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