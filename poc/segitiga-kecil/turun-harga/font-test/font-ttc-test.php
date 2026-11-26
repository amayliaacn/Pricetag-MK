<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 4);
$ttcPath = 'C:/Windows/Fonts/mingliub.ttc';
$assetFontDir = $root . '/public/assets/fonts';
$windowsFontDir = 'C:/Windows/Fonts';
$output = __DIR__ . '/segitiga-kecil-turun-harga-font-ttc-test.pdf';
$tempDir = $root . '/writable/mpdf-turun-harga-font-ttc-test';

if (!is_readable($ttcPath)) {
    throw new RuntimeException('TTC tidak tersedia: ' . $ttcPath);
}

function u16(string $data, int $offset): int
{
    return unpack('n', substr($data, $offset, 2))[1];
}

function u32(string $data, int $offset): int
{
    return unpack('N', substr($data, $offset, 4))[1];
}

function decodeName(string $raw, int $platform): string
{
    if ($platform === 0 || $platform === 3) {
        $out = '';
        for ($i = 0; $i + 1 < strlen($raw); $i += 2) {
            $out .= mb_convert_encoding(substr($raw, $i, 2), 'UTF-8', 'UTF-16BE');
        }
        return $out;
    }
    return trim($raw);
}

function readTtcFaces(string $path): array
{
    $data = file_get_contents($path);
    if ($data === false || substr($data, 0, 4) !== 'ttcf') {
        throw new RuntimeException('Header TTC tidak valid.');
    }

    $num = u32($data, 8);
    $faces = [];
    for ($face = 1; $face <= $num; $face++) {
        $offset = u32($data, 12 + (($face - 1) * 4));
        $numTables = u16($data, $offset + 4);
        $tables = [];
        for ($i = 0; $i < $numTables; $i++) {
            $entry = $offset + 12 + ($i * 16);
            $tag = substr($data, $entry, 4);
            $tables[$tag] = ['offset' => u32($data, $entry + 8), 'length' => u32($data, $entry + 12)];
        }

        $names = [];
        if (isset($tables['name'])) {
            $base = $tables['name']['offset'];
            $count = u16($data, $base + 2);
            $stringOffset = u16($data, $base + 4);
            for ($i = 0; $i < $count; $i++) {
                $rec = $base + 6 + ($i * 12);
                $platform = u16($data, $rec);
                $nameId = u16($data, $rec + 6);
                if (!in_array($nameId, [1, 2, 4, 6], true)) continue;
                $length = u16($data, $rec + 8);
                $offsetInString = u16($data, $rec + 10);
                $raw = substr($data, $base + $stringOffset + $offsetInString, $length);
                $value = decodeName($raw, $platform);
                if ($value !== '') $names[$nameId] ??= $value;
            }
        }

        $weight = null;
        $fsType = null;
        if (isset($tables['OS/2'])) {
            $os2 = $tables['OS/2']['offset'];
            $weight = u16($data, $os2 + 4);
            $fsType = u16($data, $os2 + 8);
        }

        $faces[] = [
            'index' => $face,
            'offset' => $offset,
            'family' => $names[1] ?? '?',
            'subfamily' => $names[2] ?? '?',
            'fullName' => $names[4] ?? '?',
            'postScript' => $names[6] ?? '?',
            'weight' => $weight,
            'fsType' => $fsType,
            'latin' => isset($tables['cmap']),
        ];
    }
    return $faces;
}

$faces = readTtcFaces($ttcPath);
$faceSummary = implode(' | ', array_map(static function (array $face): string {
    return sprintf('#%d %s / %s / weight=%s / fsType=%s', $face['index'], $face['family'], $face['subfamily'], $face['weight'] ?? '?', $face['fsType'] ?? '?');
}, $faces));

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdata['ttcmingliu'] = [
    'R' => 'mingliub.ttc',
    'TTCfontID' => ['R' => 1],
];
$fontdata['ttcpoppins'] = ['R' => 'Poppins-SemiBold.ttf'];
$fontdata['ttcextra'] = ['R' => 'Poppins-ExtraBold.ttf'];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], [$assetFontDir, $windowsFontDir])));

$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 10, 'margin_bottom' => 10,
    'autoPageBreak' => false, 'tempDir' => $tempDir,
    'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
]);
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$refPt = 10.0 / 0.352778;
$text = '( PROGRAM SELAMA ALOKASI MASIH ADA )';
$targetW = 135.47;
$targetH = 6.35;

