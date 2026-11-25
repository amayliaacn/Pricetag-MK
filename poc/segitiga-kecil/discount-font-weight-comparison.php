<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$fontDir = __DIR__ . '/fonts';
$bg = $root . '/public/assets/img/Picture3-ketinggian.png';
$out = __DIR__ . '/discount-font-weight-comparison.pdf';

$fonts = [
    'semi' => ['label' => 'Poppins SemiBold (600)', 'file' => 'Poppins-SemiBold.ttf'],
    'bold' => ['label' => 'Poppins Bold (700)', 'file' => 'Poppins-Bold.ttf'],
];
foreach ($fonts as $key => $font) {
    if (!is_file($fontDir . '/' . $font['file'])) {
        throw new RuntimeException('Font tidak ditemukan: ' . $font['file']);
    }
}

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdata['semi'] = ['R' => $fonts['semi']['file']];
$fontdata['bold'] = ['R' => $fonts['bold']['file']];
$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
    'autoPageBreak' => false, 'tempDir' => $root . '/writable/mpdf',
    'fontDir' => array_values(array_unique(array_merge($d['fontDir'], [$fontDir]))),
    'fontdata' => $fontdata, 'default_font' => 'arial',
]);

$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$boxes = [
    'plu' => [70.42, .09, 49.90, 7.32, 6.5, '#111111'],
    'product' => [4.50, 27.04, 103.87, 9.53, 12, '#111111'],
    'disc' => [3.18, 46.26, 27.25, 9.53, 12, '#111111'],
    'brand' => [57, 9, 28, 12, 14, '#111111'],
    'percent' => [107, 76.76, 16.83, 13.78, 18, '#e30613'],
    'discount' => [111, 52, 22, 15, 18, '#e30613'],
    'normalRp' => [9.53, 100.19, 8.86, 9.53, 12, '#111111'],
    'normal' => [25, 99.5, 57, 13, 22, '#111111'],
    'promoRp' => [9.53, 115, 8.86, 10, 12, '#e30613'],
    'promo' => [25, 114, 62, 15, 25, '#e30613'],
    'period' => [4, 139, 72, 10, 10, '#111111'],
    'date' => [4, 149, 72, 10, 10, '#111111'],
    'allocation' => [4, 161, 80, 10, 10, '#e30613'],
    'still' => [4, 171, 80, 10, 10, '#e30613'],
];

$set = static function (Mpdf $pdf, string $alias, float $pt): void { $pdf->SetFont($alias, '', $pt); };
$ink = static function (Mpdf $pdf, string $alias, string $text, float $w, float $h) use ($set): array {
    $set($pdf, $alias, 10 / .352778);
    $measured = max(.01, (float)$pdf->GetStringWidth($text));
    // Fit the complete font ink box, not only CapHeight. The old baseline
    // (y=9.5) was scaled afterwards, pushing descenders below the SVG viewport.
    $bbox = explode(' ', trim((string)($pdf->CurrentFont['desc']['FontBBox'] ?? '[-500 -500 2500 1000]'), '[]'));
    $bottom = ((float)($bbox[1] ?? -500) / 1000) * 10;
    $top = ((float)($bbox[3] ?? 1000) / 1000) * 10;
    $sy = $h / max(.01, $top - $bottom);
    return [$w / $measured, $sy, -$bottom * $sy];
};
$svg = static function (Mpdf $pdf, string $alias, string $text, float $w, float $h, string $color) use ($esc, $ink): string {
    [$sx, $sy, $baseline] = $ink($pdf, $alias, $text, $w, $h);
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'mm" height="'.$h.'mm" viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="none"><g transform="translate(0 '.$baseline.') scale('.$sx.' '.$sy.')" fill="'.$color.'"><text x="0" y="0" font-family="'.$alias.'" font-size="10">'.$esc($text).'</text></g></svg>';
};

$drawPop = static function (Mpdf $pdf, string $alias, float $ox, float $oy) use ($bg, $boxes, $svg): void {
    // Keep the image absolutely positioned; flow=true could move the first
    // image to a new page and made the left background disappear.
    $pdf->Image($bg, $ox, $oy, 142.5, 200, 'png', '', false, false);
    $items = [
        ['brand', 'KIN'], ['plu', 'PLU : 319787'], ['product', 'YOG SLURP 80 STRW'], ['disc', 'DISC.'], ['discount', '50'], ['percent', '%'],
        ['normalRp', 'Rp'], ['normal', '6.703'], ['promoRp', 'Rp'], ['promo', '3.352'],
        ['period', 'AKHIR PERIODE :'], ['date', '20 APRIL 2025'], ['allocation', 'SELAMA ALOKASI'], ['still', 'MASIH ADA'],
    ];
    foreach ($items as [$key, $text]) {
        [$x, $y, $w, $h, $unused, $color] = $boxes[$key];
        $pdf->WriteFixedPosHTML($svg($pdf, $alias, $text, $w, $h, $color), $ox + $x, $oy + $y, $w, $h, 'auto');
    }
    // Red strike-through over the normal price, matching the Word reference.
    $pdf->SetDrawColor(230, 6, 19);
    $pdf->SetLineWidth(.8);
    $pdf->Line($ox + 24, $oy + 106, $ox + 82, $oy + 101);
};

foreach (['semi', 'bold'] as $index => $alias) {
    if ($index > 0) $pdf->AddPage('L');
    $pdf->SetTitle('Segitiga Kecil Diskon - ' . $fonts[$alias]['label']);
    $drawPop($pdf, $alias, 3, 5);
    $drawPop($pdf, $alias, 151.5, 5);
}
$pdf->Output($out, 'F');
fwrite(STDOUT, "Generated: {$out}\n");
