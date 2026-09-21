<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Price Tag</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-theme.css') ?>" rel="stylesheet">
</head>
<body class="mk-body">
    <main class="mk-login-shell">
        <div class="mk-login-card p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="Manna Kampus" class="mk-logo mb-3">
                <h3 class="mk-title mb-1">Price Tag System</h3>
                <p class="mk-subtitle mb-0">Masuk untuk import dan cetak price tag outlet.</p>
            </div>

            <?php if(session()->getFlashdata('msg')):?>
                <div class="alert alert-danger">
                    <?= esc(session()->getFlashdata('msg')) ?>
                </div>
            <?php endif;?>

            <form action="<?= base_url('auth/processLogin') ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control form-control-lg" id="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" id="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn mk-btn-primary btn-lg fw-semibold mk-icon-link justify-content-center">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Masuk</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
