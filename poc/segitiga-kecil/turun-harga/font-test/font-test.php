<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 4);
$assetFontDir = $root . '/public/assets/fonts';
$windowsFontDir = 'C:/Windows/Fonts';
$output = __DIR__ . '/segitiga-kecil-turun-harga-font-test.pdf';
$tempDir = $root . '/writable/mpdf-turun-harga-font-test';

$fonts = [
    'calibri' => ['label' => 'Calibri Bold', 'file' => 'calibrib.ttf', 'path' => $assetFontDir . '/calibrib.ttf'],
    'poppins' => ['label' => 'Poppins SemiBold', 'file' => 'Poppins-SemiBold.ttf', 'path' => $assetFontDir . '/Poppins-SemiBold.ttf'],
    'extra' => ['label' => 'Poppins ExtraBold', 'file' => 'Poppins-ExtraBold.ttf', 'path' => $assetFontDir . '/Poppins-ExtraBold.ttf'],
];

foreach ($fonts as $font) {
    if (!is_readable($font['path'])) {
        throw new RuntimeException('Font tidak tersedia: ' . $font['path']);
    }
}

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdata['turuncalibri'] = ['R' => $fonts['calibri']['file']];
$fontdata['turunpoppins'] = ['R' => $fonts['poppins']['file']];
$fontdata['turunextra'] = ['R' => $fonts['extra']['file']];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], [$assetFontDir, $windowsFontDir])));

$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 10, 'margin_bottom' => 10,
    'autoPageBreak' => false, 'tempDir' => $tempDir,
    'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
]);

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$referencePt = 10.0 / 0.352778;

