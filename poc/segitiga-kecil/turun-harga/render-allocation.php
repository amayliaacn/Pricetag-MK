<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 3);
$fontDir = $root . '/public/assets/fonts';
$background = $root . '/public/assets/img/Picture3-ketinggian.png';
$normalOutput = __DIR__ . '/segitiga-kecil-turun-harga-allocation-v4.pdf';
$debugOutput = __DIR__ . '/segitiga-kecil-turun-harga-allocation-v4-debug.pdf';
$tempDir = $root . '/writable/mpdf-turun-harga-allocation';

$fonts = [
    'calibri' => ['file' => 'calibrib.ttf', 'path' => $fontDir . '/calibrib.ttf'],
    'poppins' => ['file' => 'Poppins-SemiBold.ttf', 'path' => $fontDir . '/Poppins-SemiBold.ttf'],
    'extra' => ['file' => 'Poppins-ExtraBold.ttf', 'path' => $fontDir . '/Poppins-ExtraBold.ttf'],
];
foreach ($fonts as $font) {
    if (!is_readable($font['path'])) throw new RuntimeException('Font tidak tersedia: ' . $font['path']);
}
if (!is_readable($background)) throw new RuntimeException('Background tidak tersedia: ' . $background);

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdata['thuruncalibri'] = ['R' => $fonts['calibri']['file']];
$fontdata['thurunpoppins'] = ['R' => $fonts['poppins']['file']];
$fontdata['thurunextra'] = ['R' => $fonts['extra']['file']];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], [$fontDir])));

$data = [
    'brand' => 'NOBO',
    'plu' => 'PLU : 350515',
    'variant' => 'OAT MILK 200ML CRMY CLAS',
    'normalRp' => 'Rp', 'normal' => '2.855',
    'promoRp' => 'Rp', 'promo' => '2.521',
    'period' => 'AKHIR PERIODE : 30 NOVEMBER 2025',
    'allocation' => '( PROGRAM SELAMA ALOKASI MASIH ADA )',
];

// Coordinates are relative to one 142.5 x 200 mm POP.
$boxes = [
    'plu' => [84.0, 11.0, 43.0, 8.0, 'poppins', '#000000', 'right'],
    'brand' => [10.45, 30.0, 121.6, 28.58, 'calibri', '#0000FF', 'center'],
    'variant' => [19.25, 64.0, 104.0, 10.0, 'poppins', '#000000', 'center'],
    'normalRp' => [5.75, 90.475, 10.0, 9.0, 'poppins', '#FF0000', 'left'],
    'normal' => [18.0, 87.475, 54.0, 19.05, 'extra', '#000000', 'left'],
    'promoRp' => [5.75, 138.0, 17.0, 14.0, 'poppins', '#FF0000', 'left'],
    'promo' => [36.0, 120.0, 92.0, 47.63, 'extra', '#FF0000', 'left'],
    'period' => [8.0, 173.0, 125.0, 5.29, 'poppins', '#000000', 'center'],
    'allocation' => [8.25, 184.5, 126.0, 6.35, 'poppins', '#FF0000', 'center'],
];

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$referencePt = 10.0 / 0.352778;

$makePdf = static function (string $path) use ($d, $fontdata, $fontdirs, $tempDir): Mpdf {
    $pdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
        'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
        'autoPageBreak' => false, 'tempDir' => $tempDir,
        'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
    ]);
    return $pdf;
};

