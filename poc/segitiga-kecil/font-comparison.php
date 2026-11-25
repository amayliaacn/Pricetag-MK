<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$assetFontDir = $root . '/public/assets/fonts';
$windowsFontDir = 'C:/Windows/Fonts';
$output = __DIR__ . '/font-comparison.pdf';

$fontFiles = [
    'berlinsans' => ['label' => 'Berlin Sans FB Demi', 'file' => 'BRLNSDB.TTF', 'path' => $assetFontDir . '/BRLNSDB.TTF'],
    'arialblack' => ['label' => 'Arial Black', 'file' => 'ariblk.ttf', 'path' => $assetFontDir . '/ariblk.ttf'],
    'arialbold' => ['label' => 'Arial Bold', 'file' => 'arialbd.ttf', 'path' => $assetFontDir . '/arialbd.ttf'],
    'poppins' => ['label' => 'Poppins ExtraBold', 'file' => 'Poppins-ExtraBold.ttf', 'path' => $assetFontDir . '/Poppins-ExtraBold.ttf'],
    'calibri' => ['label' => 'Calibri Bold', 'file' => 'calibrib.ttf', 'path' => $windowsFontDir . '/calibrib.ttf'],
    'century' => ['label' => 'Century', 'file' => 'CENTURY.TTF', 'path' => $windowsFontDir . '/CENTURY.TTF'],
];

foreach ($fontFiles as $key => $font) {
    if (! is_file($font['path'])) {
        unset($fontFiles[$key]);
    }
}

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], [$assetFontDir, $windowsFontDir])));

foreach ($fontFiles as $alias => $font) {
    $fontdata[$alias] = ['R' => $font['file']];
}

$pdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'L',
    'margin_left' => 8,
    'margin_right' => 8,
    'margin_top' => 8,
    'margin_bottom' => 8,
    'autoPageBreak' => true,
    'tempDir' => $root . '/writable/mpdf',
    'fontDir' => $fontdirs,
    'fontdata' => $fontdata,
    'default_font' => 'arial',
]);

$esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$names = array_map(static fn (array $font): string => $font['label'] . ' [' . $font['file'] . ']', $fontFiles);

$tests = [
    'PLU : 319787', 'YOG SLURP 80 STRW', 'DISC.', '%', 'Rp',
    'AKHIR PERIODE :', '20 APRIL 2025', 'SELAMA ALOKASI', 'MASIH ADA',
];

$normalSizes = [
    'PLU : 319787' => 32, 'YOG SLURP 80 STRW' => 16, 'DISC.' => 16, '%' => 16,
    'Rp' => 16, 'AKHIR PERIODE :' => 16, '20 APRIL 2025' => 16,
    'SELAMA ALOKASI' => 16, 'MASIH ADA' => 16,
];

// WordArt boxes from the left POP, converted from the audited VML points to mm.
$boxes = [
    'PLU : 319787' => [70.42, 0.09, 49.90, 7.32, 'left'],
    'YOG SLURP 80 STRW' => [4.50, 27.04, 103.87, 9.53, 'left'],
    'DISC.' => [3.18, 46.26, 27.25, 9.53, 'left'],
    '%' => [107.00, 76.76, 16.83, 13.78, 'left'],
    'Rp' => [9.53, 100.19, 8.86, 9.53, 'left'],
    'AKHIR PERIODE :' => [-0.95, 122.24, 50.80, 12.70, 'left'],
    '20 APRIL 2025' => [-0.95, 122.24, 50.80, 12.70, 'left'],
    'SELAMA ALOKASI' => [-0.95, 141.15, 44.45, 12.70, 'left'],
    'MASIH ADA' => [-0.95, 141.15, 44.45, 12.70, 'left'],
];

$setFont = static function (Mpdf $pdf, string $alias, float $size): void {
    $pdf->SetFont($alias, '', $size);
};

$inkScaled = static function (Mpdf $pdf, string $alias, string $text, float $targetW, float $targetH) use ($setFont): array {
    // PopA4Pdf::stretchEl measures a 10 mm reference glyph using 10 / 0.352778 pt.
    // GetStringWidth() returns millimetres, so using 10 pt here would make sx
    // approximately 10x too large and would push the SVG glyph outside its viewBox.
    $baseSize = 10.0 / 0.352778;
    $setFont($pdf, $alias, $baseSize);
    $width = max(0.01, (float) $pdf->GetStringWidth($text));
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0;
    return [
        'sx' => $targetW / $width,
        'sy' => $targetH / max(0.01, $cap),
        'fs' => 10.0,
    ];
};

