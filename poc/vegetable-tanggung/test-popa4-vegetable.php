<?php

declare(strict_types=1);

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
define('WRITEPATH', __DIR__ . DIRECTORY_SEPARATOR . 'popa4-test-writable' . DIRECTORY_SEPARATOR);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/app/Libraries/VegetableTanggungRenderer.php';
require dirname(__DIR__, 2) . '/app/Libraries/PopA4Pdf.php';

if (! function_exists('log_message')) {
    function log_message(string $level, string $message, array $context = []): void
    {
        // CodeIgniter menyediakan fungsi ini saat aplikasi berjalan normal;
        // stub kecil ini hanya untuk harness CLI terisolasi.
    }
}

use App\Libraries\PopA4Pdf;

$products = [
    'short' => ['name' => 'BAYAM', 'sku_plu' => '024030', 'normal_price' => 3500],
    'long' => ['name' => 'KENTANG IMPORT PREMIUM PILIHAN', 'sku_plu' => '000099', 'normal_price' => 125000],
    'jeruk' => ['name' => 'JERUK NIPIS/PCL CRH /MRT', 'sku_plu' => '024011', 'normal_price' => 4800],
];

$cases = [
    'qty-1' => [[$products['jeruk'], 1]],
    'qty-3' => [[$products['jeruk'], 3]],
    'qty-mixed' => [[$products['short'], 1], [$products['long'], 2]],
];

foreach ($cases as $case => $entries) {
    $groups = ['vegetable_tgg' => []];
    foreach ($entries as [$product, $qty]) {
        $groups['vegetable_tgg'][] = ['row' => $product, 'qty' => $qty];
    }

    $pdf = (new PopA4Pdf())->renderMixed($groups);
    $path = __DIR__ . '/vegetable-tanggung-popa4-' . $case . '.pdf';
    file_put_contents($path, $pdf, LOCK_EX);
    $ascii = (string) preg_replace('/[^\x20-\x7E\r\n]/', '', $pdf);
    $pages = preg_match_all('/\/Type \/Page\b/', $ascii);
    if ($pages !== array_sum(array_column($entries, 1))) {
        throw new RuntimeException("{$case}: jumlah halaman tidak sesuai.");
    }
    echo $case . ': pages=' . $pages . ' bytes=' . strlen($pdf) . ' file=' . $path . PHP_EOL;
}
