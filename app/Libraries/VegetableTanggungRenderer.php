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

/**
 * Renderer vektor untuk Vegetable Tanggung: satu produk pada satu halaman A4.
 *
 * Koordinat, bounding box, transformasi, scaling, alignment, dan warna diambil
 * dari POC vegetable-tanggung/render-dynamic.php. Method renderOnPage() tidak
 * menambah halaman dan menulis langsung ke instance mPDF milik pemanggil.
 */
final class VegetableTanggungRenderer
{
    private const MM_PER_PT = 25.4 / 72;
    private const FONT_SIZE = 100;
    private const KERNING = TextVars::FC_KERNING;
    // Casing mengikuti input POC referensi dynamic-a (/100GR), sehingga
    // glyph dan baseline unit tetap identik dengan hasil kalibrasi.
    private const UNIT = '/100GR';

    /** WordArt object, measured fill bounds, RGB, stroke width, alignment. */
    private const ELEMENTS = [
        'name'  => [[161.85, 81, 315, 36], [162.255, 81, 476.073, 117],      [0, 0, 255], 1,    'center'],
        'plu'   => [[125.85, 133.25, 63, 19.9], [126.581, 133.25, 187.191, 153.15], [0, 0, 0], .75, 'left'],
        'rp'    => [[192, 144, 33, 24], [192.440, 144, 225, 168],            [255, 0, 0], .75, 'center'],
        'price' => [[228, 133.1, 186, 64.9], [229.035, 133.1, 409.602, 198], [255, 0, 0], 1.75, 'center'],
        'unit'  => [[423, 162, 54, 36], [423.514, 162, 476.029, 198],        [255, 0, 0], .75, 'left'],
    ];

    /**
     * Render one product on the current mPDF page.
     *
     * @param array{name:string,sku_plu:string,price:int|float,unit?:string} $product
     */
    public function renderOnPage(Mpdf $mpdf, array $product): void
    {
        $texts = $this->texts($product);
        $reference = $this->texts([
            'name' => 'JERUK NIPIS/PCL CRH /MRT',
            'sku_plu' => '024011',
            'price' => 4800,
        ]);

        $fonts = [
            'name' => $this->fontPath(['cambriab.ttf', 'CAMBRIAB.TTF']),
            'plu' => $this->fontPath(['arialbd.ttf', 'ARIALBD.TTF']),
            'rp' => $this->fontPath(['MAGNETOB.TTF', 'magnetob.ttf']),
            'price' => $this->fontPath(['NIAGSOL.TTF', 'niagsol.ttf']),
            'unit' => $this->fontPath(['AGENCYR.TTF', 'agencyr.ttf']),
        ];

        $aliases = [];
        foreach ($fonts as $element => $path) {
            $alias = 'vegtanggung' . $element . substr(hash_file('sha256', $path), 0, 12);
            $aliases[$element] = $alias;
            $mpdf->AddFontDirectory(dirname($path));
            $mpdf->fontdata[$alias] = ['R' => basename($path)];
            // fontdata yang ditambahkan setelah konstruktor mPDF tidak
            // otomatis menjadi font aktif. AddFont() memuat descriptor dan
            // resource TrueType sebelum SetFont/Text dipanggil; tanpa ini
            // mPDF diam-diam memakai fallback default (DejaVuSansCondensed).
            $mpdf->AddFont($alias);
        }

        $writeCache = defined('WRITEPATH') ? WRITEPATH . 'mpdf/vegetable-tanggung' : '';
        $cacheDir = $writeCache !== '' && (is_dir(dirname($writeCache)) ? is_writable(dirname($writeCache)) : is_writable(dirname(dirname($writeCache))))
            ? $writeCache
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vegetable-tanggung-mpdf';
        if (! is_dir($cacheDir) && ! mkdir($cacheDir, 0775, true) && ! is_dir($cacheDir)) {
            throw new RuntimeException('Cache mPDF Vegetable Tanggung tidak dapat dibuat.');
        }
        $fontCache = new FontCache(new Cache($cacheDir . '/ttfontdata'));

        foreach (self::ELEMENTS as $element => [$object, $bounds, $color, $stroke, $align]) {
            $mpdf->SetFont($aliases[$element], '', self::FONT_SIZE);
            $font = new TTFontFile($fontCache, $mpdf->getFontDescriptor());
            $font->getMetrics($fonts[$element], $aliases[$element]);
            $font->fh = fopen($fonts[$element], 'rb');
            if ($font->fh === false) {
                throw new RuntimeException("Font {$element} tidak dapat dibuka.");
            }
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

            $mpdf->SetTextColor(...$color);
            $mpdf->SetDrawColor(...$color);
            $mpdf->SetLineWidth($stroke * self::MM_PER_PT);
            $matrix = sprintf(
                '2 Tr %.8F 0 0 %.8F %.5F %.5F Tm ',
                $sx,
                $sy,
                $origin,
                297 / self::MM_PER_PT - $baseline
            );
            $mpdf->Text(0, 0, $texts[$element], [], self::KERNING, $matrix, 'SVG');
        }
    }

    /** @return array{name:string,plu:string,rp:string,price:string,unit:string} */
    private function texts(array $product): array
    {
        $name = $product['name'] ?? null;
        if (! is_string($name) || ! mb_check_encoding($name, 'UTF-8') || trim($name) === ''
            || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $name)) {
            throw new InvalidArgumentException('name harus berupa teks UTF-8 satu baris.');
        }
        $name = trim(str_replace("\u{00A0}", ' ', $name));
        $name = function_exists('mb_strtoupper')
            ? mb_strtoupper($name, 'UTF-8')
            : strtoupper($name);
        $sku = $product['sku_plu'] ?? null;
        if (! is_string($sku) || ! preg_match('/\A[0-9]+\z/', $sku)) {
            throw new InvalidArgumentException('sku_plu harus berupa string digit.');
        }
        $price = $product['price'] ?? null;
        if (! is_int($price) && ! (is_float($price) && floor($price) === $price)) {
            throw new InvalidArgumentException('price harus berupa bilangan bulat rupiah.');
        }
        $price = (int) $price;
        if ($price < 0) {
            throw new InvalidArgumentException('price tidak boleh negatif.');
        }

        return [
            'name' => $name,
            'plu' => 'PLU : ' . $sku,
            'rp' => 'Rp',
            'price' => preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $price),
            'unit' => self::UNIT,
        ];
    }

    private function fontPath(array $names): string
    {
        $directories = [];
        if (defined('FCPATH')) {
            $directories[] = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'assets/fonts';
        }
        $directories[] = (getenv('WINDIR') ?: 'C:/Windows') . '/Fonts';
        foreach ($directories as $directory) {
            foreach ($names as $name) {
                $path = $directory . DIRECTORY_SEPARATOR . $name;
                if (is_readable($path)) {
                    return $path;
                }
            }
        }
        throw new RuntimeException('Font tidak ditemukan: ' . implode(', ', $names));
    }

    /** @return array{0:float,1:float,2:float,3:float} */
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
            if (! $glyph || $code >= 0x10000) {
                throw new InvalidArgumentException("Glyph tidak didukung: {$character}");
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
