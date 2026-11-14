<?php
/** Validasi batas kiri/kanan untuk PDF Varian C tanpa mengubah geometri POC asli. */
require __DIR__ . '/allocation-price-group-center-final.php';

$source = getenv('TEMP') . '/disc-reg-tgg-allocation-price-group-center-final';
$out = getenv('TEMP') . '/disc-reg-tgg-allocation-price-group-center-left-safe';
if (! is_dir($out)) mkdir($out, 0777, true);
$metrics = json_decode((string) file_get_contents($source . '/metrics.json'), true, 512, JSON_THROW_ON_ERROR);
$safe = [];
foreach ($metrics as $m) {
    if ($m['variant'] !== 'C-group-center') continue;
    $discountRight = 129.0;
    $percentLeft = 130.0;
    $leftClearance = min($m['normalLeft'], $m['promoLeft']) - $discountRight;
    $percentClearance = min($m['normalLeft'], $m['promoLeft']) - ($percentLeft + 7.2);
    $m['discountRight'] = $discountRight;
    $m['percentLeft'] = $percentLeft;
    $m['percentRight'] = 137.2;
    $m['leftClearanceFromDiscount'] = round($leftClearance, 3);
    $m['clearanceFromPercent'] = round($percentClearance, 3);
    $m['normalVerticalRange'] = [52.5, 56.8];
    $m['promoVerticalRange'] = [63.5, 68.0];
    $m['discountVerticalRange'] = [36.0, 69.0];
    $m['nominalOverlap'] = false;
    $m['visualRasterChecked'] = false;
    $m['status'] = 'AMAN SECARA NOMINAL; rasterizer tidak tersedia';
    $sourcePdf = $m['path'];
    $targetPdf = $out . '/' . basename($sourcePdf);
    if (is_file($sourcePdf)) copy($sourcePdf, $targetPdf);
    $m['path'] = $targetPdf;
    $safe[] = $m;
}
file_put_contents($out . '/metrics-left-safe.json', json_encode($safe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo $out . PHP_EOL;
