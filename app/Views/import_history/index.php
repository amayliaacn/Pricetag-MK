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
        <a class="navbar-brand" href="<?= base_url('dashboard') ?>"><img src="<?= base_url('assets/img/logo.png') ?>" alt="Manna Kampus" class="mk-logo-sm"></a>
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn mk-user-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                    <span>Halo, <strong><?= esc(session()->get('username')) ?></strong> <span class="text-muted">(<?= esc(session()->get('role')) ?>)</span></span>
                </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item mk-account-password" href="<?= base_url('profile/password') ?>"><i class="bi bi-key me-2 text-warning"></i>Ubah Password</a></li>
                    <li><a class="dropdown-item text-danger mk-account-logout" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-0">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>" class="text-decoration-none"><i class="bi bi-house-door text-warning"></i></a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">History Import</li>
        </ol>
    </nav>
    <?php if (session()->get('outlet_name') || session()->get('outlet_code')): ?><div class="mk-dashboard-outlet"><i class="bi bi-shop"></i><div><small>OUTLET AKTIF</small><strong><?= esc(session()->get('outlet_code') ?: session()->get('outlet_name')) ?></strong></div></div><?php endif; ?>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-0 mb-2" style="<?= session()->get('role') === 'user' ? 'margin-top:-25px;' : '' ?>">
        <div>
            <h2 class="mk-title mb-1">History Import</h2>
            <p class="mk-subtitle mb-0">Daftar riwayat import data price tag.</p>
        </div>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm mk-icon-link" style="min-width:190px; justify-content:center;"><i class="bi bi-speedometer2"></i><span>Kembali ke Dashboard</span></a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <section class="mk-template-banner mb-3">
        <div class="mk-template-icon"><i class="bi bi-file-earmark-excel"></i><span><i class="bi bi-download"></i></span></div>
        <div class="mk-template-copy"><h5>Gunakan Template Excel</h5><p>Pastikan format data sesuai dengan template yang telah disediakan untuk menghindari kesalahan import.</p></div>
        <a href="<?= base_url('templates/pricetag/Template%20POP%20Price%20Tag.xlsx') ?>" download="Template POP Price Tag.xlsx" class="btn mk-btn-primary"><i class="bi bi-download me-2"></i>Download Template Excel</a>
    </section>

    <section class="mk-card p-3 p-lg-4 mb-3">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6 border-lg-end">
                <form action="<?= base_url('pricetag/import') ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="return_to" value="import-history">
                    <label for="file_excel" class="form-label">Pilih File Excel</label>
                    <div class="d-flex gap-2">
                        <input id="file_excel" type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                        <button type="submit" class="btn btn-success text-nowrap"><i class="bi bi-upload me-1"></i>Import Excel</button>
                    </div>
                    <div class="form-text mt-2">Format file harus .xlsx. Pastikan data sesuai dengan template.</div>
                    <?php if (session()->get('role') === 'super_admin'): ?>
                        <div class="mt-3">
                            <label class="form-label">Outlet Tujuan Import File</label>
                            <select name="outlet_id" class="form-select" required>
                                <option value="">Pilih Outlet Tujuan Import File</option>
                                <?php foreach ($activeOutlets as $outlet): ?>
                                    <option value="<?= (int) $outlet['id'] ?>"><?= esc($outlet['code'] . ' - ' . $outlet['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-lg-6">
                <form method="get" action="<?= base_url('import-history') ?>">
                    <div class="row g-2 align-items-end justify-content-lg-end">
                        <?php if (session()->get('role') === 'super_admin'): ?>
                            <div class="col-md-4"><label class="form-label">Outlet</label><select name="outlet_id" class="form-select" aria-label="Filter outlet"><option value="">Semua Outlet</option><?php foreach ($outlets as $outlet): ?><option value="<?= (int) $outlet['id'] ?>" <?= (string) $filters['outlet_id'] === (string) $outlet['id'] ? 'selected' : '' ?>><?= esc($outlet['code']) ?></option><?php endforeach; ?></select></div>
                        <?php endif; ?>
                        <div class="col"><select name="month" class="form-select" aria-label="Filter bulan"><option value="">Semua Bulan</option><?php foreach ([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $number=>$monthName): ?><option value="<?= $number ?>" <?= (string) $filters['month'] === (string) $number ? 'selected' : '' ?>><?= $monthName ?></option><?php endforeach; ?></select></div>
                        <div class="col"><select name="year" class="form-select" aria-label="Filter tahun"><option value="">Semua Tahun</option><?php foreach ($years as $year): ?><option value="<?= esc($year) ?>" <?= (string) $filters['year'] === (string) $year ? 'selected' : '' ?>><?= esc($year) ?></option><?php endforeach; ?></select></div>
                        <div class="col-auto d-flex gap-2"><button class="btn btn-warning"><i class="bi bi-search"></i> Filter</button><a class="btn btn-outline-secondary" href="<?= base_url('import-history') ?>">Reset</a></div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="mk-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mk-history-title mb-0"><i class="bi bi-clock-history"></i>Data History Import</h5>
            <span class="mk-data-total"><i class="bi bi-info-circle"></i>Total Data: <?= count($history) ?></span>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-dark"><tr><th>No</th><th>Nama File</th><?php if (session()->get('role') === 'super_admin'): ?><th>Outlet</th><?php endif; ?><th>Tanggal</th><th>Waktu</th><th>Diimpor Oleh</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($history)): ?><tr><td colspan="<?= session()->get('role') === 'super_admin' ? 8 : 7 ?>" class="text-center text-muted py-4">Belum ada riwayat impor.</td></tr>
            <?php else: foreach ($history as $index => $item): $timestamp = strtotime($item['imported_at']); ?>
                <tr><td><?= $index + 1 ?></td><td><?= esc($item['file_name']) ?></td><?php if (session()->get('role') === 'super_admin'): ?><td><span class="badge text-bg-light border"><?= esc($item['outlet_code']) ?></span></td><?php endif; ?><td><?= date('d M Y', $timestamp) ?></td><td><?= date('H:i:s', $timestamp) ?></td><td><?= esc($item['username']) ?></td><td><span class="mk-import-status"><i class="bi bi-check-circle-fill"></i>Berhasil</span></td><td><a class="btn btn-primary btn-sm" title="Lihat data impor" href="<?= base_url('pricetag?import=' . (int) $item['id']) ?>"><i class="bi bi-eye"></i></a></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
