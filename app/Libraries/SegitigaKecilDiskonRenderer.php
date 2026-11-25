<?php

declare(strict_types=1);

namespace App\Libraries;

use InvalidArgumentException;
use Mpdf\Mpdf;
use RuntimeException;

final class SegitigaKecilDiskonRenderer
{
    private const POP_W = 142.5;
    private const POP_H = 200.0;
    private const FONT_PT = 10 / 0.352778;

    public function __construct(private readonly array $fontPaths, private readonly string $backgroundPath)
    {
        foreach (['calibri', 'poppins', 'extra', 'century'] as $key) {
            if (!isset($fontPaths[$key]) || !is_readable($fontPaths[$key])) {
                throw new RuntimeException("Font {$key} tidak tersedia atau tidak terbaca.");
            }
        }
        if (!is_readable($backgroundPath)) throw new RuntimeException('Background tidak tersedia: ' . $backgroundPath);
        $this->aliases = self::fontAliases();
    }

    /** Configuration to merge into the caller's mPDF constructor. */
    public static function fontConfig(array $fontPaths): array
    {
        foreach (self::fontAliases() as $key => $alias) {
            if (!isset($fontPaths[$key]) || !is_readable($fontPaths[$key])) {
                throw new RuntimeException("Font {$key} tidak tersedia atau tidak terbaca.");
            }
        }
        $fontdata = [];
        $fontDirs = [];
        foreach (self::fontAliases() as $key => $alias) {
            $path = $fontPaths[$key];
            $fontDirs[] = dirname($path);
            $fontdata[$alias] = ['R' => basename($path)];
        }
        return ['aliases' => self::fontAliases(), 'fontdata' => $fontdata, 'fontDir' => array_values(array_unique($fontDirs))];
    }

    /** Short aliases are intentionally lowercase and space-free for SVG CSS resolution. */
    public static function fontAliases(): array
    {
        return ['calibri' => 'segkecilcalibri', 'poppins' => 'segkecilpoppins', 'extra' => 'segkecilextra', 'century' => 'segkecilcentury'];
    }

    /** Render exactly two identical POPs on the active mPDF page. */
    public function renderOnPage(Mpdf $mpdf, array $product): void
    {
        foreach ($this->aliases as $key => $alias) {
            if (!isset($mpdf->fontdata[$alias])) throw new RuntimeException("Alias font {$alias} belum didaftarkan sebelum konstruktor mPDF.");
        }
        $data = $this->normalize($product);
        $this->renderPop($mpdf, 3.0, 5.0, $data);
        $this->renderPop($mpdf, 151.5, 5.0, $data);
    }

    private array $aliases = [];

    private function normalize(array $p): array
    {
        $brand = $this->text($p['brand'] ?? '', 'brand');
        $variant = $this->text($p['variant'] ?? $p['name'] ?? '', 'variant');
        $plu = $p['plu'] ?? $p['sku_plu'] ?? null;
        if (!is_string($plu) || !preg_match('/\A\d+\z/', $plu)) throw new InvalidArgumentException('plu harus string digit.');
        $normal = $this->integer($p['normal'] ?? $p['normal_price'] ?? null, 'normal');
        $promo = $this->integer($p['promo'] ?? $p['promo_price'] ?? null, 'promo');
        $discount = $p['discount'] ?? 0;
        if (!is_numeric($discount)) throw new InvalidArgumentException('discount harus numerik.');
        $allocation = $p['allocation'] ?? null;
        return [
            'brand' => $brand, 'variant' => $variant, 'product' => $variant, 'plu' => 'PLU : ' . $plu,
            'disc' => 'DISC.', 'discount' => (string)(int)$discount, 'percent' => '%',
            'normalRp' => 'Rp', 'normal' => number_format($normal, 0, ',', '.'),
            'promoRp' => 'Rp', 'promo' => number_format($promo, 0, ',', '.'),
            'period' => 'AKHIR PERIODE :', 'date' => $this->formatDate($this->text($p['period'] ?? $p['end_date'] ?? '', 'period')),
            'allocation' => 'SELAMA ALOKASI', 'still' => 'MASIH ADA',
            'showAllocation' => is_numeric($allocation) && (float)$allocation > 0,
        ];
    }

