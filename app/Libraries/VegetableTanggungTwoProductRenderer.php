<?php

declare(strict_types=1);

namespace App\Libraries;

use InvalidArgumentException;
use Mpdf\Cache;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Css\TextVars;
use Mpdf\Fonts\FontCache;
use Mpdf\Mpdf;
use Mpdf\TTFontFile;
use RuntimeException;

final class VegetableTanggungTwoProductRenderer
{
    private const MM_PER_PT = 25.4 / 72;
    private const FONT_SIZE = 100;
    private const KERNING = TextVars::FC_KERNING;
    private const VERTICAL_OFFSET_PT = -10.63;

    private const LAYOUTS = [
        'top' => [
            'name'  => [[161.85, 81, 315, 18], [161.85, 81, 476.85, 99], [0, 0, 255], 1, 'center'],
            'plu'   => [[126, 108, 63, 13], [126, 108, 189, 121], [0, 0, 0], .75, 'left'],
            'rp'    => [[192, 111, 33, 24], [192, 111, 225, 135], [255, 0, 0], .75, 'center'],
            'price' => [[228, 108, 186, 27], [228, 108, 414, 135], [255, 0, 0], 1.75, 'center'],
            'unit'  => [[426, 108, 54, 25.1], [426, 108, 480, 133.1], [255, 0, 0], .75, 'left'],
        ],
        'bottom' => [
            'name'  => [[162, 144, 315, 18], [162, 144, 477, 162], [0, 0, 255], 1, 'center'],
            'plu'   => [[126, 167, 63, 13], [126, 167, 189, 180], [0, 0, 0], .75, 'left'],
            'rp'    => [[192, 171, 33, 27.3], [192, 171, 225, 198.3], [255, 0, 0], .75, 'center'],
            'price' => [[228, 171, 186, 27], [228, 171, 414, 198], [255, 0, 0], 1.75, 'center'],
            'unit'  => [[423, 172.9, 54, 25.1], [423, 172.9, 477, 198], [255, 0, 0], .75, 'left'],
        ],
    ];

