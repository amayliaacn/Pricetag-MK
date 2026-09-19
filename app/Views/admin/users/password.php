<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4" style="max-width: 620px;">
        <div class="card border-0 shadow-sm">
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
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary">Kembali</a>
                        <button type="submit" class="btn btn-warning">Simpan Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
