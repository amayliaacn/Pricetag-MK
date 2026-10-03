<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-theme.css') ?>" rel="stylesheet">
</head>
<body class="mk-body">

<nav class="navbar navbar-expand-lg mk-topbar">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url('dashboard') ?>">
            <img src="<?= base_url('assets/img/logo.png') ?>" class="mk-logo-sm" alt="Manna Kampus">
        </a>
        <span class="mk-user-pill">Halo, <strong><?= esc(session()->get('username')) ?></strong></span>
    </div>
</nav>

<main class="container py-4">
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
        <a href="<?= base_url('import-history?mode=manual') ?>" class="btn btn-light border">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Data Manual
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
        <a href="#manual-table" class="btn btn-outline-warning ms-auto">
            <i class="bi bi-eye me-2"></i>Lihat Data Tersimpan
        </a>
    </section>

    <form method="post" action="<?= base_url('import-history/manual/'.$history['id'].'/save') ?>">
        <section class="mk-card p-3 p-lg-4" id="manual-table">
            <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-box-seam fs-2 text-warning"></i>
                <div>
                    <h5 class="mk-history-title mb-1">Tambah Data Produk</h5>
                    <p class="text-primary mb-0">Klik pada tabel di bawah untuk mengisi data. Setiap kolom dapat diedit langsung.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle manual-entry-table">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Awal Periode *</th>
                            <th>Akhir Periode *</th>
                            <th>SKU / PLU *</th>
                            <th>Brand / Merk *</th>
                            <th>Harga Normal (Rp) *</th>
                            <th>Program Promo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="manualRows">
                        <?php for($i=0;$i<10;$i++):$row=$rows[$i]??[]; ?>
                        <tr>
                            <td class="row-number"><?= $i+1 ?></td>
                            <td><input type="date" name="rows[<?= $i ?>][start_period]" value="<?= esc($row['start_period']??'') ?>" class="form-control"></td>
                            <td><input type="date" name="rows[<?= $i ?>][end_period]" value="<?= esc($row['end_period']??'') ?>" class="form-control"></td>
                            <td><input name="rows[<?= $i ?>][sku_plu]" value="<?= esc($row['sku_plu']??'') ?>" class="form-control" placeholder="Masukkan SKU / PLU"></td>
                            <td><input name="rows[<?= $i ?>][brand]" value="<?= esc($row['brand']??'') ?>" class="form-control" placeholder="Masukkan merk"></td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" name="rows[<?= $i ?>][normal_price]" value="<?= esc($row['normal_price']??0) ?>" class="form-control">
                                </div>
                            </td>
                            <td><input name="rows[<?= $i ?>][promo]" value="<?= esc($row['promo']??'') ?>" class="form-control" placeholder="Masukkan program promo"></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between border-top mt-3 pt-3">
                <button type="button" class="btn mk-btn-primary" id="addRow">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Baris
                </button>
                <div>
                    <button type="reset" class="btn btn-outline-secondary me-2">
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
const t=document.getElementById('manualRows');
function n(){
    [...t.rows].forEach((r,i)=>{
        r.querySelector('.row-number').textContent=i+1;
        r.querySelectorAll('input').forEach(x=>x.name=x.name.replace(/row&#x73;**\\[**\d+**\\]**/,'rows['+i+']'))
    })
}
document.getElementById('addRow').onclick=()=>{
    let i=t.rows.length,r=t.rows[0].cloneNode(true);
    r.querySelectorAll('input').forEach(x=>{
        x.value='';
        x.name=x.name.replace(/row&#x73;**\\[**\d+**\\]**/,'rows['+i+']')
    });
    t.append(r);
    n()
};
t.onclick=e=>{
    let b=e.target.closest('.remove-row');
    if(!b)return;
    if(t.rows.length>1)b.closest('tr').remove();
    else b.closest('tr').querySelectorAll('input').forEach(x=>x.value='');
    n()
};
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= view('partials/app_footer') ?>

</body>
</html>