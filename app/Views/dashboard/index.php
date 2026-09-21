<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-theme.css') ?>" rel="stylesheet">
</head>
<body class="mk-body">
    <nav class="navbar navbar-expand-lg mk-topbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= base_url('dashboard') ?>">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="Manna Kampus" class="mk-logo-sm">
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="mk-user-pill">
                    <i class="bi bi-person"></i>
                    <span>Halo, <strong><?= esc($username) ?></strong> <span class="text-muted">(<?= esc($role) ?>)</span></span>
                </span>
                <a href="<?= base_url('profile/password') ?>" class="btn mk-btn-outline btn-sm mk-icon-link me-2">
                    <i class="bi bi-key"></i>
                    <span>Ubah Password</span>
                </a>
                <a href="<?= base_url('logout') ?>" class="btn btn-dark btn-sm mk-icon-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="mk-card p-4 p-md-5">
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
                        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                            <div>
                                <h2 class="mk-title mb-1">Dashboard Price Tag</h2>
                                <p class="mk-subtitle mb-0">Kelola akun, outlet, import Excel, dan cetak label harga.</p>
                            </div>
                            <?php if (session()->get('outlet_name')): ?>
                                <div class="mk-dashboard-outlet">
                                    <i class="bi bi-shop"></i>
                                    <div>
                                        <small>Outlet Aktif</small>
                                        <strong><?= esc(session()->get('outlet_name')) ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row g-3">
                            <?php if ($role === 'super_admin'): ?>
                                <div class="col-md-4">
                                    <div class="mk-action-card orange p-4">
                                        <i class="bi bi-people fs-2 mb-3 d-block"></i>
                                        <h5>Kelola Pengguna</h5>
                                        <p>Tambah, edit, nonaktifkan, dan reset password pengguna.</p>
                                        <a href="<?= base_url('admin/users') ?>" class="btn btn-light btn-sm fw-bold mk-icon-link"><i class="bi bi-arrow-right-circle"></i><span>Buka Kelola User</span></a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mk-action-card dark p-4">
                                        <i class="bi bi-shop fs-2 mb-3 d-block"></i>
                                        <h5>Kelola Outlet</h5>
                                        <p>Tambah, edit, nonaktifkan, dan hapus master outlet.</p>
                                        <a href="<?= base_url('admin/outlets') ?>" class="btn btn-light btn-sm fw-bold mk-icon-link"><i class="bi bi-arrow-right-circle"></i><span>Buka Kelola Outlet</span></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-4">
                                <div class="mk-action-card green p-4">
                                    <i class="bi bi-file-earmark-spreadsheet fs-2 mb-3 d-block"></i>
                                    <h5>Import Excel & Cetak</h5>
                                    <p>Unggah file Excel & cetak PDF price tag.</p>
                                    <a href="<?= base_url('pricetag') ?>" class="btn btn-light btn-sm fw-bold mk-icon-link"><i class="bi bi-arrow-right-circle"></i><span>Buka Price Tag</span></a>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
