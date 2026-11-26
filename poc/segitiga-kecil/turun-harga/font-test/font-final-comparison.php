<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 4);
$assetFontDir = $root . '/public/assets/fonts';
$windowsFontDir = 'C:/Windows/Fonts';
$ttcPath = $windowsFontDir . '/mingliub.ttc';
$output = __DIR__ . '/segitiga-kecil-turun-harga-font-final-comparison.pdf';
$tempDir = $root . '/writable/mpdf-turun-harga-font-final-comparison';

foreach ([$assetFontDir . '/Poppins-SemiBold.ttf', $assetFontDir . '/Poppins-ExtraBold.ttf', $ttcPath] as $path) {
    if (!is_readable($path)) throw new RuntimeException('Font tidak tersedia: ' . $path);
}

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdata['finalmingliu'] = ['R' => 'mingliub.ttc', 'TTCfontID' => ['R' => 1]];
$fontdata['finalpoppins'] = ['R' => 'Poppins-SemiBold.ttf'];
$fontdata['finalextra'] = ['R' => 'Poppins-ExtraBold.ttf'];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], [$assetFontDir, $windowsFontDir])));

$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
    'autoPageBreak' => false, 'tempDir' => $tempDir,
    'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
]);

$text = '( PROGRAM SELAMA ALOKASI MASIH ADA )';
$targetW = 135.47;
$targetH = 6.35;
$refPt = 10.0 / 0.352778;
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$metric = static function (Mpdf $pdf, string $alias, string $value) use ($refPt): array {
    $pdf->SetFont($alias, '', $refPt);
    return [
        'width' => max(0.01, (float) $pdf->GetStringWidth($value)),
        'cap' => (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0,
    ];
};

$samples = [
    ['MingLiU-ExtB Regular', 'finalmingliu', 0.00],
    ['MingLiU-ExtB Regular + stroke 0.10 pt', 'finalmingliu', 0.10],
    ['MingLiU-ExtB Regular + stroke 0.20 pt', 'finalmingliu', 0.20],
    ['MingLiU-ExtB Regular + stroke 0.30 pt', 'finalmingliu', 0.30],
    ['Poppins SemiBold', 'finalpoppins', 0.00],
    ['Poppins ExtraBold', 'finalextra', 0.00],
];

$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="297mm" height="210mm" viewBox="0 0 297 210" preserveAspectRatio="none">';
$svg .= '<rect x="0" y="0" width="297" height="210" fill="#fff"/>';
$svg .= '<text x="8" y="8" font-family="arial" font-size="5" fill="#222">Segitiga Kecil Turun Harga — Perbandingan font keterangan alokasi</text>';
$svg .= '<text x="8" y="14" font-family="arial" font-size="3.5" fill="#666">Target WordArt: ' . $targetW . ' × ' . $targetH . ' mm | warna #FF0000 | satu elemen SVG text per sampel</text>';

$rows = [];
$startY = 19.0;
$rowStep = 29.0;
foreach ($samples as $index => [$label, $alias, $strokePt]) {
    $y = $startY + ($index * $rowStep);
    $m = $metric($pdf, $alias, $text);
    $sx = $targetW / $m['width'];
    $strokeMm = $strokePt * 0.352778;
    $sy = max(0.01, ($targetH - (2 * $strokeMm)) / max(0.01, $m['cap']));
    $x = 57.0;
    $baseline = $y + $targetH - max(0.20, $strokeMm) - 0.15;
    $rows[] = compact('label', 'alias', 'strokePt', 'y', 'sx', 'sy', 'baseline', 'm');
    $svg .= '<text x="8" y="' . ($y + 3.5) . '" font-family="arial" font-size="4" fill="#333">' . $esc($label) . '</text>';
    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $targetW . '" height="' . $targetH . '" fill="none" stroke="#bdbdbd" stroke-width=".22"/>';
    $svg .= '<g transform="translate(' . $x . ' ' . $baseline . ') scale(' . $sx . ' ' . $sy . ')" fill="#FF0000"';
    if ($strokePt > 0) {
        $svg .= ' stroke="#FF0000" stroke-width="' . $strokeMm . '" stroke-linejoin="round" paint-order="stroke fill"';
    }
    $svg .= '><text x="0" y="0" font-family="' . $alias . '" font-size="10">' . $esc($text) . '</text></g>';
}
$svg .= '</svg>';
$pdf->WriteHTML($svg);

$pdf->Output($output, 'F');
fwrite(STDOUT, "Generated: {$output}\n");
fwrite(STDOUT, "Text: {$text}\n");
foreach ($rows as $row) {
    fwrite(STDOUT, sprintf("%s | font=%s | width=%.5f | cap=%.5f | sx=%.5f | sy=%.5f | baseline=%.5f | stroke=%.2fpt\n", $row['label'], $row['alias'], $row['m']['width'], $row['m']['cap'], $row['sx'], $row['sy'], $row['baseline'], $row['strokePt']));
}
