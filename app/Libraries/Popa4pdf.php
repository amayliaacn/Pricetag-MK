<?php

namespace App\Libraries;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class PopA4Pdf
{
    /* ===================== PENGATURAN YANG BOLEH ANDA UBAH ===================== */

    // Gambar kotak kuning (ada di public/assets/img/Picture1.jpg) + ukuran aslinya di Word (cm)
    private const IMG_FILE = 'assets/img/Picture1.jpg';
    private const BOX_W    = 19.51;
    private const BOX_H    = 27.94;
    // Posisi gambar di kertas A4 (cm). Default: di tengah. Geser kalau hasil cetak belum pas.
    private const BOX_X    = 0.745;   // (21,0 - 19,51) / 2
    private const BOX_Y    = 0.50;    // posisi dari atas halaman sesuai layout Word

    // Folder font (file .ttf disalin dari C:\Windows\Fonts ke public/assets/fonts)
    private const FONT_DIR = 'assets/fonts';

    // family => file .ttf. Kalau nama font di Word beda, cukup ganti file-nya di sini.
    private const FONTS = [
        'arial'      => ['R' => 'arial.ttf', 'B' => 'arialbd.ttf', 'I' => 'ariali.ttf', 'BI' => 'arialbi.ttf'],
        'berlinsans' => ['R' => 'BRLNSDB.TTF'],     // Berlin Sans FB Demi  (varian)
        'baskerville'=> ['R' => 'BASKVILL.TTF'],    // Baskerville Old Face (nama produk)
        'bookantiqua'=> ['R' => 'BOOKOS.TTF'],      // Book Antiqua (harga)
        'arialnarrow'=> ['R' => 'ARIALN.TTF', 'B' => 'ARIALNB.TTF', 'I' => 'ARIALNI.TTF', 'BI' => 'ARIALNBI.TTF'],
        'bodonimt'   => ['R' => 'BOD_R.TTF'],       // Bodoni MT            (nama merk)
        'bodoniblack'=> ['R' => 'BOD_BLAR.TTF'],    // Bodoni MT Black      (harga coret)
        'bodonipost' => ['R' => 'BOD_PSTC.TTF'],    // Bodoni MT Poster Compressed (angka besar)
    ];

    // Ukuran font (pt). Yang berlabel "fit" otomatis mengecil kalau teks kepanjangan.
    private const PT_NOTE       = 20;    // Akhir Periode
    private const PT_PLU        = 27;    // PLU
    private const PT_MERK_MAX   = 33;    // Baskerville Old Face
    private const PT_VARIANT_MAX = 60;   // Berlin Sans FB Demi
    private const PT_NORMAL     = 68;    // harga coret
    private const PT_BIG_MAX    = 420;   // bounding box WordArt 17
    private const PT_SMALL      = 99;    // ".347"
    private const PT_RP_SMALL   = 16;
    private const PT_RP_BIG     = 35;

    // Lebar maksimum area teks (cm) untuk fit
    private const W_MERK    = 17.0;
    private const W_VARIANT = 16.7;
    private const W_BIG     = 11.3;

    /* Posisi (cm) diukur dari pojok kiri-atas gambar kotak kuning.
       'base' = garis dasar teks (bagian bawah huruf/angka), 'left'/'right' = tepi teks. */
    private const POS = [
        'periode' => ['right' => 18.47, 'base' => 2.01],
        'merk'    => ['cx' => 10.13,    'base' => 4.81],
        'variant' => ['cx' => 9.47,     'base' => 7.44],
        'rp_small'=> ['left' => 1.06,   'base' => 9.96],
        'normal'  => ['left' => 2.59,   'base' => 11.06],
        'plu'     => ['right' => 18.47, 'base' => 9.14],
        'rp_big'  => ['left' => 1.06,   'base' => 18.55],
        'big'     => ['left' => 2.33,   'base' => 25.71],
        'small'   => ['right' => 18.47, 'base' => 25.50],
    ];

    /* Teks yang "diregangkan" seperti WordArt di Word (angka besar & varian).
       Ukuran dalam cm, diukur dari pojok kiri-atas gambar kotak kuning.
         h    = tinggi HURUF KAPITAL / angka (tinggi = angka ini, TIDAK ikut mengecil)
         w    = lebar maksimum (kalau teks panjang, huruf dipepetkan sampai muat)
         base = garis dasar teks, dari atas kotak kuning
         max  = batas melebar terhadap tinggi (1.0 = tidak boleh lebih lebar dari bentuk asli font) */
    private const BIG_BOX     = ['x'  => 2.33, 'w' => 11.2, 'h' => 13.0, 'base' => 25.71, 'max' => 1.0];
    private const VARIANT_BOX = ['cx' => 9.47, 'w' => 16.7, 'h' => 1.65, 'base' => 7.44,  'max' => 1.3];
    // Menebalkan angka besar (mm). 0 = normal. Naikkan (mis. 0.6) kalau kurang tebal.
    private const BIG_BOLD    = 0.0;

    // Garis coret: dari kiri-bawah ke kanan-atas (cm, relatif kotak kuning)
    private const STRIKE = ['x1' => 1.38, 'y1' => 10.90, 'over' => 0.79, 'slope' => 0.146, 'thick' => 0.21];

