<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-theme.css') ?>" rel="stylesheet">
    <style>
        /* Tabel detail tidak boleh memaksa 13 kolom masuk ke layar sempit.
           Lebar minimum ditangani oleh .table-responsive sehingga pengguna
           dapat menggeser tabel secara horizontal. */
        #tabelProdukDetail { width: 100%; min-width: 1180px; table-layout: auto; }
        /* Seluruh isi tabel harus tetap terlihat. Teks panjang boleh turun
           baris, sehingga tidak diganti dengan tanda titik-titik. */
        #tabelProdukDetail th, #tabelProdukDetail td {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            overflow-wrap: anywhere;
            word-break: normal;
            vertical-align: middle;
        }
        #tabelProdukDetail thead th {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            overflow-wrap: anywhere;
            line-height: 1.2;
            vertical-align: middle;
        }
        #tabelProdukDetail .template-size { width: 100%; min-width: 92px; }
        #tabelProdukDetail .template-size-wrapper { position: relative; }
        #tabelProdukDetail .template-size-wrapper.is-required .template-size {
            border-color: #ff8a1f;
            color: #d96b00;
            background-color: #fffaf5;
            font-weight: 600;
            padding-left: 2rem;
        }
        #tabelProdukDetail .template-size-warning {
            position: absolute;
            z-index: 1;
            left: .65rem;
            top: 50%;
            transform: translateY(-50%);
            color: #f07800;
            pointer-events: none;
        }
        #templateToast { position: fixed; top: 24px; right: 24px; z-index: 1080; min-width: 260px; display: none; }
        #templateToast.print-discount-error { max-width: min(36rem, calc(100vw - 48px)); max-height: 65vh; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; }
        /* Batasi tinggi modal dan scroll hanya pada daftar produk. Form harus
           menjadi flex container agar footer tetap terlihat di bawah modal. */
        #modalCetak .modal-content {
            max-height: calc(100vh - 2rem);
            display: flex;
        }
        #modalCetak #formCetak {
            min-height: 0;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
        }
        #modalCetak .modal-body {
            min-height: 0;
            flex: 1 1 auto;
            overflow-y: auto;
        }
        #modalCetak .modal-footer { flex-shrink: 0; background: #fff; }
        #modalCetak { --pop-orange:#E88719; --pop-green:#198754; --pop-border:#E5E7EB; }
        #modalCetak .modal-header { border-bottom:1px solid var(--pop-border); padding:16px 18px; }
        #modalCetak .modal-title { color:#171717; font-size:18px; font-weight:600; }
        #modalCetak .modal-title i { color:var(--pop-orange); font-size:22px; margin-right:8px; }
        #modalCetak .modal-body { padding:16px 18px; }
        #modalCetak .pop-section { background:#fff; border:1px solid var(--pop-border); border-radius:9px; padding:16px; }
        #modalCetak .pop-section-title, #modalCetak .pop-preview-title { color:#171717; font-weight:600; }
        #modalCetak .pop-section-title { font-size:16px; }
        #modalCetak .pop-section-desc { color:#6B7280; font-size:13px; margin:4px 0 10px; }
        #modalCetak .vegetable-pair-candidate { min-height:60px; display:flex; align-items:center; gap:10px; }
        #modalCetak .vegetable-pair-candidate + .vegetable-pair-candidate { margin-top:2px; }
        #modalCetak .vegetable-pair-candidate input { appearance:none; width:21px; height:21px; flex:0 0 21px; border:1px solid #CBD0D5; border-radius:50%; background:#fff; position:relative; }
        #modalCetak .vegetable-pair-candidate input:checked { background:var(--pop-green); border-color:var(--pop-green); }
        #modalCetak .vegetable-pair-candidate input:checked::after { content:'✓'; color:#fff; position:absolute; inset:0; display:grid; place-items:center; font-size:14px; font-weight:700; }
        #modalCetak .vegetable-pair-candidate input:disabled { background:#F3F4F6; border-color:#BFC5CC; cursor:not-allowed; }
        #modalCetak .vegetable-pair-candidate.is-paired .vegetable-candidate-text { color:#6B7280; }
        #modalCetak .vegetable-pair-candidate.is-paired:hover input:disabled { border-color:#DC3545; box-shadow:0 0 0 2px rgba(220,53,69,.16); }
        #modalCetak .vegetable-pair-candidate.is-paired:hover { cursor:not-allowed; }
        #modalCetak .vegetable-candidate-text { color:#171717; font-size:14px; font-weight:600; line-height:1.25; flex:1; }
        #modalCetak .vegetable-candidate-plu { display:block; color:#6B7280; font-size:13px; font-weight:400; margin-top:4px; }
        #modalCetak .vegetable-candidate-status { display:inline-flex; align-items:center; justify-content:center; width:82px; padding:5px 8px; border-radius:5px; background:#F0F1F3; color:#6B7280; font-size:11px; font-weight:600; line-height:1.2; white-space:nowrap; }
        #modalCetak .vegetable-candidate-status.is-paired { background:#E8F5ED; color:var(--pop-green); }
        #modalCetak #btnGabungkanVegetable { width:100%; height:44px; border:0; border-radius:999px; background:#E9ECEF; color:#9299A1; font-weight:600; margin-top:10px; }
        #modalCetak #btnGabungkanVegetable:not(:disabled) { background:var(--pop-green); color:#fff; cursor:pointer; }
        #modalCetak #btnGabungkanVegetable:not(:disabled):hover { background:#157347; }
        #modalCetak .pop-preview-title { font-size:15px; margin:20px 0 12px; }
        #modalCetak .pop-preview-table { margin-bottom:0; }
        #modalCetak .pop-preview-table thead th { background:#F5F6F8; color:#212529; font-weight:600; border-bottom:1px solid var(--pop-border); }
        #modalCetak .pop-preview-table td { color:#212529; border-bottom:1px solid var(--pop-border); padding:12px 10px; vertical-align:middle; }
        #modalCetak .pair-product-group { border-left:3px solid var(--pop-green); padding-left:8px; }
        #modalCetak .pair-product-group > div + div { margin-top:5px; }
        #modalCetak .pair-template-group { display:inline-flex; align-items:center; gap:8px; }
        #modalCetak .pair-marker { display:inline-flex; align-items:center; gap:4px; padding:5px 8px; margin-top:0; border-radius:5px; background:#E8F5ED; color:var(--pop-green); font-size:11px; font-weight:600; }
        #modalCetak .split-pair { display:inline-flex; align-items:center; gap:6px; min-height:32px; margin-top:10px; padding:7px 13px; border:1px solid #DC3545; border-radius:6px; background:#FFF0F1; color:#C82333; font-size:12px; font-weight:600; }
        #modalCetak .split-pair:hover { background:#DC3545; color:#fff; }
        #modalCetak .qty-stepper { display:inline-flex; align-items:center; justify-content:center; gap:5px; }
        #modalCetak .qty-stepper button { width:30px; height:30px; padding:0; border:0; border-radius:50%; display:grid; place-items:center; background:#FFF0DF; color:var(--pop-orange); }
        #modalCetak .qty-stepper button:hover:not(:disabled) { background:#FFE0B9; }
        #modalCetak .qty-stepper button:disabled { background:#F3F4F6; color:#B5B5B5; cursor:not-allowed; }
        #modalCetak .qty-stepper input { width:43px; height:30px; padding:0; border:1px solid transparent; border-radius:5px; background:transparent; color:#212529; font-size:14px; font-weight:600; text-align:center; font-variant-numeric:tabular-nums; }
        #modalCetak .qty-stepper input:focus { border-color:var(--pop-orange); outline:0; }
        #modalCetak .qty-stepper input::-webkit-outer-spin-button, #modalCetak .qty-stepper input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
        #modalCetak .qty-stepper input[type=number] { -moz-appearance:textfield; }
        #modalCetak .remove-print-product { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; margin-left:4px; padding:0; border:0; background:transparent; color:#DC3545; cursor:pointer; }
        #modalCetak .remove-print-product i { font-size:15px; line-height:1; }
        #modalCetak .remove-print-product:hover { color:#B02A37; }
        #modalCetak .pop-summary { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-top:16px; padding:14px; border-radius:6px; background:#F5F6F8; color:#212529; font-size:13px; }
        #modalCetak .pop-summary-lines { display:flex; flex-direction:column; gap:8px; }
        #modalCetak .pop-summary-total { font-size:14px; font-weight:600; white-space:nowrap; }
        @media (max-width:576px) { #modalCetak .modal-body { padding:12px; } #modalCetak .pop-preview-table { min-width:620px; } #modalCetak .pop-summary { align-items:flex-start; } }
        .search-print-card .card-body { padding: 12px 16px; }
        .search-print-card .control-label { display: block; font-size: .875rem; font-weight: 700; color: #202124; margin-bottom: 4px; }
        .search-print-card .search-group { flex: 1 1 480px; min-width: 300px; }
        .search-print-card .size-group { flex: 0 0 145px; }
        .search-print-card .search-control { position: relative; }
        .search-print-card .search-control i { position: absolute; left: 10px; top: 10px; color: #6c757d; }
        .search-print-card .search-control input { padding-left: 32px; }
        .search-print-card .control-divider { height: 48px; border-left: 1px solid #dee2e6; }
        .detail-table-card { width: 100%; overflow: visible; }
        .detail-table-card .table-responsive {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: auto;
        }
        .detail-table-card .table-responsive::-webkit-scrollbar { height: 12px; }
        .detail-table-card .table-responsive::-webkit-scrollbar-thumb {
            background: #adb5bd;
            border-radius: 999px;
        }
        .detail-table-card .table-responsive::-webkit-scrollbar-track { background: #f1f3f5; }
        .detail-table-header { align-items: center; }
        .detail-table-title { min-width: 0; }
        .detail-table-title h4 { white-space: nowrap; }
        .detail-table-title p { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bulk-template-control { min-width: 0; flex: 0 0 auto; flex-wrap: nowrap !important; }
        .bulk-template-control .bulk-template-count { white-space: nowrap; background: #fff0e6; border: 0; border-radius: 999px; color: #f06416; font-size: .75rem; font-weight: 600; line-height: 1.25; padding: .38rem .72rem; }
        .bulk-template-divider { width: 1px; height: 34px; margin: 0 2px; background: #e5e7eb; }
        .bulk-template-group { display: flex; align-items: center; gap: 10px; padding: 7px 9px; background: #fff7f1; border-radius: 11px; }
        .bulk-template-control .bulk-template-label { color: #111827; font-size: .875rem; font-weight: 700; white-space: nowrap; }
        .bulk-template-control .bulk-template-label i { color: #ff6900; font-size: 1.6rem; line-height: 1; vertical-align: middle; }
        .bulk-template-control .bulk-template-select { width: 108px; min-width: 105px; max-width: 110px; height: 35px; padding: .25rem 1.85rem .25rem .65rem; background-color: #fff; border: 1px solid #e1e5eb; border-radius: 8px; box-shadow: 0 1px 2px rgba(17, 24, 39, .05); }
        .bulk-template-control .btn-apply-template { height: 35px; padding: .25rem .8rem; background-color: #ff6900; border-color: #ff6900; border-radius: 8px; color: #fff; font-weight: 600; }
        .bulk-template-control .btn-apply-template:disabled { background-color: #fdba8c; border-color: #fdba8c; color: #fff; opacity: 1; cursor: not-allowed; }
        .bulk-template-control .btn-apply-template:hover,
        .bulk-template-control .btn-apply-template:focus { background-color: #d96500; border-color: #d96500; color: #fff; }
        #btnCetak:disabled { background-color: rgba(220, 53, 69, .55); border-color: rgba(220, 53, 69, .55); color: #fff; opacity: 1; cursor: not-allowed; }
        @media (max-width: 991.98px) {
            .detail-table-card .px-3.pt-3 { flex-wrap: wrap; align-items: flex-start; }
            .detail-table-card .px-3.pt-3 > div { min-width: 0; }
            .detail-table-title { flex: 1 1 100%; }
            .detail-table-title h4,
            .detail-table-title p { white-space: normal; }
            .bulk-template-control { flex: 1 1 100%; justify-content: flex-start !important; flex-wrap: wrap !important; }
            .bulk-template-divider { display: none; }
            .bulk-template-group { flex-wrap: wrap; }
        }
        @media (min-width: 1400px) {
            .detail-table-card { width: calc(100vw - 66px); margin-left: 0; position: relative; left: 50%; transform: translateX(-50%); }
        }
    </style>
</head>
<?php $isManualDetail = ! empty($history) && (int) ($history['source_type'] ?? 0) === 1; $detailLabel = $isManualDetail ? 'Detail Input' : 'Detail Import'; $infoLabel = $isManualDetail ? 'Informasi Input' : 'Informasi Import'; $historyLabel = $isManualDetail ? 'History Input' : 'History Import'; ?>
<body class="mk-body <?= $isManualDetail ? 'manual-detail-page' : '' ?>">
    <nav class="navbar navbar-expand-lg mk-topbar">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('dashboard') ?>"><img src="<?= base_url('assets/img/logo.png') ?>" alt="Manna Kampus" class="mk-logo-sm"></a>
            <div class="dropdown">
                <button class="btn mk-user-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i> <span>Halo, <strong><?= esc(session()->get('username')) ?></strong> <span class="text-muted">(<?= esc(session()->get('role')) ?>)</span></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?= base_url('profile/password') ?>"><i class="bi bi-key me-2 text-warning"></i>Ubah Password</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-0"><nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>" class="text-decoration-none"><i class="bi bi-house-door text-warning"></i></a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
        <?php if (! empty($history)): ?><li class="breadcrumb-item"><a href="<?= base_url('import-history?mode=' . ($isManualDetail ? 'manual' : 'excel')) ?>" class="text-decoration-none text-warning fw-semibold"><?= $historyLabel ?></a></li><?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?= $detailLabel ?></li>
    </ol></nav><?php if (session()->get('outlet_name') || session()->get('outlet_code')): ?><div class="mk-dashboard-outlet"><i class="bi bi-shop"></i><div><small>OUTLET AKTIF</small><strong><?= esc(session()->get('outlet_code') ?: session()->get('outlet_name')) ?></strong></div></div><?php endif; ?></div>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-0 mb-2" style="<?= session()->get('role') === 'user' ? 'margin-top:-25px;' : '' ?>">
        <div><h2 class="mk-title mb-1"><?= ! empty($history) ? $detailLabel : 'Daftar Data Price Tag' ?></h2>
            <p class="mk-subtitle mb-0"><?= ! empty($history) ? ($isManualDetail ? 'Detail data produk yang telah diinput secara manual.' : 'Detail data produk dari file yang telah diimport.') : 'Kelola, cari, dan cetak price tag produk.' ?></p>
        </div>
        <div class="d-flex gap-2">
        <?php if (! empty($history)): ?><a href="<?= base_url('import-history?mode=' . ($isManualDetail ? 'manual' : 'excel')) ?>" class="btn mk-btn-primary btn-sm"><?= $isManualDetail ? 'History Input Manual' : 'History Import' ?></a><?php endif; ?>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm mk-icon-link" style="min-width:190px; justify-content:center;"><i class="bi bi-speedometer2"></i><span>Kembali ke Dashboard</span></a>
        </div>
    </div>
    <?php if (! empty($history)): ?>
        <section class="mk-template-banner mb-3 py-3">
            <div class="mk-template-icon"><i class="bi bi-file-earmark-text"></i></div>
            <?php $detailImportedAt = new DateTimeImmutable($history['imported_at'], new DateTimeZone('UTC')); $detailImportedAt = $detailImportedAt->setTimezone(new DateTimeZone('Asia/Jakarta')); ?>
            <div class="mk-template-copy"><h5><?= $infoLabel ?></h5><p class="mb-0"><i class="bi bi-file-earmark me-2"></i><?= esc($history['file_name']) ?> <span class="mx-2">|</span> <i class="bi bi-shop me-1"></i><?= esc($history['outlet_code'] . ' - ' . $history['outlet_name']) ?> <span class="mx-2">|</span> <i class="bi bi-calendar3 me-1"></i><?= esc($detailImportedAt->format('d M Y')) ?> <span class="mx-2">|</span> <i class="bi bi-clock me-1"></i><?= esc($detailImportedAt->format('H:i:s')) ?> <span class="mx-2">|</span> <i class="bi bi-person me-1"></i><?= esc($history['username'] ?? '-') ?></p></div>
        </section>
    <?php endif; ?>
    <div class="container mt-4">
    <div class="card shadow-sm mb-4 search-print-card">
    <div class="card-body d-flex flex-wrap gap-3 align-items-end">
        <div class="search-group">
            <label for="searchProduk" class="control-label">Cari Produk</label>
            <div class="search-control"><i class="bi bi-search"></i><input type="search" id="searchProduk" class="form-control" placeholder="Cari berdasarkan PLU, nama barang, atau varian..." autocomplete="off"></div>
        </div>
        <div class="control-divider d-none d-lg-block"></div>
        <div class="size-group">
            <label for="filterUkuran" class="control-label">Filter Template POP</label>
            <select id="filterUkuran" class="form-select"><option value="all" <?= ($templateFilter ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Ukuran</option><option value="kcl" <?= ($templateFilter ?? '') === 'kcl' ? 'selected' : '' ?>>Kecil</option><option value="tgg" <?= ($templateFilter ?? '') === 'tgg' ? 'selected' : '' ?>>Tanggung</option><option value="mpdf" <?= ($templateFilter ?? '') === 'mpdf' ? 'selected' : '' ?>>A4</option><option value="pricetag" <?= ($templateFilter ?? '') === 'pricetag' ? 'selected' : '' ?>>Price Tag</option><option value="a5" <?= ($templateFilter ?? '') === 'a5' ? 'selected' : '' ?>>A5</option><option value="segitiga" <?= ($templateFilter ?? '') === 'segitiga' ? 'selected' : '' ?>>Segitiga</option><option value="segitiga_kecil" <?= ($templateFilter ?? '') === 'segitiga_kecil' ? 'selected' : '' ?>>Segitiga Kecil</option><option value="fresh" <?= ($templateFilter ?? '') === 'fresh' ? 'selected' : '' ?>>Butcher</option><option value="curah" <?= ($templateFilter ?? '') === 'curah' ? 'selected' : '' ?>>Vegetable</option><option value="vegetable_tgg" <?= ($templateFilter ?? '') === 'vegetable_tgg' ? 'selected' : '' ?>>Vegetable Tanggung</option></select>
        </div>

        <button type="button" id="btnCetak" class="btn btn-danger" disabled>
            <i class="bi bi-printer me-1"></i>Cetak Terpilih (<span id="jumlahTerpilih">0</span>)
        </button>
    </div>
    </div>
    <div class="mt-4">
        <?php
            $formatPeriod = static function ($value): string {
                if (empty($value)) return '-';

                $date = \DateTime::createFromFormat('!Y-m-d', (string) $value);
                if ($date === false) return '-';

                $months = [
                    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                    5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                    9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
                ];

                return $date->format('d') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
            };
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4" style="display:none !important;">
            <div>
                <h3><?= ! empty($history) ? 'Snapshot Import' : 'Daftar Data Price Tag' ?></h3>
                <?php if (! empty($history)): ?><div class="text-muted small"><?= esc($history['file_name']) ?> · <?= esc($history['outlet_code'] . ' - ' . $history['outlet_name']) ?> · <?= esc($history['imported_at']) ?></div><?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('import-history') ?>" class="btn mk-btn-primary btn-sm">History Import</a>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= nl2br(esc(session()->getFlashdata('error'))) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="product-table-header" class="card shadow-sm detail-table-card">
            <div class="card-body p-0">
                <div class="px-3 pt-3 d-flex justify-content-between gap-3 detail-table-header"><div class="detail-table-title"><h4 class="mb-1"><i class="bi bi-database text-warning me-2"></i>Data Produk</h4><p class="text-muted mb-0"><?= $isManualDetail ? 'Daftar produk yang diinput manual dari file ' . esc($history['file_name']) . '.' : 'Daftar produk yang diimport dari file Excel.' ?></p></div><div class="d-flex align-items-center justify-content-end gap-2 bulk-template-control"><span id="bulkTemplateCount" class="badge bulk-template-count">0 produk dipilih</span><span class="bulk-template-divider" aria-hidden="true"></span><div class="bulk-template-group"><span class="bulk-template-label"><i class="bi bi-layers-fill me-1"></i>Atur Template</span><select id="bulkTemplateSize" class="form-select form-select-sm bulk-template-select" aria-label="Ukuran template untuk produk terpilih"><option value="">Pilih ukuran</option><option value="kcl">Kecil</option><option value="tgg">Tanggung</option><option value="mpdf">A4</option><option value="pricetag">Price Tag</option><option value="a5">A5</option><option value="segitiga">Segitiga</option><option value="segitiga_kecil">Segitiga Kecil</option><option value="fresh">Butcher</option><option value="curah">Vegetable</option><option value="vegetable_tgg">Vegetable Tanggung</option></select><button type="button" id="btnApplyTemplate" class="btn btn-apply-template btn-sm text-nowrap" disabled><i class="bi bi-check-lg me-1"></i>Terapkan</button></div><?php if ($isManualDetail): ?><a class="btn btn-primary btn-sm text-nowrap" href="<?= base_url('import-history/manual/' . (int) $history['id']) ?>"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a><?php else: ?><button type="button" class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</button><?php endif; ?></div></div>
                <div class="table-responsive">
                <table id="tabelProdukDetail" class="table table-striped table-hover m-0 align-middle">
                    <colgroup>
                        <col style="width:4%"><col style="width:4%"><col style="width:7%"><col style="width:7%"><col style="width:6%"><col style="width:13%"><col style="width:12%"><col style="width:9%"><col style="width:10%"><col style="width:7%"><col style="width:8%"><col style="width:9%"><col style="width:4%">
                    </colgroup>
                    <thead class="table-dark">
                        <tr>
                            <!-- Ditambahkan label All dan sedikit perapihan style -->
                            <th style="width:70px;">
                                <div class="d-flex align-items-center gap-1">
                                    <input type="checkbox" id="checkAll" class="form-check-input mt-0">
                                    <label for="checkAll" class="mb-0 text-white user-select-none" style="cursor:pointer;">All</label>
                                </div>
                            </th>
                            <th>No</th>
                            <th>Awal Periode</th>
                            <th>Akhir Periode</th>
                            <th>SKU/PLU</th>
                            <th>Nama Produk</th>
                            <th>Varian</th>
                            <th>Harga Normal</th>
                            <th>Diskon / Harga Promo</th>
                            <th>Alokasi (Pcs)</th>
                            <th>Status Cetak</th>
                            <th>Ukuran Template *</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tags)): ?>
                            <tr id="emptySourceState"><td colspan="13" class="text-center py-3">Belum ada data produk</td></tr>
                        <?php else: ?>
                            <?php foreach ($tags as $tagIndex => $tag): ?>
                                <?php
                                    $tagId = (int) ($tag['id'] ?? 0);
                                    $isPrinted = (int) ($tag['is_printed'] ?? 0) === 1;
                $templateSize = in_array(($tag['template_size'] ?? ''), ['tgg', 'kcl', 'mpdf', 'pricetag', 'a5', 'fresh', 'curah', 'segitiga', 'segitiga_kecil', 'vegetable_tgg'], true) ? $tag['template_size'] : '';
                                ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-check-input product-check"
                                            data-id="<?= $tagId ?>"
                                            data-sku="<?= esc($tag['sku_plu']) ?>"
                                            data-name="<?= esc($tag['name']) ?>"
                                            data-variant="<?= esc($tag['variant'] ?? '') ?>"
                                            data-size="<?= esc($templateSize) ?>"
                                            data-allocation="<?= (int) ($tag['allocation_pcs'] ?? 0) ?>"
                                            data-discount="<?= (float) ($tag['discount_percent'] ?? 0) ?>"
                                            data-promo="<?= esc($tag['promo_price'] ?? '') ?>"
                                        >
                                    </td>
                                    <td><?= $tagIndex + 1 ?></td>
                                    <td><?= esc($formatPeriod($tag['start_period'] ?? null)) ?></td>
                                    <td><?= esc($formatPeriod($tag['end_period'] ?? null)) ?></td>
                                    <td><?= str_starts_with((string) ($tag['sku_plu'] ?? ''), 'AUTO-') ? '<span class="text-muted">Tanpa PLU</span>' : esc($tag['sku_plu']) ?></td>
                                    <td class="product-name-cell"><?= esc($tag['name']) ?></td>
                                    <td><?= esc($tag['variant']) ?: '-' ?></td>
                                    <td>Rp <?= number_format($tag['normal_price'], 0, ',', '.') ?></td>
                                    <td><?= !empty($tag['discount_percent']) ? number_format($tag['discount_percent'], 0, ',', '.') . '%' : (!empty($tag['promo_price']) ? 'Rp ' . number_format($tag['promo_price'], 0, ',', '.') : (!empty($tag['promo_text']) ? esc($tag['promo_text']) : '-')) ?></td>
                                    <td>
                                        <?= !empty($tag['allocation_pcs']) ? number_format($tag['allocation_pcs'], 0, ',', '.') : '-' ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= base_url('pricetag/printed/' . $tagId) ?>" class="d-inline printed-form">
                                            <input type="hidden" name="is_printed" value="0">
                                            <label class="form-check d-flex align-items-center gap-1 mb-0">
                                                <input
                                                    type="checkbox"
                                                    name="is_printed"
                                                    value="1"
                                                    class="form-check-input printed-check"
                                                    <?= $isPrinted ? 'checked' : '' ?>
                                                    <?= $tagId === 0 ? 'disabled' : '' ?>
                                                >
                                                <span class="badge <?= $isPrinted ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isPrinted ? 'Sudah' : 'Belum' ?></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td><form method="post" class="template-size-form" action="<?= base_url('pricetag/template/' . $tagId) ?><?= !empty($history['id']) ? '?import_id=' . (int) $history['id'] : '' ?>"><div class="template-size-wrapper <?= $templateSize === '' ? 'is-required' : '' ?>"><i class="bi bi-exclamation-triangle-fill template-size-warning <?= $templateSize === '' ? '' : 'd-none' ?>" aria-hidden="true"></i><select name="template_size" class="form-select form-select-sm template-size" <?= $tagId === 0 ? 'disabled' : '' ?>><option value="" <?= $templateSize === '' ? 'selected' : '' ?>>Pilih ukuran</option><option value="kcl" <?= $templateSize === 'kcl' ? 'selected' : '' ?>>Kecil</option><option value="tgg" <?= $templateSize === 'tgg' ? 'selected' : '' ?>>Tanggung</option><option value="mpdf" <?= $templateSize === 'mpdf' ? 'selected' : '' ?>>A4</option><option value="pricetag" <?= $templateSize === 'pricetag' ? 'selected' : '' ?>>Price Tag</option><option value="a5" <?= $templateSize === 'a5' ? 'selected' : '' ?>>A5</option><option value="segitiga" <?= $templateSize === 'segitiga' ? 'selected' : '' ?>>Segitiga</option><option value="segitiga_kecil" <?= $templateSize === 'segitiga_kecil' ? 'selected' : '' ?>>Segitiga Kecil</option><option value="fresh" <?= $templateSize === 'fresh' ? 'selected' : '' ?>>Butcher</option><option value="curah" <?= $templateSize === 'curah' ? 'selected' : '' ?>>Vegetable</option><option value="vegetable_tgg" <?= $templateSize === 'vegetable_tgg' ? 'selected' : '' ?>>Vegetable Tanggung</option></select></div></form></td>
                                    <td class="text-center" style="min-width:76px;">
                                        <button type="button" class="btn btn-primary mk-icon-btn btn-edit"
                                            data-id="<?= $tagId ?>"
                                            data-sku="<?= esc($tag['sku_plu']) ?>"
                                            data-name="<?= esc($tag['name']) ?>"
                                            data-variant="<?= esc($tag['variant'] ?? '') ?>"
                                            data-price="<?= (int) $tag['normal_price'] ?>"
                                            data-discount="<?= esc($tag['discount_percent'] ?? '') ?>"
                                            data-promo="<?= esc($tag['promo_price'] ?? '') ?>"
                                            data-template="<?= esc($templateSize) ?>"
                                            data-allocation="<?= esc($tag['allocation_pcs'] ?? '') ?>"
                                            data-start="<?= esc($tag['start_period'] ?? '') ?>"
                                            data-end="<?= esc($tag['end_period'] ?? '') ?>" title="Edit produk" aria-label="Edit produk"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="emptyFilterState" style="display:none"><td colspan="13" class="text-center py-3">Produk tidak ditemukan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    </main>
    <div id="templateToast" class="alert alert-success shadow-sm py-2 px-3 mb-0">Ukuran template produk berhasil disimpan.</div>

    <?php
    $discountForm = session()->getFlashdata('discount_form');
    $discountFormFields = ['sku_plu', 'name', 'variant', 'normal_price', 'discount_percent', 'promo_price', 'allocation_pcs', 'start_period', 'end_period'];
    $discountFormValues = [];
    if ($discountForm !== null) {
        foreach ($discountFormFields as $field) {
            $value = old($field, '', false);
            $discountFormValues[$field] = is_scalar($value) ? (string) $value : '';
        }
    }
    ?>
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="post" id="formEdit">
                <div class="modal-header"><h5 class="modal-title">Edit Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <?php if (is_int($discountForm)): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">PLU</label><input name="sku_plu" id="editSku" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Nama Produk</label><input name="name" id="editName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Varian</label><input name="variant" id="editVariant" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Harga Normal</label><input type="number" name="normal_price" id="editPrice" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Diskon (%)</label><input type="text" inputmode="decimal" pattern="\s*(?:\+?0*[1-9][0-9]?(?:[.,]0+)?\s*)?" title="Persentase bulat 1–99 atau kosong" name="discount_percent" id="editDiscount" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Harga Promo</label><input type="number" min="0" name="promo_price" id="editPromo" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Alokasi (Pcs)</label><input type="number" min="0" name="allocation_pcs" id="editAllocation" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Awal Periode</label><input type="date" name="start_period" id="editStart" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Akhir Periode</label><input type="date" name="end_period" id="editEnd" class="form-control"></div>
                </div></div>
                <div class="modal-footer justify-content-between gap-3">
                    <div class="text-danger small me-auto"><i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Template satu harga seperti Special Prize/Butcher: isi Harga Normal saja dan kosongkan Diskon/Harga Promo. A5 Discount boleh menggunakan Diskon saja.
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
                </div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="post" action="<?= base_url('pricetag/create') ?>">
                <?php if (!empty($history['id'])): ?><input type="hidden" name="import_id" value="<?= (int) $history['id'] ?>"><?php endif; ?>
                <div class="modal-header"><h5 class="modal-title">Tambah Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <?php if ($discountForm === 'create'): ?>
                    <div class="alert alert-danger mx-3 mt-3 mb-0" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">SKU/PLU <small class="text-muted"></small></label><input name="sku_plu" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Nama Produk</label><input name="name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Varian</label><input name="variant" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Harga Normal</label><input type="number" name="normal_price" class="form-control" min="0"></div>
                    <div class="col-md-4"><label class="form-label">Diskon (%)</label><input type="text" inputmode="decimal" pattern="\s*(?:\+?0*[1-9][0-9]?(?:[.,]0+)?\s*)?" title="Persentase bulat 1–99 atau kosong" name="discount_percent" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Harga Promo</label><input type="number" name="promo_price" class="form-control" min="0"></div>
                    <div class="col-md-4"><label class="form-label">Alokasi (Pcs)</label><input type="number" name="allocation_pcs" class="form-control" min="0"></div>
                    <div class="col-md-6"><label class="form-label">Awal Periode</label><input type="date" name="start_period" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Akhir Periode</label><input type="date" name="end_period" class="form-control"></div>
                </div></div>
                <div class="modal-footer justify-content-between gap-3">
                    <div class="text-danger small me-auto"><i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Template satu harga seperti Special Prize/Butcher: isi Harga Normal saja dan kosongkan Diskon/Harga Promo. A5 Discount boleh menggunakan Diskon saja.
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Produk</button></div>
                </div>
            </form>
        </div></div>
    </div>
    </div>

    <!-- Modal Cetak -->
    <div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= base_url('print-pdf') ?>" method="post" id="formCetak">
                    <?php if (!empty($history['id'])): ?>
                        <input type="hidden" name="import_id" value="<?= (int) $history['id'] ?>">
                    <?php endif; ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-printer"></i>Cetak POP Price Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="allvarPrintAlert" class="alert alert-info py-2 mb-3 d-none">Produk Tanggung dapat dipilih menggunakan template All Varian secara terpisah.</div>

                        <div id="vegetablePairPanel" class="pop-section mb-3 d-none">
                            <div class="pop-section-title">Pengaturan Vegetable Tanggung</div>
                            <div class="pop-section-desc">Centang 2 produk untuk digabungkan dalam 1 kertas POP.</div>
                            <div id="vegetablePairCandidates" class="small"></div>
                            <button type="button" id="btnGabungkanVegetable" disabled>Gabungkan 2 Produk</button>
                        </div>

                        <div id="vegetablePreviewHeader" class="pop-preview-title d-none">Preview Hasil Cetak</div>

                        <table class="table table-sm align-middle pop-preview-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th>Template</th>
                                    <th style="width:100px;">Qty Cetak</th>
                                    <th aria-hidden="true"></th>
                                </tr>
                            </thead>
                            <tbody id="tabelProdukTerpilih">
                                <!-- Diisi otomatis via JS -->
                            </tbody>
                        </table>
                        <div id="vegetablePrintTotals" class="pop-summary d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Cetak POP Price Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        const checkAll       = document.getElementById('checkAll');
        const productChecks  = document.querySelectorAll('.product-check');
        const btnCetak       = document.getElementById('btnCetak');
        const jumlahTerpilih = document.getElementById('jumlahTerpilih');
        const tabelBody      = document.getElementById('tabelProdukTerpilih');
        const searchProduk   = document.getElementById('searchProduk');
        const filterUkuran   = document.getElementById('filterUkuran');
        const productRows    = document.querySelectorAll('#tabelProdukDetail tbody tr');
        const bulkTemplateCount = document.getElementById('bulkTemplateCount');
        const bulkTemplateSize = document.getElementById('bulkTemplateSize');
        const btnApplyTemplate = document.getElementById('btnApplyTemplate');
        const editModal      = new bootstrap.Modal(document.getElementById('modalEdit'));
        const formEdit       = document.getElementById('formEdit');

        // Diskon dan Harga Promo adalah dua cara alternatif untuk menentukan harga akhir.
        // Saat salah satunya diisi, kosongkan yang lain agar data tidak bertentangan.
        const discountMessages = <?= json_encode(\App\Libraries\DiscountValidator::MESSAGES, JSON_HEX_TAG) ?>;
        document.querySelectorAll('#formEdit, #modalTambah form').forEach(form => {
            const discount = form.querySelector('[name="discount_percent"]');
            const promo = form.querySelector('[name="promo_price"]');
            if (!discount || !promo) return;
            discount.addEventListener('input', () => {
                discount.setCustomValidity('');
                if (discount.value.trim() !== '') promo.value = '';
            });
            discount.addEventListener('invalid', () => {
                const value = discount.value.trim().replace('−', '-').replace(',', '.');
                const numeric = /^[+-]?[0-9]+(?:\.[0-9]+)?$/.test(value);
                const amount = Number(value);
                const reason = !numeric ? 'malformed' : amount === 0 ? 'zero'
                    : amount < 0 || amount >= 100 ? 'out_of_range' : 'fraction';
                discount.setCustomValidity(discountMessages[reason]);
            });
            promo.addEventListener('input', () => {
                if (promo.value !== '') discount.value = '';
                discount.setCustomValidity('');
            });
        });

        <?php if ($discountForm !== null): ?>
        {
            const isCreate = <?= $discountForm === 'create' ? 'true' : 'false' ?>;
            const form = isCreate ? document.querySelector('#modalTambah form') : formEdit;
            const values = <?= json_encode($discountFormValues, JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
            if (!isCreate) form.action = <?= json_encode(base_url('pricetag/update/' . (int) $discountForm), JSON_HEX_TAG) ?>;
            Object.entries(values).forEach(([name, value]) => { form.elements.namedItem(name).value = value; });
            bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).show();
        }
        <?php endif; ?>

        const templateToast = document.getElementById('templateToast');
        let toastTimer;
        document.querySelectorAll('.template-size-form').forEach(form => {
            const select = form.querySelector('.template-size');
            <?php if ($isManualDetail): ?>
            select.disabled = false;
            const skuCell = form.closest('tr')?.querySelector('td:nth-child(5)');
            if (skuCell) { const sku = document.createElement('input'); sku.type = 'hidden'; sku.name = 'sku_plu'; sku.value = skuCell.textContent.trim(); form.appendChild(sku); }
            <?php endif; ?>
            let previousValue = select.value;
            select.addEventListener('change', async () => {
                const formData = new FormData(form);
                const wrapper = form.querySelector('.template-size-wrapper');
                const warning = form.querySelector('.template-size-warning');
                select.disabled = true;
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    if (!response.ok) throw new Error('Gagal menyimpan ukuran template.');
                    previousValue = select.value;
                    wrapper.classList.toggle('is-required', select.value === '');
                    warning.classList.toggle('d-none', select.value !== '');
                    const productCheck = form.closest('tr')?.querySelector('.product-check');
                    if (productCheck) productCheck.dataset.size = select.value;
                    updateTombolCetak();
                    templateToast.className = 'alert alert-success shadow-sm py-2 px-3 mb-0';
                    templateToast.textContent = 'Ukuran template produk berhasil disimpan.';
                    templateToast.style.display = 'block';
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => templateToast.style.display = 'none', 2500);
                } catch (error) {
                    select.value = previousValue;
                    wrapper.classList.toggle('is-required', select.value === '');
                    warning.classList.toggle('d-none', select.value !== '');
                    templateToast.className = 'alert alert-danger shadow-sm py-2 px-3 mb-0';
                    templateToast.textContent = error.message;
                    templateToast.style.display = 'block';
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => { templateToast.style.display = 'none'; templateToast.className = 'alert alert-success shadow-sm py-2 px-3 mb-0'; }, 3000);
                    updateTombolCetak();
                } finally {
                    select.disabled = false;
                }
            });
        });

        document.querySelectorAll('.printed-form').forEach(form => {
            const checkbox = form.querySelector('.printed-check');
            const badge = form.querySelector('.badge');
            let previousValue = checkbox.checked;
            checkbox.addEventListener('change', async () => {
                checkbox.disabled = true;
                const selected = checkbox.checked;
                const formData = new FormData(form);
                formData.set('is_printed', selected ? '1' : '0');
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    if (!response.ok) throw new Error('Status cetak gagal disimpan.');
                    previousValue = selected;
                    badge.textContent = selected ? 'Sudah' : 'Belum';
                    badge.className = `badge ${selected ? 'text-bg-success' : 'text-bg-secondary'}`;
                } catch (error) {
                    checkbox.checked = previousValue;
                    templateToast.className = 'alert alert-danger shadow-sm py-2 px-3 mb-0';
                    templateToast.textContent = error.message;
                    templateToast.style.display = 'block';
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => { templateToast.style.display = 'none'; templateToast.className = 'alert alert-success shadow-sm py-2 px-3 mb-0'; }, 3000);
                } finally {
                    checkbox.disabled = false;
                }
            });
        });

        function getChecked() {
            return document.querySelectorAll('.product-check:checked');
        }

        const validTemplateSizes = new Set(['kcl', 'tgg', 'mpdf', 'pricetag', 'a5', 'fresh', 'curah', 'segitiga', 'segitiga_kecil', 'vegetable_tgg']);

        function checkedProductsHaveValidTemplates() {
            return [...getChecked()].every(productCheck => validTemplateSizes.has(productCheck.dataset.size));
        }

        function getVisibleProductChecks() {
            return [...productRows]
                .filter(row => row.style.display === '')
                .map(row => row.querySelector('.product-check'))
                .filter(Boolean);
        }

        function updateTombolCetak() {
            const checkedProducts = [...getChecked()];
            const jumlah = checkedProducts.length;
            jumlahTerpilih.textContent = jumlah;
            btnCetak.disabled = jumlah === 0 || !checkedProductsHaveValidTemplates();
            bulkTemplateCount.textContent = `${jumlah} produk dipilih`;
            if (!btnApplyTemplate.dataset.running) {
                btnApplyTemplate.disabled = jumlah === 0 || bulkTemplateSize.value === '';
            }

            // Check All hanya mencerminkan produk yang sedang terlihat.
            const visibleChecks = getVisibleProductChecks();
            const visibleChecked = visibleChecks.filter(cb => cb.checked).length;
            checkAll.disabled = visibleChecks.length === 0;
            checkAll.checked = visibleChecks.length > 0 && visibleChecked === visibleChecks.length;
            checkAll.indeterminate = visibleChecked > 0 && visibleChecked < visibleChecks.length;
        }

        checkAll.addEventListener('change', function () {
            getVisibleProductChecks().forEach(checkbox => checkbox.checked = checkAll.checked);
            updateTombolCetak();
        });

        productChecks.forEach(cb => {
            cb.addEventListener('change', updateTombolCetak);
        });

        bulkTemplateSize.addEventListener('change', updateTombolCetak);

        btnApplyTemplate.addEventListener('click', async function () {
            const checkedProducts = [...getChecked()];
            const size = bulkTemplateSize.value;
            if (checkedProducts.length === 0 || size === '' || this.dataset.running) {
                updateTombolCetak();
                return;
            }

            this.dataset.running = '1';
            this.disabled = true;
            let successCount = 0;
            let failedCount = 0;
            const failures = [];

            for (const productCheck of checkedProducts) {
                const id = productCheck.dataset.id;
                const row = productCheck.closest('tr');
                const select = row?.querySelector('.template-size');
                const form = row?.querySelector('.template-size-form');
                const wrapper = row?.querySelector('.template-size-wrapper');
                const warning = row?.querySelector('.template-size-warning');

                // Endpoint lama untuk data manual ID 0 mencari berdasarkan SKU.
                // Karena SKU dapat duplikat, jangan mengirim request yang berisiko
                // mengubah produk lain yang tidak dipilih.
                if (!id || id === '0' || !form || !select) {
                    failedCount++;
                    failures.push(productCheck.dataset.sku || 'Produk tanpa ID');
                    continue;
                }

                const formData = new FormData(form);
                formData.set('template_size', size);
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok || !contentType.includes('application/json')) {
                        throw new Error('Gagal menyimpan ukuran template.');
                    }
                    const result = await response.json();
                    if (result.success !== true) {
                        throw new Error(result?.message || 'Gagal menyimpan ukuran template.');
                    }

                    select.value = size;
                    productCheck.dataset.size = size;
                    wrapper?.classList.toggle('is-required', size === '');
                    warning?.classList.toggle('d-none', size !== '');
                    successCount++;
                } catch (error) {
                    failedCount++;
                    failures.push(productCheck.dataset.sku || `ID ${id}`);
                }
            }

            delete this.dataset.running;
            updateTombolCetak();
            let message = `${successCount} produk berhasil diperbarui`;
            if (failedCount > 0) message += `, ${failedCount} gagal`;
            templateToast.className = failedCount > 0
                ? 'alert alert-warning shadow-sm py-2 px-3 mb-0'
                : 'alert alert-success shadow-sm py-2 px-3 mb-0';
            templateToast.textContent = message + (failures.length ? `. Tidak diubah: ${failures.join(', ')}` : '.');
            templateToast.style.display = 'block';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                templateToast.style.display = 'none';
                templateToast.className = 'alert alert-success shadow-sm py-2 px-3 mb-0';
            }, 5000);
        });

        document.querySelectorAll('.btn-edit').forEach(button => button.addEventListener('click', function () {
            const d = this.dataset;
            if (!d.id || d.id === '0') {
                alert('Produk ini belum memiliki ID database. Silakan simpan ulang data manual terlebih dahulu.');
                return;
            }
            formEdit.action = `<?= base_url('pricetag/update') ?>/${d.id}`;
            document.getElementById('editSku').value = d.sku;
            document.getElementById('editName').value = d.name;
            document.getElementById('editVariant').value = d.variant;
            document.getElementById('editPrice').value = d.price;
            document.getElementById('editDiscount').value = d.discount;
            document.getElementById('editDiscount').setCustomValidity('');
            document.getElementById('editPromo').value = d.promo;
            document.getElementById('editAllocation').value = d.allocation;
            document.getElementById('editStart').value = d.start;
            document.getElementById('editEnd').value = d.end;
            editModal.show();
        }));

        function filterRows() {
            const keyword = searchProduk.value.trim().toLowerCase();
            const ukuran = filterUkuran.value;
            let visibleProducts = 0;

            productRows.forEach(row => {
                const checkbox = row.querySelector('.product-check');
                if (!checkbox) return;
                const sizeSelect = row.querySelector('.template-size');

                const matches = checkbox.dataset.sku.toLowerCase().includes(keyword)
                    || checkbox.dataset.name.toLowerCase().includes(keyword)
                    || checkbox.dataset.variant.toLowerCase().includes(keyword);
                const matchesSize = ukuran === 'all' || (sizeSelect && sizeSelect.value === ukuran);
                row.style.display = matches && matchesSize ? '' : 'none';
                if (matches && matchesSize) visibleProducts++;
                if (ukuran !== 'all') {
                    checkbox.checked = matches && matchesSize;
                }
            });
            const emptyFilterState = document.getElementById('emptyFilterState');
            if (emptyFilterState) emptyFilterState.style.display = visibleProducts === 0 ? '' : 'none';
            updateTombolCetak();
        }

        searchProduk.addEventListener('input', filterRows);
        filterUkuran.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('template', filterUkuran.value);
            url.searchParams.delete('page');
            url.searchParams.delete('per_page');
            window.location.href = url.toString();
        });
        filterRows();

        let modalProducts = [];
        let vegetablePairs = [];
        let pairingSelectionOrder = [];
        let modalQty = new Map();
        let modalHasVegetable = false;
        const pairPanel = document.getElementById('vegetablePairPanel');
        const pairCandidates = document.getElementById('vegetablePairCandidates');
        const btnGabungkan = document.getElementById('btnGabungkanVegetable');
        const printTotals = document.getElementById('vegetablePrintTotals');
        const printSummary = document.getElementById('vegetablePrintSummary');
        const allvarPrintAlert = document.getElementById('allvarPrintAlert');
        const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
        const isVegetable = product => product.size === 'vegetable_tgg';
        const pairedIds = () => new Set(vegetablePairs.flatMap(pair => [pair.top.id, pair.bottom.id]));
        const pairingSelectedIds = () => pairingSelectionOrder.slice(0, 2);

        function templateLabel(size) {
            return {mpdf:'A4', pricetag:'Price Tag', a5:'A5', fresh:'Butcher', curah:'Vegetable', segitiga:'Segitiga', segitiga_kecil:'Segitiga Kecil', vegetable_tgg:'Vegetable Tanggung'}[size] || 'Kecil';
        }

        function parseFiniteNumber(value) {
            const raw = String(value ?? '').trim();
            if (raw === '' || !/^-?(?:\d+(?:\.\d+)?|\.\d+)$/.test(raw)) return null;
            const number = Number(raw);
            return Number.isFinite(number) ? number : null;
        }

        function isValidSmallTriangleDiscount(value) {
            const raw = String(value ?? '').trim();
            if (raw === '' || !/^\d+$/.test(raw)) return false;
            const number = Number(raw);
            return Number.isInteger(number) && number >= 1 && number <= 99;
        }

        function isLegacySmallTriangleDiscount(value) {
            const raw = String(value ?? '').trim();
            return raw === '' || raw === '0';
        }

        function hasPositivePromo(value) {
            const number = parseFiniteNumber(value);
            return number !== null && number > 0;
        }

        function isValidSmallTriangleProduct(product) {
            return isValidSmallTriangleDiscount(product.discount)
                || (isLegacySmallTriangleDiscount(product.discount) && hasPositivePromo(product.promo));
        }

        function smallTriangleDesignLabel(product) {
            return 'Segitiga Kecil';
        }

        function discountFields(product) {
            const {id, size, allocation, discount} = product;
            if (size === 'segitiga_kecil') return '';
            const canAllvar = size === 'tgg' && Number(allocation || 0) === 0;
            const isA4 = size === 'mpdf';
            const hasDiscountTemplate = ['kcl', 'tgg', 'a5', 'segitiga'].includes(size);
            const showDiscountInfo = Number(discount || 0) > 0 && Number(discount || 0) < 10;
            const canChooseDiscount = showDiscountInfo && hasDiscountTemplate && !isA4;
            const showA4DiscountInfo = showDiscountInfo && isA4;
            if (isVegetable(product)) return '';
            if (canChooseDiscount) return `<div class="mt-2 small"><div class="text-muted mb-1">Diskon &lt; 10%</div><label class="d-block"><input type="radio" name="discount_mode[${id}]" value="auto" checked> Turun harga</label><label class="d-block"><input type="radio" name="discount_mode[${id}]" value="show"> Tampil diskon</label></div>`;
            if (showA4DiscountInfo) return `<input type="hidden" name="discount_mode[${id}]" value="show"><div class="mt-2 small"><div class="text-muted mb-1">Diskon persen A4</div><span class="text-success">Otomatis menggunakan template Disc A4</span></div>`;
            return `<input type="hidden" name="discount_mode[${id}]" value="auto">`;
        }

        function formatQty(value) {
            const number = Math.max(1, parseInt(value, 10) || 1);
            return number < 10 ? `0${number}` : String(number);
        }

        function qtyStepper(value, className, dataAttribute, hiddenName = '') {
            const hidden = hiddenName ? `<input type="hidden" name="${hiddenName}" value="${Math.max(1, parseInt(value, 10) || 1)}">` : '';
            return `${hidden}<span class="qty-stepper"><button type="button" class="qty-minus" ${dataAttribute} aria-label="Kurangi qty"><i class="bi bi-dash"></i></button><input type="number" value="${formatQty(value)}" min="1" step="1" class="${className}" ${dataAttribute} aria-label="Qty cetak"><button type="button" class="qty-plus" ${dataAttribute} aria-label="Tambah qty"><i class="bi bi-plus"></i></button></span>`;
        }

        function updateStepperStates() {
            tabelBody.querySelectorAll('.qty-stepper').forEach(stepper => {
                const input = stepper.querySelector('input[type="number"]');
                const minus = stepper.querySelector('.qty-minus');
                if (input && minus) minus.disabled = Number(input.value) <= 1;
            });
        }

        function productRow(product) {
            const id = product.id;
            const canAllvar = product.size === 'tgg' && Number(product.allocation || 0) === 0;
            const row = document.createElement('tr');
            const vegetableLabel = modalHasVegetable && isVegetable(product) ? '<div class="small text-muted">POP 1 Produk</div>' : '';
            const templateText = product.size === 'kcl' ? '<span class="text-muted">Kecil</span>' : `<span>${product.size === 'segitiga_kecil' ? smallTriangleDesignLabel(product) : templateLabel(product.size)}</span>`;
            row.innerHTML = `<td>${escapeHtml(product.sku)}<input type="hidden" name="tag_ids[]" value="${escapeHtml(id)}"></td><td>${escapeHtml(product.name)}${product.variant ? ' - ' + escapeHtml(product.variant) : ''}${vegetableLabel}</td><td><input type="hidden" name="size[${escapeHtml(id)}]" value="${escapeHtml(product.size)}">${product.size === 'tgg' ? `<label class="form-check mb-0"><input type="checkbox" name="allvar[${escapeHtml(id)}]" value="1" class="form-check-input allvar-product" ${canAllvar ? '' : 'disabled'}><span class="form-check-label">All Varian</span></label>` : templateText}${discountFields(product)}</td><td class="text-center">${qtyStepper(modalQty.get(id) || 1, 'modal-qty', `data-product-id="${escapeHtml(id)}"`, `qty[${escapeHtml(id)}]` )}</td><td class="text-center"><button type="button" class="remove-print-product" data-product-id="${escapeHtml(id)}" title="Keluarkan dari daftar cetak" aria-label="Keluarkan dari daftar cetak"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>`;
            return row;
        }

        function pairRow(pair, pairIndex) {
            const row = document.createElement('tr');
            const top = pair.top, bottom = pair.bottom;
            row.innerHTML = `<td>${escapeHtml(top.sku)}<br>${escapeHtml(bottom.sku)}<input type="hidden" name="tag_ids[]" value="${escapeHtml(top.id)}"><input type="hidden" name="tag_ids[]" value="${escapeHtml(bottom.id)}"><input type="hidden" name="size[${escapeHtml(top.id)}]" value="vegetable_tgg"><input type="hidden" name="size[${escapeHtml(bottom.id)}]" value="vegetable_tgg"></td><td><div class="pair-product-group"><div>${escapeHtml(top.name)}</div><div>${escapeHtml(bottom.name)}</div><button type="button" class="split-pair" data-pair-index="${pairIndex}"><i class="bi bi-link-45deg"></i><span>Pisahkan</span></button></div></td><td><span class="pair-template-group"><span>Vegetable Tanggung</span><span class="pair-marker"><i class="bi bi-layers"></i>2 Produk / POP</span></span><input type="hidden" name="vegetable_pairs[${pairIndex}][top_id]" value="${escapeHtml(top.id)}"><input type="hidden" name="vegetable_pairs[${pairIndex}][bottom_id]" value="${escapeHtml(bottom.id)}"><input type="hidden" name="vegetable_pairs[${pairIndex}][qty]" value="${escapeHtml(pair.qty)}"></td><td class="text-center">${qtyStepper(pair.qty, 'modal-pair-qty', `data-pair-index="${pairIndex}"`)}</td><td></td>`;
            return row;
        }

        function renderPairCandidates() {
            const vegProducts = modalProducts.filter(isVegetable);
            pairPanel.classList.toggle('d-none', vegProducts.length < 2);
            const used = pairedIds();
            pairCandidates.innerHTML = vegProducts.length < 2 ? '' : vegProducts.map(product => `<label class="vegetable-pair-candidate ${used.has(product.id) ? 'is-paired' : ''}"><input type="checkbox" class="vegetable-pair-check" value="${escapeHtml(product.id)}" ${used.has(product.id) ? 'disabled' : ''}><span class="vegetable-candidate-text">${escapeHtml(product.name)}<span class="vegetable-candidate-plu">PLU: ${escapeHtml(product.sku)}</span></span><span class="vegetable-candidate-status ${used.has(product.id) ? 'is-paired' : ''}">${used.has(product.id) ? '2 Produk' : '1 Produk'}</span></label>`).join('');
            document.querySelectorAll('.vegetable-pair-check').forEach(input => input.addEventListener('change', () => {
                const id = String(input.value);
                pairingSelectionOrder = pairingSelectionOrder.filter(selectedId => String(selectedId) !== id);
                if (input.checked) pairingSelectionOrder.push(id);
                updatePairButton();
            }));
            updatePairButton();
        }

        function updatePairButton() {
            btnGabungkan.disabled = pairingSelectionOrder.length !== 2;
        }

        function removeProductFromPrintList(productId) {
            const id = String(productId);
            if (pairedIds().has(id)) return;

            modalProducts = modalProducts.filter(product => String(product.id) !== id);
            modalQty.delete(productId);
            pairingSelectionOrder = pairingSelectionOrder.filter(selectedId => String(selectedId) !== id);

            const checkbox = [...productChecks].find(productCheck => String(productCheck.dataset.id) === id);
            if (checkbox) checkbox.checked = false;

            renderPreview();
            updateTombolCetak();
            if (modalProducts.length === 0) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCetak')).hide();
            }
        }

        function renderPreview() {
            tabelBody.innerHTML = '';
            document.getElementById('vegetablePreviewHeader').classList.toggle('d-none', modalProducts.length === 0);
            printTotals.classList.toggle('d-none', modalProducts.length === 0);
            printTotals.classList.toggle('border-top', modalHasVegetable);
            const used = pairedIds();
            modalProducts.forEach(product => {
                const pairIndex = vegetablePairs.findIndex(pair => pair.top.id === product.id);
                if (pairIndex >= 0) { const pair = vegetablePairs[pairIndex]; tabelBody.appendChild(pairRow(pair, pairIndex)); return; }
                if (used.has(product.id)) return;
                tabelBody.appendChild(productRow(product));
            });
            renderPairCandidates();
            const singleCount = modalProducts.filter(product => !used.has(product.id)).reduce((total, product) => total + Number(modalQty.get(product.id) || 1), 0);
            const pairCount = vegetablePairs.reduce((total, pair) => total + Number(pair.qty || 0), 0);
            printTotals.innerHTML = modalHasVegetable ? `<div class="pop-summary-lines"><div>POP 1 Produk: <strong>${singleCount} lembar</strong></div><div>POP 2 Produk: <strong>${pairCount} lembar</strong></div></div><div class="pop-summary-total">Total: ${singleCount + pairCount} lembar</div>` : `<div>Produk Terpilih: <strong>${modalProducts.length}</strong> produk</div><div class="pop-summary-total">Total Qty Cetak: ${singleCount + pairCount} POP</div>`;
            printTotals.classList.remove('d-none');
            tabelBody.querySelectorAll('.modal-qty').forEach(input => input.addEventListener('change', () => { const value = Number(input.value); if (Number.isInteger(value) && value > 0) modalQty.set(input.dataset.productId, value); else modalQty.set(input.dataset.productId, 1); renderPreview(); }));
            tabelBody.querySelectorAll('.modal-pair-qty').forEach(input => input.addEventListener('change', () => { const value = Number(input.value); const index = Number(input.dataset.pairIndex); if (Number.isInteger(value) && value > 0) vegetablePairs[index].qty = value; else vegetablePairs[index].qty = 1; renderPreview(); }));
            tabelBody.querySelectorAll('.qty-plus, .qty-minus').forEach(button => button.addEventListener('click', () => { const input = button.parentElement.querySelector('input[type="number"]'); const value = Math.max(1, (parseInt(input.value, 10) || 1) + (button.classList.contains('qty-plus') ? 1 : -1)); input.value = formatQty(value); input.dispatchEvent(new Event('change', {bubbles:true})); }));
            tabelBody.querySelectorAll('.qty-stepper input').forEach(input => { const value = Number(input.value); input.value = Number.isInteger(value) && value > 0 ? formatQty(value) : '01'; input.addEventListener('focus', () => input.select()); input.addEventListener('blur', () => { if (!/^\d+$/.test(input.value) || Number(input.value) < 1) { input.value = '01'; input.dispatchEvent(new Event('change', {bubbles:true})); } }); });
            tabelBody.querySelectorAll('.split-pair').forEach(button => button.addEventListener('click', () => { const index = Number(button.dataset.pairIndex); const pair = vegetablePairs[index]; modalQty.set(pair.top.id, pair.topQty); modalQty.set(pair.bottom.id, pair.bottomQty); vegetablePairs.splice(index, 1); renderPreview(); }));
            tabelBody.querySelectorAll('.remove-print-product').forEach(button => button.addEventListener('click', () => removeProductFromPrintList(button.dataset.productId)));
            updateStepperStates();
        }

        btnGabungkan.addEventListener('click', () => {
            const selected = pairingSelectedIds();
            if (selected.length !== 2) return;
            const top = modalProducts.find(product => String(product.id) === String(selected[0]));
            const bottom = modalProducts.find(product => String(product.id) === String(selected[1]));
            if (!top || !bottom || !isVegetable(top) || !isVegetable(bottom) || top.id === bottom.id) { tampilkanErrorCetak('Pilih tepat dua produk Vegetable Tanggung yang belum berpasangan.'); return; }
            const pair = {top, bottom, topQty: Number(modalQty.get(top.id) || 1), bottomQty: Number(modalQty.get(bottom.id) || 1), qty: 1};
            vegetablePairs.push(pair);
            pairingSelectionOrder = [];
            renderPreview();
        });

        // Bangun isi modal setiap kali tombol Cetak ditekan.
        btnCetak.addEventListener('click', function () {
            const checkedProducts = [...getChecked()];
            if (checkedProducts.some(cb => !cb.dataset.size)) { tampilkanErrorCetak('Pastikan produk sudah memilih ukuran template.'); return; }
            modalProducts = checkedProducts.map(cb => ({id: cb.dataset.id, sku: cb.dataset.sku, name: cb.dataset.name, variant: cb.dataset.variant, size: cb.dataset.size, allocation: cb.dataset.allocation, discount: cb.dataset.discount, promo: cb.dataset.promo}));
            vegetablePairs = [];
            pairingSelectionOrder = [];
            modalQty = new Map(modalProducts.map(product => [product.id, 1]));
            modalHasVegetable = modalProducts.some(isVegetable);
            allvarPrintAlert.classList.toggle('d-none', !modalProducts.some(product => product.size === 'tgg' && Number(product.allocation || 0) === 0));
            document.getElementById('vegetablePreviewHeader').classList.toggle('d-none', modalProducts.length === 0);
            renderPreview();
            const sizes = modalProducts.map(product => product.size);
            const hasMpdf = sizes.some(size => ['mpdf', 'pricetag', 'a5', 'fresh', 'curah', 'segitiga', 'segitiga_kecil', 'vegetable_tgg'].includes(size));
            document.getElementById('formCetak').action = hasMpdf ? '<?= base_url('print-mpdf') ?>' : '<?= base_url('print-pdf') ?>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCetak')).show();
        });

        function tampilkanErrorCetak(message, detailed = false) {
            templateToast.className = 'alert alert-danger shadow-sm py-2 px-3 mb-0';
            templateToast.classList.toggle('print-discount-error', detailed);
            templateToast.textContent = message;
            templateToast.style.display = 'block';
            clearTimeout(toastTimer);
            if (!detailed) {
                toastTimer = setTimeout(() => {
                    templateToast.style.display = 'none';
                    templateToast.className = 'alert alert-success shadow-sm py-2 px-3 mb-0';
                }, 4000);
            }
        }

        function sembunyikanErrorDiskonCetak() {
            if (templateToast.classList.contains('print-discount-error')) {
                templateToast.style.display = 'none';
            }
        }
        document.getElementById('modalCetak').addEventListener('hidden.bs.modal', sembunyikanErrorDiskonCetak);

        // Validasi di halaman ini agar error tidak ikut terbuka di target _blank.
        // Target tersebut tetap dipakai untuk hasil PDF yang berhasil dibuat.
        document.getElementById('formCetak').addEventListener('submit', async function (event) {
            event.preventDefault();
            sembunyikanErrorDiskonCetak();
            const sizes = [...this.querySelectorAll('input[name^="size["]')].map(input => input.value);
            if (sizes.some(size => !size)) {
                tampilkanErrorCetak('Pastikan produk sudah memilih ukuran template.');
                return;
            }
            const qtyInputs = [...this.querySelectorAll('input[type="number"]')];
            if (qtyInputs.some(input => !/^\d+$/.test(input.value) || Number(input.value) < 1)) {
                tampilkanErrorCetak('Qty Cetak harus berupa bilangan bulat positif.');
                return;
            }
            const submittedIds = [...this.querySelectorAll('input[name="tag_ids[]"]')].map(input => input.value);
            if (new Set(submittedIds).size !== submittedIds.length) {
                tampilkanErrorCetak('Ada produk yang tercetak lebih dari satu kali.');
                return;
            }
            const pairTopIds = [...this.querySelectorAll('input[name^="vegetable_pairs["][name$="][top_id]"]')].map(input => input.value);
            const pairBottomIds = [...this.querySelectorAll('input[name^="vegetable_pairs["][name$="][bottom_id]"]')].map(input => input.value);
            const pairIds = [...pairTopIds, ...pairBottomIds];
            if (new Set(pairIds).size !== pairIds.length || pairTopIds.length !== pairBottomIds.length) {
                tampilkanErrorCetak('Data pasangan Vegetable Tanggung tidak lengkap atau berulang.');
                return;
            }
            const uniqueSizes = [...new Set(sizes.filter(Boolean))];
            if (uniqueSizes.length > 1) {
                tampilkanErrorCetak('Tidak dapat mencetak beberapa ukuran template sekaligus. Silakan pilih produk dengan ukuran template yang sama.');
                return;
            }

            const invalidSmallTriangle = modalProducts.filter(product => product.size === 'segitiga_kecil' && !isValidSmallTriangleProduct(product));
            if (invalidSmallTriangle.length > 0) {
                const products = invalidSmallTriangle.map(product => `PLU: ${product.sku}\nProduk: ${product.name}`);
                tampilkanErrorCetak(['Segitiga Kecil membutuhkan diskon bulat 1% sampai 99% atau nominal promo positif.', ...products, 'Lengkapi diskon/promo atau pilih template lain sebelum mencetak.'].join('\n\n'), true);
                return;
            }

            const hasMpdf = sizes.some(size => ['mpdf', 'pricetag', 'a5', 'fresh', 'curah', 'segitiga', 'segitiga_kecil', 'vegetable_tgg'].includes(size));
            this.action = hasMpdf ? '<?= base_url('print-mpdf') ?>' : '<?= base_url('print-pdf') ?>';

            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (response.status === 422 && response.headers.get('Content-Type')?.includes('application/json')) {
                    const error = await response.json().catch(() => null);
                    if (error?.code === 'invalid_print_discount' && Array.isArray(error.products)) {
                        const products = error.products.map(product =>
                            `ID: ${product.id} | PLU: ${product.plu}\nProduk: ${product.name}\nDiskon: ${product.discount_percent}%\n${product.reason}`
                        );
                        tampilkanErrorCetak([error.message, ...products, error.guidance].join('\n\n'), true);
                        return;
                    }
                }
                const isPdfResult = /\/print-(pdf|mpdf)\/[a-f0-9]{32}(?:$|\?)/i.test(response.url);
                if (response.ok && isPdfResult) {
                    window.open(response.url, '_blank');
                } else {
                    tampilkanErrorCetak('Gagal membuat PDF. Silakan periksa pilihan template dan coba lagi.');
                }
            } catch (error) {
                tampilkanErrorCetak('Gagal menghubungi server untuk mencetak PDF.');
            } finally {
                submitButton.disabled = false;
            }
        });
    </script>
    <?= view('partials/app_footer') ?>
</body>
</html>