    public function renderOnPage(Mpdf $mpdf, array $topProduct, array $bottomProduct): void
    {
        $texts = ['top' => $this->texts($topProduct), 'bottom' => $this->texts($bottomProduct)];
        $reference = [
            'top' => $this->texts(['name' => 'CABE KRT MERAH CRH', 'sku_plu' => '024494', 'price' => 6710]),
            'bottom' => $this->texts(['name' => 'CABE KRT HIJAU CRH', 'sku_plu' => '024496', 'price' => 6897]),
        ];
        $fonts = [
            'name' => $this->fontPath(['cambriab.ttf', 'CAMBRIAB.TTF']),
            'plu' => $this->fontPath(['arialbd.ttf', 'ARIALBD.TTF']),
            'rp' => $this->fontPath(['MAGNETOB.TTF', 'magnetob.ttf']),
            'price' => $this->fontPath(['NIAGSOL.TTF', 'niagsol.ttf']),
            'unit' => $this->fontPath(['AGENCYR.TTF', 'agencyr.ttf']),
        ];
        $aliases = [];
        foreach ($fonts as $element => $path) {
            $alias = 'vegtanggung2' . $element . substr(hash_file('sha256', $path), 0, 12);
            $aliases[$element] = $alias;
            $mpdf->AddFontDirectory(dirname($path));
            $mpdf->fontdata[$alias] = ['R' => basename($path)];
            $mpdf->AddFont($alias);
        }
        $cacheDir = defined('WRITEPATH') ? rtrim(WRITEPATH, '/\\') . '/mpdf/vegetable-tanggung-2product' : sys_get_temp_dir() . '/vegetable-tanggung-2product';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) throw new RuntimeException('Cache mPDF tidak dapat dibuat.');
        $fontCache = new FontCache(new Cache($cacheDir . '/ttfontdata'));
        foreach (self::LAYOUTS as $slot => $elements) {
            foreach ($elements as $element => [$object, $bounds, $color, $stroke, $align]) {
                $object[1] += self::VERTICAL_OFFSET_PT;
                $bounds[1] += self::VERTICAL_OFFSET_PT;
                $bounds[3] += self::VERTICAL_OFFSET_PT;
                $mpdf->SetFont($aliases[$element], '', self::FONT_SIZE);
                $font = new TTFontFile($fontCache, $mpdf->getFontDescriptor());
                $font->getMetrics($fonts[$element], $aliases[$element]);
                $font->fh = fopen($fonts[$element], 'rb');
                if ($font->fh === false) throw new RuntimeException("Font {$element} tidak dapat dibuka.");
                try {
                    [$rx0, $ry0, $rx1, $ry1] = $this->inkBounds($font, $reference[$slot][$element]);
                    [$x0, $y0, $x1, $y1] = $this->inkBounds($font, $texts[$slot][$element]);
                } finally { fclose($font->fh); }
                [$left, $top, $right, $bottom] = $bounds;
                [$objectLeft, $objectTop, $objectWidth, $objectHeight] = $object;
                $baseSx = ($right - $left) / ($rx1 - $rx0);
                $baseSy = ($bottom - $top) / ($ry1 - $ry0);
                $baseline = $top + $ry1 * $baseSy;
                $sx = min($baseSx, ($right - $left) / ($x1 - $x0));
                $sy = min($baseSy, $objectHeight / ($y1 - $y0));
                $baseline = max($objectTop + $y1 * $sy, min($baseline, $objectTop + $objectHeight + $y0 * $sy));
                $width = ($x1 - $x0) * $sx;
                $inkLeft = $align === 'center' ? ($left + $right - $width) / 2 : $left;
                $inkLeft = max($objectLeft, min($inkLeft, $objectLeft + $objectWidth - $width));
                $origin = $inkLeft - $x0 * $sx;
                $mpdf->SetTextColor(...$color); $mpdf->SetDrawColor(...$color); $mpdf->SetLineWidth($stroke * self::MM_PER_PT);
                $matrix = sprintf('2 Tr %.8F 0 0 %.8F %.5F %.5F Tm ', $sx, $sy, $origin, 297 / self::MM_PER_PT - $baseline);
                $mpdf->Text(0, 0, $texts[$slot][$element], [], self::KERNING, $matrix, 'SVG');
            }
        }
    }

    private function texts(array $product): array
    {
        $name = $product['name'] ?? null;
        if (!is_string($name) || !mb_check_encoding($name, 'UTF-8') || trim($name) === '' || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $name)) throw new InvalidArgumentException('name harus berupa teks UTF-8 satu baris.');
        $name = function_exists('mb_strtoupper') ? mb_strtoupper(trim(str_replace("\u{00A0}", ' ', $name)), 'UTF-8') : strtoupper(trim(str_replace("\u{00A0}", ' ', $name)));
        $sku = $product['sku_plu'] ?? null;
        if (!is_string($sku) || !preg_match('/\A[0-9]+\z/', $sku)) throw new InvalidArgumentException('sku_plu harus berupa string digit.');
        $price = $product['price'] ?? null;
        if ((!is_int($price) && !(is_float($price) && floor($price) === $price)) || $price < 0) throw new InvalidArgumentException('price harus berupa bilangan bulat rupiah.');
        return ['name' => $name, 'plu' => 'PLU : ' . $sku, 'rp' => 'Rp', 'price' => number_format((int) $price, 0, '.', '.'), 'unit' => '/100GR'];
    }

    private function fontPath(array $names): string
    {
        $dirs = [];
        if (defined('FCPATH')) $dirs[] = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'assets/fonts';
        $dirs[] = (getenv('WINDIR') ?: 'C:/Windows') . '/Fonts';
        foreach ($dirs as $dir) foreach ($names as $name) { $path = $dir . DIRECTORY_SEPARATOR . $name; if (is_readable($path)) return $path; }
        throw new RuntimeException('Font tidak ditemukan: ' . implode(', ', $names));
    }

    private function inkBounds(TTFontFile $font, string $text): array
    {
        $font->seek_table('head', 50); $locationFormat = $font->read_ushort(); $font->seek_table('maxp', 4); $font->getLOCA($locationFormat, $font->read_ushort()); [$glyf] = $font->get_table_pos('glyf');
        $glyphs = []; foreach ($font->glyphToChar as $glyph => $codes) foreach ($codes as $code) $glyphs[$code] = $glyph;
        $bounds = [INF, INF, -INF, -INF]; $advance = 0; $previous = null;
        foreach (mb_str_split($text) as $character) {
            $code = mb_ord($character); $glyph = $glyphs[$code] ?? null; if (!$glyph || $code >= 0x10000) throw new InvalidArgumentException("Glyph tidak didukung: {$character}");
            $advance += ($font->kerninfo[$previous][$code] ?? 0) * self::FONT_SIZE / 1000; $offset = $font->glyphPos[$glyph];
            if ($offset !== $font->glyphPos[$glyph + 1]) { $font->seek($glyf + $offset + 2); foreach ([0, 1, 2, 3] as $i) { $coordinate = $font->read_short() * self::FONT_SIZE / $font->unitsPerEm; $coordinate += $i % 2 === 0 ? $advance : 0; $bounds[$i] = $i < 2 ? min($bounds[$i], $coordinate) : max($bounds[$i], $coordinate); } }
            $advance += unpack('n', $font->charWidths, $code * 2)[1] * self::FONT_SIZE / 1000; $previous = $code;
        }
        if ($bounds[0] >= $bounds[2] || $bounds[1] >= $bounds[3]) throw new InvalidArgumentException('Teks tidak memiliki glyph yang dapat diukur.');
        return $bounds;
    }
}
