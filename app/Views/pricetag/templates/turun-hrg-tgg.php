<?php
/**
 * Template: Harga Langsung Reguler - Besar/Custom (POP Price Tag)
 * Ukuran KERTAS/LABEL FISIK TOTAL: 157mm x 84mm (15,7cm x 8,4cm)
 */

$pageWidth  = (float) ($size['width'] ?? 157);
$pageHeight = (float) ($size['height'] ?? 84);

/* MARGIN & UKURAN FISIK */
$mpdfMargin   = 2;
$yellowBorder = 5;
$whiteTopZone = 20;

/* SAFE ZONE */
$safeZoneTop    = $whiteTopZone + $yellowBorder; // 25mm
$safeZoneLeft   = $yellowBorder;                 // 5mm
$safeZoneRight  = $yellowBorder;                 // 5mm
$safeZoneBottom = $yellowBorder;                 // 5mm

/* PADDING TEMPLATE */
$padTop    = max(0, $safeZoneTop - $mpdfMargin);
$padLeft   = max(0, $safeZoneLeft - $mpdfMargin);
$padRight  = max(0, $safeZoneRight - $mpdfMargin);
$padBottom = max(0, $safeZoneBottom - $mpdfMargin);

/* UKURAN TAG & AREA KONTEN */
$tagWidth          = $pageWidth - ($mpdfMargin * 2);
$renderSafetyGap   = 1;
$tagHeight         = $pageHeight - ($mpdfMargin * 2) - $renderSafetyGap;
$contentHeightReal = $tagHeight - $padTop - $padBottom;

/* SCALE & INDENT */
$baseHeight = 41;
$scale      = $contentHeightReal > 0 ? $contentHeightReal / $baseHeight : 1;
$scale      = max(0.55, min($scale, 2.2));
$logoIndent = 32;

/* FONT SIZE BASE */
$fsNamaBarang  = 13;
$fsRpSmall     = 14;
$fsHargaNormal = 24;
$fsRpBig       = 20;
$fsRibuan      = 72;
$fsRatusan     = 37;
$fsMeta        = 9;

/*
 * POSISI RATUSAN PROMO
 * Semakin besar = ,991 semakin turun.
 * Coba 7-10mm jika ingin disesuaikan lagi.
 */
$promoRatusanSpacer = 8;
$promoLowerOffset   = 18;

/* HELPER FUNCTIONS */
function turunHrgBesarPtScale($base, $scale) {
    return round($base * $scale, 1) . 'pt';
}

function turunHrgBesarSplitPromoPrice($promoPrice) {
    $promoPrice = (int) $promoPrice;
    $ribuan     = intdiv($promoPrice, 1000);
    $ratusan    = str_pad((string) ($promoPrice % 1000), 3, '0', STR_PAD_LEFT);
    return [$ribuan, $ratusan];
}

function turunHrgBesarCoretSvgDataUri($widthPx, $heightPx, $color = '#000000') {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $widthPx . '" height="' . $heightPx . '" viewBox="0 0 ' . $widthPx . ' ' . $heightPx . '">'
         . '<line x1="' . round($widthPx * 0.15) . '" y1="' . round($heightPx * 0.9) . '" x2="' . round($widthPx * 0.98) . '" y2="' . round($heightPx * 0.1) . '" stroke="' . $color . '" stroke-width="' . round($heightPx * 0.15) . '" stroke-linecap="butt"/>'
         . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function turunHrgBesarHitungOverlayCoret($teks, $fontSizePt) {
    $charWidthRatio = 0.56;
    $overshootRatio = 0.05;
    $jumlahKarakter = mb_strlen($teks);

    $lebarTeksPt    = $jumlahKarakter * $fontSizePt * $charWidthRatio;
    $lebarOverlayPt = $lebarTeksPt * (1 + $overshootRatio);
    $tinggiPt       = $fontSizePt * 0.9;
    $marginLeftPt   = $lebarTeksPt;

    return [
        'lebar_mm'       => round($lebarOverlayPt * 0.3528, 1),
        'tinggi_mm'      => round($tinggiPt * 0.3528, 1),
        'margin_kiri_mm' => round($marginLeftPt * 0.3528, 1),
        'lebar_px'       => (int) round($lebarOverlayPt * 1.333),
        'tinggi_px'      => (int) round($tinggiPt * 1.333),
    ];
}

function turunHrgBesarFormatPeriode($rawDate) {
    if (empty($rawDate)) return '';

    $timestamp = strtotime($rawDate);
    if ($timestamp === false) return $rawDate;

    $bulanSingkat = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
        5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU',
        9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
    ];

    $tanggal = date('j', $timestamp);
    $bulan   = $bulanSingkat[(int) date('n', $timestamp)];
    $tahun2  = date('y', $timestamp);

    return "{$tanggal} {$bulan} '{$tahun2}";
}
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
    width: <?= $tagWidth ?>mm;
    height: <?= $tagHeight ?>mm;
    box-sizing: border-box;
    padding: <?= $padTop ?>mm <?= $padRight ?>mm <?= $padBottom ?>mm <?= $padLeft ?>mm;
    overflow: hidden;
}

