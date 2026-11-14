<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-poc';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'allocbook' => ['R' => 'BKANT.TTF'],
    'allocbookbold' => ['R' => 'ANTQUAB.TTF'],
    'allocarialblack' => ['R' => 'ariblk.ttf'],
    'allocarialnarrow' => ['R' => 'ARIALN.TTF'],
    'allocarialnarrowb' => ['R' => 'ARIALNB.TTF'],
    'allocberlin' => ['R' => 'BRLNSB.TTF'],
    'allocberlinregular' => ['R' => 'BRLNSR.TTF'],
    'alloccalisto' => ['R' => 'CALIST.TTF'],
];

$tests = [
    [
        'name' => 'long-product',
        'productText' => 'BANGO KEC RF 260G MANIS EXTRA LARGE FAMILY PACK',
        'allocationText' => 'Alokasi : 100Pcs',
        'discount' => 11,
    ],
    [
        'name' => 'large-allocation',
        'productText' => 'BANGO KEC RF 260G MANIS',
        'allocationText' => 'Alokasi : 13800Pcs',
        'discount' => 11,
    ],
    [
        'name' => 'long-product-large-allocation-88',
        'productText' => 'BANGO KEC RF 260G MANIS EXTRA LARGE FAMILY PACK',
        'allocationText' => 'Alokasi : 13800Pcs',
        'discount' => 88,
    ],
];