    private function renderPop(Mpdf $mpdf, float $px, float $py, array $d): void
    {
        $b = [
            'brand'=>[10.45,30,121.6,28.58,'calibri','#0000FF'], 'plu'=>[84,8,43,8,'poppins','#111111'],
            'product'=>[19.25,64,104,10,'poppins','#111111'], 'disc'=>[12,84,29,10,'poppins','#111111'],
            'discount'=>[47,81,64,47,'century','#FF0000'], 'percent'=>[116,114,17,14,'poppins','#FF0000'],
            'normalRp'=>[4.5,138,10,9,'poppins','#FF0000'], 'normal'=>[18,138,52,16,'extra','#111111'],
            'promoRp'=>[59,167,10,9,'poppins','#FF0000'], 'promo'=>[72,154,62,35,'extra','#FF0000'],
        ];
        if ($d['showAllocation']) {
            $b += ['period'=>[8.5,160,51,5.1,'poppins','#111111'],'date'=>[12.5,168,43,5.1,'poppins','#111111'],'allocation'=>[8.5,176.5,45,5.1,'poppins','#FF0000'],'still'=>[16.5,184,29,4.65,'poppins','#FF0000']];
        } else {
            $b += ['period'=>[11.05,167.775,45.9,5.1,'poppins','#111111'],'date'=>[12.5,175.775,43,5.1,'poppins','#111111']];
        }
        $img = '<img src="'.htmlspecialchars($this->backgroundPath, ENT_QUOTES, 'UTF-8').'" style="display:block;width:142.5mm;height:200mm;" />';
        $mpdf->WriteFixedPosHTML($img, $px, $py, self::POP_W, self::POP_H, 'auto');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none" style="background:transparent">';
        foreach ($b as $key => [$x,$y,$w,$h,$font,$color]) {
            $text = $d[$key];
            if ($key === 'product') { $w = min(104, max(18, $this->scaledWidth($mpdf, 'poppins', $text, 'YOG SLURP 80 STRW', 104))); $x = 71.25 - $w/2; }
            if ($key === 'normal') { $w = min(52, max(12, $this->scaledWidth($mpdf, 'extra', $text, '6.703', 52))); }
            if ($key === 'promo') { $w = min(62, max(12, $this->scaledWidth($mpdf, 'extra', $text, '3.352', 62))); $x = 134 - $w; }
            if ($key === 'date') { $w = min($w, max(12, $this->scaledWidth($mpdf, 'poppins', $text, '20 APRIL 2025', $w))); $x = 34 - $w/2; }
            $t = $this->measure($mpdf, $font, $text, $w, $h, $x, $y);
            $svg .= '<g transform="translate('.$t['x'].' '.$t['y'].') scale('.$t['sx'].' '.$t['sy'].')" fill="'.$color.'"><text x="0" y="0" font-family="'.$t['family'].'" font-size="10">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</text>';
            if ($key === 'discount' || in_array($key, ['period','date','allocation','still'], true)) $svg .= '<text x="0.18" y="0" font-family="'.$t['family'].'" font-size="10">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</text>';
            $svg .= '</g>';
        }
        $normalW = $b['normal'][2];
        $svg .= '<line x1="17.5" y1="154" x2="'.(18 + $normalW + 1.5).'" y2="140" stroke="#FF0000" stroke-width="0.8"/></svg>';
        $mpdf->WriteFixedPosHTML($svg, $px, $py, self::POP_W, self::POP_H, 'auto');
    }

    private function measure(Mpdf $m, string $font, string $text, float $w, float $h, float $x, float $y): array
    {
        $m->SetFont($this->aliases[$font], '', self::FONT_PT);
        $w0 = max(.01, (float)$m->GetStringWidth($text));
        $cap = (($m->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * 10;
        return ['family'=>$this->aliases[$font], 'x'=>$x, 'y'=>$y+$h+3, 'sx'=>$w/$w0, 'sy'=>$h/max(.01,$cap)];
    }

    private function scaledWidth(Mpdf $m, string $font, string $text, string $reference, float $referenceWidth): float
    {
        $m->SetFont($this->aliases[$font], '', self::FONT_PT);
        return (float)$m->GetStringWidth($text) * ($referenceWidth / max(.01, (float)$m->GetStringWidth($reference)));
    }

    private function text(mixed $v, string $key): string
    {
        if (!is_string($v) || trim($v)==='' || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u',$v)) throw new InvalidArgumentException("{$key} harus teks satu baris.");
        return mb_strtoupper(trim(str_replace("\u{00A0}",' ',$v)), 'UTF-8');
    }
    private function integer(mixed $v, string $key): int
    { if ((!is_int($v)&&!(is_float($v)&&floor($v)===$v))||$v<0) throw new InvalidArgumentException("{$key} tidak valid."); return (int)$v; }
    private function formatDate(string $s): string
    { $m=['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER']; return preg_replace_callback('/^(\d{1,2})\s+([^\s]+)\s+(\d{4})$/u',static fn($x)=>$x[1].' '.(in_array($x[2],$m,true)?$x[2]:$x[2]).' '.$x[3],$s)??$s; }
}
