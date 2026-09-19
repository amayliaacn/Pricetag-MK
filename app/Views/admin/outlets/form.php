<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php $isEdit = $outlet !== null; ?>
    <div class="container py-4" style="max-width: 680px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4"><?= esc($title) ?></h3>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= $isEdit ? base_url('admin/outlets/update/' . $outlet['id']) : base_url('admin/outlets/store') ?>" method="post">
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Outlet</label>
                        <input type="text" name="code" class="form-control" id="code" value="<?= esc(old('code', $outlet['code'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Outlet</label>
                        <input type="text" name="name" class="form-control" id="name" value="<?= esc(old('name', $outlet['name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" <?= (int) old('is_active', $outlet['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label for="is_active" class="form-check-label">Outlet aktif</label>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('admin/outlets') ?>" class="btn btn-outline-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Outlet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