$renderPop = static function (Mpdf $pdf, float $pageX, float $pageY, bool $debug) use ($background, $data, $boxes, $esc, $referencePt): array {
    $metrics = [];
    foreach ($boxes as $key => [$x, $y, $w, $h, $font]) {
        $pdf->SetFont('thurun' . $font, '', $referencePt);
        $text = $data[$key];
        $metrics[$key] = [
            'naturalWidth' => (float) $pdf->GetStringWidth($text),
            'capHeight' => (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0,
        ];
    }

    $img = '<img src="' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . '" style="display:block;width:142.5mm;height:200mm;" />';
    $pdf->WriteFixedPosHTML($img, $pageX, $pageY, 142.5, 200, 'auto');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none">';
    if ($debug) {
        $svg .= '<rect x="0" y="0" width="142.5" height="200" fill="none" stroke="#0066cc" stroke-width=".35" stroke-dasharray="2,1"/>';
    }

    foreach ($boxes as $key => [$x, $y, $w, $h, $font, $color, $align]) {
        $text = $data[$key];
        $naturalWidth = $metrics[$key]['naturalWidth'];
        $capHeight = $metrics[$key]['capHeight'];
        $scaleX = $w / max(.01, $naturalWidth);
        $scaleY = $h / max(.01, $capHeight);
        // Keep the approved target box while leaving a small descender allowance.
        $baseline = $y + $h - .7;
        // mPDF's SVG text-anchor handling is not reliable after scaling. The
        // target width is the post-scale visual width, so calculate the start
        // coordinate explicitly and leave text-anchor unset.
        $actualScaledWidth = $naturalWidth * $scaleX;
        $anchorX = match ($align) {
            'center' => $x + (($w - $actualScaledWidth) / 2),
            'right' => $x + $w - $actualScaledWidth,
            default => $x,
        };
        $svg .= '<g transform="translate(' . $anchorX . ' ' . $baseline . ') scale(' . $scaleX . ' ' . $scaleY . ')" fill="' . $color . '">';
        $svg .= '<text x="0" y="0" font-family="thurun' . $font . '" font-size="10">' . $esc($text) . '</text></g>';
        if ($debug) {
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#ff8800" stroke-width=".22" stroke-dasharray="1,1"/>';
            $svg .= '<line x1="' . $x . '" y1="' . $baseline . '" x2="' . ($x + $w) . '" y2="' . $baseline . '" stroke="#8800aa" stroke-width=".18"/>';
            $svg .= '<text x="1" y="' . ($y - 1.2) . '" font-family="arial" font-size="2.2" fill="#0066cc">' . $esc($key . ' ' . $align . ' W' . $w . ' H' . $h . ' B' . round($baseline, 2)) . '</text>';
        }
        $metrics[$key] += ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'baseline' => $baseline, 'scaleX' => $scaleX, 'scaleY' => $scaleY];
    }

    // Strike only the normal price, with endpoints derived from its target visual width.
    $normal = $metrics['normal'];
    $svg .= '<line x1="' . ($normal['x'] - .5) . '" y1="' . ($normal['y'] + $normal['h'] - 1.0) . '" x2="' . ($normal['x'] + $normal['w'] + .5) . '" y2="' . ($normal['y'] + 2.0) . '" stroke="#FF0000" stroke-width="1.4111" stroke-linecap="round"/>';
    if ($debug) {
        $svg .= '<line x1="0" y1="100" x2="142.5" y2="100" stroke="#0066cc" stroke-width=".15" stroke-dasharray="1,2"/>';
        $svg .= '<line x1="2" y1="74" x2="2" y2="87.475" stroke="#00a060" stroke-width=".25"/>';
        $svg .= '<line x1="2" y1="106.525" x2="2" y2="120" stroke="#00a060" stroke-width=".25"/>';
        $svg .= '<text x="3" y="81" font-family="arial" font-size="2.2" fill="#00a060">13.475 mm</text>';
        $svg .= '<text x="3" y="114" font-family="arial" font-size="2.2" fill="#00a060">13.475 mm</text>';
    }
    $svg .= '</svg>';
    $pdf->WriteFixedPosHTML($svg, $pageX, $pageY, 142.5, 200, 'auto');
    return $metrics;
};

$renderDocument = static function (string $output, bool $debug) use ($makePdf, $renderPop): array {
    $pdf = $makePdf($output);
    $left = $renderPop($pdf, 3.0, 5.0, $debug);
    $renderPop($pdf, 151.5, 5.0, $debug);
    $pdf->Output($output, 'F');
    return $left;
};

$normalMetrics = $renderDocument($normalOutput, false);
$debugMetrics = $renderDocument($debugOutput, true);

fwrite(STDOUT, "Generated: {$normalOutput}\nGenerated: {$debugOutput}\n");
foreach ($normalMetrics as $key => $metric) {
    fwrite(STDOUT, sprintf("%s: x=%.2f y=%.2f w=%.2f h=%.2f baseline=%.2f sx=%.5f sy=%.5f natural=%.5f cap=%.5f\n", $key, $metric['x'], $metric['y'], $metric['w'], $metric['h'], $metric['baseline'], $metric['scaleX'], $metric['scaleY'], $metric['naturalWidth'], $metric['capHeight']));
}
