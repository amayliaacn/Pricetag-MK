<?php

declare(strict_types=1);

use Mpdf\Cache;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Css\TextVars;
use Mpdf\Fonts\FontCache;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\TTFontFile;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

final class VegetableTanggungDynamic
{
    private const MM_PER_PT = 25.4 / 72;
    private const FONT_SIZE = 100;
    private const KERNING = TextVars::FC_KERNING;
    private const REFERENCE = [
        'name' => 'JERUK NIPIS/PCL CRH /MRT',
        'sku_plu' => '024011',
        'price' => 4800,
        'unit' => '/100GR',
    ];

    // WordArt object x/y/width/height, then measured WPS fill bounds (left/top/right/bottom),
    // excluding the stroke. Coordinates are in pt from the top-left of the page.
    private const ELEMENTS = [
        'name'  => [[161.85, 81, 315, 36], [162.255, 81, 476.073, 117],      [0, 0, 255], 1,    'center'],
        'plu'   => [[125.85, 133.25, 63, 19.9], [126.581, 133.25, 187.191, 153.15], [0, 0, 0], .75, 'left'],
        'rp'    => [[192, 144, 33, 24], [192.440, 144, 225, 168],            [255, 0, 0], .75, 'center'],
        'price' => [[228, 133.1, 186, 64.9], [229.035, 133.1, 409.602, 198], [255, 0, 0], 1.75, 'center'],
        'unit'  => [[423, 162, 54, 36], [423.514, 162, 476.029, 198],        [255, 0, 0], .75, 'left'],
    ];

    public function __construct(private readonly ?string $arialUnicodeBold = null)
    {
    }

    /** @return array{pdf: string, layout: array, pluFont: string} */
    public function render(array $product): array
    {
        $texts = $this->texts($product);
        $reference = $this->texts(self::REFERENCE);
        $windows = (getenv('WINDIR') ?: 'C:/Windows') . '/Fonts';
        $pluFont = $this->arialUnicodeBold;
        foreach (['ArialUnicode-Bold.ttf', 'Arial Unicode MS Bold.ttf', 'ARIALUNIB.TTF'] as $candidate) {
            if ($pluFont === null && is_file("$windows/$candidate")) {
                $pluFont = "$windows/$candidate";
            }
        }
        $fonts = [
            'name' => "$windows/cambriab.ttf",
            'plu' => $pluFont ?? "$windows/arialbd.ttf",
            'rp' => "$windows/MAGNETOB.TTF",
            'price' => "$windows/NIAGSOL.TTF",
            'unit' => "$windows/AGENCYR.TTF",
        ];
        $pluFontName = $pluFont === null ? 'Arial Bold (fallback)' : 'Arial Unicode MS Bold';
        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];
        $aliases = [];
        foreach ($fonts as $element => $path) {
            if (!is_readable($path)) {
                throw new RuntimeException("Font $element tidak dapat dibaca: $path");
            }
            $alias = 'vegtanggung' . $element . substr(hash_file('sha256', $path), 0, 12);
            $aliases[$element] = $alias;
            $fontDirs[] = dirname($path);
            $fontData[$alias] = ['R' => basename($path)];
        }
        $pdf = new Mpdf([
            'format' => [210, 297],
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'tempDir' => __DIR__ . '/dynamic-cache',
            'fontDir' => array_unique($fontDirs),
            'fontdata' => $fontData,
            'default_font' => $aliases['plu'],
        ]);
        $pdf->SetTitle('Vegetable Tanggung - 1 Produk');
        $pdf->AddPage();
        $layout = [];
        $cache = new FontCache(new Cache(__DIR__ . '/dynamic-cache/mpdf/ttfontdata'));

        foreach (self::ELEMENTS as $element => [$object, $bounds, $color, $stroke, $align]) {
            $pdf->SetFont($aliases[$element], '', self::FONT_SIZE);
            $font = new TTFontFile($cache, $pdf->getFontDescriptor());
            $font->getMetrics($fonts[$element], $aliases[$element]);
            if ($element === 'plu' && $pluFont !== null &&
                !preg_match('/arialunicode.*bold/i', preg_replace('/[^a-z]/i', '', $font->fullName))) {
                throw new RuntimeException('File PLU bukan Arial Unicode MS Bold.');
            }
            $font->fh = fopen($fonts[$element], 'rb');
            try {
                [$rx0, $ry0, $rx1, $ry1] = $this->inkBounds($font, $reference[$element]);
                [$x0, $y0, $x1, $y1] = $this->inkBounds($font, $texts[$element]);
            } finally {
                fclose($font->fh);
            }

            [$left, $top, $right, $bottom] = $bounds;
            [$objectLeft, $objectTop, $objectWidth, $objectHeight] = $object;
            $baseSx = ($right - $left) / ($rx1 - $rx0);
            $baseSy = ($bottom - $top) / ($ry1 - $ry0);
            $baseline = $top + $ry1 * $baseSy;
            $sx = min($baseSx, ($right - $left) / ($x1 - $x0));
            $sy = min($baseSy, $objectHeight / ($y1 - $y0));
            $baseline = max($objectTop + $y1 * $sy, min(
                $baseline,
                $objectTop + $objectHeight + $y0 * $sy
            ));
            $width = ($x1 - $x0) * $sx;
            $inkLeft = $align === 'center' ? ($left + $right - $width) / 2 : $left;
            $inkLeft = max($objectLeft, min($inkLeft, $objectLeft + $objectWidth - $width));
            $origin = $inkLeft - $x0 * $sx;

            $pdf->SetTextColor(...$color);
            $pdf->SetDrawColor(...$color);
            $pdf->SetLineWidth($stroke * self::MM_PER_PT);
            // Scale the text matrix independently from the graphics state so the
            // outline stroke remains constant in page points. Text() handles PDF escaping.
            $matrix = sprintf('2 Tr %.8F 0 0 %.8F %.5F %.5F Tm ', $sx, $sy, $origin,
                297 / self::MM_PER_PT - $baseline);
            $pdf->Text(0, 0, $texts[$element], [], self::KERNING, $matrix, 'SVG');

            $layout[$element] = [
                'text' => $texts[$element],
                'baseline' => round($baseline, 3),
                'bounds' => array_map(static fn ($coordinate) => round($coordinate, 3), [
                    $inkLeft - $stroke / 2,
                    $baseline - $y1 * $sy - $stroke / 2,
                    $inkLeft + $width + $stroke / 2,
                    $baseline - $y0 * $sy + $stroke / 2,
                ]),
            ];
        }

