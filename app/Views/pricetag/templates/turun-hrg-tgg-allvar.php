<?php
/**
 * Template: Harga Langsung All Variant (POP Price Tag)
 * Sumber desain: BARU_POP_THG_TGG_ORI_ALL.docx
 *
 * Data yang dibutuhkan per tag (dari $tags[$i]):
 *   nama_barang    => Nama barang/produk
 *   periode        => Tanggal akhir periode promo
 *   harga_normal   => Harga sebelum promo (dicoret)
 *   harga_ribuan   => Bagian ribuan dari harga promo (mis. "12" untuk Rp 12.900)
 *   harga_ratusan  => Bagian ratusan/ribuan-kecil dari harga promo (mis. "900")
 *
 * Catatan: template ini TIDAK menampilkan PLU, karena berlaku untuk semua
 * varian barang tersebut ("All Variant" ditulis statis, bukan diambil dari data).
 *
 * $tags dan $size dikirim dari PrintPdf::index()
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
        font-size: 18pt;
        letter-spacing: 1px;
    }
    .all-variant {
        text-align: right;
        color: #000000;
        font-weight: bold;
        font-size: 12pt;
    }

    .top-row { width: 100%; }
    .rp-symbol-sm {
        font-size: 9pt;
        color: #E30613;
        font-weight: bold;
    }
    .harga-normal {
        color: #E30613;
        font-size: 12pt;
        text-decoration: line-through;
    }

    .promo-table { width: 100%; margin-top: 2mm; }
    .promo-price { margin: 2mm 0 0 0; }
    .rp-symbol {
        font-size: 11pt;
        color: #E30613;
        font-weight: bold;
        vertical-align: bottom;
    }
    .ribuan {
        color: #E30613;
        font-weight: bold;
        font-style: italic;
        font-size: 32pt;
        line-height: 1;
    }
    .ratusan {
        color: #E30613;
        font-weight: bold;
        font-style: italic;
        font-size: 16pt;
        vertical-align: top;
    }

    .meta { font-size: 8pt; color: #000; margin: 1mm 0; }
    .note {
        font-size: 8pt;
        color: #E30613;
        font-style: italic;
        margin-top: 1mm;
    }
</style>
</head>
<body>

<?php foreach ($tags as $tag): ?>
    <div class="tag">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="nama-barang" width="60%"><?= esc($tag['nama_barang'] ?? '') ?></td>
                <td class="all-variant" width="40%">All Variant</td>
            </tr>
        </table>

        <span class="rp-symbol-sm">Rp</span>
        <span class="harga-normal"><?= number_format((float)($tag['harga_normal'] ?? 0), 0, ',', '.') ?></span>

        <p class="meta">Akhir Periode : <?= esc($tag['periode'] ?? '') ?></p>

        <p class="promo-price">
            <span class="rp-symbol">Rp</span>
            <span class="ribuan"><?= esc($tag['harga_ribuan'] ?? '') ?></span><span class="ratusan">,<?= esc($tag['harga_ratusan'] ?? '') ?></span>
        </p>

        <p class="note">" TANPA ALOKASI "</p>
    </div>
<?php endforeach; ?>

</body>
</html>