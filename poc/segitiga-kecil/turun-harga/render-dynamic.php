<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 3);
$fontDir = $root . '/public/assets/fonts';
$background = $root . '/public/assets/img/Picture3-ketinggian.png';
$normalOutput = __DIR__ . '/segitiga-kecil-turun-harga-dynamic-v2.pdf';
$debugOutput = __DIR__ . '/segitiga-kecil-turun-harga-dynamic-v2-debug.pdf';
$tempDir = $root . '/writable/mpdf-turun-harga-dynamic';
$referencePt = 10.0 / 0.352778;

$fontdata = (new FontVariables())->getDefaults()['fontdata'];
$fontdata['thdcalibri'] = ['R' => 'calibrib.ttf'];
$fontdata['thdpoppins'] = ['R' => 'Poppins-SemiBold.ttf'];
$fontdata['thdextra'] = ['R' => 'Poppins-ExtraBold.ttf'];
$fontdirs = array_values(array_unique(array_merge((new ConfigVariables())->getDefaults()['fontDir'], [$fontDir])));
foreach ([$fontDir . '/calibrib.ttf', $fontDir . '/Poppins-SemiBold.ttf', $fontDir . '/Poppins-ExtraBold.ttf', $background] as $path) {
    if (!is_readable($path)) throw new RuntimeException('Asset tidak tersedia: ' . $path);
}

$cases = [
    ['A', 'NOBO', 'OAT MILK 200ML CRMY CLAS', '350515', 2855, 2521, '30 NOVEMBER 2025', 100],
    ['B', 'NOBO', 'OAT MILK 200ML CRMY CLAS', '350515', 2855, 2521, '30 NOVEMBER 2025', null],
    ['C', 'ABC', 'KECAP MANIS 20ML', '001234', 950, 500, '31 JANUARI 2027', 10],
    ['D', 'INDOMIE', 'MI GORENG ORIGINAL 85GR', '022130', 12500, 9900, '28 FEBRUARI 2027', 0],
    ['E', 'KAPAL API SPECIAL', 'KOPI BUBUK SPECIAL MIX 10 X 25GR', '12345678', 35900, 29900, '30 SEPTEMBER 2027', 250],
    ['F', 'ULTRA MILK', null, '000125', 150000, 125900, '31 DESEMBER 2027', null],
    ['G', 'SUN', 'PAKET PRODUK PROMO', '000001', 1250000, 999900, '31 AGUSTUS 2027', 1000],
    ['H', 'TEST PRODUCT', '', '000010', 10, 0, '30 NOVEMBER 2027', null],
];

$boxes = [
    'plu' => [84.0, 11.0, 43.0, 8.0, 'poppins', '#000000', 'right'],
    'brand' => [10.45, 30.0, 121.6, 28.58, 'calibri', '#0000FF', 'center'],
    'variant' => [19.25, 64.0, 104.0, 10.0, 'poppins', '#000000', 'center'],
    'normalRp' => [5.75, 90.475, 10.0, 9.0, 'poppins', '#FF0000', 'left'],
    'normal' => [18.0, 87.475, 54.0, 19.05, 'extra', '#000000', 'left'],
    'promoRp' => [5.75, 138.0, 17.0, 14.0, 'poppins', '#FF0000', 'left'],
    'promo' => [36.0, 120.0, 92.0, 47.63, 'extra', '#FF0000', 'left'],
    'periodAlloc' => [8.0, 173.0, 125.0, 5.29, 'poppins', '#000000', 'center'],
    'periodNoAlloc' => [8.0, 179.28, 125.0, 5.29, 'poppins', '#000000', 'center'],
    'allocation' => [8.25, 184.5, 126.0, 6.35, 'poppins', '#FF0000', 'center'],
];

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$formatDate = static function (string $value): string {
    $months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    if (!preg_match('/^\s*(\d{1,2})\s+([^\s]+)\s+(\d{4})\s*$/u', $value, $m)) throw new InvalidArgumentException('end_period tidak valid: ' . $value);
    $month = mb_strtoupper($m[2], 'UTF-8');
    if (!in_array($month, $months, true)) throw new InvalidArgumentException('Bulan end_period tidak valid: ' . $value);
    return (int) $m[1] . ' ' . $month . ' ' . $m[3];
};

