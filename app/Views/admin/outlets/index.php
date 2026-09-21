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
                <h3 class="mb-1">Kelola Outlet</h3>
                <div class="text-muted">Tambah, edit, nonaktifkan, atau hapus master outlet.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-primary btn-sm mk-icon-link"><i class="bi bi-people"></i><span>Kelola User</span></a>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm mk-icon-link"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?= esc(session()->getFlashdata('success')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= esc(session()->getFlashdata('error')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="mk-table-card bg-white">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="mk-table-title">
                    <i class="bi bi-shop"></i>
                    <span>Daftar Outlet</span>
                </div>
                <a href="<?= base_url('admin/outlets/create') ?>" class="btn mk-btn-primary btn-sm mk-icon-link"><i class="bi bi-plus-circle"></i><span>Tambah Outlet</span></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Outlet</th>
                            <th>Status</th>
                            <th>Diperbarui</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outlets as $outlet): ?>
                            <tr>
                                <td>
                                    <span class="mk-outlet-chip">
                                        <i class="bi bi-shop"></i>
                                        <span>
                                            <strong><?= esc($outlet['code']) ?></strong>
                                            <span><?= (int) $outlet['is_active'] === 1 ? 'Outlet aktif' : 'Outlet nonaktif' ?></span>
                                        </span>
                                    </span>
                                </td>
                                <td class="fw-semibold"><?= esc($outlet['name']) ?></td>
                                <td><span class="badge text-bg-<?= (int) $outlet['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int) $outlet['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td class="small text-muted"><?= esc($outlet['updated_at'] ?? '-') ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('admin/outlets/edit/' . $outlet['id']) ?>" class="btn btn-outline-primary mk-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="<?= base_url('admin/outlets/toggle/' . $outlet['id']) ?>" class="btn btn-outline-secondary mk-icon-btn" title="<?= (int) $outlet['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= (int) $outlet['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>" onclick="return confirm('Ubah status outlet ini?')"><i class="bi bi-power"></i></a>
                                        <a href="<?= base_url('admin/outlets/delete/' . $outlet['id']) ?>" class="btn btn-outline-danger mk-icon-btn" title="Hapus" aria-label="Hapus" onclick="return confirm('Yakin ingin menghapus outlet ini?')"><i class="bi bi-trash"></i></a>
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
