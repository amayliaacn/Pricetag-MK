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
            <div class="dropdown">
                <button class="btn mk-user-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                    <span>Halo, <strong><?= esc(session()->get('username')) ?></strong> <span class="text-muted">(<?= esc(session()->get('role')) ?>)</span></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?= base_url('profile/password') ?>"><i class="bi bi-key me-2 text-warning"></i>Ubah Password</a></li>
                    <li><a class="dropdown-item" href="<?= base_url('admin/users') ?>"><i class="bi bi-people me-2"></i>Kelola User</a></li>
                    <li><a class="dropdown-item text-danger mk-account-logout" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-4" style="max-width: 620px;">
        <div class="mk-card">
            <div class="card-body p-4">
                <h3 class="mb-1">Ubah Password</h3>
                <p class="text-muted mb-4">Pengguna: <strong><?= esc($user['username']) ?></strong></p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= base_url('admin/users/password/' . $user['id']) ?>" method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" id="password" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirm" class="form-control" id="password_confirm" minlength="6" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary mk-icon-link" onclick="if (document.referrer) { history.back(); return false; }"><i class="bi bi-arrow-left"></i><span>Kembali</span></a>
                        <button type="submit" class="btn btn-warning mk-icon-link"><i class="bi bi-check2-circle"></i><span>Simpan Password</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
