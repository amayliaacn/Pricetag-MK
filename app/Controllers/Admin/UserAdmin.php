<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UserAdmin extends BaseController
{
    public function index()
    {
        // Batasi hanya super_admin yang boleh akses
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $model = new UserModel();
        $data = [
            'title' => 'Kelola Pengguna',
            'users' => $model->findAll()
        ];

        return view('admin/users/index', $data);
    }

    public function create()
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        return view('admin/users/create', ['title' => 'Tambah Pengguna Baru']);
    }

    public function store()
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();

        $data = [
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'     => $this->request->getPost('role')
        ];

        $model->insert($data);
        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    public function delete($id)
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        // Mencegah admin menghapus akunnya sendiri yang sedang aktif login
        if (session()->get('id') == $id) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Anda tidak dapat menghapus akun sendiri yang sedang digunakan.');
        }

        $model = new UserModel();
        $model->delete($id);

        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna berhasil dihapus!');
    }
}