    /* ========================================================================== */

    private Mpdf $mpdf;
    private array $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                            'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function __construct()
    {
        $d  = (new ConfigVariables())->getDefaults();
        $fd = (new FontVariables())->getDefaults();

        $fontdata = [];
        foreach (self::FONTS as $fam => $files) {
            $fontdata[$fam] = $files;
        }

        $this->mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_left'   => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_header' => 0, 'margin_footer' => 0,
            'autoPageBreak' => false,
            'tempDir'       => WRITEPATH . 'mpdf',
            'fontDir'       => array_merge($d['fontDir'], [FCPATH . self::FONT_DIR]),
            'fontdata'      => $fd['fontdata'] + $fontdata,
            'default_font'  => 'arial',
        ]);
    }

    /** @param array<int,array{row:array,qty?:int}> $items */
    public function render(array $items): string
    {
        $first = true;
        foreach ($items as $it) {
            $html = $this->pageHtml($it['row']);
            $qty  = max(1, (int) ($it['qty'] ?? 1));
            for ($i = 0; $i < $qty; $i++) {
                if (! $first) {
                    $this->mpdf->AddPage();
                }
                $this->mpdf->WriteHTML($html);
                $first = false;
            }
        }
        return $this->mpdf->Output('', 'S');
    }

    /* ------------------------------------------------------------------ */

    private function pageHtml(array $row): string
    {
        // ---- 1. teks ----
        $promo  = (int) round((float) $row['promo_price']);
        $normal = (int) round((float) $row['normal_price']);
        $T = [
            'merk'    => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
            'variant' => mb_strtoupper(trim((string) ($row['variant'] ?? ''))),
            'plu'     => (string) ($row['sku_plu'] ?? ''),
            'periode' => $this->tanggal($row['end_period'] ?? ''),
            'normal'  => number_format($normal, 0, ',', '.'),
            'big'     => $promo >= 1000 ? (string) intdiv($promo, 1000) : (string) $promo,
            'small'   => $promo >= 1000 ? str_pad((string) ($promo % 1000), 3, '0', STR_PAD_LEFT) : '',
        ];

        // ---- 2. font & ukuran (fit) ----
        $F = [
            'note'    => ['family' => 'arial',      'pt' => self::PT_NOTE],
            'plu'     => ['family' => 'arialnarrow', 'pt' => self::PT_PLU],
            'rp'      => ['family' => 'arial'],
            'rp_small'=> ['pt' => self::PT_RP_SMALL],
            'rp_big'  => ['pt' => self::PT_RP_BIG],
            'merk'    => ['family' => 'baskerville', 'pt' => $this->fit('baskerville', '', $T['merk'], self::W_MERK, self::PT_MERK_MAX)],
            'variant' => ['family' => 'berlinsans', 'pt' => $this->fit('berlinsans', '', $T['variant'], self::W_VARIANT, self::PT_VARIANT_MAX)],
            'normal'  => ['family' => 'bookantiqua', 'pt' => self::PT_NORMAL, 'style' => 'B'],
            'big'     => ['family' => 'bookantiqua', 'pt' => $this->fit('bookantiqua', '', $T['big'], self::W_BIG, self::PT_BIG_MAX)],
            'small'   => ['family' => 'bookantiqua', 'pt' => self::PT_SMALL],
        ];

        // ---- 3. posisi (cm) ----
        $P = self::POS;
        $L = ['box' => ['x' => self::BOX_X, 'y' => self::BOX_Y, 'w' => self::BOX_W, 'h' => self::BOX_H]];
        $ox = self::BOX_X;
        $oy = self::BOX_Y;

        $top = fn(string $fam, string $style, int|float $pt, float $base) =>
            round($oy + $base - $this->baselineOffsetCm($fam, $style, $pt), 3);

        $L['periode'] = ['left' => round($ox + $P['periode']['right'] - 12.0, 3), 'w' => 12.0,
                         'top' => $top('arial', 'I', $F['note']['pt'], $P['periode']['base'])];
        $L['plu']     = ['left' => round($ox + $P['plu']['right'] - 12.0, 3), 'w' => 12.0,
                         'top' => $top('arialnarrow', 'I', $F['plu']['pt'], $P['plu']['base'])];
        $L['merk']    = ['left' => round($ox + $P['merk']['cx'] - 9.0, 3), 'w' => 18.0,
                         'top' => $top('baskerville', '', $F['merk']['pt'], $P['merk']['base'])];
        $L['variant'] = ['left' => round($ox + $P['variant']['cx'] - 8.36, 3), 'w' => 16.72,
                         'top' => $top('berlinsans', '', $F['variant']['pt'], $P['variant']['base'])];
        $L['rp_small']= ['left' => round($ox + $P['rp_small']['left'], 3),
                         'top' => $top('arial', 'BI', self::PT_RP_SMALL, $P['rp_small']['base'])];
        $L['rp_big']  = ['left' => round($ox + $P['rp_big']['left'], 3),
                         'top' => $top('arial', 'BI', self::PT_RP_BIG, $P['rp_big']['base'])];
        $L['normal']  = ['left' => round($ox + $P['normal']['left'], 3),
                         'top' => $top('bookantiqua', '', self::PT_NORMAL, $P['normal']['base'])];
        $L['big']     = ['left' => round($ox + $P['big']['left'], 3),
                         'top' => $top('bookantiqua', '', $F['big']['pt'], $P['big']['base'])];
        $L['small']   = ['left' => round($ox + $P['small']['right'] - 12.0, 3), 'w' => 12.0,
                         'top' => $top('bookantiqua', '', self::PT_SMALL, $P['small']['base'])];

        // garis coret: panjang mengikuti lebar teks harga normal
        $wNormal = $this->textWidthCm('bookantiqua', '', self::PT_NORMAL, $T['normal']);
        $x2      = $P['normal']['left'] + $wNormal + self::STRIKE['over'];
        $len     = $x2 - self::STRIKE['x1'];
        $rise    = $len * self::STRIKE['slope'];
        $pad     = self::STRIKE['thick'];
        $L['strike'] = [
            'left' => round($ox + self::STRIKE['x1'], 3),
            'top'  => round($oy + self::STRIKE['y1'] - $rise - $pad, 3),
            'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
            'vw'   => round($len, 3), 'vh' => round($rise + 2 * $pad, 3),
            'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
        ];

        // ---- 4. teks regang (SVG): varian & angka besar ----
        $S = [
            'w'       => self::BOX_W * 10,      // mm
            'h'       => self::BOX_H * 10,
            'bold'    => self::BIG_BOLD,
            'variant' => $this->stretch('berlinsans', $T['variant'], self::VARIANT_BOX, 'center'),
            'big'     => $this->stretch('bookantiqua', $T['big'], self::BIG_BOX, 'left'),
        ];

        // ---- 5. render view ----
        $img = FCPATH . self::IMG_FILE;
        return view('pricetag/templates/turun-hrg-a4', compact('T', 'L', 'F', 'S', 'img'));
    }

    /* ---------------- util ---------------- */

    private function tanggal(string $ymd): string
    {
        $ts = strtotime($ymd);
        if (! $ts) {
            return $ymd;
        }
        return date('j', $ts) . ' ' . $this->bulan[(int) date('n', $ts)] . " '" . date('y', $ts);
    }

    private function setFont(string $fam, string $style, float $pt): void
    {
        $this->mpdf->SetFont($fam, $style, $pt);
    }

    private function textWidthCm(string $fam, string $style, float $pt, string $text): float
    {
        $this->setFont($fam, $style, $pt);
        return $this->mpdf->GetStringWidth($text) / 10;   // mm -> cm
    }

    /** ukuran font (pt) supaya teks pas selebar $maxCm, tidak lebih dari $maxPt */
    private function fit(string $fam, string $style, string $text, float $maxCm, float $maxPt): float
    {
        $w100 = $this->textWidthCm($fam, $style, 100, $text);
        if ($w100 <= 0) {
            return $maxPt;
        }
        return round(min($maxPt, 100 * $maxCm / $w100), 1);
    }

    /**
     * Hitung skala x/y supaya teks memenuhi kotak seperti WordArt (huruf ditinggikan / dipepetkan).
     * Hasil dipakai view sebagai <g transform="translate(x,y) scale(sx,sy)"> di dalam SVG (satuan mm).
     */
    private function stretch(string $fam, string $text, array $box, string $align): array
    {
        $base = 10.0;                               // ukuran font dasar (mm)
        $this->setFont($fam, '', $base / 0.352778); // mm -> pt
        $w0  = $this->mpdf->GetStringWidth($text);  // lebar teks (mm) pada ukuran dasar
        $cap = (($this->mpdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * $base;
        if ($w0 <= 0 || $cap <= 0) {
            return ['family' => $fam, 'fs' => $base, 'x' => 0, 'y' => 0, 'sx' => 1, 'sy' => 1, 'text' => $text];
        }
        $sy = ($box['h'] * 10) / $cap;
        $sx = min(($box['w'] * 10) / $w0, $sy * $box['max']);
        $drawn = $w0 * $sx;
        $x = $align === 'center' ? $box['cx'] * 10 - $drawn / 2 : $box['x'] * 10;

        return ['family' => $fam, 'fs' => $base, 'x' => round($x, 2), 'y' => round($box['base'] * 10, 2),
                'sx' => round($sx, 4), 'sy' => round($sy, 4), 'text' => $text];
    }

    /** jarak (cm) dari atas kotak teks ke garis dasar huruf, untuk line-height = ukuran font */
    private function baselineOffsetCm(string $fam, string $style, float $pt): float
    {
        $this->setFont($fam, $style, $pt);
        $desc = $this->mpdf->CurrentFont['desc'];
        $a = ($desc['Ascent'] ?? 900) / 1000;
        $d = abs($desc['Descent'] ?? -200) / 1000;
        $em = (1 - ($a + $d)) / 2 + $a;         // setengah leading + ascent
        return $em * $pt * 0.0352778;           // pt -> cm
    }
}