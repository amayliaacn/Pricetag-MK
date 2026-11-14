<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$outDir = getenv('TEMP') . '/disc-reg-tgg-allocation-percent-price-variants';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$fontDir = [dirname(__DIR__, 2) . '/public/assets/fonts', 'C:\Windows\Fonts'];
$fontData = [
    'bookantiquab' => ['R' => 'ANTQUAB.TTF'],
    'bookantiqua' => ['R' => 'BKANT.TTF'],
    'calistomt' => ['R' => 'CALIST.TTF'],
    'arialblack' => ['R' => 'ariblk.ttf'],
    'arialnarrow' => ['R' => 'ARIALN.TTF'],
    'arialnarrowb' => ['R' => 'ARIALNB.TTF'],
    'berlinsansfbregular' => ['R' => 'BRLNSR.TTF'],
];

$variants = [
    'A' => ['percentX' => 130.0, 'percentY' => 51.0, 'priceScale' => 1.00],
    'B' => ['percentX' => 130.0, 'percentY' => 50.5, 'priceScale' => 0.97],
    'C' => ['percentX' => 130.0, 'percentY' => 50.0, 'priceScale' => 0.95],
];
$discounts = [11, 55, 88, 99];
$normalPrices = [999, 9268, 12345];

function metric($pdf, string $family, string $text, float $width, float $height, float $x, float $base, string $align = 'left', float $max = 0): array
{
    $pdf->SetFont($family, '', 10 / 0.352778);
    $w0 = $pdf->GetStringWidth($text);
    $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
    $sy = $height / $cap;
    $sx = $width / $w0;
    if ($max > 0) {
        $sx = min($sx, $sy * $max);
    }
    $drawn = $w0 * $sx;
    $left = match ($align) {
        'center' => $x - $drawn / 2,
        'right' => $x - $drawn,
        default => $x,
    };
    return ['x' => round($left, 3), 'y' => $base, 'w0' => round($w0, 3), 'sx' => round($sx, 5), 'sy' => round($sy, 5), 'width' => round($drawn, 3), 'right' => round($left + $drawn, 3)];
}

function textSvg(array $m, string $family, string $text, string $color, ?string $stroke = null): string
{
    $strokeAttr = $stroke ? ' stroke="' . $stroke . '" stroke-width="0.25"' : '';
    return '<g transform="translate(' . $m['x'] . ',' . $m['y'] . ') scale(' . $m['sx'] . ',' . $m['sy'] . ')"><text x="0" y="0" font-family="' . $family . '" font-size="10" fill="' . $color . '"' . $strokeAttr . '>' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</text></g>';
}

$report = [];
foreach ($variants as $variant => $cfg) {
    foreach ($discounts as $discount) {
        foreach ($normalPrices as $normalPrice) {
            $pdf = new \Mpdf\Mpdf([
                'format' => 'A4', 'margin_left' => 0, 'margin_right' => 0,
                'margin_top' => 0, 'margin_bottom' => 0,
                'fontDir' => $fontDir, 'fontdata' => $fontData,
            ]);

            $discountBox = metric($pdf, 'bookantiquab', (string) $discount, 64, 33, 65, 69, 'left', 1.8);
            $percent = metric($pdf, 'calistomt', '%', 7.2, 9, $cfg['percentX'], $cfg['percentY']);
            $normalText = number_format($normalPrice, 0, ',', '.');
            $normal = metric($pdf, 'bookantiqua', $normalText, 19 * $cfg['priceScale'], 4.86 * $cfg['priceScale'], 157.55, 54, 'center', 1.3);
            $normalRp = metric($pdf, 'calistomt', 'Rp', 5 * $cfg['priceScale'], 2.8 * $cfg['priceScale'], $normal['x'] - 6.5, 52.5, 'left', 1.1);
            $promo = metric($pdf, 'berlinsansfbregular', '8.249', 35, 5, 153.45, 65, 'left', 1.9);
            $productRight = 168.5;
            $promo['x'] = round($productRight - $promo['width'], 3);
            $promo['right'] = round($promo['x'] + $promo['width'], 3);
            $strikeWidth = round($normal['width'], 3);
            $strikeLeft = $normal['x'];
            $strikeRise = $strikeWidth * 0.212;

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="21cm" height="29.7cm" viewBox="0 0 210 297">';
            $svg .= textSvg($discountBox, 'bookantiquab', (string) $discount, '#FF0000', '#FF0000');
            $svg .= textSvg($percent, 'calistomt', '%', '#FF0000');
            $svg .= textSvg($normalRp, 'calistomt', 'Rp', '#FF0000');
            $svg .= textSvg($normal, 'bookantiqua', $normalText, '#000000');
            $svg .= '<line x1="' . $strikeLeft . '" y1="53.2" x2="' . ($strikeLeft + $strikeWidth) . '" y2="' . round(53.2 - $strikeRise, 3) . '" stroke="#FF0000" stroke-width="0.07"/>';
            $svg .= textSvg($promo, 'berlinsansfbregular', '8.249', '#FF0000');
            $svg .= '</svg>';
            $pdf->WriteHTML('<div style="position:absolute;left:0;top:0;width:21cm;height:29.7cm">' . $svg . '</div>');

            $name = 'variant-' . $variant . '-d' . $discount . '-normal-' . $normalPrice . '.pdf';
            $path = $outDir . '/' . $name;
            $pdf->Output($path, \Mpdf\Output\Destination::FILE);
            $report[] = [
                'variant' => $variant, 'discount' => $discount, 'normal_price' => $normalPrice,
                'percent' => $percent, 'discount_box' => $discountBox, 'normal' => $normal,
                'normalRp' => $normalRp, 'strike' => ['left' => $strikeLeft, 'right' => round($strikeLeft + $strikeWidth, 3)],
                'discount_to_percent_gap' => round($percent['x'] - $discountBox['right'], 3),
                'percent_to_normalRp_gap' => round($normalRp['x'] - $percent['right'], 3),
                'percent_to_normal_gap' => round($normal['x'] - $percent['right'], 3),
                'path' => $path,
            ];
        }
    }
}

file_put_contents($outDir . '/metrics.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $outDir . PHP_EOL;
