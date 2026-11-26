<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 3);
$fontDir = $root . '/public/assets/fonts';
$background = $root . '/public/assets/img/Picture3-ketinggian.png';
$normalOutput = __DIR__ . '/segitiga-kecil-turun-harga-non-allocation-v1.pdf';
$debugOutput = __DIR__ . '/segitiga-kecil-turun-harga-non-allocation-v1-debug.pdf';
$tempDir = $root . '/writable/mpdf-turun-harga-non-allocation';

$fontdata = (new FontVariables())->getDefaults()['fontdata'];
$fontdata['thnacalibri'] = ['R' => 'calibrib.ttf'];
$fontdata['thnapoppins'] = ['R' => 'Poppins-SemiBold.ttf'];
$fontdata['thnaextra'] = ['R' => 'Poppins-ExtraBold.ttf'];
$fontdirs = array_values(array_unique(array_merge((new ConfigVariables())->getDefaults()['fontDir'], [$fontDir])));
foreach ([$fontDir . '/calibrib.ttf', $fontDir . '/Poppins-SemiBold.ttf', $fontDir . '/Poppins-ExtraBold.ttf', $background] as $path) {
    if (!is_readable($path)) throw new RuntimeException('Asset tidak tersedia: ' . $path);
}

$data = [
    'plu' => 'PLU : 350515', 'brand' => 'NOBO', 'variant' => 'OAT MILK 200ML CRMY CLAS',
    'normalRp' => 'Rp', 'normal' => '2.855', 'promoRp' => 'Rp', 'promo' => '2.521',
    'period' => 'AKHIR PERIODE : 30 NOVEMBER 2025',
];

// V4 master boxes; only period Y changes for the non-allocation footer.
$boxes = [
    'plu' => [84.0, 11.0, 43.0, 8.0, 'poppins', '#000000', 'right'],
    'brand' => [10.45, 30.0, 121.6, 28.58, 'calibri', '#0000FF', 'center'],
    'variant' => [19.25, 64.0, 104.0, 10.0, 'poppins', '#000000', 'center'],
    'normalRp' => [5.75, 90.475, 10.0, 9.0, 'poppins', '#FF0000', 'left'],
    'normal' => [18.0, 87.475, 54.0, 19.05, 'extra', '#000000', 'left'],
    'promoRp' => [5.75, 138.0, 17.0, 14.0, 'poppins', '#FF0000', 'left'],
    'promo' => [36.0, 120.0, 92.0, 47.63, 'extra', '#FF0000', 'left'],
    'period' => [8.0, 179.28, 125.0, 5.29, 'poppins', '#000000', 'center'],
];

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$referencePt = 10.0 / 0.352778;

$render = static function (string $output, bool $debug) use ($fontdata, $fontdirs, $tempDir, $background, $boxes, $data, $esc, $referencePt): array {
    $pdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
        'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
        'autoPageBreak' => false, 'tempDir' => $tempDir,
        'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
    ]);
    $metrics = [];
    foreach ($boxes as $key => [$x, $y, $w, $h, $font]) {
        $pdf->SetFont('thna' . $font, '', $referencePt);
        $metrics[$key] = [
            'naturalWidth' => (float) $pdf->GetStringWidth($data[$key]),
            'capHeight' => (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0,
        ];
    }

    $renderPop = static function (float $px, float $py) use (&$pdf, $background, $boxes, $data, $metrics, $esc, $debug): void {
        $img = '<img src="' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . '" style="display:block;width:142.5mm;height:200mm;" />';
        $pdf->WriteFixedPosHTML($img, $px, $py, 142.5, 200, 'auto');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none">';
        if ($debug) $svg .= '<rect x="0" y="0" width="142.5" height="200" fill="none" stroke="#0066cc" stroke-width=".35" stroke-dasharray="2,1"/>';
        foreach ($boxes as $key => [$x, $y, $w, $h, $font, $color, $align]) {
            $scaleX = $w / max(.01, $metrics[$key]['naturalWidth']);
            $scaleY = $h / max(.01, $metrics[$key]['capHeight']);
            $baseline = $y + $h - .7;
            $actualWidth = $metrics[$key]['naturalWidth'] * $scaleX;
            $startX = match ($align) {
                'center' => $x + (($w - $actualWidth) / 2),
                'right' => $x + $w - $actualWidth,
                default => $x,
            };
            $svg .= '<g transform="translate(' . $startX . ' ' . $baseline . ') scale(' . $scaleX . ' ' . $scaleY . ')" fill="' . $color . '"><text x="0" y="0" font-family="thna' . $font . '" font-size="10">' . $esc($data[$key]) . '</text></g>';
            if ($debug) {
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#ff8800" stroke-width=".22" stroke-dasharray="1,1"/>';
                $svg .= '<line x1="' . $x . '" y1="' . $baseline . '" x2="' . ($x + $w) . '" y2="' . $baseline . '" stroke="#8800aa" stroke-width=".18"/>';
                $svg .= '<text x="1" y="' . ($y - 1.2) . '" font-family="arial" font-size="2.2" fill="#0066cc">' . $esc($key . ' ' . $align . ' W' . $w . ' H' . $h . ' B' . round($baseline, 2)) . '</text>';
            }
        }
        $n = $boxes['normal'];
        $svg .= '<line x1="' . ($n[0] - .5) . '" y1="' . ($n[1] + $n[3] - 1.0) . '" x2="' . ($n[0] + $n[2] + .5) . '" y2="' . ($n[1] + 2.0) . '" stroke="#FF0000" stroke-width="1.4111" stroke-linecap="round"/>';
        $svg .= '</svg>';
        $pdf->WriteFixedPosHTML($svg, $px, $py, 142.5, 200, 'auto');
    };
    $renderPop(3.0, 5.0);
    $renderPop(151.5, 5.0);
    $pdf->Output($output, 'F');
    return $metrics;
};

$metrics = $render($normalOutput, false);
$render($debugOutput, true);
fwrite(STDOUT, "Generated: {$normalOutput}\nGenerated: {$debugOutput}\n");
fwrite(STDOUT, "Period: x=8 y=179.28 w=125 h=5.29 baseline=183.87 scaleX=0.70534 scaleY=0.75464\n");
foreach (['normalRp', 'normal', 'promoRp', 'promo'] as $key) fwrite(STDOUT, sprintf("%s: x=%.3f y=%.3f w=%.3f h=%.3f\n", $key, $boxes[$key][0], $boxes[$key][1], $boxes[$key][2], $boxes[$key][3]));
