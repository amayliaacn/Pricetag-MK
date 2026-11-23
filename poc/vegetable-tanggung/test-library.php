<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/app/Libraries/VegetableTanggungRenderer.php';

define('WRITEPATH', __DIR__ . '/test-writable/');

use App\Libraries\VegetableTanggungRenderer;
use Mpdf\Mpdf;

$products = [
    'A' => ['name' => 'JERUK NIPIS/PCL CRH /MRT', 'sku_plu' => '024011', 'price' => 4800, 'unit' => '/100gr'],
    'B' => ['name' => 'BAYAM', 'sku_plu' => '024030', 'price' => 3500, 'unit' => '/100gr'],
    'C' => ['name' => 'WORTEL IMPORT', 'sku_plu' => '024025', 'price' => 6500, 'unit' => '/100gr'],
    'D' => ['name' => 'KENTANG IMPORT PREMIUM PILIHAN', 'sku_plu' => '024099', 'price' => 125000, 'unit' => '/100gr'],
];

$renderer = new VegetableTanggungRenderer();
$pdf = new Mpdf([
    'format' => [210, 297],
    'orientation' => 'P',
    'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
    'autoPageBreak' => false,
    'tempDir' => dirname(__DIR__, 2) . '/writable/mpdf',
]);

foreach ($products as $case => $product) {
    // mPDF membuat buffer internal awal saat konstruksi. Seperti POC asli,
    // selalu buka page eksplisit sebelum menulis agar page pertama ikut masuk
    // ke page tree final dan tidak tertimpa saat serialisasi.
    $pdf->AddPage();
    $renderer->renderOnPage($pdf, $product);
    echo $case . ': rendered' . PHP_EOL;
}

$output = __DIR__ . '/vegetable-tanggung-library-abcd.pdf';
file_put_contents($output, $pdf->Output('', 'S'), LOCK_EX);
echo 'Output: ' . $output . PHP_EOL;