$normalize = static function (array $row) use ($formatDate): array {
    [$case, $brand, $variant, $plu, $normal, $promo, $end, $allocation] = $row;
    if (!is_string($plu) || !preg_match('/^\d+$/', $plu)) throw new InvalidArgumentException("Case {$case}: sku_plu harus string digit.");
    if (!is_numeric($normal) || (int) $normal < 0 || !is_numeric($promo) || (int) $promo < 0) throw new InvalidArgumentException("Case {$case}: harga tidak valid.");
    if ($allocation !== null && $allocation !== '' && (!is_numeric($allocation) || (float) $allocation < 0)) throw new InvalidArgumentException("Case {$case}: allocation_pcs invalid.");
    $variant = is_string($variant) && trim($variant) !== '' ? mb_strtoupper(trim($variant), 'UTF-8') : null;
    return [
        'case' => $case, 'brand' => mb_strtoupper(trim($brand), 'UTF-8'), 'variant' => $variant,
        'plu' => 'PLU : ' . $plu, 'normal' => number_format((int) $normal, 0, ',', '.'),
        'promo' => number_format((int) $promo, 0, ',', '.'), 'period' => 'AKHIR PERIODE : ' . $formatDate($end),
        'allocation' => '( PROGRAM SELAMA ALOKASI MASIH ADA )',
        'mode' => is_numeric($allocation) && (float) $allocation > 0 ? 'Allocation' : 'Non-Allocation',
    ];
};

