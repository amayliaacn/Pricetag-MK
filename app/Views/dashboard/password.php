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
            <div class="d-flex align-items-center">
                <span class="me-3">Halo, <strong><?= esc($username) ?></strong> (<?= esc($role) ?>)</span>
                <a href="<?= base_url('logout') ?>" class="btn btn-dark btn-sm mk-icon-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width: 620px;">
        <div class="mk-card">
            <div class="card-body p-4">
                <h3 class="mb-1">Ubah Password</h3>
                <p class="text-muted mb-4">Perbarui password akun Anda secara mandiri.</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= base_url('profile/password') ?>" method="post">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Lama</label>
                        <input type="password" name="current_password" class="form-control" id="current_password" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" id="password" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirm" class="form-control" id="password_confirm" minlength="6" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary mk-icon-link"><i class="bi bi-arrow-left"></i><span>Kembali</span></a>
                        <button type="submit" class="btn mk-btn-primary mk-icon-link"><i class="bi bi-check2-circle"></i><span>Simpan Password</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
