<?php
/**
 * Template: Harga Langsung + Limited Alokasi (POP Price Tag)
 * Sumber desain: BARU_POP_THG_TGG_REG_ALOKASI_LIMITED.docx
 *
 * Data yang dibutuhkan per tag (dari $tags[$i]):
 *   nama_barang    => Nama barang/produk
 *   plu            => Kode PLU/SKU
 *   periode        => Tanggal akhir periode promo
 *   alokasi        => Jumlah alokasi stok promo (pcs)
 *   harga_normal   => Harga sebelum promo (dicoret)
 *   harga_ribuan   => Bagian ribuan dari harga promo (mis. "12" untuk Rp 12.900)
 *   harga_ratusan  => Bagian ratusan/ribuan-kecil dari harga promo (mis. "900")
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

    .nama-barang {
        text-align: center;
        color: #1E7FC2;
        font-weight: bold;
        font-size: 18pt;
        letter-spacing: 1px;
        margin: 0 0 2mm 0;
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
    .plu-text {
        font-size: 8pt;
        color: #000;
        text-align: right;
    }

    .note {
        font-size: 8pt;
        color: #1E9FD6;
        font-style: italic;
        margin: 1.5mm 0 0 0;
        line-height: 1.3;
    }
    .alokasi {
        font-size: 8pt;
        color: #1E9FD6;
        font-weight: bold;
        margin: 0;
    }

    .promo-table { width: 100%; margin-top: 1.5mm; }
    .promo-price { margin: 1.5mm 0 0 0; }
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
</style>
</head>
<body>

<?php foreach ($tags as $tag): ?>
    <div class="tag">
        <p class="nama-barang">&laquo;<?= esc($tag['nama_barang'] ?? '') ?>&raquo;</p>

        <table class="top-row" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%">
                    <span class="rp-symbol-sm">Rp</span>
                    <span class="harga-normal"><?= number_format((float)($tag['harga_normal'] ?? 0), 0, ',', '.') ?></span>
                </td>
                <td width="50%" class="plu-text">( PLU : <?= esc($tag['plu'] ?? '') ?> )</td>
            </tr>
        </table>

        <p class="note">*Promo Berlaku Selama Alokasi Masih ada</p>
        <p class="alokasi">Alokasi : <?= esc($tag['alokasi'] ?? '') ?> Pcs</p>

        <p class="meta">Akhir Periode : <?= esc($tag['periode'] ?? '') ?></p>

        <p class="promo-price">
            <span class="rp-symbol">Rp</span>
            <span class="ribuan"><?= esc($tag['harga_ribuan'] ?? '') ?></span><span class="ratusan">,<?= esc($tag['harga_ratusan'] ?? '') ?></span>
        </p>
    </div>
<?php endforeach; ?>

</body>
</html>