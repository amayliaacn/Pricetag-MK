<?php

declare(strict_types=1);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// The running turun-harga-tgg template uses an A4 portrait sheet.
$width = 210;
$height = 297;
$pdf = new Mpdf([
    'mode' => 'c',
    'format' => [$width, $height],
    'orientation' => 'P',
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
    'tempDir' => __DIR__,
]);
$pdf->SetTitle('Vegetable Tanggung - 1 Produk');
$pdf->AddPage();

// The SVG uses page coordinates measured from the rendered DOCX, including
// the original WordArt outlines. Its PLU outline is the WPS font fallback.
$referenceWidthPt = 595.3;
$referenceHeightPt = 841.9;
$mmPerPoint = 25.4 / 72;
$pdf->Image(
    __DIR__ . '/vegetable-tanggung.svg',
    0,
    0,
    $referenceWidthPt * $mmPerPoint,
    $referenceHeightPt * $mmPerPoint,
    'svg',
    '',
    true,
    false,
    false,
    false
);

$output = __DIR__ . '/vegetable-tanggung.pdf';
$pdf->Output($output, Destination::FILE);
fwrite(STDOUT, $output . PHP_EOL);