$svgText = static function (Mpdf $pdf, string $alias, string $text, float $x, float $y, float $w, float $h, bool $syntheticBold = false) use ($esc, $inkScaled): string {
    $scale = $inkScaled($pdf, $alias, $text, $w, $h);
    $copies = $syntheticBold ? [0, 0.18] : [0];
    // x/y are intentionally not emitted here. The WordArt coordinates are
    // reported outside the sample cell; the glyph must be rendered locally.
    $out = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . 'mm" height="' . $h . 'mm" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none">';
    foreach ($copies as $extra) {
        $out .= '<g transform="translate(' . ($extra + 0.2) . ',0) scale(' . $scale['sx'] . ',' . $scale['sy'] . ')" fill="#111">';
        $out .= '<text x="0" y="9.5" font-family="' . $alias . '" font-size="' . $scale['fs'] . '">' . $esc($text) . '</text></g>';
    }
    return $out . '</svg>';
};

$renderGrid = static function (Mpdf $pdf, string $title, array $texts, array $fontFiles, bool $scaled) use ($esc, $normalSizes, $boxes, $svgText, $setFont): void {
    $html = '<style>body{font-family:arial;color:#111}h1{font-size:18pt;margin:0 0 3mm}table.grid{border-collapse:collapse;width:100%;table-layout:fixed}table.grid th,table.grid td{border:.2mm solid #bbb;padding:1mm;vertical-align:top}table.grid th{font-size:7pt;color:#555;text-align:left}table.grid td.label{width:22mm;font-size:8pt;color:#555}.font{font-size:6.5pt;color:#555;line-height:1.05;margin-bottom:.6mm;white-space:nowrap}.sample{height:16mm;overflow:hidden}</style>';
    $html .= '<h1>' . $esc($title) . '</h1><table class="grid"><tr><th>Text</th>';
    foreach ($fontFiles as $font) {
        $html .= '<th>' . $esc($font['label']) . '<br>' . $esc($font['file']) . '</th>';
    }
    $html .= '</tr>';
    foreach ($texts as $text) {
        $html .= '<tr><td class="label">' . $esc($text) . '</td>';
        foreach ($fontFiles as $alias => $font) {
            $html .= '<td><div class="sample">';
            if ($scaled) {
                [$x, $y, $w, $h] = $boxes[$text];
                // Keep the WordArt aspect ratio while fitting the comparison cell.
                $displayW = min($w, 60.0);
                $displayH = $h * ($displayW / max(0.01, $w));
                $html .= $svgText($pdf, $alias, $text, $x, $y, $displayW, $displayH);
            } else {
                $setFont($pdf, $alias, (float) $normalSizes[$text]);
                $html .= '<span style="font-family:' . $alias . ';font-size:' . $normalSizes[$text] . 'pt;">' . $esc($text) . '</span>';
            }
            $html .= '</div></td>';
        }
        $html .= '</tr>';
    }
    $pdf->WriteHTML($html . '</table>');
};

$renderGrid($pdf, 'Segitiga Kecil Diskon — A. Ukuran sama, tanpa stretching', $tests, array_intersect_key($fontFiles, array_flip(['berlinsans', 'arialblack', 'arialbold', 'poppins'])), false);
$pdf->AddPage();
$renderGrid($pdf, 'Segitiga Kecil Diskon — B. Ink bounds + scaling ke bounding box WordArt POP kiri', $tests, array_intersect_key($fontFiles, array_flip(['berlinsans', 'arialblack', 'arialbold', 'poppins'])), true);

$pdf->AddPage();
$pdf->WriteHTML('<style>body{font-family:arial}h1{font-size:18pt}table{border-collapse:collapse;width:100%}td,th{border:.2mm solid #aaa;padding:2mm;font-size:9pt}.sample{min-height:15mm;border:.2mm solid #ddd;padding:2mm}</style>');
$pdf->WriteHTML('<h1>Font tambahan — verifikasi weight dan synthetic bold</h1><table><tr><th>Uji</th><th>Font/file</th><th>Hasil</th></tr>');
$extra = [
    ['Calibri Bold: KIN', 'calibri', 'KIN', false],
    ['Century Regular: 50', 'century', '50', false],
    ['Century synthetic Bold: 50', 'century', '50', true],
    ['Poppins ExtraBold: 6.703', 'poppins', '6.703', false],
    ['Poppins ExtraBold: 3.352', 'poppins', '3.352', false],
    ['Poppins ExtraBold + synthetic Bold: 3.352', 'poppins', '3.352', true],
];
foreach ($extra as [$label, $alias, $text, $bold]) {
    $pdf->WriteHTML('<tr><td>' . $esc($label) . '</td><td>' . $esc($fontFiles[$alias]['label'] . ' [' . $fontFiles[$alias]['file'] . ']') . '</td><td class="sample">');
    $pdf->WriteHTML($svgText($pdf, $alias, $text, 0, 0, 55, 15, $bold));
    $pdf->WriteHTML('</td></tr>');
}
$pdf->WriteHTML('</table>');

$pdf->Output($output, 'F');
fwrite(STDOUT, "Generated: {$output}\n");
fwrite(STDOUT, "Fonts: " . implode(', ', $names) . "\n");