.tag-break {
    page-break-after: always;
}

/* NAMA PRODUK */
.nama-barang {
    text-align: left;
    color: #1E7FC2;
    font-weight: bold;
    font-size: <?= turunHrgBesarPtScale($fsNamaBarang, $scale) ?>;
    line-height: 1.1;
    letter-spacing: 0.2px;
    margin: 0 0 <?= round(1 * $scale, 1) ?>mm <?= $logoIndent ?>mm;
    text-transform: uppercase;
}

/* TABEL HARGA UTAMA */
.price-row {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.price-row td {
    padding: 0;
    margin: 0;
}

/* HARGA NORMAL */
.harga-normal-block {
    width: 35%;
    vertical-align: top;
    white-space: nowrap;
}

.rp-symbol-sm {
    font-size: <?= turunHrgBesarPtScale($fsRpSmall, $scale) ?>;
    color: #E30613;
    font-weight: normal;
    vertical-align: super;
    line-height: 1 !important;
}

.harga-normal {
    color: #E30613;
    font-weight: bold;
    font-size: <?= turunHrgBesarPtScale($fsHargaNormal, $scale) ?>;
    line-height: 1;
}

.coret-img {
    display: inline-block;
    vertical-align: middle;
}

/* RP PROMO */
.promo-rp-cell {
    width: 8%;
    vertical-align: top;
    white-space: nowrap;
    padding-top: 7mm !important;
    padding-right: 1mm !important;
}

.rp-symbol {
    color: #E30613;
    font-size: <?= turunHrgBesarPtScale($fsRpBig, $scale) ?>;
    font-weight: normal;
    line-height: 1;
    display: block;
    margin-top: <?= $promoLowerOffset ?>mm;
}

/* ANGKA BESAR PROMO */
.promo-ribuan-cell {
    width: 25%;
    vertical-align: top;
    white-space: nowrap;
}

.ribuan {
    color: #E30613;
    font-weight: bold;
    font-style: italic;
    font-size: <?= turunHrgBesarPtScale($fsRibuan, $scale) ?>;
    line-height: 0.82;
    display: block;
    margin-top: <?= $promoLowerOffset ?>mm;
}

/* RATUSAN PROMO */
.promo-ratusan-top {
    width: 12%;
    height: 14mm;
    font-size: 1pt;
    line-height: 1;
    vertical-align: top;
}

.promo-ratusan-cell {
    width: 12%;
    vertical-align: top;
    white-space: nowrap;
    padding-left: 1mm !important;
}

.ratusan {
    color: #E30613;
    font-weight: bold;
    font-style: italic;
    font-size: <?= turunHrgBesarPtScale($fsRatusan, $scale) ?>;
    line-height: 0.9;
    display: block;
    margin-top: <?= $promoLowerOffset ?>mm;
}

/* PLU */
.plu-block {
    width: 20%;
    vertical-align: top;
    text-align: right;
    white-space: nowrap;
    color: #000;
    font-size: <?= turunHrgBesarPtScale(9, $scale) ?>;
}

/* META */
.meta-block {
    width: 35%;
    text-align: center;
    margin-top: <?= round(0.5 * $scale, 1) ?>mm;
}

.meta-label {
    font-size: <?= turunHrgBesarPtScale($fsMeta, $scale) ?>;
    color: #000;
    font-weight: normal;
    line-height: 1;
    margin: 0 0 <?= round(0.8 * $scale, 1) ?>mm 0;
}

.meta-value {
    font-size: <?= turunHrgBesarPtScale($fsMeta, $scale) ?>;
    color: #000;
    font-weight: bold;
    line-height: 1;
    margin: 0 0 <?= round(1.5 * $scale, 1) ?>mm 0;
}

.note {
    font-size: <?= turunHrgBesarPtScale($fsMeta, $scale) ?>;
    color: #E30613;
    font-weight: bold;
    line-height: 1;
    margin: 0;
    white-space: nowrap;
}
</style>
</head>

<body>

<?php
$totalTags = count($tags);

foreach ($tags as $index => $tag):
    $namaBarang = trim(
        ($tag['name'] ?? '') . ' ' .
        ($tag['variant'] ?? '')
    );

    [$hargaRibuan, $hargaRatusan] =
        turunHrgBesarSplitPromoPrice($tag['promo_price'] ?? 0);

    $isLastTag = ($index === $totalTags - 1);

    $normalPriceFormatted = number_format(
        (float) ($tag['normal_price'] ?? 0),
        0,
        ',',
        '.'
    );

    $hargaNormalFontPt = $fsHargaNormal * $scale;

    $overlayCoret = turunHrgBesarHitungOverlayCoret(
        'Rp ' . $normalPriceFormatted,
        $hargaNormalFontPt
    );

    $coretMarginLeftMm = $overlayCoret['margin_kiri_mm'];
?>

<div class="tag<?= $isLastTag ? '' : ' tag-break' ?>">

    <!-- NAMA PRODUK -->
    <p class="nama-barang"><?= esc($namaBarang) ?></p>

    <!-- HARGA -->
    <table class="price-row" cellpadding="0" cellspacing="0">

        <!-- BARIS PERTAMA -->
        <tr>

            <!-- HARGA NORMAL / CORET -->
            <td class="harga-normal-block" rowspan="2">
                <span class="rp-symbol-sm">Rp</span>
                <span class="harga-normal"><?= $normalPriceFormatted ?></span>
                <img
                    class="coret-img"
                    src="<?= turunHrgBesarCoretSvgDataUri(
                        $overlayCoret['lebar_px'],
                        $overlayCoret['tinggi_px']
                    ) ?>"
                    width="<?= $overlayCoret['lebar_mm'] ?>mm"
                    height="<?= $overlayCoret['tinggi_mm'] ?>mm"
                    style="margin-left:-<?= $coretMarginLeftMm ?>mm;"
                    alt="">
            </td>

            <!-- RP PROMO -->
            <td class="promo-rp-cell" rowspan="2">
                <span class="rp-symbol">Rp</span>
            </td>

            <!-- ANGKA BESAR -->
            <td class="promo-ribuan-cell" rowspan="2">
                <span class="ribuan"><?= esc($hargaRibuan) ?></span>
            </td>

            <!-- RUANG AGAR ,991 TURUN -->
            <td class="promo-ratusan-top">&nbsp;</td>

            <!-- PLU -->
            <td class="plu-block" rowspan="2">
                ( PLU : <?= esc($tag['sku_plu'] ?? '') ?> )
            </td>

        </tr>

        <!-- BARIS KEDUA -->
        <tr>
            <td class="promo-ratusan-cell">
                <span class="ratusan">.<?= esc($hargaRatusan) ?></span>
            </td>
        </tr>

    </table>

    <!-- AKHIR PERIODE & NOTE -->
<div class="meta-block">
    <p class="meta-label">Akhir Periode :</p>
    <p class="meta-value"><?= esc(turunHrgBesarFormatPeriode($tag['end_period'] ?? '')) ?></p>
    <p class="note">" TANPA ALOKASI "</p>
</div>

<?php endforeach; ?>

</body>
</html>
