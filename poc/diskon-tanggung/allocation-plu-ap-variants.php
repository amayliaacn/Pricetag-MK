<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-plu-ap-variants';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'bookantiqua' => ['R' => 'BKANT.TTF'],
    'calistomt' => ['R' => 'CALIST.TTF'],
    'arialblack' => ['R' => 'ariblk.ttf'],
    'arialnarrow' => ['R' => 'ARIALN.TTF'],
];

$product = ['x' => 56.5, 'y' => 35.2, 'width' => 112.0, 'height' => 7.0];
$productRight = $product['x'] + $product['width'];
$variants = [
    'A-current' => ['pluAnchor' => 154.5, 'apRight' => 160.0, 'reference' => 'current production'],
    'B-nominal-right' => ['pluAnchor' => $productRight, 'apRight' => $productRight, 'reference' => 'right edge = nominal product right'],
    // LibreOffice visual bounds are not measurable in this environment; C is
    // deliberately based on the measurable nominal-right geometry, not a guess.
    'C-reference-provisional' => ['pluAnchor' => $productRight, 'apRight' => $productRight, 'reference' => 'provisional: LibreOffice bbox unavailable'],
];

function metric($pdf, string $family, string $text, float $w, float $h, float $anchor, float $baseline, string $align, float $max = 0): array
{
    $pdf->SetFont($family, '', 10 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
    $sy = ($h * 10) / $cap;
    $sx = $w / $w0;
    if ($max > 0) $sx = min($sx, $sy * $max);
    $drawn = $w0 * $sx;
    // All layout coordinates in this harness are millimetres (SVG viewBox units).
    $x = $align === 'right' ? $anchor - $drawn : ($align === 'center' ? $anchor - $drawn / 2 : $anchor);
    return ['x' => round($x, 3), 'y' => $baseline, 'w0' => round($w0, 3), 'sx' => round($sx, 5), 'sy' => round($sy, 5), 'width' => round($drawn, 3), 'right' => round($x + $drawn, 3), 'height' => $h * 10, 'family' => $family, 'text' => $text];
}

function svgText(array $m, string $family, string $text, string $color): string
{
    return '<g transform="translate(' . $m['x'] . ',' . $m['y'] . ') scale(' . $m['sx'] . ',' . $m['sy'] . ')"><text x="0" y="0" font-family="' . $family . '" font-size="10" fill="' . $color . '">' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

$report = [];
foreach ($variants as $name => $cfg) {
    $pdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0, 'fontDir' => $fontDir, 'fontdata' => $fontData]);
    $m = [
        'product' => metric($pdf, 'arialblack', 'BANGO KEC RF 260G MANIS', 112, 7, 112.5, 35.2, 'center', 1.1),
        'discount' => metric($pdf, 'bookantiqua', '99', 64, 31, 65, 69, 'left', 1.8),
        'percent' => metric($pdf, 'calistomt', '%', 7.2, 9, 130, 50, 'left'),
        'plu' => metric($pdf, 'arialnarrow', '( PLU : 016450 )', 28, 3.2, $cfg['pluAnchor'], 41.3, $name === 'A-current' ? 'center' : 'right', 1.1),
        'ap' => metric($pdf, 'arialblack', 'AP : 04 OKT 26', 23, 3.2, $cfg['apRight'], 46.7, 'right', 1.1),
    ];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
    $svg .= svgText($m['product'], 'arialblack', $m['product']['text'], '#00B0F0');
    $svg .= svgText($m['discount'], 'bookantiqua', '99', '#FF0000');
    $svg .= svgText($m['percent'], 'calistomt', '%', '#FF0000');
    $svg .= svgText($m['plu'], 'arialnarrow', '( PLU : 016450 )', '#000000');
    $svg .= svgText($m['ap'], 'arialblack', 'AP : 04 OKT 26', '#00B0F0');
    $svg .= '</svg>';
    $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');
    $path = $outDir . '/' . $name . '.pdf';
    $pdf->Output($path, \Mpdf\Output\Destination::FILE);
    $report[] = ['variant' => $name, 'reference_basis' => $cfg['reference'], 'product_right' => $productRight, 'plu' => $m['plu'], 'ap' => $m['ap'], 'discount' => $m['discount'], 'percent' => $m['percent'], 'plu_right_delta_vs_product' => round($m['plu']['right'] - $productRight, 3), 'ap_right_delta_vs_product' => round($m['ap']['right'] - $productRight, 3), 'path' => $path];
}

file_put_contents($outDir . '/metrics.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
