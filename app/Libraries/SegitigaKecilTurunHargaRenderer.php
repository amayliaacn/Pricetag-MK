<?php

declare(strict_types=1);

namespace App\Libraries;

use InvalidArgumentException;
use Mpdf\Mpdf;
use RuntimeException;

final class SegitigaKecilTurunHargaRenderer
{
    private const POP_W = 142.5;
    private const POP_H = 200.0;
    private const FONT_PT = 10 / 0.352778;

    private array $aliases;

    public function __construct(private readonly array $fontPaths, private readonly string $backgroundPath)
    {
        $this->aliases = self::fontAliases();
        foreach (array_keys($this->aliases) as $key) {
            if (!isset($fontPaths[$key]) || !is_readable($fontPaths[$key])) {
                throw new RuntimeException("Font {$key} tidak tersedia atau tidak terbaca.");
            }
        }
        if (!is_readable($backgroundPath)) {
            throw new RuntimeException('Background tidak tersedia atau tidak terbaca: ' . $backgroundPath);
        }
    }

    public static function fontAliases(): array
    {
        return [
            'calibri' => 'segturuncalibri',
            'poppins' => 'segturunpoppins',
            'extra' => 'segturunextra',
        ];
    }

    public static function fontConfig(array $fontPaths): array
    {
        $data = [];
        $dirs = [];
        foreach (self::fontAliases() as $key => $alias) {
            if (!isset($fontPaths[$key]) || !is_readable($fontPaths[$key])) {
                throw new RuntimeException("Font {$key} tidak tersedia atau tidak terbaca.");
            }
            $data[$alias] = ['R' => basename($fontPaths[$key])];
            $dirs[] = dirname($fontPaths[$key]);
        }
        return ['aliases' => self::fontAliases(), 'fontdata' => $data, 'fontDir' => array_values(array_unique($dirs))];
    }

    public function renderOnPage(Mpdf $mpdf, array $product): void
    {
        foreach ($this->aliases as $alias) {
            if (!isset($mpdf->fontdata[$alias])) {
                throw new RuntimeException("Alias font {$alias} belum didaftarkan sebelum konstruktor mPDF.");
            }
        }
        $data = $this->normalize($product);
        $this->renderPop($mpdf, 3.0, 5.0, $data);
        $this->renderPop($mpdf, 151.5, 5.0, $data);
    }

    private function normalize(array $p): array
    {
        foreach (['brand', 'sku_plu', 'normal_price', 'promo_price', 'end_period'] as $key) {
            if (!array_key_exists($key, $p)) throw new InvalidArgumentException("field {$key} wajib");
        }
        $brand = $this->text($p['brand'], 'brand');
        if (array_key_exists('variant', $p) && $p['variant'] !== null && !is_string($p['variant'])) {
            throw new InvalidArgumentException('variant harus teks satu baris.');
        }
        $variant = array_key_exists('variant', $p) && $p['variant'] !== null && trim($p['variant']) !== ''
            ? $this->text($p['variant'], 'variant') : null;
        if (!is_string($p['sku_plu']) || !preg_match('/^\d+$/', $p['sku_plu'])) {
            throw new InvalidArgumentException('sku_plu harus string digit');
        }
        $normal = $this->price($p['normal_price'], 'normal_price');
        $promo = $this->price($p['promo_price'], 'promo_price');
        $allocation = $p['allocation_pcs'] ?? null;
        if ($allocation !== null && $allocation !== '' &&
            ((!is_int($allocation) && !is_float($allocation) && !is_string($allocation)) ||
            !is_numeric($allocation) || (float)$allocation < 0 || floor((float)$allocation) !== (float)$allocation)) {
            throw new InvalidArgumentException('allocation_pcs invalid');
        }
        $date = $this->formatDate($p['end_period']);
        return [
            'brand' => $brand, 'variant' => $variant, 'plu' => 'PLU : ' . $p['sku_plu'],
            'normal' => number_format($normal, 0, ',', '.'), 'promo' => number_format($promo, 0, ',', '.'),
            'period' => 'AKHIR PERIODE : ' . $date,
            'allocation' => '( PROGRAM SELAMA ALOKASI MASIH ADA )',
            'mode' => is_numeric($allocation) && (float)$allocation > 0 ? 'Allocation' : 'Non-Allocation',
        ];
    }

