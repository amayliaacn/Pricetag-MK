<?php
/**
 * Template: Diskon Reguler (POP Price Tag)
 * Sumber desain: BARU_POP_DISC_REG_KCL___HG.docx
 *
 * Data yang dibutuhkan per tag (dari $tags[$i]):
 *   merk          => Nama merk/brand
 *   variant       => Nama variant produk
 *   program       => Angka persen diskon (mis. 20)
 *   plu           => Kode PLU/SKU
 *   periode       => Tanggal akhir periode promo
 *   harga_normal  => Harga sebelum diskon
 *   harga_promo   => Harga setelah diskon
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

    .merk {
        text-align: center;
        color: #1E7FC2;
        font-weight: bold;
        font-size: 22pt;
        letter-spacing: 1px;
        margin: 0;
    }
    .variant {
        text-align: center;
        color: #000000;
        font-weight: bold;
        font-size: 13pt;
        margin: 0 0 2mm 0;
    }

    .diskon-row {
        width: 100%;
    }
    .diskon-label {
        color: #E30613;
        font-weight: bold;
        font-size: 13pt;
        vertical-align: top;
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

    .meta {
        font-size: 8pt;
        color: #000;
        margin-top: 1mm;
    }

    .price-table {
        width: 100%;
        margin-top: 2mm;
    }
    .price-table td { vertical-align: middle; }
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
        font-size: 15pt;
        font-weight: bold;
    }
    .arrow {
        font-size: 12pt;
        color: #000;
        text-align: center;
    }
</style>
</head>
<body>

<?php foreach ($tags as $tag): ?>
    <div class="tag">
        <p class="merk"><?= esc($tag['merk'] ?? '') ?></p>
        <p class="variant"><?= esc($tag['variant'] ?? '') ?></p>

        <table class="diskon-row" cellpadding="0" cellspacing="0">
            <tr>
                <td class="diskon-label" width="24%" style="white-space:nowrap;">Diskon :</td>
                <td class="program">
                    <?= esc($tag['program'] ?? '') ?><span class="persen">%</span>
                </td>
            </tr>
        </table>

        <p class="meta">( PLU : <?= esc($tag['plu'] ?? '') ?> )</p>
        <p class="meta">Akhir Periode : <?= esc($tag['periode'] ?? '') ?></p>

        <table class="price-table" cellpadding="0" cellspacing="0">
            <tr>
                <td width="45%">
                    <span class="rp-symbol">Rp</span>
                    <span class="harga-normal"><?= number_format((float)($tag['harga_normal'] ?? 0), 0, ',', '.') ?></span>
                </td>
                <td width="10%" class="arrow">&rArr;</td>
                <td width="45%">
                    <span class="rp-symbol">Rp</span>
                    <span class="harga-promo"><?= number_format((float)($tag['harga_promo'] ?? 0), 0, ',', '.') ?></span>
                </td>
            </tr>
        </table>
    </div>
<?php endforeach; ?>

</body>
</html>