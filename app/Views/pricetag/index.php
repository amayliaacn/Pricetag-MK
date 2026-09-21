<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
    <div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <form action="<?= base_url('pricetag/import') ?>" method="post" enctype="multipart/form-data" class="d-flex align-items-center m-0">
            <input type="file" name="file_excel" class="form-control me-2" accept=".xlsx, .xls" required>
            <button type="submit" class="btn btn-success">Import Excel</button>
        </form>

        <button type="button" id="btnCetak" class="btn btn-danger" disabled data-bs-toggle="modal" data-bs-target="#modalCetak">
            Cetak Produk Terpilih (<span id="jumlahTerpilih">0</span>)
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Daftar Data Price Tag</h3>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
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

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-striped table-hover m-0">
                    <thead class="table-dark">
                        <tr>
                            <!-- Ditambahkan label All dan sedikit perapihan style -->
                            <th style="width:70px;">
                                <div class="d-flex align-items-center gap-1">
                                    <input type="checkbox" id="checkAll" class="form-check-input mt-0">
                                    <label for="checkAll" class="mb-0 text-white user-select-none" style="cursor:pointer;">All</label>
                                </div>
                            </th>
                            <th>Awal Periode</th>
                            <th>Akhir Periode</th>
                            <th>SKU/PLU</th>
                            <th>Merk</th>
                            <th>Nama Produk</th>
                            <th>Varian</th>
                            <th>Harga Normal</th>
                            <th>Diskon (%)</th>
                            <th>Harga Promo</th>
                            <th>Alokasi (Pcs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tags)): ?>
                            <tr><td colspan="11" class="text-center py-3">Belum ada data. Silakan import file Excel.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tags as $tag): ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-check-input product-check"
                                            data-sku="<?= esc($tag['sku_plu']) ?>"
                                            data-name="<?= esc($tag['name']) ?>"
                                            data-variant="<?= esc($tag['variant'] ?? '') ?>"
                                        >
                                    </td>
                                    <td><?= esc($formatPeriod($tag['start_period'] ?? null)) ?></td>
                                    <td><?= esc($formatPeriod($tag['end_period'] ?? null)) ?></td>
                                    <td><?= esc($tag['sku_plu']) ?></td>
                                    <td><?= esc($tag['brand'] ?? '') ?: '-' ?></td>
                                    <td><?= esc($tag['name']) ?></td>
                                    <td><?= esc($tag['variant']) ?: '-' ?></td>
                                    <td>Rp <?= number_format($tag['normal_price'], 0, ',', '.') ?></td>
                                    <td>
                                        <?= !empty($tag['discount_percent']) ? number_format($tag['discount_percent'], 0, ',', '.') . '%' : '-' ?>
                                    </td>
                                    <td>
                                        <?= !empty($tag['promo_price']) ? 'Rp ' . number_format($tag['promo_price'], 0, ',', '.') : '-' ?>
                                    </td>
                                    <td>
                                        <?= !empty($tag['allocation_pcs']) ? number_format($tag['allocation_pcs'], 0, ',', '.') : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal Cetak -->
    <div class="modal fade" id="modalCetak" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= base_url('print-pdf') ?>" method="post" target="_blank" id="formCetak">
                    <div class="modal-header">
                        <h5 class="modal-title">Cetak POP Price Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Template Label</label>
                            <select name="template" class="form-select" required>
                                <?php foreach ($templates as $key => $tpl): ?>
                                    <option value="<?= esc($key) ?>">
                                        <?= esc($tpl['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Produk</th>
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

        // Bangun isi modal setiap kali tombol Cetak ditekan
        btnCetak.addEventListener('click', function () {
            tabelBody.innerHTML = '';

            getChecked().forEach(cb => {
                const sku     = cb.dataset.sku;
                const name    = cb.dataset.name;
                const variant = cb.dataset.variant;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        ${sku}
                        <input type="hidden" name="skus[]" value="${sku}">
                    </td>
                    <td>${name}${variant ? ' - ' + variant : ''}</td>
                    <td>
                        <input type="number" name="qty[${sku}]" value="1" min="1" class="form-control form-control-sm" required>
                    </td>
                `;
                tabelBody.appendChild(row);
            });
        });
    </script>
</body>
</html>
