<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-promo-baseline-production';
if (! is_dir($outDir)) mkdir($outDir, 0777, true);
$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'bookantiqua' => ['R' => 'BKANT.TTF'], 'calistomt' => ['R' => 'CALIST.TTF'],
    'berlinsansfbregular' => ['R' => 'BRLNSR.TTF'], 'arialblack' => ['R' => 'ariblk.ttf'],
    'arialnarrow' => ['R' => 'ARIALN.TTF'], 'arialnarrowb' => ['R' => 'ARIALNB.TTF'],
];
$cases = [
    '11-8249' => ['discount' => 11, 'normal' => '9.268', 'promo' => '8.249'],
    '15-7878' => ['discount' => 15, 'normal' => '9.268', 'promo' => '7.878'],
    '99-93' => ['discount' => 99, 'normal' => '9.268', 'promo' => '93'],
    '99-999' => ['discount' => 99, 'normal' => '99.900', 'promo' => '999'],
];

function stretch($pdf, string $family, string $text, float $widthMm, float $heightMm, float $anchorMm, float $baselineMm, string $align, float $max = 0, float $fs = 10): array
{
    $pdf->SetFont($family, '', $fs / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * $fs;
    $sy = $heightMm / $cap;
    $sx = $widthMm / $w0;
    if ($max > 0) $sx = min($sx, $sy * $max);
    $drawn = $w0 * $sx;
    $x = $align === 'right' ? $anchorMm - $drawn : ($align === 'center' ? $anchorMm - $drawn / 2 : $anchorMm);
    return ['x' => round($x, 3), 'y' => $baselineMm, 'w0' => round($w0, 3), 'sx' => round($sx, 5), 'sy' => round($sy, 5), 'width' => round($drawn, 3), 'right' => round($x + $drawn, 3), 'height' => $heightMm, 'fs' => $fs, 'family' => $family, 'text' => $text];
}

function label($pdf, string $text, float $y, float $scale = 1.0): array
{
    $pdf->SetFont('arialnarrow', '', 2.8 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 2.8;
    return ['x' => 0, 'y' => $y, 'w0' => round($w0, 3), 'sx' => $scale, 'sy' => round(2.8 / $cap, 5), 'width' => round($w0 * $scale, 3), 'right' => 0, 'height' => 2.8, 'fs' => 2.8, 'family' => 'arialnarrow', 'text' => $text];
}

function svgText(array $m, string $color): string
{
    return '<g transform="translate(' . $m['x'] . ',' . $m['y'] . ') scale(' . $m['sx'] . ',' . $m['sy'] . ')"><text x="0" y="0" font-family="' . $m['family'] . '" font-size="' . $m['fs'] . '" fill="' . $color . '">' . htmlspecialchars($m['text'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

$report = [];
foreach ($cases as $caseName => $case) {
    $pdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0, 'fontDir' => $fontDir, 'fontdata' => $fontData]);
    $e = [];
    $e['product'] = stretch($pdf, 'arialblack', 'BANGO KEC RF 260G MANIS', 112, 7, 112.5, 35.2, 'center', 1.1);
    $e['discount'] = stretch($pdf, 'bookantiqua', (string) $case['discount'], 64, 31, 65, 69, 'left', 1.8);
    $e['percent'] = stretch($pdf, 'calistomt', '%', 7.2, 9, 130, 50, 'left');
    $e['plu'] = stretch($pdf, 'arialnarrow', '( PLU : 016450 )', 28, 3.2, 154.5, 41.3, 'center', 1.1);
    $e['ap'] = stretch($pdf, 'arialblack', 'AP : 04 OKT 26', 23, 3.2, 168.5, 46.7, 'right', 1.1);
    $e['normal'] = stretch($pdf, 'bookantiqua', $case['normal'], 18.05, 4.617, 157.55, 54, 'center', 1.3);
    $e['promo'] = stretch($pdf, 'berlinsansfbregular', $case['promo'], 35, 5, 168.5, 65, 'right', 1.9);
    $e['normalRp'] = stretch($pdf, 'calistomt', 'Rp', 4.75, 2.66, 144.05, 52.5, 'center', 1.1);
    $e['promoRp'] = stretch($pdf, 'calistomt', 'Rp', 5, 2.8, $e['promo']['x'] - 1.2, 63.5, 'right');
    $normalLabel = label($pdf, '( Harga Normal )', 56.8);
    $normalCenter = $e['normal']['x'] + $e['normal']['width'] / 2;
    $normalLabel['x'] = $normalCenter - $normalLabel['width'] / 2;
    $normalLabel['right'] = $normalLabel['x'] + $normalLabel['width'];
    $promoLabel = label($pdf, '( Harga Sesudah di Diskon )', 68, 1.225);
    $promoCenter = $e['promo']['x'] + $e['promo']['width'] / 2;
    $promoLabel['x'] = $promoCenter - $promoLabel['width'] / 2;
    $promoLabel['right'] = $promoLabel['x'] + $promoLabel['width'];
    if ($promoLabel['right'] > 168.5) {
        $promoLabel['x'] = 168.5 - $promoLabel['width'];
        $promoLabel['right'] = 168.5;
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
    $svg .= svgText($e['product'], '#00B0F0') . svgText($e['discount'], '#FF0000') . svgText($e['percent'], '#FF0000');
    $svg .= svgText($e['plu'], '#000000') . svgText($e['ap'], '#00B0F0');
    $svg .= svgText($e['normalRp'], '#FF0000') . svgText($e['normal'], '#000000') . svgText($e['promoRp'], '#FF0000') . svgText($e['promo'], '#FF0000');
    $svg .= svgText($normalLabel, '#000000') . svgText($promoLabel, '#000000') . '</svg>';
    $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');
    $path = $outDir . '/A-baseline-' . $caseName . '.pdf';
    $pdf->Output($path, \Mpdf\Output\Destination::FILE);
    $report[] = ['case' => $caseName, 'elements' => $e, 'normalLabel' => $normalLabel, 'promoLabel' => $promoLabel, 'productRight' => 168.5, 'path' => $path];
}
file_put_contents($outDir . '/metrics.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
