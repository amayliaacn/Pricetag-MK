<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$fontDir = __DIR__ . '/fonts';
$output = __DIR__ . '/font-weight-simple.pdf';
$fonts = [
    'semi' => ['label' => 'Poppins SemiBold (600)', 'file' => 'Poppins-SemiBold.ttf'],
    'bold' => ['label' => 'Poppins Bold (700)', 'file' => 'Poppins-Bold.ttf'],
    'extra' => ['label' => 'Poppins ExtraBold (800)', 'file' => 'Poppins-ExtraBold.ttf', 'path' => $root . '/public/assets/fonts/Poppins-ExtraBold.ttf'],
];
foreach ($fonts as $key => &$font) {
    $font['path'] ??= $fontDir . '/' . $font['file'];
    if (!is_file($font['path'])) throw new RuntimeException('Font tidak ditemukan: ' . $font['path']);
}
unset($font);

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
foreach ($fonts as $alias => $font) $fontdata[$alias] = ['R' => $font['file']];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], [$fontDir, $root . '/public/assets/fonts'])));
$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10,
    'tempDir' => $root . '/writable/mpdf', 'fontDir' => $fontdirs,
    'fontdata' => $fontdata, 'default_font' => 'arial',
]);
$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$texts = ['PLU : 319787', 'YOG SLURP 80 STRW', 'DISC.', 'Rp', '%', 'AKHIR PERIODE :', '20 APRIL 2025', 'SELAMA ALOKASI', 'MASIH ADA'];
$boxes = [
    'PLU : 319787' => [49.90, 7.32], 'YOG SLURP 80 STRW' => [103.87, 9.53],
    'DISC.' => [27.25, 9.53], 'Rp' => [8.86, 9.53], '%' => [16.83, 13.78],
    'AKHIR PERIODE :' => [50.80, 12.70], '20 APRIL 2025' => [50.80, 12.70],
    'SELAMA ALOKASI' => [44.45, 12.70], 'MASIH ADA' => [44.45, 12.70],
];
$set = static function (Mpdf $pdf, string $alias, float $size): void { $pdf->SetFont($alias, '', $size); };
$stretchEl = static function (Mpdf $pdf, string $alias, string $text, float $wMm, float $hMm, float $xMm, float $baselineMm) use ($set): array {
    $set($pdf, $alias, 10 / .352778);
    $w0 = max(.01, (float)$pdf->GetStringWidth($text));
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0;
    $sx = $wMm / $w0;
    $sy = $hMm / max(.01, $cap);
    return ['family' => $alias, 'fs' => 10.0, 'x' => $xMm, 'y' => $baselineMm, 'sx' => $sx, 'sy' => $sy, 'w0' => $w0, 'cap' => $cap];
};

// Page 1: native mPDF text, identical point size, no transforms.
$html = '<style>table{border-collapse:collapse;width:100%;table-layout:fixed}th,td{border:.2mm solid #aaa;padding:2mm;vertical-align:middle}th{font-size:9pt;color:#555}td.label{width:38mm;font:9pt arial;color:#555}.sample{font-size:20pt;line-height:1.5;white-space:nowrap}</style>';
$html .= '<h1>Font Weight Simple — Natural font</h1><table><tr><th>Text</th>';
foreach ($fonts as $font) $html .= '<th>'.$esc($font['label']).'<br>'.$esc($font['file']).'</th>';
$html .= '</tr>';
foreach ($texts as $text) {
    $html .= '<tr><td class="label">'.$esc($text).'</td>';
    foreach ($fonts as $alias => $font) $html .= '<td class="sample"><span style="font-family:'.$alias.'">'.$esc($text).'</span></td>';
    $html .= '</tr>';
}
$pdf->WriteHTML($html . '</table>');

// Pages 2-4: one SVG coordinate system per page, matching the existing
// renderer: units in the viewBox are millimetres, fs=10 is the reference,
// sx uses GetStringWidth(), and sy uses CapHeight.
$renderWordArtPage = static function (Mpdf $pdf, string $alias, array $font, array $texts, array $boxes, callable $stretchEl, callable $esc): void {
    $pdf->AddPage('L');
    $viewW = 277.0;
    $viewH = 190.0;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="277mm" height="190mm" viewBox="0 0 277 190" preserveAspectRatio="none">';
    $svg .= '<rect x="0" y="0" width="277" height="190" fill="#fff" stroke="#aaa" stroke-width=".2"/>';
    $svg .= '<text x="2" y="7" font-family="arial" font-size="6" fill="#111">Font Weight Simple — WordArt scaling — '.$esc($font['label']).'</text>';
    $rowY = 12.0;
    foreach ($texts as $text) {
        [$w, $h] = $boxes[$text];
        // The baseline is below the cap-height target, leaving 3 mm for descenders.
        $baseline = $rowY + $h + 3.0;
        $t = $stretchEl($pdf, $alias, $text, $w, $h, 48.0, $baseline);
        // Keep the explanatory label outside the WordArt render area.
        // The same x-coordinate is used on pages 2-4 for an objective comparison.
        $svg .= '<text x="165" y="'.($rowY + 5).'" font-family="arial" font-size="7" fill="#555">'.$esc($text).' ['.$w.' × '.$h.' mm]</text>';
        $svg .= '<g transform="translate('.$t['x'].' '.$t['y'].') scale('.$t['sx'].' '.$t['sy'].')" fill="#111">';
        $svg .= '<text x="0" y="0" font-family="'.$t['family'].'" font-size="'.$t['fs'].'">'.$esc($text).'</text></g>';
        $rowY += max(16.0, $h + 7.0);
    }
    $svg .= '</svg>';
    $pdf->WriteHTML($svg);
};

$renderWordArtPage($pdf, 'semi', $fonts['semi'], $texts, $boxes, $stretchEl, $esc);
$renderWordArtPage($pdf, 'bold', $fonts['bold'], $texts, $boxes, $stretchEl, $esc);
$renderWordArtPage($pdf, 'extra', $fonts['extra'], $texts, $boxes, $stretchEl, $esc);
$pdf->Output($output, 'F');
fwrite(STDOUT, "Generated: {$output}\n");
