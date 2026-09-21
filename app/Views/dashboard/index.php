<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Price Tag App</a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Halo, <strong><?= esc($username) ?></strong> (<?= esc($role) ?>)</span>
                <a href="<?= base_url('profile/password') ?>" class="btn btn-outline-light btn-sm me-2">Ubah Password</a>
                <a href="<?= base_url('logout') ?>" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Menampilkan Pesan Error / Peringatan dari Filter -->
<?php if (session()->getFlashdata('msg')) : ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('msg') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
                        <h4>Selamat Datang di Sistem Price Tag</h4>
                        <p class="text-muted">Gunakan menu di bawah ini untuk mengelola aplikasi.</p>
                        <hr>
                        
                        <div class="row mt-4">
                            <?php if ($role === 'super_admin'): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-primary text-white p-3">
                                        <h5>Kelola Pengguna</h5>
                                        <p>Tambah, edit, nonaktifkan, dan reset password pengguna.</p>
                                        <a href="<?= base_url('admin/users') ?>" class="btn btn-light btn-sm text-primary fw-bold">Buka Kelola User</a>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-info text-white p-3">
                                        <h5>Kelola Outlet</h5>
                                        <p>Tambah, edit, nonaktifkan, dan hapus master outlet.</p>
                                        <a href="<?= base_url('admin/outlets') ?>" class="btn btn-light btn-sm text-info fw-bold">Buka Kelola Outlet</a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-4 mb-3">
                                <div class="card bg-success text-white p-3">
                                    <h5>Import Excel & Cetak</h5>
                                    <p>Unggah file Excel & cetak PDF price tag.</p>
                                    <a href="<?= base_url('pricetag') ?>" class="btn btn-light btn-sm text-success fw-bold">Buka Price Tag</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
