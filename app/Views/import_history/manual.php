<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-theme.css') ?>" rel="stylesheet">

    <!-- CSS tabel contenteditable (boleh dipindah ke admin-theme.css) -->
    <style>
        .manual-entry-table td { padding: 6px 8px; }
        .manual-entry-table { width: 100%; table-layout: fixed; }
        .manual-entry-table th:nth-child(1), .manual-entry-table td:nth-child(1) { width: 42px; }
        .manual-entry-table th:nth-child(2), .manual-entry-table td:nth-child(2),
        .manual-entry-table th:nth-child(3), .manual-entry-table td:nth-child(3) { width: 120px; }
        .manual-entry-table th:nth-child(4), .manual-entry-table td:nth-child(4) { width: 95px; }
        .manual-entry-table th:nth-child(5), .manual-entry-table td:nth-child(5) { width: auto; }
        .manual-entry-table th:nth-child(6), .manual-entry-table td:nth-child(6) { width: 130px; }
        .manual-entry-table th:nth-child(7), .manual-entry-table td:nth-child(7) { width: 120px; }
        .manual-entry-table td.row-number { width: 42px; text-align: center; }
        .manual-entry-table td.col-aksi, .manual-entry-table th.col-aksi { width: 70px; text-align: center; }
        .manual-entry-table .cell {
            min-height: 38px; padding: 8px 12px; background: #fff;
            border: 1px solid #ced4da; border-radius: .375rem;
            font-size: .9rem; outline: none; white-space: nowrap; overflow: hidden;
        }
        /* Nama produk/Brand tetap berada di kolom yang sama dan boleh wrap. */
        .manual-entry-table .cell[data-field="brand"] {
            min-width: 0; max-width: none; width: 100%; white-space: normal;
            overflow-y: auto; overflow-x: hidden; overflow-wrap: anywhere;
            height: 38px; max-height: 76px; line-height: 1.35;
        }
        .manual-entry-table { min-width: 0; }
        .manual-entry-table th.col-aksi, .manual-entry-table td.col-aksi { min-width: 64px; }
        .manual-entry-table .cell:empty::before { content: attr(data-ph); color: #a3a8b2; pointer-events: none; }
        .manual-entry-table .cell:focus { border-color: #f26b0f; box-shadow: 0 0 0 .2rem rgba(242,107,15,.18); }
        .manual-entry-table .cell.err { border-color: #dc3545; background: #fff5f5; }
        .manual-entry-table .cell.num { text-align: left; }
        .manual-entry-table thead th { white-space: nowrap; font-size: 14px; }
        .manual-entry-table thead th:nth-child(6) { min-width: 0; }
        .manual-entry-table th, .manual-entry-table td { overflow: hidden; }
        .manual-entry-table td:not(.row-number):not(.col-aksi) .cell { width: 100%; }
        .manual-entry-table { min-width: 1250px; }
    </style>
</head>
<body class="mk-body mk-manual-entry-page">

<nav class="navbar navbar-expand-lg mk-topbar manual-page-topbar">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url('dashboard') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" class="mk-logo-sm" alt="Manna Kampus">
        </a>
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

<main class="container py-4 manual-entry-container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item">
                <a href="<?= base_url('dashboard') ?>"><i class="bi bi-house-door text-warning"></i></a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= base_url('import-history?mode=manual') ?>">Data POP Price Tag</a>
            </li>
            <li class="breadcrumb-item active">Isi Data Manual</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mk-title mb-1">Isi Data Manual</h2>
            <p class="mk-subtitle mb-0">Tambahkan data produk secara manual untuk dicetak sebagai POP Price Tag.</p>
        </div>
        <a href="<?= base_url('import-history?mode=manual') ?>" class="btn mk-btn-primary">
            <i class="bi bi-arrow-left me-2"></i>History Input Data Manual
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <section class="mk-template-banner mb-3">
        <div class="mk-template-icon mk-manual-banner-icon">
            <span class="mk-tab-icon">
                <i class="bi bi-file-earmark-text"></i>
                <i class="bi bi-info-circle-fill mk-tab-plus"></i>
            </span>
        </div>
        <div class="mk-template-copy">
            <h5>Informasi Input</h5>
            <p class="mb-1">Outlet <strong class="ms-5">:</strong> <?= esc($history['outlet_code'].' ('.$history['outlet_name'].')') ?></p>
            <p class="mb-0">Tanggal Input <strong class="ms-2">:</strong> <?= esc((new DateTimeImmutable($history['imported_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d M Y H:i:s')) ?></p>
        </div>
        <a href="<?= base_url('pricetag?import=' . (int) $history['id']) ?>" class="btn btn-warning ms-auto">
            <i class="bi bi-eye me-2"></i>Lihat Detail Data
        </a>
    </section>

    <form method="post" id="manualForm" action="<?= base_url('import-history/manual/'.$history['id'].'/save') ?>">
        <?= csrf_field() ?>
        <section class="mk-card p-3 p-lg-4" id="manual-table">
            <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-box-seam fs-2 text-warning"></i>
                <div>
                    <h5 class="mk-history-title mb-1">Tambah Data Produk</h5>
                    <p class="text-primary mb-0">Klik sel untuk mengisi, atau copy dari Excel lalu paste (Ctrl+V) ke sel pertama. Tab/Enter/panah untuk pindah sel.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle manual-entry-table">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Awal Periode</th>
                            <th>Akhir Periode</th>
                            <th>SKU / PLU</th>
                            <th>Brand / Merk <span class="text-danger">*</span></th>
                            <th>Harga Normal</th>
                            <th>Program Promo</th>
                            <th class="col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="manualRows">
                        <?php
                        // Y-m-d (database) -> dd-Mmm-yy (tampilan)
                        $tgl = static function ($v) {
                            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $v);
                            return $date ? $date->format('d-M-y') : (string) $v;
                        };
                        $initialRows = max(50, count($rows)); for ($i = 0; $i < $initialRows; $i++):
                            $row   = $rows[$i] ?? [];
                            $harga = (float) ($row['normal_price'] ?? 0);
                        ?>
                        <tr>
                            <td class="row-number"><?= $i + 1 ?></td>
                            <td><div class="cell" contenteditable="true" data-field="start_period" data-tipe="tgl" data-ph="12-Jul-2026"><?= esc($tgl($row['start_period'] ?? '')) ?></div></td>
                            <td><div class="cell" contenteditable="true" data-field="end_period" data-tipe="tgl" data-ph="30-Jul-2026"><?= esc($tgl($row['end_period'] ?? '')) ?></div></td>
                            <td><div class="cell" contenteditable="true" data-field="sku_plu" data-tipe="teks" data-ph="023509"><?= esc($row['sku_plu'] ?? '') ?></div></td>
                            <td><div class="cell" contenteditable="true" data-field="brand" data-tipe="teks" data-ph="Masukkan brand / merk"><?= esc($row['brand'] ?? '') ?></div></td>
                            <td><div class="cell num" contenteditable="true" data-field="normal_price" data-tipe="rp" data-ph="23.500"><?= $harga > 0 ? number_format($harga, 0, ',', '.') : '' ?></div></td>
                            <td><div class="cell" contenteditable="true" data-field="promo" data-tipe="teks" data-ph="Set 10%"><?= esc($row['promo'] ?? '') ?></div></td>
                            <td class="col-aksi"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center gap-3 border-top mt-3 pt-3 manual-actions-floating">
                <button type="button" class="btn mk-btn-primary" id="addRow">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Baris
                </button>
                <span class="manual-row-total">Total Baris: <strong id="rowTotal">0</strong></span>
                <span class="manual-actions-spacer"></span>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" id="resetRows">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>Simpan Data
                    </button>
                </div>
            </div>
        </section>
    </form>
</main>

<script>
(function () {
    const tb   = document.getElementById('manualRows');
    const form = document.getElementById('manualForm');
    const awal = tb.innerHTML;                       // kondisi awal untuk tombol Reset
    const d2   = n => String(n).padStart(2, '0');

    const COLS = [
        {field:'start_period', tipe:'tgl',  ph:'12-Jul-2026',   req:false},
        {field:'end_period',   tipe:'tgl',  ph:'30-Jul-2026',   req:false},
        {field:'sku_plu',      tipe:'teks', ph:'023509',        req:false},
        {field:'brand',        tipe:'teks', ph:'Masukkan brand / merk', req:true},
        {field:'normal_price', tipe:'rp',   ph:'23.500',        req:false},
        {field:'promo',        tipe:'teks', ph:'Set 10%',       req:false}
    ];

    const cells = tr => [...tr.querySelectorAll('.cell')];
    const nomor = () => { [...tb.rows].forEach((r, i) => r.querySelector('.row-number').textContent = i + 1); document.getElementById('rowTotal').textContent = tb.rows.length; };

    function buatBaris() {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td class="row-number"></td>' +
            COLS.map(c => `<td><div class="cell ${c.tipe === 'rp' ? 'num' : ''}" contenteditable="true" data-field="${c.field}" data-tipe="${c.tipe}" data-ph="${c.ph}"></div></td>`).join('') +
            '<td class="col-aksi"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>';
        return tr;
    }

    // ---------- format & validasi ----------
    function fmt(el) {
        const t = el.dataset.tipe;
        let v = el.textContent.trim();
        el.classList.remove('err');
        if (!v) return;
        if (t === 'tgl') {
            const m = v.match(/^(\d{1,2})[-\/.]([A-Za-z]{3}|\d{1,2})[-\/.](\d{2}|\d{4})$/);
            let ok = false;
            if (m) {
                const months = {Jan:1,Feb:2,Mar:3,Apr:4,May:5,Jun:6,Jul:7,Aug:8,Sep:9,Oct:10,Nov:11,Dec:12};
                const d = +m[1], key = m[2].substring(0,1).toUpperCase() + m[2].substring(1).toLowerCase(), mo = isNaN(+m[2]) ? months[key] : +m[2], y = m[3].length === 2 ? 2000 + (+m[3]) : +m[3], x = new Date(y, mo - 1, d);
                ok = x.getFullYear() === y && x.getMonth() === mo - 1 && x.getDate() === d;
                if (ok) v = d2(d) + '-' + Object.keys(months).find(k => months[k] === mo) + '-' + String(y).slice(-2);
            }
            el.textContent = v;
            if (!ok) el.classList.add('err');
        } else if (t === 'rp') {
            const a = v.replace(/\D/g, '');
            if (!a) { el.classList.add('err'); return; }
            el.textContent = Number(a).toLocaleString('id-ID');
        }
    }
    tb.addEventListener('focusout', e => { if (e.target.classList.contains('cell')) fmt(e.target); });
    tb.addEventListener('input', e => {
        const c = e.target;
        if (!c.classList.contains('cell')) return;
        c.classList.remove('err');
        if (!c.textContent) c.innerHTML = '';        // supaya placeholder muncul lagi
        if (c.dataset.field === 'brand') {
            c.style.height = '38px';
            c.style.height = Math.min(c.scrollHeight, 76) + 'px';
        }
    });

    // ---------- navigasi keyboard ----------
    function pindah(el, dr) {
        const td = el.closest('td'), tr = td.parentElement, ci = [...tr.children].indexOf(td);
        const t = tb.rows[tr.rowIndex - 1 + dr]?.cells[ci]?.querySelector('.cell');
        if (t) {
            t.focus();
            const r = document.createRange(); r.selectNodeContents(t); r.collapse(false);
            const s = getSelection(); s.removeAllRanges(); s.addRange(r);
        }
        return !!t;
    }
    tb.addEventListener('keydown', e => {
        if (!e.target.classList.contains('cell')) return;
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!pindah(e.target, 1)) { tambah(); }
        } else if (e.key === 'ArrowDown') { e.preventDefault(); pindah(e.target, 1); }
        else if (e.key === 'ArrowUp')    { e.preventDefault(); pindah(e.target, -1); }
    });

    // ---------- paste dari Excel (multi baris & kolom) ----------
    tb.addEventListener('paste', e => {
        const el = e.target;
        if (!el.classList.contains('cell')) return;
        e.preventDefault();
        const teks = e.clipboardData.getData('text/plain').replace(/\r/g, '').replace(/\n$/, '');
        const rows = teks.split('\n').map(r => r.split('\t'));
        if (rows.length === 1 && rows[0].length === 1) {
            document.execCommand('insertText', false, rows[0][0]);
            return;
        }
        const td = el.closest('td'), r0 = td.parentElement.rowIndex - 1, c0 = [...td.parentElement.children].indexOf(td) - 1;
        rows.forEach((kol, i) => {
            while (!tb.rows[r0 + i]) tb.appendChild(buatBaris());
            const cs = cells(tb.rows[r0 + i]);
            kol.forEach((v, j) => { const c = cs[c0 + j]; if (c) { c.textContent = v.trim(); fmt(c); } });
        });
        nomor();
    });

    // ---------- tambah / hapus / reset ----------
    function tambah() {
        const tr = buatBaris(); tb.appendChild(tr); nomor(); cells(tr)[0].focus();
    }
    nomor();
    document.getElementById('addRow').onclick = tambah;

    tb.addEventListener('click', e => {
        const b = e.target.closest('.remove-row');
        if (!b) return;
        const tr = b.closest('tr');
        if (tb.rows.length > 1) tr.remove();
        else cells(tr).forEach(c => { c.innerHTML = ''; c.classList.remove('err'); });
        nomor();
    });

    document.getElementById('resetRows').onclick = () => {
        if (confirm('Kembalikan tabel ke kondisi awal?')) { tb.innerHTML = awal; nomor(); }
    };

    // ---------- submit: ubah isi sel jadi input hidden rows[i][field] ----------
    form.addEventListener('submit', e => {
        form.querySelectorAll('input.gen').forEach(x => x.remove());
        const data = [];
        let salah = 0;

        [...tb.rows].forEach(tr => {
            const cs = cells(tr), v = cs.map(c => c.textContent.trim());
            if (v.every(x => !x)) return;                        // lewati baris kosong
            cs.forEach((c, i) => {
                fmt(c);
                if (COLS[i].req && !v[i]) c.classList.add('err');
                if (c.classList.contains('err')) salah++;
            });
            data.push(v);
        });

        if (!data.length) { e.preventDefault(); alert('Belum ada data yang diisi.'); return; }
        if (salah)        { e.preventDefault(); alert('Ada ' + salah + ' kolom yang belum diisi atau formatnya salah (ditandai merah).'); return; }

        data.forEach((v, i) => {
            COLS.forEach((c, j) => {
                let val = v[j];
                if (c.tipe === 'tgl' && val) { const p = val.split('-'), months = {Jan:1,Feb:2,Mar:3,Apr:4,May:5,Jun:6,Jul:7,Aug:8,Sep:9,Oct:10,Nov:11,Dec:12}; val = '20' + p[2] + '-' + String(months[p[1]]).padStart(2,'0') + '-' + p[0]; }  // -> Y-m-d
                if (c.tipe === 'rp')         val = val.replace(/\D/g, '');                                       // -> angka murni
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.className = 'gen';
                inp.name = 'rows[' + i + '][' + c.field + ']'; inp.value = val;
                form.appendChild(inp);
            });
        });
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= view('partials/app_footer') ?>

</body>
</html>
