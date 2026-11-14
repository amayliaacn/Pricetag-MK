<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-promo-group-variants';
if (! is_dir($outDir)) mkdir($outDir, 0777, true);
$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'bookantiqua' => ['R' => 'BKANT.TTF'], 'calistomt' => ['R' => 'CALIST.TTF'],
    'berlinsansfbregular' => ['R' => 'BRLNSR.TTF'], 'arialblack' => ['R' => 'ariblk.ttf'],
];
$cases = [
    '11-8249' => ['discount' => 11, 'promo' => '8.249'],
    '15-7878' => ['discount' => 15, 'promo' => '7.878'],
    '99-93' => ['discount' => 99, 'promo' => '93'],
    '99-999' => ['discount' => 99, 'promo' => '999'],
];
$productRight = 168.5;

function metric($pdf, string $family, string $text, float $widthMm, float $heightMm, float $anchorMm, float $baselineMm, string $align, float $max = 0): array
{
    $pdf->SetFont($family, '', 10 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
    $sy = $heightMm / $cap;
    $sx = $widthMm / $w0;
    if ($max > 0) $sx = min($sx, $sy * $max);
    $width = $w0 * $sx;
    $x = $align === 'right' ? $anchorMm - $width : ($align === 'center' ? $anchorMm - $width / 2 : $anchorMm);
    return ['x' => round($x, 3), 'y' => $baselineMm, 'w0' => round($w0, 3), 'sx' => round($sx, 5), 'sy' => round($sy, 5), 'width' => round($width, 3), 'right' => round($x + $width, 3), 'height' => $heightMm, 'family' => $family, 'text' => $text, 'fs' => 10];
}

function svgText(array $m, string $color): string
{
    return '<g transform="translate(' . $m['x'] . ',' . $m['y'] . ') scale(' . $m['sx'] . ',' . $m['sy'] . ')"><text x="0" y="0" font-family="' . $m['family'] . '" font-size="' . ($m['fs'] ?? 10) . '" fill="' . $color . '">' . htmlspecialchars($m['text'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

$metrics = [];
foreach (['A-clamped-current', 'B-group-center'] as $variant) {
    foreach ($cases as $caseName => $case) {
        $pdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0, 'fontDir' => $fontDir, 'fontdata' => $fontData]);
        $promo = metric($pdf, 'berlinsansfbregular', $case['promo'], 35, 5, $productRight, 65, 'right', 1.9);
        // Measure the label exactly as production does, then apply its local
        // horizontal stretch factor without mixing cm and mm.
        $pdf->SetFont('arialblack', '', 2.8 / 0.352778);
        $labelW0 = $pdf->GetStringWidth('( Harga Sesudah di Diskon )');
        $labelCap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
        $label = ['x' => 0, 'y' => 68, 'w0' => round($labelW0, 3), 'sx' => 1.225, 'sy' => round(2.8 / $labelCap, 5), 'width' => round($labelW0 * 1.225, 3), 'right' => 0, 'height' => 2.8, 'family' => 'arialblack', 'text' => '( Harga Sesudah di Diskon )', 'fs' => 2.8];
        $promoCenter = $promo['x'] + $promo['width'] / 2;
        $label['x'] = $promoCenter - $label['width'] / 2;
        $label['right'] = $label['x'] + $label['width'];
        $rp = metric($pdf, 'calistomt', 'Rp', 5, 2.8, $promo['x'] - 1.2, 63.5, 'right');
        $groupRight = max($promo['right'], $label['right']);
        $shift = $variant === 'B-group-center' ? max(0, $groupRight - $productRight) : 0;
        if ($variant === 'A-clamped-current' && $label['right'] > $productRight) {
            $label['x'] = $productRight - $label['width'];
            $label['right'] = $productRight;
        }
        if ($variant === 'B-group-center') {
            $promo['x'] -= $shift; $promo['right'] -= $shift;
            $label['x'] -= $shift; $label['right'] -= $shift;
            $rp['x'] -= $shift; $rp['right'] -= $shift;
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
        $svg .= svgText(['x' => 56.5, 'y' => 35.2, 'sx' => 1, 'sy' => 1, 'family' => 'arialblack', 'text' => 'BANGO KEC RF 260G MANIS'], '#00B0F0');
        $discount = metric($pdf, 'bookantiqua', (string) $case['discount'], 64, 31, 65, 69, 'left', 1.8);
        $svg .= svgText($discount, '#FF0000') . svgText($rp, '#FF0000') . svgText($promo, '#FF0000');
        $svg .= svgText($label, '#000000') . '</svg>';
        $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');
        $path = $outDir . '/' . $variant . '-' . $caseName . '.pdf';
        $pdf->Output($path, \Mpdf\Output\Destination::FILE);
        $metrics[] = ['variant' => $variant, 'case' => $caseName, 'discount' => $case['discount'], 'promo_text' => $case['promo'], 'promo' => $promo, 'label' => $label, 'rp' => $rp, 'promo_center' => round($promo['x'] + $promo['width'] / 2, 3), 'label_center' => round($label['x'] + $label['width'] / 2, 3), 'group_right_before_shift' => round($groupRight, 3), 'shift_left' => round($shift, 3), 'overflows_product_right' => $label['right'] > $productRight, 'product_right' => $productRight, 'path' => $path];
    }
}
file_put_contents($outDir . '/metrics.json', json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