        return ['pdf' => $pdf->Output('', Destination::STRING_RETURN), 'layout' => $layout, 'pluFont' => $pluFontName];
    }

    private function texts(array $product): array
    {
        foreach (['name', 'unit'] as $key) {
            $value = $product[$key] ?? null;
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') ||
                trim($value) === '' || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $value)) {
                throw new InvalidArgumentException("$key harus berupa teks UTF-8 satu baris.");
            }
        }
        if (!is_string($product['sku_plu'] ?? null) || !preg_match('/\A[0-9]+\z/', $product['sku_plu'])) {
            throw new InvalidArgumentException('sku_plu harus berupa string digit.');
        }
        if (!is_int($product['price'] ?? null) || $product['price'] < 0) {
            throw new InvalidArgumentException('price harus berupa bilangan bulat rupiah.');
        }
        return [
            'name' => trim(str_replace("\u{00A0}", ' ', $product['name'])),
            'plu' => 'PLU : ' . $product['sku_plu'],
            'rp' => 'Rp',
            'price' => preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $product['price']),
            'unit' => trim(str_replace("\u{00A0}", ' ', $product['unit'])),
        ];
    }

    /** Measure actual glyph boxes in font units, with the same advances/kerning as mPDF. */
    private function inkBounds(TTFontFile $font, string $text): array
    {
        $font->seek_table('head', 50);
        $locationFormat = $font->read_ushort();
        $font->seek_table('maxp', 4);
        $font->getLOCA($locationFormat, $font->read_ushort());
        [$glyf] = $font->get_table_pos('glyf');
        $glyphs = [];
        foreach ($font->glyphToChar as $glyph => $codes) {
            foreach ($codes as $code) {
                $glyphs[$code] = $glyph;
            }
        }
        $bounds = [INF, INF, -INF, -INF];
        $advance = 0;
        $previous = null;
        foreach (mb_str_split($text) as $character) {
            $code = mb_ord($character);
            $glyph = $glyphs[$code] ?? null;
            if (!$glyph || $code >= 0x10000) {
                throw new InvalidArgumentException("Glyph tidak didukung: $character");
            }

            $advance += ($font->kerninfo[$previous][$code] ?? 0) * self::FONT_SIZE / 1000;
            $offset = $font->glyphPos[$glyph];
            if ($offset !== $font->glyphPos[$glyph + 1]) {
                $font->seek($glyf + $offset + 2);
                foreach ([0, 1, 2, 3] as $i) {
                    $coordinate = $font->read_short() * self::FONT_SIZE / $font->unitsPerEm;
                    $coordinate += $i % 2 === 0 ? $advance : 0;
                    $bounds[$i] = $i < 2 ? min($bounds[$i], $coordinate) : max($bounds[$i], $coordinate);
                }
            }
            $advance += unpack('n', $font->charWidths, $code * 2)[1] * self::FONT_SIZE / 1000;
            $previous = $code;
        }
        if ($bounds[0] >= $bounds[2] || $bounds[1] >= $bounds[3]) {
            throw new InvalidArgumentException('Teks tidak memiliki glyph yang dapat diukur.');
        }
        return $bounds;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $samples = [
        'A' => ['name' => 'JERUK NIPIS/PCL CRH /MRT', 'sku_plu' => '024011', 'price' => 4800, 'unit' => '/100GR'],
        'B' => ['name' => 'BAYAM', 'sku_plu' => '024030', 'price' => 3500, 'unit' => '/IKAT'],
        'C' => ['name' => 'WORTEL IMPORT', 'sku_plu' => '024025', 'price' => 6500, 'unit' => '/KG'],
        'D' => ['name' => 'KENTANG IMPORT PREMIUM PILIHAN', 'sku_plu' => '024099', 'price' => 125000, 'unit' => '/KG'],
    ];
    $selection = strtoupper($argv[1] ?? 'ALL');
    if ($selection !== 'ALL' && !isset($samples[$selection])) {
        fwrite(STDERR, "Pemakaian: php render-dynamic.php [A|B|C|D|ALL]\n");
        exit(1);
    }
    $renderer = new VegetableTanggungDynamic(getenv('VEGETABLE_PLU_FONT') ?: null);
    foreach ($selection === 'ALL' ? $samples : [$selection => $samples[$selection]] as $case => $product) {
        $result = $renderer->render($product);
        $output = __DIR__ . '/vegetable-tanggung-dynamic-' . strtolower($case) . '.pdf';
        if (file_put_contents($output, $result['pdf']) === false) {
            throw new RuntimeException("PDF gagal ditulis: $output");
        }
        unset($result['pdf']);
        fwrite(STDOUT, json_encode(['file' => $output] + $result, JSON_THROW_ON_ERROR) . PHP_EOL);
    }
}