$metrics = static function (Mpdf $pdf, string $alias, string $value) use ($refPt): array {
    $pdf->SetFont($alias, '', $refPt);
    return [
        'width' => (float) $pdf->GetStringWidth($value),
        'cap' => (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10,
        'font' => $pdf->CurrentFont['desc']['name'] ?? $alias,
    ];
};

$ttcStatus = 'Gagal';
$ttcError = '';
$ttcMetrics = null;
try {
    $ttcMetrics = $metrics($pdf, 'ttcmingliu', $text);
    $ttcStatus = 'Berhasil';
} catch (Throwable $e) {
    $ttcError = get_class($e) . ': ' . $e->getMessage();
}

$svgText = static function (Mpdf $pdf, string $alias, string $value, float $x, float $y, float $w, float $h, string $color, bool $scale) use ($metrics, $esc): string {
    $m = $metrics($pdf, $alias, $value);
    $sx = $scale ? $w / max(0.01, $m['width']) : 1.0;
    $sy = $scale ? $h / max(0.01, $m['cap']) : 1.0;
    $baseline = $scale ? $y + $h + 3 : $y + 10;
    return '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#ccc" stroke-width=".25"/>'
        . '<g transform="translate(' . $x . ' ' . $baseline . ') scale(' . $sx . ' ' . $sy . ')" fill="' . $color . '">'
        . '<text x="0" y="0" font-family="' . $alias . '" font-size="10">' . $esc($value) . '</text></g>';
};

$pdf->WriteHTML('<h1 style="font-size:18pt">MingLiU-ExtB TTC — face audit dan rendering</h1>');
$pdf->WriteHTML('<p>File: ' . $esc($ttcPath) . '<br>Jumlah face: ' . count($faces) . '<br>' . $esc($faceSummary) . '<br>mPDF 8.3.1 mengharuskan TTCfontID dimulai dari 1.</p>');
$pdf->WriteHTML('<table style="border-collapse:collapse;width:100%"><tr><th style="border:.2mm solid #aaa;padding:2mm">Index</th><th style="border:.2mm solid #aaa;padding:2mm">Family</th><th style="border:.2mm solid #aaa;padding:2mm">Subfamily</th><th style="border:.2mm solid #aaa;padding:2mm">Full name</th><th style="border:.2mm solid #aaa;padding:2mm">PostScript</th><th style="border:.2mm solid #aaa;padding:2mm">Weight</th><th style="border:.2mm solid #aaa;padding:2mm">fsType</th></tr>' . implode('', array_map(static fn(array $f): string => '<tr><td style="border:.2mm solid #aaa;padding:2mm">' . $f['index'] . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $esc($f['family']) . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $esc($f['subfamily']) . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $esc($f['fullName']) . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $esc($f['postScript']) . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $f['weight'] . '</td><td style="border:.2mm solid #aaa;padding:2mm">' . $f['fsType'] . '</td></tr>', $faces)) . '</table>');
$pdf->WriteHTML('<p><strong>Registrasi face 1:</strong> ' . $esc($ttcStatus) . '<br>' . ($ttcMetrics ? 'GetStringWidth=' . $ttcMetrics['width'] . ' mm; CapHeight=' . $ttcMetrics['cap'] . ' mm; font=' . $esc($ttcMetrics['font']) : $esc($ttcError)) . '</p>');

$pdf->AddPage();
$pdf->WriteHTML('<h1 style="font-size:18pt">Perbandingan visual — warna merah</h1>');
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="273mm" height="170mm" viewBox="0 0 273 170" preserveAspectRatio="none">';
$svg .= '<text x="0" y="7" font-family="arial" font-size="5" fill="#555">MingLiU-ExtB natural (face 1)</text>';
if ($ttcStatus === 'Berhasil') $svg .= $svgText($pdf, 'ttcmingliu', $text, 5, 12, 125, 12, '#E30613', false);
else $svg .= '<text x="5" y="25" font-family="arial" font-size="6" fill="#b00000">TTC gagal: ' . $esc($ttcError) . '</text>';
$svg .= '<text x="140" y="7" font-family="arial" font-size="5" fill="#555">MingLiU-ExtB scaling 135,47 × 6,35 mm</text>';
if ($ttcStatus === 'Berhasil') $svg .= $svgText($pdf, 'ttcmingliu', $text, 140, 12, $targetW, $targetH, '#E30613', true);
$svg .= '<text x="0" y="55" font-family="arial" font-size="5" fill="#555">Poppins SemiBold pembanding</text>' . $svgText($pdf, 'ttcpoppins', $text, 5, 60, 125, 12, '#E30613', false);
$svg .= '<text x="140" y="55" font-family="arial" font-size="5" fill="#555">Poppins ExtraBold pembanding</text>' . $svgText($pdf, 'ttcextra', $text, 140, 60, 125, 12, '#E30613', false);
$pdf->WriteHTML($svg . '</svg>');

$pdf->AddPage();
$pdf->WriteHTML('<h1 style="font-size:18pt">SVG scaling dan teks native mPDF</h1>');
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="273mm" height="170mm" viewBox="0 0 273 170" preserveAspectRatio="none">';
if ($ttcStatus === 'Berhasil') $svg .= '<text x="5" y="12" font-family="arial" font-size="5" fill="#555">TTC face 1 — SVG scaling target</text>' . $svgText($pdf, 'ttcmingliu', $text, 5, 18, $targetW, $targetH, '#E30613', true);
$svg .= '</svg>';
$pdf->WriteHTML($svg);
if ($ttcStatus === 'Berhasil') {
    $pdf->SetFont('ttcmingliu', '', $refPt);
    $pdf->WriteHTML('<p style="color:#e30613;font-size:10pt">Native mPDF: ' . $esc($text) . '</p>');
}

$pdf->Output($output, 'F');
fwrite(STDOUT, "Generated: {$output}\n");
fwrite(STDOUT, "Faces: " . count($faces) . "\n");
foreach ($faces as $face) {
    fwrite(STDOUT, sprintf("Face #%d: family=%s; subfamily=%s; full=%s; postscript=%s; weight=%s; fsType=%s; cmap=%s\n", $face['index'], $face['family'], $face['subfamily'], $face['fullName'], $face['postScript'], $face['weight'] ?? '?', $face['fsType'] ?? '?', $face['latin'] ? 'yes' : 'no'));
}
fwrite(STDOUT, "TTC status: {$ttcStatus}; " . ($ttcError ?: 'GetStringWidth=' . ($ttcMetrics['width'] ?? 'n/a') . " mm\n"));
