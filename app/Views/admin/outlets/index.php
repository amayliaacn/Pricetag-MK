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
                <h3 class="mb-1">Kelola Outlet</h3>
                <div class="text-muted">Tambah, edit, nonaktifkan, atau hapus master outlet.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-primary btn-sm">Kelola User</a>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?= esc(session()->getFlashdata('success')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= esc(session()->getFlashdata('error')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Daftar Outlet</strong>
                <a href="<?= base_url('admin/outlets/create') ?>" class="btn btn-primary btn-sm">+ Tambah Outlet</a>
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
                                <td><span class="badge text-bg-light border"><?= esc($outlet['code']) ?></span></td>
                                <td class="fw-semibold"><?= esc($outlet['name']) ?></td>
                                <td><span class="badge text-bg-<?= (int) $outlet['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int) $outlet['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td class="small text-muted"><?= esc($outlet['updated_at'] ?? '-') ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('admin/outlets/edit/' . $outlet['id']) ?>" class="btn btn-outline-primary">Edit</a>
                                        <a href="<?= base_url('admin/outlets/toggle/' . $outlet['id']) ?>" class="btn btn-outline-secondary" onclick="return confirm('Ubah status outlet ini?')"><?= (int) $outlet['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></a>
                                        <a href="<?= base_url('admin/outlets/delete/' . $outlet['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin menghapus outlet ini?')">Hapus</a>
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
