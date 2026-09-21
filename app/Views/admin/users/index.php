<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">Kelola Pengguna</h3>
                <div class="text-muted">Atur akun petugas berdasarkan outlet.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/outlets') ?>" class="btn btn-outline-primary btn-sm">Kelola Outlet</a>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
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
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total User</div><div class="fs-3 fw-semibold"><?= $totalUsers ?></div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">User Aktif</div><div class="fs-3 fw-semibold text-success"><?= $activeUsers ?></div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Petugas Outlet</div><div class="fs-3 fw-semibold text-primary"><?= $outletUsers ?></div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong>Daftar Pengguna</strong>
                <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary btn-sm">+ Tambah Pengguna</a>
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
                                        <span class="badge text-bg-light border"><?= esc($user['outlet_code']) ?></span>
                                        <span class="small text-muted"><?= esc($user['outlet_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-danger small">Belum ada outlet</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge text-bg-<?= (int) ($user['is_active'] ?? 1) === 1 ? 'success' : 'secondary' ?>"><?= (int) ($user['is_active'] ?? 1) === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td class="small text-muted"><?= esc($user['created_at']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-outline-primary">Edit</a>
                                        <a href="<?= base_url('admin/users/password/' . $user['id']) ?>" class="btn btn-outline-warning">Password</a>
                                        <?php if ($user['id'] != session()->get('id')): ?>
                                            <a href="<?= base_url('admin/users/toggle/' . $user['id']) ?>" class="btn btn-outline-secondary" onclick="return confirm('Ubah status pengguna ini?')"><?= (int) ($user['is_active'] ?? 1) === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></a>
                                            <a href="<?= base_url('admin/users/delete/' . $user['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?')">Hapus</a>
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
