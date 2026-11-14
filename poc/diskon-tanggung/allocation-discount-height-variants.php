<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-discount-height-variants';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'bookantiqua' => ['R' => 'BKANT.TTF'],
    'calistomt' => ['R' => 'CALIST.TTF'],
    'arialblack' => ['R' => 'ariblk.ttf'],
];
$variants = [
    'A' => ['height' => 33.0, 'baseline' => 69.0],
    // Keep the baseline fixed so the lower edge remains aligned with variant A.
    'B' => ['height' => 32.0, 'baseline' => 69.0],
    'C' => ['height' => 31.0, 'baseline' => 69.0],
];
$discounts = [11, 55, 88, 99];

function heightMetric($pdf, string $family, string $text, float $width, float $height, float $x, float $baseline): array
{
    $pdf->SetFont($family, '', 10 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
    $sy = ($height / $cap);
    $sx = $width / $w0;
    return [
        'x' => $x, 'y' => $baseline, 'w0' => round($w0, 3),
        'sx' => round($sx, 5), 'sy' => round($sy, 5),
        'width' => round($w0 * $sx, 3), 'height' => $height,
        'top' => round($baseline - $height, 3), 'bottom' => $baseline,
    ];
}

function svgText(array $m, string $family, string $text, string $color, ?string $stroke = null): string
{
    $strokeAttr = $stroke ? ' stroke="' . $stroke . '" stroke-width="0.25"' : '';
    return '<g transform="translate(' . $m['x'] . ',' . $m['y'] . ') scale(' . $m['sx'] . ',' . $m['sy'] . ')"><text x="0" y="0" font-family="' . $family . '" font-size="10" fill="' . $color . '"' . $strokeAttr . '>' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

$report = [];
foreach ($variants as $variant => $config) {
    foreach ($discounts as $discount) {
        $pdf = new \Mpdf\Mpdf([
            'format' => 'A4', 'margin_left' => 0, 'margin_right' => 0,
            'margin_top' => 0, 'margin_bottom' => 0,
            'fontDir' => $fontDir, 'fontdata' => $fontData,
        ]);

        $big = heightMetric($pdf, 'bookantiqua', (string) $discount, 64, $config['height'], 65, $config['baseline']);
        $percent = heightMetric($pdf, 'calistomt', '%', 7.2, 9, 130, 50);
        $product = heightMetric($pdf, 'arialblack', 'BANGO KEC RF 260G MANIS', 112, 7, 56.5, 35.2);
        $normal = heightMetric($pdf, 'bookantiqua', '9.268', 18.05, 4.617, 148.525, 54);
        $promo = heightMetric($pdf, 'arialblack', '8.249', 30.109, 5, 138.396, 65);
        $normalRp = heightMetric($pdf, 'calistomt', 'Rp', 4.75, 2.66, 141.675, 52.5);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
        $svg .= svgText($product, 'arialblack', 'BANGO KEC RF 260G MANIS', '#00B0F0');
        $svg .= svgText($big, 'bookantiqua', (string) $discount, '#FF0000', '#FF0000');
        $svg .= svgText($percent, 'calistomt', '%', '#FF0000');
        $svg .= svgText($normalRp, 'calistomt', 'Rp', '#FF0000');
        $svg .= svgText($normal, 'bookantiqua', '9.268', '#000000');
        $strikeRise = $normal['width'] * 0.212;
        $svg .= '<line x1="' . $normal['x'] . '" y1="53.2" x2="' . ($normal['x'] + $normal['width']) . '" y2="' . round(53.2 - $strikeRise, 3) . '" stroke="#FF0000" stroke-width="0.07"/>';
        $svg .= svgText($promo, 'arialblack', '8.249', '#FF0000');
        $svg .= '</svg>';
        $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');

        $path = $outDir . '/height-' . $variant . '-discount-' . $discount . '.pdf';
        $pdf->Output($path, \Mpdf\Output\Destination::FILE);
        $report[] = [
            'variant' => $variant, 'discount' => $discount,
            'discount_box' => $big, 'product_box' => $product,
            'percent_box' => $percent, 'normal_box' => $normal,
            'normal_rp_box' => $normalRp, 'promo_box' => $promo,
            // Product visual ink bounds are not available without rasterization;
            // report the nominal gap from product baseline to discount top.
            'product_baseline_to_discount_top_gap' => round($big['top'] - $product['y'], 3),
            'discount_to_percent_gap' => round($percent['x'] - ($big['x'] + $big['width']), 3),
            'path' => $path,
        ];
    }
}

file_put_contents($outDir . '/metrics.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
