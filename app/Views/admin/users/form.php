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
                <a href="<?= base_url('admin/users') ?>" class="btn mk-btn-outline btn-sm mk-icon-link"><i class="bi bi-people"></i><span>Kelola User</span></a>
                <a href="<?= base_url('logout') ?>" class="btn btn-dark btn-sm mk-icon-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
            </div>
        </div>
    </nav>
    <?php
        $isEdit = $user !== null;
        $role = old('role', $user['role'] ?? 'user');
        $outletId = old('outlet_id', $user['outlet_id'] ?? '');
        $isActive = old('is_active', $user['is_active'] ?? 1);
    ?>
    <div class="container py-4" style="max-width: 760px;">
        <div class="mk-card">
            <div class="card-body p-4">
                <h3 class="mb-1"><?= esc($title) ?></h3>
                <p class="text-muted mb-4">Pilih outlet untuk role user. Super admin otomatis punya akses semua outlet.</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= $isEdit ? base_url('admin/users/update/' . $user['id']) : base_url('admin/users/store') ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" id="username" value="<?= esc(old('username', $user['username'] ?? '')) ?>" required>
                    </div>

                    <?php if (! $isEdit): ?>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Awal</label>
                            <input type="password" name="password" class="form-control" id="password" minlength="6" required>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" class="form-select" id="role" required>
                                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User Outlet</option>
                                <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="outlet_id" class="form-label">Outlet</label>
                            <select name="outlet_id" class="form-select" id="outlet_id">
                                <option value="">Pilih outlet</option>
                                <?php foreach ($outlets as $outlet): ?>
                                    <option value="<?= esc($outlet['id']) ?>" <?= (string) $outletId === (string) $outlet['id'] ? 'selected' : '' ?>>
                                        <?= esc($outlet['code']) ?> - <?= esc($outlet['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch mt-4">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" <?= (int) $isActive === 1 ? 'checked' : '' ?>>
                        <label for="is_active" class="form-check-label">Akun aktif</label>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary mk-icon-link"><i class="bi bi-arrow-left"></i><span>Kembali</span></a>
                        <button type="submit" class="btn mk-btn-primary mk-icon-link"><i class="bi bi-check2-circle"></i><span>Simpan Pengguna</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const role = document.getElementById('role');
        const outlet = document.getElementById('outlet_id');
        function syncOutlet() {
            outlet.disabled = role.value === 'super_admin';
            if (role.value === 'super_admin') outlet.value = '';
        }
        role.addEventListener('change', syncOutlet);
        syncOutlet();
    </script>
</body>
</html>
