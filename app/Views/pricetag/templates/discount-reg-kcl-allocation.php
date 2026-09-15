<?php
/**
 * Template: Diskon Reguler + Limited Alokasi (POP Price Tag)
 * Sumber desain: BARU_POP_DISC_REG_KCL_HG_LIMITED_ALOKASI_NEW.docx
 *
 * Data yang dibutuhkan per tag (dari $tags[$i]), sesuai PriceTagModel:
 *   brand             => Nama merk/brand
 *   name              => Nama produk
 *   variant           => Varian produk (mis. berat/ukuran)
 *   discount_percent  => Angka persen diskon (mis. 20)
 *   sku_plu           => Kode PLU/SKU
 *   end_period        => Tanggal akhir periode promo (AP)
 *   allocation_pcs    => Jumlah alokasi stok promo (pcs)
 *   normal_price      => Harga sebelum diskon
 *   promo_price       => Harga setelah diskon
 *
 * $tags dan $size dikirim dari PrintPdf::index().
 * $size berisi ['width' => .., 'height' => ..] dalam mm (ukuran halaman mpdf).
 * mpdf sudah diberi margin 2mm di tiap sisi (lihat margin_left/right/top/bottom
 * di controller), jadi area konten HTML dihitung: ukuran halaman - 4mm.
 *
 * FONT AUTO-SCALE:
 * Template ini dipakai untuk beberapa ukuran label (mis. Kecil 70x45mm,
 * Tanggung 70x70mm, dll). Supaya tidak perlu tuning manual tiap ukuran,
 * semua font-size dihitung proporsional terhadap tinggi label, dengan
 * acuan desain awal = tinggi 45mm (ukuran "Kecil").
 */

// Fallback jika $size tidak dikirim, supaya template tidak error
$pageWidth  = (float) ($size['width'] ?? 70);
$pageHeight = (float) ($size['height'] ?? 45);

// mpdf margin per sisi -> HARUS SAMA dengan margin_* di PrintPdf::index()
$mpdfMargin = 2; // mm
$tagWidth   = $pageWidth - ($mpdfMargin * 2);
$tagHeight  = $pageHeight - ($mpdfMargin * 2);

// Skala font mengikuti tinggi label. Acuan desain: tinggi konten 41mm (45mm - 4mm margin)
$baseHeight = 41;
$scale = $tagHeight > 0 ? $tagHeight / $baseHeight : 1;
$scale = max(0.55, min($scale, 1.8)); // batasi biar tidak terlalu kecil/besar ekstrem

// Helper untuk generate ukuran font (pt) hasil skala
function ptScale($base, $scale) {
    return round($base * $scale, 1) . 'pt';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    /* @page tidak perlu diset ukurannya karena sudah ditentukan oleh
       parameter 'format' di Mpdf() pada controller */
    @page { margin: 0; }
    body {
        margin: 0;
        padding: 0;
        font-family: sans-serif;
    }
    .tag {
        width: <?= $tagWidth ?>mm;
        height: <?= $tagHeight ?>mm;
        box-sizing: border-box;
        padding: <?= round(1.2 * $scale, 1) ?>mm <?= round(2.5 * $scale, 1) ?>mm;
        page-break-after: always;
        overflow: hidden;
    }
    .tag:last-child { page-break-after: auto; }

    .merk {
        text-align: center;
        color: #1E7FC2;
        font-weight: bold;
        font-size: <?= ptScale(11, $scale) ?>;
        line-height: 1.05;
        letter-spacing: 0.3px;
        margin: 0;
    }
    .variant {
        text-align: center;
        color: #000000;
        font-weight: bold;
        font-size: <?= ptScale(8, $scale) ?>;
        line-height: 1.1;
        margin: 0 0 <?= round(0.6 * $scale, 1) ?>mm 0;
    }

    .diskon-row { width: 100%; }
    .diskon-label {
        color: #E30613;
        font-weight: bold;
        font-size: <?= ptScale(8, $scale) ?>;
        white-space: nowrap;
        vertical-align: middle;
    }
    .program {
        color: #E30613;
        font-weight: bold;
        font-style: italic;
        font-size: <?= ptScale(16, $scale) ?>;
        line-height: 1;
    }
    .program .persen {
        font-size: <?= ptScale(10, $scale) ?>;
        vertical-align: top;
    }

    .meta {
        font-size: <?= ptScale(6, $scale) ?>;
        color: #000;
        line-height: 1.15;
        margin: <?= round(0.25 * $scale, 2) ?>mm 0;
    }

    .note {
        font-size: <?= ptScale(5, $scale) ?>;
        color: #1E9FD6;
        font-style: italic;
        line-height: 1.1;
        margin-top: <?= round(0.7 * $scale, 1) ?>mm;
        white-space: nowrap;
    }
    .alokasi {
        font-size: <?= ptScale(6, $scale) ?>;
        color: #1E9FD6;
        font-weight: bold;
        margin: 0;
    }

    .price-table {
        width: 100%;
        margin-top: <?= round(0.7 * $scale, 1) ?>mm;
    }
    .price-table td { vertical-align: middle; }
    .rp-symbol {
        font-size: <?= ptScale(6, $scale) ?>;
        color: #E30613;
        font-weight: bold;
    }
    .harga-normal {
        color: #E30613;
        font-size: <?= ptScale(7, $scale) ?>;
        text-decoration: line-through;
    }
    .harga-promo {
        color: #E30613;
        font-size: <?= ptScale(11, $scale) ?>;
        font-weight: bold;
    }
    .arrow {
        font-size: <?= ptScale(9, $scale) ?>;
        color: #000;
        text-align: center;
    }
</style>
</head>
<body>

<?php foreach ($tags as $tag): ?>
    <div class="tag">
        <p class="merk"><?= esc($tag['brand'] ?? '') ?></p>
        <p class="variant">
            <?= esc(trim(($tag['name'] ?? '') . ' ' . ($tag['variant'] ?? ''))) ?>
        </p>

        <table class="diskon-row" cellpadding="0" cellspacing="0">
            <tr>
                <td class="diskon-label" width="30%">Diskon :</td>
                <td class="program">
                    <?= esc($tag['discount_percent'] ?? '') ?><span class="persen">%</span>
                </td>
            </tr>
        </table>

        <p class="meta">( PLU : <?= esc($tag['sku_plu'] ?? '') ?> )</p>
        <p class="meta">AP : <?= esc($tag['end_period'] ?? '') ?></p>

        <p class="note">*Promo Berlaku Selama Alokasi masih Ada</p>
        <p class="alokasi">Alokasi : <?= esc($tag['allocation_pcs'] ?? '') ?> Pcs</p>

        <table class="price-table" cellpadding="0" cellspacing="0">
            <tr>
                <td width="45%">
                    <span class="rp-symbol">Rp</span>
                    <span class="harga-normal"><?= number_format((float)($tag['normal_price'] ?? 0), 0, ',', '.') ?></span>
                </td>
                <td width="10%" class="arrow">&rArr;</td>
                <td width="45%">
                    <span class="rp-symbol">Rp</span>
                    <span class="harga-promo"><?= number_format((float)($tag['promo_price'] ?? 0), 0, ',', '.') ?></span>
                </td>
            </tr>
        </table>
    </div>
<?php endforeach; ?>

</body>
</html>