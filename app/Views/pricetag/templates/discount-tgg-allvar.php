<?php
/**
 * Template: Diskon All Variant (POP Price Tag)
 * Sumber desain: BARU_POP_DISC_THG_TGG_ALL.docx
 *
 * Data yang dibutuhkan per tag (dari $tags[$i]):
 *   nama_barang   => Nama barang/produk
 *   program       => Angka persen diskon (mis. 20)
 *   periode       => Tanggal akhir periode promo
 *   harga_normal  => Harga sebelum diskon
 *   harga_promo   => Harga setelah diskon
 *
 * Catatan: template ini TIDAK menampilkan PLU / Variant spesifik,
 * karena berlaku untuk semua varian barang tersebut ("All Variant"
 * ditulis statis, bukan diambil dari data).
 *
 * $tags dan $size dikirim dari PrintPdf::index()
 * Sesuaikan nama key array di bawah jika nama kolom di DB kamu berbeda.
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    body {
        margin: 0;
        padding: 0;
        font-family: sans-serif;
    }
    .tag {
        width: 100%;
        box-sizing: border-box;
        padding: 4mm 5mm;
        page-break-after: always;
    }
    .tag:last-child { page-break-after: auto; }

    .header-table { width: 100%; margin-bottom: 2mm; }
    .nama-barang {
        color: #1E7FC2;
        font-weight: bold;
        font-size: 20pt;
        letter-spacing: 1px;
    }
    .all-variant {
        text-align: right;
        color: #000000;
        font-weight: bold;
        font-size: 12pt;
    }

    .body-table { width: 100%; }
    .diskon-label {
        color: #E30613;
        font-weight: bold;
        font-size: 13pt;
    }
    .program {
        color: #E30613;
        font-weight: bold;
        font-style: italic;
        font-size: 34pt;
        line-height: 1;
    }
    .program .persen {
        font-size: 18pt;
        vertical-align: top;
    }

    .price-block { text-align: left; }
    .rp-symbol {
        font-size: 8pt;
        color: #E30613;
        font-weight: bold;
    }
    .harga-normal {
        color: #E30613;
        font-size: 11pt;
        text-decoration: line-through;
    }
    .harga-promo {
        color: #E30613;
        font-size: 13pt;
        font-weight: bold;
    }
    .price-caption {
        font-size: 7pt;
        color: #000;
        margin: 0 0 1.5mm 0;
    }

    .meta {
        font-size: 8pt;
        color: #000;
        margin-top: 2mm;
    }
</style>
</head>
<body>

<?php foreach ($tags as $tag): ?>
    <div class="tag">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="nama-barang" width="65%"><?= esc($tag['nama_barang'] ?? '') ?></td>
                <td class="all-variant" width="35%">All Variant</td>
            </tr>
        </table>

        <table class="body-table" cellpadding="0" cellspacing="0">
            <tr>
                <td width="55%">
                    <div class="diskon-label">Diskon :</div>
                    <div class="program">
                        <?= esc($tag['program'] ?? '') ?><span class="persen">%</span>
                    </div>
                </td>
                <td width="45%" class="price-block">
                    <p class="price-caption">
                        <span class="rp-symbol">Rp</span>
                        <span class="harga-normal"><?= number_format((float)($tag['harga_normal'] ?? 0), 0, ',', '.') ?></span><br>
                        ( Harga Normal )
                    </p>
                    <p class="price-caption">
                        <span class="rp-symbol">Rp</span>
                        <span class="harga-promo"><?= number_format((float)($tag['harga_promo'] ?? 0), 0, ',', '.') ?></span><br>
                        ( Harga Sesudah di Diskon )
                    </p>
                </td>
            </tr>
        </table>

        <p class="meta">Akhir Periode : <?= esc($tag['periode'] ?? '') ?></p>
    </div>
<?php endforeach; ?>

</body>
</html>