<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$fontPath = 'C:/Windows/Fonts/CENTURY.TTF';
$output = __DIR__ . '/century-comparison.pdf';
if (!is_file($fontPath)) {
    throw new RuntimeException('Century Regular tidak ditemukan: ' . $fontPath);
}

$d = (new ConfigVariables())->getDefaults();
$fd = (new FontVariables())->getDefaults();
$fontdata = $fd['fontdata'];
$fontdata['centurytest'] = ['R' => 'CENTURY.TTF'];
$fontdirs = array_values(array_unique(array_merge($d['fontDir'], ['C:/Windows/Fonts'])));
$pdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'L',
    'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10,
    'autoPageBreak' => false, 'tempDir' => $root . '/writable/mpdf',
    'fontDir' => $fontdirs, 'fontdata' => $fontdata, 'default_font' => 'arial',
]);

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$alias = 'centurytest';
$text = '50';
$targetW = 63.50;
$targetH = 46.78;
$pdf->SetFont($alias, '', 10 / .352778);
$w0 = max(.01, (float) $pdf->GetStringWidth($text));
$cap = (($pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0;
$sx = $targetW / $w0;
$sy = $targetH / max(.01, $cap);
$fontBox = $pdf->CurrentFont['desc']['FontBBox'] ?? 'unknown';

$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="277mm" height="190mm" viewBox="0 0 277 190" preserveAspectRatio="none">';
$svg .= '<rect x="0" y="0" width="277" height="190" fill="#fff" stroke="#aaa" stroke-width=".2"/>';
$svg .= '<text x="4" y="8" font-family="arial" font-size="6" fill="#111">Century Regular — angka diskon 50 — target '.$targetW.' × '.$targetH.' mm</text>';

$draw = static function (string $label, float $x, bool $synthetic) use (&$svg, $alias, $text, $targetW, $targetH, $sx, $sy, $esc): void {
    $top = 18.0;
    $baseline = $top + $targetH + 5.0;
    $svg .= '<text x="'.$x.'" y="14" font-family="arial" font-size="6" fill="#555">'.$esc($label).'</text>';
    $svg .= '<rect x="'.$x.'" y="'.$top.'" width="'.$targetW.'" height="'.$targetH.'" fill="none" stroke="#bbb" stroke-width=".25"/>';
    $svg .= '<g transform="translate('.$x.' '.$baseline.') scale('.$sx.' '.$sy.')" fill="#e30613">';
    $svg .= '<text x="0" y="0" font-family="'.$alias.'" font-size="10">'.$text.'</text>';
    if ($synthetic) {
        // Synthetic bold: a second glyph pass with a small horizontal offset.
        $svg .= '<text x="0.16" y="0" font-family="'.$alias.'" font-size="10">'.$text.'</text>';
    }
    $svg .= '</g>';
};

$draw('Variasi A — Century Regular tanpa synthetic bold', 18.0, false);
$draw('Variasi B — Century Regular dengan synthetic bold', 155.0, true);
$svg .= '</svg>';
$pdf->WriteHTML($svg);
$pdf->Output($output, 'F');

fwrite(STDOUT, "Generated: {$output}\n");
fwrite(STDOUT, "Font: Century Regular [CENTURY.TTF]\n");
fwrite(STDOUT, "w0={$w0}; cap={$cap}; sx={$sx}; sy={$sy}; FontBBox={$fontBox}\n");
