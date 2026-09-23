<?php
/**
 * Template: Harga Langsung Kecil (POP Price Tag)
 * Sumber desain: BARU_POP_THG_KCL.docx
 *
 * Data yang dibutuhkan per tag (dari $tags[$i]):
 *   name            => Nama produk
 *   variant        => Nama variant produk
 *   sku_plu            => Kode sku_plu/SKU
 *   end_period        => Tanggal akhir end_period promo
 *   normal_price   => Harga sebelum promo (dicoret)
 *   promo_price    => Harga promo (ditulis utuh, tidak dipecah ribuan/ratusan)
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

    .variant {
        text-align: center;
        color: #000000;
        font-weight: bold;
        font-size: 12pt;
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
    .sku_plu-text {
        font-size: 8pt;
        color: #000;
        text-align: right;
    }

    .meta { font-size: 8pt; color: #000; margin: 1mm 0; }

    .promo-price { margin: 2mm 0 0 0; }
    .rp-symbol {
        font-size: 11pt;
        color: #E30613;
        font-weight: bold;
        vertical-align: bottom;
    }
    .harga-promo {
        color: #E30613;
        font-weight: bold;
        font-style: italic;
        font-size: 30pt;
        line-height: 1;
    }

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
        <p class="variant"><?= esc(trim(($tag['name'] ?? '') . ' ' . ($tag['variant'] ?? ''))) ?></p>

        <table class="top-row" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%">
                    <span class="rp-symbol-sm">Rp</span>
                    <span class="harga-normal"><?= number_format((float)($tag['normal_price'] ?? 0), 0, ',', '.') ?></span>
                </td>
                <td width="50%" class="sku_plu-text">( PLU  : <?= esc($tag['sku_plu'] ?? '') ?> )</td>
            </tr>
        </table>

        <p class="meta">Akhir Periode : <?= esc($tag['end_period'] ?? '') ?></p>

        <p class="promo-price">
            <span class="rp-symbol">Rp</span>
            <span class="harga-promo"><?= number_format((float)($tag['promo_price'] ?? 0), 0, ',', '.') ?></span>
        </p>

        <p class="note">" TANPA ALOKASI "</p>
    </div>
<?php endforeach; ?>

</body>
</html>
