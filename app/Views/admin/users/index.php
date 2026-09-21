<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-theme.css') ?>" rel="stylesheet">
</head>
<body class="mk-body">
    <nav class="navbar navbar-expand-lg mk-topbar">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('dashboard') ?>">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="Manna Kampus" class="mk-logo-sm">
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('profile/password') ?>" class="btn mk-btn-outline btn-sm mk-icon-link"><i class="bi bi-key"></i><span>Ubah Password</span></a>
                <a href="<?= base_url('logout') ?>" class="btn btn-dark btn-sm mk-icon-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">Kelola Pengguna</h3>
                <div class="text-muted">Atur akun petugas berdasarkan outlet.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/outlets') ?>" class="btn btn-outline-primary btn-sm mk-icon-link"><i class="bi bi-shop"></i><span>Kelola Outlet</span></a>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm mk-icon-link"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
            $totalUsers = count($users);
            $activeUsers = count(array_filter($users, fn ($user) => (int) ($user['is_active'] ?? 1) === 1));
            $outletUsers = count(array_filter($users, fn ($user) => $user['role'] === 'user'));
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="mk-stat p-3"><div class="text-muted small">Total User</div><div class="fs-3 fw-semibold"><?= $totalUsers ?></div></div></div>
            <div class="col-md-4"><div class="mk-stat p-3"><div class="text-muted small">User Aktif</div><div class="fs-3 fw-semibold text-success"><?= $activeUsers ?></div></div></div>
            <div class="col-md-4"><div class="mk-stat p-3"><div class="text-muted small">Petugas Outlet</div><div class="fs-3 fw-semibold" style="color: var(--mk-orange);"><?= $outletUsers ?></div></div></div>
        </div>

        <div class="mk-table-card bg-white">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="mk-table-title">
                    <i class="bi bi-people"></i>
                    <span>Daftar Pengguna</span>
                </div>
                <a href="<?= base_url('admin/users/create') ?>" class="btn mk-btn-primary btn-sm mk-icon-link"><i class="bi bi-plus-circle"></i><span>Tambah Pengguna</span></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Outlet</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pengguna.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($user['username']) ?></td>
                                <td><span class="badge text-bg-<?= $user['role'] === 'super_admin' ? 'danger' : 'primary' ?>"><?= esc($user['role']) ?></span></td>
                                <td>
                                    <?php if ($user['role'] === 'super_admin'): ?>
                                        <span class="text-muted">Semua Outlet</span>
                                    <?php elseif (! empty($user['outlet_code'])): ?>
                                        <span class="mk-outlet-chip">
                                            <i class="bi bi-shop"></i>
                                            <span>
                                                <strong><?= esc($user['outlet_code']) ?></strong>
                                                <span><?= esc($user['outlet_name']) ?></span>
                                            </span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger small">Belum ada outlet</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge text-bg-<?= (int) ($user['is_active'] ?? 1) === 1 ? 'success' : 'secondary' ?>"><?= (int) ($user['is_active'] ?? 1) === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td class="small text-muted"><?= esc($user['created_at']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-outline-primary mk-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="<?= base_url('admin/users/password/' . $user['id']) ?>" class="btn btn-outline-warning mk-icon-btn" title="Ubah password" aria-label="Ubah password"><i class="bi bi-key"></i></a>
                                        <?php if ($user['id'] != session()->get('id')): ?>
                                            <a href="<?= base_url('admin/users/toggle/' . $user['id']) ?>" class="btn btn-outline-secondary mk-icon-btn" title="<?= (int) ($user['is_active'] ?? 1) === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= (int) ($user['is_active'] ?? 1) === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>" onclick="return confirm('Ubah status pengguna ini?')"><i class="bi bi-power"></i></a>
                                            <a href="<?= base_url('admin/users/delete/' . $user['id']) ?>" class="btn btn-outline-danger mk-icon-btn" title="Hapus" aria-label="Hapus" onclick="return confirm('Yakin ingin menghapus pengguna ini?')"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
