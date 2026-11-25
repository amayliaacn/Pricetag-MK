<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$fontDir = __DIR__ . '/fonts';
$windowsFontDir = 'C:/Windows/Fonts';
$background = $root . '/public/assets/img/Picture3-ketinggian.png';
$output = __DIR__ . '/segitiga-kecil-diskon-v1.pdf';

$fontFiles = [
    'calibri' => ['label' => 'Calibri Bold', 'file' => 'calibrib.ttf', 'path' => $windowsFontDir . '/calibrib.ttf'],
    'poppins' => ['label' => 'Poppins SemiBold', 'file' => 'Poppins-SemiBold.ttf', 'path' => $fontDir . '/Poppins-SemiBold.ttf'],
    'extra' => ['label' => 'Poppins ExtraBold', 'file' => 'Poppins-ExtraBold.ttf', 'path' => $root . '/public/assets/fonts/Poppins-ExtraBold.ttf'],
    'century' => ['label' => 'Century Regular + synthetic bold', 'file' => 'CENTURY.TTF', 'path' => $windowsFontDir . '/CENTURY.TTF'],
];
foreach ($fontFiles as $font) {
    if (!is_file($font['path'])) throw new RuntimeException('Font tidak ditemukan: ' . $font['path']);
}
if (!is_file($background)) throw new RuntimeException('Background tidak ditemukan: ' . $background);

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
foreach ($fontFiles as $alias => $font) $fontdata[$alias] = ['R' => $font['file']];
$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
    'autoPageBreak' => false, 'tempDir' => $root . '/writable/mpdf',
    'fontDir' => array_values(array_unique(array_merge($d['fontDir'], [$fontDir, $root . '/public/assets/fonts', $windowsFontDir]))),
    'fontdata' => $fontdata, 'default_font' => 'arial',
]);

$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$data = [
    'brand' => 'KIN', 'plu' => 'PLU : 319787', 'product' => 'YOG SLURP 80 STRW',
    'disc' => 'DISC.', 'discount' => '50', 'percent' => '%', 'normalRp' => 'Rp',
    'normal' => '6.703', 'promoRp' => 'Rp', 'promo' => '3.352',
    'period' => 'AKHIR PERIODE :', 'date' => '20 APRIL 2025',
    'allocation' => 'SELAMA ALOKASI', 'still' => 'MASIH ADA',
];

// Coordinates are relative to one 142.5 x 200 mm POP.
$boxes = [
    // Baseline visual structure calibrated from the left POP reference.
    // Brand: approximately 91% of the white panel width, centered.
    'brand' => [10.45, 30, 121.6, 28.58, 'calibri', '#0000FF'],
    'plu' => [84, 8, 43, 8, 'poppins', '#111111'],
    // Centered on brand center: 10.45 + 121.6 / 2 = 71.25 mm.
    'product' => [19.25, 64, 104, 10, 'poppins', '#111111'],
    'disc' => [12, 84, 29, 10, 'poppins', '#111111'],
    'discount' => [47, 81, 64, 47, 'century', '#FF0000'],
    'percent' => [116, 114, 17, 14, 'poppins', '#FF0000'],
    'normalRp' => [4.5, 138, 10, 9, 'poppins', '#FF0000'],
    'normal' => [18, 138, 52, 16, 'extra', '#111111'],
    // promo center = 154 + 35 / 2 = 171.5; Rp top = 171.5 - 9 / 2 = 167.
    'promoRp' => [59, 167, 10, 9, 'poppins', '#FF0000'],
    'promo' => [72, 154, 62, 35, 'extra', '#FF0000'],
    // Reduced about 21.4%; both groups share the brand horizontal center.
    // Local center for both lower-left groups: x = 34 mm.
    'period' => [8.5, 160, 51, 5.1, 'poppins', '#111111'],
    'date' => [12.5, 168, 43, 5.1, 'poppins', '#111111'],
    'allocation' => [8.5, 176.5, 45, 5.1, 'poppins', '#FF0000'],
    'still' => [16.5, 184, 29, 4.65, 'poppins', '#FF0000'],
];

$setFont = static function (Mpdf $pdf, string $alias): void {
    $pdf->SetFont($alias, '', 10 / .352778);
};

$stretchEl = static function (Mpdf $pdf, string $alias, string $text, float $wMm, float $hMm, float $xMm, float $baselineMm) use ($setFont): array {
    $setFont($pdf, $alias);
    $w0 = max(.01, (float) $pdf->GetStringWidth($text));
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0;
    return [
        'family' => $alias, 'fs' => 10.0, 'x' => $xMm, 'y' => $baselineMm,
        'sx' => $wMm / $w0, 'sy' => $hMm / max(.01, $cap), 'w0' => $w0,
    ];
};

$renderPop = static function (Mpdf $pdf, float $pageX, float $pageY) use ($background, $data, $boxes, $stretchEl, $esc): void {
    // Match the existing renderer: background is an absolutely positioned HTML
    // image, followed by a transparent SVG text layer.
    $image = '<img src="'.$esc($background).'" style="display:block;width:142.5mm;height:200mm;" />';
    $pdf->WriteFixedPosHTML($image, $pageX, $pageY, 142.5, 200, 'auto');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none" style="background:transparent">';
    foreach ($boxes as $key => [$x, $y, $w, $h, $font, $color]) {
        $text = $data[$key];
        $t = $stretchEl($pdf, $font, $text, $w, $h, $x, $y + $h + 3.0);
        $svg .= '<g transform="translate('.$t['x'].' '.$t['y'].') scale('.$t['sx'].' '.$t['sy'].')" fill="'.$color.'">';
        $svg .= '<text x="0" y="0" font-family="'.$t['family'].'" font-size="'.$t['fs'].'">'.$esc($text).'</text>';
        if ($key === 'discount' || in_array($key, ['period', 'date', 'allocation', 'still'], true)) {
            // Approved Century comparison plus a light synthetic pass for the
            // four small reference lines; Poppins remains the same font.
            $svg .= '<text x="0.18" y="0" font-family="'.$t['family'].'" font-size="'.$t['fs'].'">'.$esc($text).'</text>';
        }
        $svg .= '</g>';
    }
    // Red strike-through relative to the normal-price element.
    // Diagonal strike: left-bottom to right-top across the normal price.
    $svg .= '<line x1="17.5" y1="154" x2="71.5" y2="140" stroke="#FF0000" stroke-width="0.8"/>';
    $svg .= '</svg>';
    $pdf->WriteFixedPosHTML($svg, $pageX, $pageY, 142.5, 200, 'auto');
};

$renderPop($pdf, 3.0, 5.0);
$renderPop($pdf, 151.5, 5.0);
$pdf->Output($output, 'F');

fwrite(STDOUT, "Generated: {$output}\n");
foreach ($fontFiles as $alias => $font) fwrite(STDOUT, $alias . ': ' . $font['path'] . "\n");
