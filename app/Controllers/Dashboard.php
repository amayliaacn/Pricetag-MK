<?php

namespace App\Controllers;

use App\Models\UserModel;

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

    public function password()
    {
        return view('dashboard/password', [
            'title'    => 'Ubah Password',
            'username' => session()->get('username'),
            'role'     => session()->get('role'),
        ]);
    }

    public function updatePassword()
    {
        $model = new UserModel();
        $user = $model->find((int) session()->get('id'));

        if (! $user) {
            session()->destroy();
            return redirect()->to('/login')->with('msg', 'Sesi tidak valid. Silakan login kembali.');
        }

        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword     = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('password_confirm');

        if (! password_verify($currentPassword, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama tidak sesuai.');
        }

        if ($newPassword === '' || strlen($newPassword) < 6) {
            return redirect()->back()->with('error', 'Password baru minimal 6 karakter.');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Konfirmasi password baru tidak sama.');
        }

        $model->update((int) $user['id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/dashboard')->with('success', 'Password berhasil diperbarui.');
    }
}