$fontMetrics = static function (Mpdf $pdf, string $alias, string $text) use ($referencePt): array {
    $pdf->SetFont($alias, '', $referencePt);
    return [
        'width' => (float) $pdf->GetStringWidth($text),
        'capHeight' => (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0,
        'fontBBox' => $pdf->CurrentFont['desc']['FontBBox'] ?? 'unknown',
    ];
};

$svgWordArt = static function (Mpdf $pdf, string $alias, string $text, float $x, float $y, float $w, float $h, string $color, bool $synthetic = false) use ($fontMetrics, $esc): string {
    $m = $fontMetrics($pdf, $alias, $text);
    $sx = $w / max(0.01, $m['width']);
    $sy = $h / max(0.01, $m['capHeight']);
    $baseline = $y + $h + 3.0;
    $svg = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#c8c8c8" stroke-width=".25"/>';
    $svg .= '<g transform="translate(' . $x . ' ' . $baseline . ') scale(' . $sx . ' ' . $sy . ')" fill="' . $color . '">';
    $svg .= '<text x="0" y="0" font-family="' . $alias . '" font-size="10">' . $esc($text) . '</text>';
    if ($synthetic) {
        $svg .= '<text x="0.16" y="0" font-family="' . $alias . '" font-size="10">' . $esc($text) . '</text>';
    }
    return $svg . '</g>';
};

$title = static function (Mpdf $pdf, string $text) use ($esc): void {
    $pdf->WriteHTML('<h1 style="font-size:18pt;margin:0 0 4mm">' . $esc($text) . '</h1>');
};

// Page 1: mapping and natural-font samples.
$title($pdf, 'Segitiga Kecil Turun Harga — Audit Font');
$html = '<style>body{font-family:arial;color:#111}table{border-collapse:collapse;width:100%}th,td{border:.2mm solid #aaa;padding:2mm;vertical-align:top}th{font-size:8pt;background:#f1f1f1}td{font-size:9pt}.sample{font-size:18pt;white-space:nowrap}</style>';
$html .= '<p>Font existing, tanpa penyalinan atau modifikasi. Reference size untuk scaling: 10 / 0.352778 pt.</p><table><tr><th>Peran</th><th>Alias mPDF</th><th>File</th><th>Sampel natural</th></tr>';
$rows = [
    ['Brand', 'turuncalibri', $fonts['calibri']['label'], $fonts['calibri']['file'], 'NOBO'],
    ['PLU / varian / Rp / periode', 'turunpoppins', $fonts['poppins']['label'], $fonts['poppins']['file'], 'PLU : 350515 — OAT MILK 200ML CRMY CLAS — Rp'],
    ['Harga normal / promo', 'turunextra', $fonts['extra']['label'], $fonts['extra']['file'], '2.855 — 2.521'],
];
foreach ($rows as [$role, $alias, $label, $file, $sample]) {
    $html .= '<tr><td>' . $esc($role) . '</td><td>' . $esc($alias) . '</td><td>' . $esc($label . ' [' . $file . ']') . '</td><td class="sample" style="font-family:' . $alias . '">' . $esc($sample) . '</td></tr>';
}
$pdf->WriteHTML($html . '</table>');

// Page 2: Poppins SemiBold target-box scaling.
$pdf->AddPage();
$title($pdf, 'Poppins SemiBold — Kandidat pengganti Geometr415 Blk BT');
$semiTexts = [
    ['PLU : 350515', 49.90, 7.32],
    ['PLU : 003506', 49.90, 7.32],
    ['OAT MILK 200ML CRMY CLAS', 112.48, 9.53],
    ['Rp', 10.48, 12.70],
    ['AKHIR PERIODE : 30 NOVEMBER 2025', 124.75, 5.29],
];
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="273mm" height="175mm" viewBox="0 0 273 175" preserveAspectRatio="none">';
$y = 8.0;
foreach ($semiTexts as [$text, $w, $h]) {
    $svg .= '<text x="0" y="' . ($y + 4) . '" font-family="arial" font-size="4.5" fill="#555">' . $esc($text) . '</text>';
    $svg .= $svgWordArt($pdf, 'turunpoppins', $text, 55, $y, $w, $h, '#111111');
    $y += 28.0;
}
$pdf->WriteHTML($svg . '</svg>');

// Page 3: Poppins ExtraBold price samples and separate thickness comparison.
$pdf->AddPage();
$title($pdf, 'Poppins ExtraBold — Harga normal dan promo');
$prices = ['0', '855', '2.855', '2.521', '25.855', '125.855', '1.250.000'];
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="273mm" height="175mm" viewBox="0 0 273 175" preserveAspectRatio="none">';
$y = 8.0;
foreach ($prices as $price) {
    $svg .= '<text x="0" y="' . ($y + 4) . '" font-family="arial" font-size="4.5" fill="#555">' . $esc($price) . '</text>';
    $svg .= $svgWordArt($pdf, 'turunextra', $price, 25, $y, 72, 19.05, '#111111');
    $svg .= $svgWordArt($pdf, 'turunextra', $price, 125, $y, 72, 19.05, '#E30613');
    $y += 23.0;
}
$svg .= '</svg>';
$pdf->WriteHTML($svg);

// Page 4: explicit synthetic-bold comparison and isolated MingLiU-ExtB TTC test.
$pdf->AddPage();
$title($pdf, 'Ketebalan harga promo dan pengujian MingLiU-ExtB TTC');
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="273mm" height="105mm" viewBox="0 0 273 105" preserveAspectRatio="none">';
$svg .= '<text x="0" y="7" font-family="arial" font-size="5" fill="#555">Poppins ExtraBold — 2.521</text>';
$svg .= $svgWordArt($pdf, 'turunextra', '2.521', 8, 12, 85, 30, '#E30613');
$svg .= '<text x="140" y="7" font-family="arial" font-size="5" fill="#555">Poppins ExtraBold + synthetic bold</text>';
$svg .= $svgWordArt($pdf, 'turunextra', '2.521', 148, 12, 85, 30, '#E30613', true);
$pdf->WriteHTML($svg . '</svg>');

$ttcStatus = 'Belum diuji';
$ttcDetail = '';
try {
    $ttcPdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10,
        'margin_top' => 10, 'margin_bottom' => 10, 'autoPageBreak' => false,
        'tempDir' => $tempDir, 'fontDir' => array_values(array_unique(array_merge($d['fontDir'], [$windowsFontDir]))),
        'fontdata' => array_merge($fd['fontdata'], ['mingliutest' => ['R' => 'mingliub.ttc']]),
        'default_font' => 'arial',
    ]);
    $ttcPdf->SetFont('mingliutest', '', $referencePt);
    $ttcWidth = $ttcPdf->GetStringWidth('( PROGRAM SELAMA ALOKASI MASIH ADA )');
    $ttcStatus = 'Berhasil diregistrasikan dan diukur';
    $ttcDetail = 'GetStringWidth=' . $ttcWidth . ' mm; face/index tidak dapat dipastikan hanya dari nama TTC.';
} catch (Throwable $e) {
    $ttcStatus = 'Gagal';
    $ttcDetail = get_class($e) . ': ' . $e->getMessage();
}
$pdf->WriteHTML('<table style="border-collapse:collapse;width:100%;margin-top:5mm"><tr><th style="border:.2mm solid #aaa;padding:2mm">Font</th><th style="border:.2mm solid #aaa;padding:2mm">File</th><th style="border:.2mm solid #aaa;padding:2mm">Status</th><th style="border:.2mm solid #aaa;padding:2mm">Catatan</th></tr><tr><td style="border:.2mm solid #aaa;padding:2mm">MingLiU-ExtB</td><td style="border:.2mm solid #aaa;padding:2mm">C:\\Windows\\Fonts\\mingliub.ttc</td><td style="border:.2mm solid #aaa;padding:2mm">' . $esc($ttcStatus) . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $esc($ttcDetail) . '</td></tr></table>');

$pdf->Output($output, 'F');

fwrite(STDOUT, "Generated: {$output}\n");
fwrite(STDOUT, "Font paths: {$fonts['calibri']['path']}; {$fonts['poppins']['path']}; {$fonts['extra']['path']}\n");
fwrite(STDOUT, "MingLiU-ExtB TTC: {$ttcStatus}; {$ttcDetail}\n");
