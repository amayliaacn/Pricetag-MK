<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4">Tambah Pengguna Baru</h3>
                <form action="<?= base_url('admin/users/store') ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" id="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" id="password" required>
                    </div>
                    <div class="mb-4">
                        <label for="role" class="form-label">Role Akses</label>
                        <select name="role" class="form-select" id="role">
                            <option value="user">User (Petugas)</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-success">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>