function allocMetric($pdf, string $family, string $text, float $widthMm, float $heightMm, float $xMm, float $maxRatio): array
{
    $pdf->SetFont($family, '', 10 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
    $sy = $heightMm / $cap;
    $sx = $widthMm / $w0;
    if ($maxRatio > 0) {
        $sx = min($sx, $sy * $maxRatio);
    }
    $drawn = $w0 * $sx;

    return [
        'w0_mm' => round($w0, 3),
        'sx' => round($sx, 5),
        'sy' => round($sy, 5),
        'width_mm' => round($drawn, 3),
        'x_mm' => round($xMm - ($drawn / 2), 3),
    ];
}

function allocText(float $x, float $y, array $metric, string $family, string $text, string $color): string
{
    return '<g transform="translate(' . $x . ',' . $y . ') scale(' . $metric['sx'] . ',' . $metric['sy'] . ')">'
        . '<text x="0" y="0" font-family="' . $family . '" font-size="10" fill="' . $color . '">'
        . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

function allocTextWidth($pdf, string $family, string $text, float $fontMm): float
{
    $pdf->SetFont($family, '', $fontMm / 0.352778);
    return $pdf->GetStringWidth($text);
}

$report = [];
foreach ($tests as $test) {
    $discount = $test['discount'];
    $productText = $test['productText'];
    $allocationText = $test['allocationText'];
    $promoText = '8.249';
    $pdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
        'fontDir' => $fontDir,
        'fontdata' => $fontData,
    ]);

    $big = allocMetric($pdf, 'allocbookbold', (string) $discount, 64, 33, 97, 1.8);
    $bigRight = $big['x_mm'] + $big['width_mm'];
    $percent = allocMetric($pdf, 'alloccalisto', '%', 7.2, 9, 0, 0);
    $percentX = 130.0;
    $ap = allocMetric($pdf, 'allocarialblack', 'AP : 04 OKT 26', 23, 3.2, 160, 1.1);
    $apLeft = round(168.5 - $ap['width_mm'], 3);
    $note = allocMetric($pdf, 'allocarialnarrowb', '*Promo Berlaku', 24, 3.6, 40, 1.1);
    $allocation = allocMetric($pdf, 'allocarialnarrowb', $allocationText, 22, 3.2, 40, 1.1);
    $normal = allocMetric($pdf, 'allocbook', '9.268', 19, 4.86, 157.55, 1.3);
    $promo = allocMetric($pdf, 'allocberlinregular', $promoText, 35, 5.0, 153.45, 1.9);
    $normalRp = allocMetric($pdf, 'alloccalisto', 'Rp', 5, 2.8, 144.05, 1.1);
    $promoRp = allocMetric($pdf, 'alloccalisto', 'Rp', 5, 2.8, 134.4, 1.1);
    $product = allocMetric($pdf, 'allocarialblack', $productText, 112, 7.0, 112.5, 1.1);
    $plu = allocMetric($pdf, 'allocarialnarrow', '( PLU : 016450 )', 28, 3.2, 154.5, 1.1);
    $pop = allocMetric($pdf, 'allocarialnarrow', 'JUMLAH POP : 3', 30, 3.2, 160, 1.1);
    $productRight = $product['x_mm'] + $product['width_mm'];
    $promoCenter = $promo['x_mm'] + ($promo['width_mm'] / 2);
    $normalLabelWidth = allocTextWidth($pdf, 'allocarialnarrow', '( Harga Normal )', 2.8);
    $promoLabelWidth = allocTextWidth($pdf, 'allocarialnarrow', '( Harga Sesudah di Diskon )', 2.8);
    $normalLabelX = round($promoCenter - ($normalLabelWidth / 2), 3);
    // Direct SVG text is wider than mPDF's GetStringWidth() estimate here;
    // use the measured rendered-width correction for this label only.
    $promoLabelRenderedWidth = round($promoLabelWidth * 1.225, 3);
    $promoLabelX = round($productRight - $promoLabelRenderedWidth, 3);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
    $svg .= '<text x="10" y="12" font-family="Arial" font-size="3">POC allocation | diskon ' . $discount . '% | alokasi ' . htmlspecialchars($allocationText, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text>';
    $svg .= allocText($product['x_mm'], 35.2, $product, 'allocarialblack', $productText, '#00B0F0');
    $svg .= allocText($plu['x_mm'], 41.3, $plu, 'allocarialnarrow', '( PLU : 016450 )', '#000000');
    $svg .= allocText($pop['x_mm'], 19, $pop, 'allocarialnarrow', 'JUMLAH POP : 3', '#000000');
    // allocMetric() returns the left edge of the transformed advance box;
    // use that edge as the SVG translation instead of the center coordinate.
    $svg .= allocText($big['x_mm'], 69, $big, 'allocbookbold', (string) $discount, '#FF0000');
    $svg .= allocText(39, 45, allocMetric($pdf, 'allocbook', 'Diskon :', 18, 5.5, 37, 1.1), 'allocbook', 'Diskon :', '#FF0000');
    $svg .= allocText(39, 54, $note, 'allocarialnarrowb', '*Promo Berlaku', '#00B0F0');
    $svg .= allocText(39, 58, $note, 'allocarialnarrowb', 'Selama Alokasi', '#00B0F0');
    $svg .= allocText(39, 62, $note, 'allocarialnarrowb', 'Masih Ada', '#00B0F0');
    $svg .= allocText(39, 69.3, $allocation, 'allocarialnarrowb', $allocationText, '#FF0000');
    $svg .= allocText($apLeft, 46.7, $ap, 'allocarialblack', 'AP : 04 OKT 26', '#00B0F0');
    $svg .= allocText($normalRp['x_mm'], 52.5, $normalRp, 'alloccalisto', 'Rp', '#FF0000');
    $svg .= allocText($promoRp['x_mm'], 63.5, $promoRp, 'alloccalisto', 'Rp', '#FF0000');
    $svg .= allocText($normal['x_mm'], 54, $normal, 'allocbook', '9.268', '#000000');
    $svg .= '<line x1="148.05" y1="53.2" x2="167.05" y2="51.0" stroke="#FF0000" stroke-width="0.5" />';
    $svg .= allocText($promo['x_mm'], 65, $promo, 'allocberlinregular', $promoText, '#FF0000');
    $svg .= '<text x="' . $normalLabelX . '" y="56.8" font-family="Arial Narrow" font-size="2.8">( Harga Normal )</text>';
    $svg .= '<text x="' . $promoLabelX . '" y="68" font-family="Arial Narrow" font-size="2.8">( Harga Sesudah di Diskon )</text>';
    $svg .= allocText($percentX, 51, $percent, 'alloccalisto', '%', '#FF0000');
    $svg .= '</svg>';

    $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');
    $path = $outDir . '/allocation-validation-' . $test['name'] . '.pdf';
    $pdf->Output($path, \Mpdf\Output\Destination::FILE);
    $report[] = ['test' => $test['name'], 'discount' => $discount, 'productText' => $productText, 'allocationText' => $allocationText, 'path' => $path, 'big' => $big, 'big_right_mm' => round($bigRight, 3), 'percent_x_mm' => $percentX, 'percent_gap_mm' => round($percentX - $bigRight, 3), 'product' => $product, 'product_right_mm' => round($productRight, 3), 'plu' => $plu, 'pop' => $pop, 'ap' => $ap, 'ap_left_mm' => $apLeft, 'note' => $note, 'allocation' => $allocation, 'normal' => $normal, 'promo' => $promo, 'promo_center_mm' => round($promoCenter, 3), 'normalLabelX' => $normalLabelX, 'promoLabelX' => $promoLabelX, 'normalLabelWidth' => round($normalLabelWidth, 3), 'promoLabelWidth' => round($promoLabelWidth, 3), 'promoLabelRenderedWidth' => $promoLabelRenderedWidth, 'normalRp' => $normalRp, 'promoRp' => $promoRp];
}

file_put_contents($outDir . '/metrics.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
