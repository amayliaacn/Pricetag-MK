<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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
        @media (max-width: 767.98px) {
            .detail-table-card .px-3.pt-3 { flex-wrap: wrap; }
            .detail-table-card .px-3.pt-3 > div { min-width: 0; }
        }
        @media (min-width: 1400px) {
            .detail-table-card { width: calc(100vw - 66px); margin-left: 0; position: relative; left: 50%; transform: translateX(-50%); }
        }
    </style>
</head>
<?php $isManualDetail = ! empty($history) && (int) ($history['source_type'] ?? 0) === 1; $detailLabel = $isManualDetail ? 'Detail Input' : 'Detail Import'; $infoLabel = $isManualDetail ? 'Informasi Input' : 'Informasi Import'; ?>
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
        <?php if (! empty($history)): ?><li class="breadcrumb-item"><a href="<?= base_url('import-history') ?>" class="text-decoration-none text-warning fw-semibold">History Import</a></li><?php endif; ?>
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
            <div class="mk-template-copy"><h5><?= $infoLabel ?></h5><p class="mb-0"><i class="bi bi-file-earmark me-2"></i><?= esc($history['file_name']) ?> <span class="mx-2">|</span> <i class="bi bi-shop me-1"></i><?= esc($history['outlet_code'] . ' - ' . $history['outlet_name']) ?> <span class="mx-2">|</span> <i class="bi bi-calendar3 me-1"></i><?= esc(date('d M Y', strtotime($history['imported_at']))) ?> <span class="mx-2">|</span> <i class="bi bi-clock me-1"></i><?= esc(date('H:i:s', strtotime($history['imported_at']))) ?> <span class="mx-2">|</span> <i class="bi bi-person me-1"></i><?= esc($history['username'] ?? '-') ?></p></div>
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
            <select id="filterUkuran" class="form-select"><option value="all">Semua Ukuran</option><option value="kcl">Kecil</option><option value="tgg">Tanggung</option><option value="mpdf">A4</option><option value="a5">A5</option><option value="segitiga">Segitiga</option><option value="fresh">Butcher</option><option value="curah">Vegetable</option></select>
        </div>

        <button type="button" id="btnCetak" class="btn btn-danger" disabled data-bs-toggle="modal" data-bs-target="#modalCetak">
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
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="product-table-header" class="card shadow-sm detail-table-card">
            <div class="card-body p-0">
                <div class="px-3 pt-3 d-flex justify-content-between align-items-start gap-3"><div><h4 class="mb-1"><i class="bi bi-database text-warning me-2"></i>Data Produk</h4><p class="text-muted mb-3"><?= $isManualDetail ? 'Daftar produk yang diinput manual dari file ' . esc($history['file_name']) . '.' : 'Daftar produk yang diimport dari file Excel.' ?></p></div><?php if ($isManualDetail): ?><a class="btn btn-primary btn-sm text-nowrap" href="<?= base_url('import-history/manual/' . (int) $history['id']) ?>"><i class="bi bi-pencil me-1"></i>Edit Data Input</a><?php else: ?><button type="button" class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</button><?php endif; ?></div>
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
                        <?php if (! $isManualDetail): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tags)): ?>
                            <tr id="emptySourceState"><td colspan="<?= $isManualDetail ? 12 : 13 ?>" class="text-center py-3">Belum ada data produk</td></tr>
                        <?php else: ?>
                            <?php foreach ($tags as $tagIndex => $tag): ?>
                                <?php
                                    $tagId = (int) ($tag['id'] ?? 0);
                                    $isPrinted = (int) ($tag['is_printed'] ?? 0) === 1;
                $templateSize = in_array(($tag['template_size'] ?? ''), ['tgg', 'kcl', 'mpdf', 'a5', 'fresh', 'curah', 'segitiga'], true) ? $tag['template_size'] : '';
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
                                        >
                                    </td>
                                    <td><?= (($page - 1) * $perPage) + $tagIndex + 1 ?></td>
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
                                    <td><form method="post" class="template-size-form" action="<?= base_url('pricetag/template/' . $tagId) ?><?= !empty($history['id']) ? '?import_id=' . (int) $history['id'] : '' ?>"><div class="template-size-wrapper <?= $templateSize === '' ? 'is-required' : '' ?>"><i class="bi bi-exclamation-triangle-fill template-size-warning <?= $templateSize === '' ? '' : 'd-none' ?>" aria-hidden="true"></i><select name="template_size" class="form-select form-select-sm template-size" <?= $tagId === 0 ? 'disabled' : '' ?>><option value="" <?= $templateSize === '' ? 'selected' : '' ?>>Pilih ukuran</option><option value="kcl" <?= $templateSize === 'kcl' ? 'selected' : '' ?>>Kecil</option><option value="tgg" <?= $templateSize === 'tgg' ? 'selected' : '' ?>>Tanggung</option><option value="mpdf" <?= $templateSize === 'mpdf' ? 'selected' : '' ?>>A4</option><option value="a5" <?= $templateSize === 'a5' ? 'selected' : '' ?>>A5</option><option value="segitiga" <?= $templateSize === 'segitiga' ? 'selected' : '' ?>>Segitiga</option><option value="fresh" <?= $templateSize === 'fresh' ? 'selected' : '' ?>>Butcher</option><option value="curah" <?= $templateSize === 'curah' ? 'selected' : '' ?>>Vegetable</option></select></div></form></td>
                                    <td>
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
                                            data-end="<?= esc($tag['end_period'] ?? '') ?>" <?= $tagId === 0 ? 'disabled title="Data snapshot tidak dapat diedit"' : '' ?> title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="emptyFilterState" style="display:none"><td colspan="<?= $isManualDetail ? 12 : 13 ?>" class="text-center py-3">Produk tidak ditemukan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <?= view('partials/pagination', ['total' => $totalTags, 'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages, 'baseUrl' => 'pricetag', 'params' => ['import' => (int) $history['id']], 'anchor' => 'product-table-header']) ?>
            </div>
        </div>
    </div>
    </main>
    <div id="templateToast" class="alert alert-success shadow-sm py-2 px-3 mb-0">Ukuran template produk berhasil disimpan.</div>

    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="post" id="formEdit">
                <div class="modal-header"><h5 class="modal-title">Edit Produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">PLU</label><input name="sku_plu" id="editSku" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Nama Produk</label><input name="name" id="editName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Varian</label><input name="variant" id="editVariant" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Harga Normal</label><input type="number" name="normal_price" id="editPrice" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Diskon (%)</label><input type="number" step="0.01" min="0" name="discount_percent" id="editDiscount" class="form-control"></div>
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
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">SKU/PLU <small class="text-muted">(opsional untuk Discount A5)</small></label><input name="sku_plu" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Nama Produk</label><input name="name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Varian</label><input name="variant" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Harga Normal</label><input type="number" name="normal_price" class="form-control" min="0"></div>
                    <div class="col-md-4"><label class="form-label">Diskon (%)</label><input type="number" step="0.01" name="discount_percent" class="form-control" min="0"></div>
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
                <form action="<?= base_url('print-pdf') ?>" method="post" target="_blank" id="formCetak">
                    <?php if (!empty($history['id'])): ?>
                        <input type="hidden" name="import_id" value="<?= (int) $history['id'] ?>">
                    <?php endif; ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Cetak POP Price Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">Produk Tanggung dapat dipilih menggunakan template All Varian secara terpisah.</div>

                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th>Template</th>
                                    <th style="width:100px;">Qty Cetak</th>
                                </tr>
                            </thead>
                            <tbody id="tabelProdukTerpilih">
                                <!-- Diisi otomatis via JS -->
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Cetak POP Price Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const checkAll       = document.getElementById('checkAll');
        const productChecks  = document.querySelectorAll('.product-check');
        const btnCetak       = document.getElementById('btnCetak');
        const jumlahTerpilih = document.getElementById('jumlahTerpilih');
        const tabelBody      = document.getElementById('tabelProdukTerpilih');
        const searchProduk   = document.getElementById('searchProduk');
        const filterUkuran   = document.getElementById('filterUkuran');
        const productRows    = document.querySelectorAll('#tabelProdukDetail tbody tr');
        const editModal      = new bootstrap.Modal(document.getElementById('modalEdit'));
        const formEdit       = document.getElementById('formEdit');

        // Diskon dan Harga Promo adalah dua cara alternatif untuk menentukan harga akhir.
        // Saat salah satunya diisi, kosongkan yang lain agar data tidak bertentangan.
        document.querySelectorAll('#formEdit, #modalTambah form').forEach(form => {
            const discount = form.querySelector('[name="discount_percent"]');
            const promo = form.querySelector('[name="promo_price"]');
            if (!discount || !promo) return;
            discount.addEventListener('input', () => {
                if (discount.value !== '') promo.value = '';
            });
            promo.addEventListener('input', () => {
                if (promo.value !== '') discount.value = '';
            });
        });

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

        function updateTombolCetak() {
            const jumlah = getChecked().length;
            jumlahTerpilih.textContent = jumlah;
            btnCetak.disabled = jumlah === 0;

            // Sinkronisasi status checkAll
            if (productChecks.length > 0) {
                checkAll.checked = (jumlah === productChecks.length);
            }
        }

        checkAll.addEventListener('change', function () {
            productChecks.forEach(cb => cb.checked = checkAll.checked);
            updateTombolCetak();
        });

        productChecks.forEach(cb => {
            cb.addEventListener('change', updateTombolCetak);
        });

        document.querySelectorAll('.btn-edit').forEach(button => button.addEventListener('click', function () {
            const d = this.dataset;
            formEdit.action = `<?= base_url('pricetag/update') ?>/${d.id}`;
            document.getElementById('editSku').value = d.sku;
            document.getElementById('editName').value = d.name;
            document.getElementById('editVariant').value = d.variant;
            document.getElementById('editPrice').value = d.price;
            document.getElementById('editDiscount').value = d.discount;
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
        filterUkuran.addEventListener('change', filterRows);
        filterRows();

        // Bangun isi modal setiap kali tombol Cetak ditekan
        btnCetak.addEventListener('click', function () {
            tabelBody.innerHTML = '';

            getChecked().forEach(cb => {
                const id      = cb.dataset.id;
                const sku     = cb.dataset.sku;
                const name    = cb.dataset.name;
                const variant = cb.dataset.variant;
                const size = cb.dataset.size;
                const allocation = Number(cb.dataset.allocation || 0);
                const discount = Number(cb.dataset.discount || 0);
                const canAllvar = size === 'tgg' && allocation === 0;
                const isA4 = size === 'mpdf';
                const hasDiscountTemplate = ['kcl', 'tgg', 'a5', 'segitiga'].includes(size);
                const showDiscountInfo = discount > 0 && discount < 10;
                const canChooseDiscount = showDiscountInfo && hasDiscountTemplate && !isA4;
                const showA4DiscountInfo = showDiscountInfo && isA4;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        ${sku}
                        <input type="hidden" name="tag_ids[]" value="${id}">
                    </td>
                    <td>${name}${variant ? ' - ' + variant : ''}</td>
                    <td>
                        <input type="hidden" name="size[${id}]" value="${size}">
                        ${size === 'tgg' ? `<label class="form-check mb-0"><input type="checkbox" name="allvar[${id}]" value="1" class="form-check-input allvar-product" ${canAllvar ? '' : 'disabled'}><span class="form-check-label">All Varian</span></label>` : ((size === 'mpdf' || size === 'a5' || size === 'fresh' || size === 'curah' || size === 'segitiga') ? `${size === 'curah' ? 'Vegetable' : (size === 'fresh' ? 'Butcher' : (size === 'a5' ? 'A5' : (size === 'segitiga' ? 'Segitiga' : 'A4')))}` : '<span class="text-muted">Kecil</span>')}
                        ${canChooseDiscount ? `<div class="mt-2 small"><div class="text-muted mb-1">Diskon &lt; 10%</div><label class="d-block"><input type="radio" name="discount_mode[${id}]" value="auto" checked> Turun harga</label><label class="d-block"><input type="radio" name="discount_mode[${id}]" value="show"> Tampil diskon</label></div>` : (showA4DiscountInfo ? `<input type="hidden" name="discount_mode[${id}]" value="auto"><div class="mt-2 small"><div class="text-muted mb-1">Diskon &lt; 10%</div><label class="d-block"><input type="radio" checked disabled> Turun harga</label><label class="d-block text-muted" title="Pilihan ini tidak tersedia untuk template A4"><input type="radio" disabled> Tampil diskon <span class="fst-italic">(tidak tersedia untuk A4)</span></label></div>` : (showDiscountInfo ? `<input type="hidden" name="discount_mode[${id}]" value="auto"><div class="mt-2 small"><div class="text-muted mb-1">Diskon &lt; 10%</div><label class="d-block"><input type="radio" checked disabled> Turun harga</label><label class="d-block text-muted"><input type="radio" disabled> Tampil diskon <span class="fst-italic">(tidak tersedia untuk ukuran ini)</span></label></div>` : `<input type="hidden" name="discount_mode[${id}]" value="auto">`))}
                    </td>
                    <td>
                        <input type="number" name="qty[${id}]" value="1" min="1" class="form-control form-control-sm" required>
                    </td>
                `;
                tabelBody.appendChild(row);
            });
            const sizes = getChecked().map(cb => cb.dataset.size);
            const hasMpdf = sizes.some(size => ['mpdf', 'a5', 'fresh', 'curah', 'segitiga'].includes(size));
            const hasLibre = sizes.some(size => ['kcl', 'tgg'].includes(size));
            document.getElementById('formCetak').action = hasMpdf && hasLibre
                ? '<?= base_url('print-mixed') ?>'
                : (hasMpdf ? '<?= base_url('print-mpdf') ?>' : '<?= base_url('print-pdf') ?>');
        });

        // Pastikan pilihan A4 mPDF tetap memakai controller baru saat form submit.
        document.getElementById('formCetak').addEventListener('submit', function () {
            const sizes = [...this.querySelectorAll('input[name^="size["]')].map(input => input.value);
            const hasMpdf = sizes.some(size => ['mpdf', 'a5', 'fresh', 'curah', 'segitiga'].includes(size));
            const hasLibre = sizes.some(size => ['kcl', 'tgg'].includes(size));
            this.action = hasMpdf && hasLibre
                ? '<?= base_url('print-mixed') ?>'
                : (hasMpdf ? '<?= base_url('print-mpdf') ?>' : '<?= base_url('print-pdf') ?>');
        });
    </script>
    <?= view('partials/app_footer') ?>
</body>
</html>