$renderDocument = static function (string $output, bool $debug) use ($fontdata, $fontdirs, $tempDir, $background, $boxes, $normalize, $cases, $referencePt, $esc): array {
    $pdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
        'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
        'autoPageBreak' => false, 'tempDir' => $tempDir,
        'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
    ]);
    $log = [];
    foreach ($cases as $row) {
        if ($pdf->page > 0) $pdf->AddPage('L');
        $d = $normalize($row);
        $mode = $d['mode'];
        $metrics = [];
        $texts = ['plu' => $d['plu'], 'brand' => $d['brand'], 'normalRp' => 'Rp', 'normal' => $d['normal'], 'promoRp' => 'Rp', 'promo' => $d['promo'], 'period' => $d['period']];
        if ($d['variant'] !== null) $texts['variant'] = $d['variant'];
        if ($mode === 'Allocation') $texts['allocation'] = $d['allocation'];
        $periodKey = $mode === 'Allocation' ? 'periodAlloc' : 'periodNoAlloc';

        $renderPop = static function (float $px, float $py) use (&$pdf, $background, $boxes, $texts, $periodKey, &$metrics, $referencePt, $esc, $debug, $mode): void {
            $img = '<img src="' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . '" style="display:block;width:142.5mm;height:200mm;" />';
            $pdf->WriteFixedPosHTML($img, $px, $py, 142.5, 200, 'auto');
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none">';
            if ($debug) $svg .= '<rect x="0" y="0" width="142.5" height="200" fill="none" stroke="#0066cc" stroke-width=".35" stroke-dasharray="2,1"/>';
            $normalMetric = null;
            foreach ($texts as $key => $text) {
                $boxKey = $key === 'period' ? $periodKey : $key;
                [$x, $y, $w, $h, $font, $color, $align] = $boxes[$boxKey];
                $pdf->SetFont('thd' . $font, '', $referencePt);
                $natural = max(.01, (float) $pdf->GetStringWidth($text));
                $cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0;
                $referenceText = match ($key) { 'brand' => 'NOBO', 'variant' => 'OAT MILK 200ML CRMY CLAS', 'normal' => '2.855', 'promo' => '2.521', default => $text };
                $pdf->SetFont('thd' . $font, '', $referencePt);
                $referenceWidth = max(.01, (float) $pdf->GetStringWidth($referenceText));
                $width = $w;
                if (in_array($key, ['brand', 'variant', 'normal', 'promo'], true)) {
                    $width = min($w, max($key === 'variant' ? 18 : 12, $natural * ($w / $referenceWidth)));
                }
                $scaleX = $width / $natural;
                $scaleY = $h / max(.01, $cap);
                $baseline = $y + $h - .7;
                $startX = match ($align) {
                    'center' => $x + (($w - $width) / 2),
                    'right' => $x + $w - $width,
                    default => $x,
                };
                $svg .= '<g transform="translate(' . $startX . ' ' . $baseline . ') scale(' . $scaleX . ' ' . $scaleY . ')" fill="' . $color . '"><text x="0" y="0" font-family="thd' . $font . '" font-size="10">' . $esc($text) . '</text></g>';
                if ($debug) {
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#ff8800" stroke-width=".22" stroke-dasharray="1,1"/>';
                    $svg .= '<text x="1" y="' . ($y - 1.2) . '" font-family="arial" font-size="2.2" fill="#0066cc">' . $esc($key . ' ' . $align . ' W' . round($width, 2) . ' B' . round($baseline, 2)) . '</text>';
                }
                $metrics[$key] = ['targetW' => $w, 'naturalW' => $natural, 'actualX' => $startX, 'actualW' => $width, 'scaleX' => $scaleX, 'scaleY' => $scaleY, 'baseline' => $baseline];
                if ($key === 'normal') $normalMetric = [$startX, $y, $width, $h];
            }
            if ($normalMetric !== null) {
                [$x, $y, $w, $h] = $normalMetric;
                $svg .= '<line x1="' . ($x - .5) . '" y1="' . ($y + $h - 1) . '" x2="' . ($x + $w + .5) . '" y2="' . ($y + 2) . '" stroke="#FF0000" stroke-width="1.4111" stroke-linecap="round"/>';
                if ($debug) {
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#00a060" stroke-width=".28" stroke-dasharray="1,1"/>';
                    $svg .= '<line x1="' . ($x - .5) . '" y1="' . ($y + $h + 1.5) . '" x2="' . ($x + $w + .5) . '" y2="' . ($y + $h + 1.5) . '" stroke="#00a060" stroke-width=".2"/>';
                }
            }
            $svg .= '</svg>';
            $pdf->WriteFixedPosHTML($svg, $px, $py, 142.5, 200, 'auto');
        };
        $renderPop(3.0, 5.0);
        $renderPop(151.5, 5.0);
        $log[] = ['case' => $d['case'], 'mode' => $mode, 'variant' => $d['variant'] !== null, 'metrics' => $metrics];
    }
    $pdf->Output($output, 'F');
    return $log;
};

$normalLog = $renderDocument($normalOutput, false);
$debugLog = $renderDocument($debugOutput, true);
fwrite(STDOUT, "Generated: {$normalOutput}\nGenerated: {$debugOutput}\n");
foreach ($normalLog as $entry) {
    $n = $entry['metrics']['normal']; $p = $entry['metrics']['promo'];
    $x1 = $n['actualX'] - .5; $x2 = $n['actualX'] + $n['actualW'] + .5;
    fwrite(STDOUT, sprintf("Case %s | %s | variant=%s | normal %.3f->%.3f x=%.3f sx=%.5f sy=%.5f | strike=(%.3f,105.525)->(%.3f,89.475) length=%.3f\n", $entry['case'], $entry['mode'], $entry['variant'] ? 'yes' : 'no', $n['naturalW'], $n['actualW'], $n['actualX'], $n['scaleX'], $n['scaleY'], $x1, $x2, $x2 - $x1));
}
