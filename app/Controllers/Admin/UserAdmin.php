<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OutletModel;
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
        $outletModel = new OutletModel();
        $data = [
            'title' => 'Kelola Pengguna',
            'users' => $model->select('users.*, outlets.code AS outlet_code, outlets.name AS outlet_name')
                             ->join('outlets', 'outlets.id = users.outlet_id', 'left')
                             ->orderBy('users.role', 'ASC')
                             ->orderBy('outlets.code', 'ASC')
                             ->orderBy('users.username', 'ASC')
                             ->findAll(),
            'outlets' => $outletModel->orderBy('code', 'ASC')->findAll(),
        ];

        return view('admin/users/index', $data);
    }

    public function create()
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $outletModel = new OutletModel();

        return view('admin/users/form', [
            'title'   => 'Tambah Pengguna Baru',
            'user'    => null,
            'outlets' => $outletModel->activeOutlets(),
        ]);
    }

    public function store()
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();
        $role = (string) $this->request->getPost('role');
        $outletId = $role === 'user' ? $this->request->getPost('outlet_id') : null;
        $password = (string) $this->request->getPost('password');

        if ($password === '' || strlen($password) < 6) {
            return redirect()->back()->withInput()->with('error', 'Password minimal 6 karakter.');
        }

        if ($model->where('username', $this->request->getPost('username'))->first()) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan.');
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => $role,
            'outlet_id' => $outletId,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $model->errors()));
        }

        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    public function edit($id)
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();
        $user = $model->find((int) $id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        $outletModel = new OutletModel();

        return view('admin/users/form', [
            'title'   => 'Edit Pengguna',
            'user'    => $user,
            'outlets' => $outletModel->activeOutlets(),
        ]);
    }

    public function update($id)
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();
        $user = $model->find((int) $id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        $role = (string) $this->request->getPost('role');
        $outletId = $role === 'user' ? $this->request->getPost('outlet_id') : null;
        $username = trim((string) $this->request->getPost('username'));
        $existing = $model->where('username', $username)->first();

        if ($existing && (int) $existing['id'] !== (int) $id) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan.');
        }

        $data = [
            'username'  => $username,
            'role'      => $role,
            'outlet_id' => $outletId,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];

        if (! $model->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $model->errors()));
        }

        return redirect()->to(base_url('admin/users'))->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function password($id)
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();
        $user = $model->find((int) $id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        return view('admin/users/password', [
            'title' => 'Ubah Password',
            'user'  => $user,
        ]);
    }

    public function updatePassword($id)
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        $password = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('password_confirm');

        if ($password === '' || strlen($password) < 6) {
            return redirect()->back()->with('error', 'Password minimal 6 karakter.');
        }

        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak sama.');
        }

        $model = new UserModel();
        $model->update((int) $id, ['password' => password_hash($password, PASSWORD_DEFAULT)]);

        return redirect()->to(base_url('admin/users'))->with('success', 'Password berhasil diperbarui.');
    }

    public function toggle($id)
    {
        if (session()->get('role') !== 'super_admin') {
            return redirect()->to('/dashboard');
        }

        if (session()->get('id') == $id) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $model = new UserModel();
        $user = $model->find((int) $id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        $model->update((int) $id, ['is_active' => (int) ! (bool) ($user['is_active'] ?? 1)]);

        return redirect()->to(base_url('admin/users'))->with('success', 'Status pengguna berhasil diperbarui.');
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
