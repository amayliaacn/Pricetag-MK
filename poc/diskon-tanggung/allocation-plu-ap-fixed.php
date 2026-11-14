<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-plu-ap-fixed';
if (! is_dir($outDir)) mkdir($outDir, 0777, true);
$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'bookantiqua' => ['R' => 'BKANT.TTF'], 'calistomt' => ['R' => 'CALIST.TTF'],
    'arialblack' => ['R' => 'ariblk.ttf'], 'arialnarrow' => ['R' => 'ARIALN.TTF'],
];

function metric($pdf, string $family, string $text, float $widthMm, float $heightMm, float $anchorMm, float $baselineMm, string $align, float $max = 0): array
{
    $pdf->SetFont($family, '', 10 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
    $sy = $heightMm / $cap;
    $sx = $widthMm / $w0;
    if ($max > 0) $sx = min($sx, $sy * $max);
    $drawn = $w0 * $sx;
    $x = $align === 'right' ? $anchorMm - $drawn : ($align === 'center' ? $anchorMm - $drawn / 2 : $anchorMm);
    return ['x' => round($x, 3), 'y' => $baselineMm, 'w0' => round($w0, 3), 'sx' => round($sx, 5), 'sy' => round($sy, 5), 'width' => round($drawn, 3), 'right' => round($x + $drawn, 3), 'height' => $heightMm, 'text' => $text, 'family' => $family];
}

function textSvg(array $m, string $color): string
{
    return '<g transform="translate(' . $m['x'] . ',' . $m['y'] . ') scale(' . $m['sx'] . ',' . $m['sy'] . ')"><text x="0" y="0" font-family="' . $m['family'] . '" font-size="10" fill="' . $color . '">' . htmlspecialchars($m['text'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

$product = ['x' => 56.5, 'y' => 35.2, 'width' => 112.0, 'right' => 168.5, 'height' => 7.0];
$variants = [
    'A-current-fixed' => ['apRight' => 160.0, 'basis' => 'current production'],
    'B-ap-right-168_5' => ['apRight' => 168.5, 'basis' => 'AP right edge aligned to product nominal right'],
];
$metrics = [];

foreach ($variants as $name => $variant) {
    $pdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0, 'fontDir' => $fontDir, 'fontdata' => $fontData]);
    $e = [];
    $e['product'] = metric($pdf, 'arialblack', 'BANGO KEC RF 260G MANIS', 112, 7, 112.5, 35.2, 'center', 1.1);
    $e['discount'] = metric($pdf, 'bookantiqua', '99', 64, 31, 65, 69, 'left', 1.8);
    $e['percent'] = metric($pdf, 'calistomt', '%', 7.2, 9, 130, 50, 'left');
    $e['plu'] = metric($pdf, 'arialnarrow', '( PLU : 016450 )', 28, 3.2, 154.5, 41.3, 'center', 1.1);
    $e['ap'] = metric($pdf, 'arialblack', 'AP : 04 OKT 26', 23, 3.2, $variant['apRight'], 46.7, 'right', 1.1);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
    $svg .= textSvg($e['product'], '#00B0F0') . textSvg($e['discount'], '#FF0000') . textSvg($e['percent'], '#FF0000');
    $svg .= textSvg($e['plu'], '#000000') . textSvg($e['ap'], '#00B0F0') . '</svg>';
    $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');
    $path = $outDir . '/' . $name . '.pdf';
    $pdf->Output($path, \Mpdf\Output\Destination::FILE);
    $metrics[] = ['variant' => $name, 'basis' => $variant['basis'], 'product_right' => $product['right'], 'plu' => $e['plu'], 'ap' => $e['ap'], 'discount' => $e['discount'], 'percent' => $e['percent'], 'ap_delta_vs_product_right' => round($e['ap']['right'] - $product['right'], 3), 'path' => $path];
}

file_put_contents($outDir . '/metrics.json', json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
