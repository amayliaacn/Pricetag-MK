<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OutletModel;

class OutletAdmin extends BaseController
{
    public function index()
    {
        $model = new OutletModel();

        return view('admin/outlets/index', [
            'title'   => 'Kelola Outlet',
            'outlets' => $model->orderBy('code', 'ASC')->findAll(),
        ]);
    }

    public function create()
    {
        return view('admin/outlets/form', [
            'title'  => 'Tambah Outlet',
            'outlet' => null,
        ]);
    }

    public function store()
    {
        $model = new OutletModel();
        $code  = trim((string) $this->request->getPost('code'));
        $name  = trim((string) $this->request->getPost('name'));

        if ($code === '' || $name === '') {
            return redirect()->back()->withInput()->with('error', 'Kode dan nama outlet wajib diisi.');
        }

        if ($model->findByCode($code)) {
            return redirect()->back()->withInput()->with('error', 'Kode outlet sudah digunakan.');
        }

        $model->insert([
            'code'      => $code,
            'name'      => $name,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to(base_url('admin/outlets'))->with('success', 'Outlet berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $model  = new OutletModel();
        $outlet = $model->find((int) $id);

        if (! $outlet) {
            return redirect()->to(base_url('admin/outlets'))->with('error', 'Outlet tidak ditemukan.');
        }

        return view('admin/outlets/form', [
            'title'  => 'Edit Outlet',
            'outlet' => $outlet,
        ]);
    }

    public function update($id)
    {
        $model  = new OutletModel();
        $outlet = $model->find((int) $id);

        if (! $outlet) {
            return redirect()->to(base_url('admin/outlets'))->with('error', 'Outlet tidak ditemukan.');
        }

        $code = trim((string) $this->request->getPost('code'));
        $name = trim((string) $this->request->getPost('name'));

        if ($code === '' || $name === '') {
            return redirect()->back()->withInput()->with('error', 'Kode dan nama outlet wajib diisi.');
        }

        $existing = $model->findByCode($code);
        if ($existing && (int) $existing['id'] !== (int) $id) {
            return redirect()->back()->withInput()->with('error', 'Kode outlet sudah digunakan.');
        }

        $model->update((int) $id, [
            'code'      => $code,
            'name'      => $name,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to(base_url('admin/outlets'))->with('success', 'Outlet berhasil diperbarui.');
    }

    public function toggle($id)
    {
        $model  = new OutletModel();
        $outlet = $model->find((int) $id);

        if (! $outlet) {
            return redirect()->to(base_url('admin/outlets'))->with('error', 'Outlet tidak ditemukan.');
        }

        $model->update((int) $id, ['is_active' => (int) ! (bool) $outlet['is_active']]);

        return redirect()->to(base_url('admin/outlets'))->with('success', 'Status outlet berhasil diperbarui.');
    }

    public function delete($id)
    {
        $model = new OutletModel();

        try {
            $model->delete((int) $id);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('admin/outlets'))->with(
                'error',
                'Outlet tidak dapat dihapus karena masih memiliki data terkait. Nonaktifkan outlet sebagai gantinya.'
            );
        }

        return redirect()->to(base_url('admin/outlets'))->with('success', 'Outlet berhasil dihapus.');
    }
}