    private function renderPop(Mpdf $mpdf, float $px, float $py, array $d): void
    {
        $boxes = [
            'plu' => [84.0, 12.5, 43.0, 8.0, 'poppins', '#000000', 'right'],
            'brand' => [10.45, 30.0, 121.6, 28.58, 'calibri', '#0000FF', 'center'],
            'variant' => [19.25, 64.0, 104.0, 10.0, 'poppins', '#000000', 'center'],
            'normalRp' => [5.75, 90.475, 10.0, 9.0, 'poppins', '#FF0000', 'left'],
            'normal' => [18.0, 87.475, 54.0, 19.05, 'extra', '#000000', 'left'],
            'promoRp' => [5.75, 138.0, 17.0, 14.0, 'poppins', '#FF0000', 'left'],
            'promo' => [36.0, 120.0, 92.0, 47.63, 'extra', '#FF0000', 'left'],
            'periodAlloc' => [8.0, 173.0, 125.0, 5.29, 'poppins', '#000000', 'center'],
            'periodNoAlloc' => [8.0, 179.28, 125.0, 5.29, 'poppins', '#000000', 'center'],
            'allocation' => [8.25, 184.5, 126.0, 6.35, 'poppins', '#FF0000', 'center'],
        ];
        $texts = ['plu'=>$d['plu'], 'brand'=>$d['brand'], 'normalRp'=>'Rp', 'normal'=>$d['normal'], 'promoRp'=>'Rp', 'promo'=>$d['promo'], 'period'=>$d['period']];
        if ($d['variant'] !== null) $texts['variant'] = $d['variant'];
        if ($d['mode'] === 'Allocation') $texts['allocation'] = $d['allocation'];
        $periodKey = $d['mode'] === 'Allocation' ? 'periodAlloc' : 'periodNoAlloc';
        $normal = null;
        $img = '<img src="'.htmlspecialchars($this->backgroundPath, ENT_QUOTES, 'UTF-8').'" style="display:block;width:142.5mm;height:200mm;" />';
        $mpdf->WriteFixedPosHTML($img, $px, $py, self::POP_W, self::POP_H, 'auto');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none">';
        foreach ($texts as $key => $text) {
            $boxKey = $key === 'period' ? $periodKey : $key;
            [$x, $y, $w, $h, $font, $color, $align] = $boxes[$boxKey];
            $mpdf->SetFont($this->aliases[$font], '', self::FONT_PT);
            $natural = max(.01, (float)$mpdf->GetStringWidth($text));
            $cap = (($mpdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10.0;
            $reference = match ($key) { 'brand'=>'NOBO', 'variant'=>'OAT MILK 200ML CRMY CLAS', 'normal'=>'2.855', 'promo'=>'2.521', default=>$text };
            $referenceWidth = max(.01, (float)$mpdf->GetStringWidth($reference));
            $width = in_array($key, ['brand','variant','normal','promo'], true)
                ? min($w, max($key === 'variant' ? 18 : 12, $natural * ($w / $referenceWidth))) : $w;
            $sx = $width / $natural;
            $sy = $h / max(.01, $cap);
            $baseline = $y + $h - .7;
            $start = match ($align) { 'center' => $x + (($w - $width) / 2), 'right' => $x + $w - $width, default => $x };
            $svg .= '<g transform="translate('.$start.' '.$baseline.') scale('.$sx.' '.$sy.')" fill="'.$color.'"><text x="0" y="0" font-family="'.$this->aliases[$font].'" font-size="10">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</text></g>';
            if ($key === 'normal') $normal = [$start, $y, $width, $h];
        }
        if ($normal !== null) {
            [$x, $y, $w, $h] = $normal;
            $svg .= '<line x1="'.($x-.5).'" y1="105.525" x2="'.($x+$w+.5).'" y2="89.475" stroke="#FF0000" stroke-width="1.4111" stroke-linecap="round"/>';
        }
        $mpdf->WriteFixedPosHTML($svg.'</svg>', $px, $py, self::POP_W, self::POP_H, 'auto');
    }

    private function text(mixed $value, string $key): string
    {
        if (!is_string($value) || trim($value) === '' || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $value)) throw new InvalidArgumentException("{$key} harus teks satu baris.");
        return mb_strtoupper(trim(str_replace("\u{00A0}", ' ', $value)), 'UTF-8');
    }

    private function price(mixed $value, string $key): int
    {
        if (!is_int($value) && !(is_float($value) && is_finite($value) && floor($value) === $value)) throw new InvalidArgumentException("{$key} harus numerik non-negatif");
        if ($value < 0) throw new InvalidArgumentException("{$key} harus numerik non-negatif");
        return (int)$value;
    }

    private function formatDate(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/^\s*(\d{1,2})\s+([^\s]+)\s+(\d{4})\s*$/u', $value, $m)) throw new InvalidArgumentException('end_period tidak valid');
        $months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        $month = mb_strtoupper($m[2], 'UTF-8');
        if (!in_array($month, $months, true)) throw new InvalidArgumentException('Bulan end_period tidak valid');
        return (int)$m[1].' '.$month.' '.$m[3];
    }
}
