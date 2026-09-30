<?php

namespace App\Libraries;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Cetak POP Price Tag A4 langsung dari HTML (mPDF), tanpa Word/LibreOffice.
 * Satu file ini menangani TIGA desain kartu:
 *   - 'a4'       -> kartu "MINYAK GORENG" (Picture1.jpg)
 *   - 'segitiga' -> kartu "Segitiga Turun Harga" / TESSA-TP.06 (Picture3.png)
 *   - 'special'  -> kartu "Segitiga Special Price" (Picture3.png / gambar sendiri)
 *   - 'diskon'   -> kartu "DISKON 33%" (GIZZI-MILK), view: segitiga-diskon.php
 * Tiap desain punya konstanta sendiri (A4_*, SEG_*, SPC_*, DSK_*) supaya tidak saling bentrok.
 *
 * Pemakaian (controller):
 *   $pdf = (new \App\Libraries\PopA4Pdf())->render([
 *       ['row' => $rowDb, 'qty' => 3],
 *   ], 'diskon');   // 'a4' (default) | 'segitiga' | 'special' | 'diskon'
 *   return $this->response->setContentType('application/pdf')->setBody($pdf);
 */
class PopA4Pdf
{
    /* ============================================================================
     * DESAIN 1: "a4" — kartu MINYAK GORENG (Picture1.jpg)
     * ==========================================================================*/

    private const A4_IMG_FILE = 'assets/img/Picture1.jpg';
    private const A4_BOX_W    = 19.51;
    private const A4_BOX_H    = 27.94;
    private const A4_BOX_X    = 0.745;
    private const A4_BOX_Y    = 0.50;

    private const A4_FONTS = [
        'arial'      => ['R' => 'arial.ttf', 'B' => 'arialbd.ttf', 'I' => 'ariali.ttf', 'BI' => 'arialbi.ttf'],
        'berlinsans' => ['R' => 'BRLNSDB.TTF'],
        'baskerville'=> ['R' => 'BASKVILL.TTF'],
        // Hanya BOOKOS.TTF tersedia di assets/fonts; gunakan font reguler
        // sebagai fallback untuk style bold agar mPDF tidak gagal memuat font.
        'bookantiqua'=> ['R' => 'BOOKOS.TTF'],
        'arialnarrow'=> ['R' => 'ARIALN.TTF', 'B' => 'ARIALNB.TTF', 'I' => 'ARIALNI.TTF', 'BI' => 'ARIALNBI.TTF'],
        'bodonimt'   => ['R' => 'BOD_R.TTF'],
        'bodoniblack'=> ['R' => 'BOD_BLAR.TTF'],
        'bodonipost' => ['R' => 'BOD_PSTC.TTF'],
    ];

    private const A4_PT_NOTE        = 20;
    private const A4_PT_PLU         = 27;
    private const A4_PT_MERK_MAX    = 33;
    private const A4_PT_VARIANT_MAX = 60;
    private const A4_PT_NORMAL      = 68;
    private const A4_PT_BIG_MAX     = 420;
    private const A4_PT_SMALL       = 99;
    private const A4_PT_RP_SMALL    = 16;
    private const A4_PT_RP_BIG      = 35;

    private const A4_W_MERK    = 17.0;
    private const A4_W_VARIANT = 16.7;
    private const A4_W_BIG     = 11.3;

    private const A4_POS = [
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

    private const A4_BIG_BOX     = ['x'  => 2.33, 'w' => 11.2, 'h' => 13.0, 'base' => 25.71, 'max' => 1.0];
    private const A4_VARIANT_BOX = ['cx' => 9.47, 'w' => 16.7, 'h' => 1.65, 'base' => 7.44,  'max' => 1.3];
    private const A4_BIG_BOLD    = 0.0;

    private const A4_STRIKE = ['x1' => 1.38, 'y1' => 10.90, 'over' => 0.79, 'slope' => 0.146, 'thick' => 0.21];

    /* ============================================================================
     * DESAIN 2: "segitiga" — kartu Segitiga Turun Harga / TESSA-TP.06 (Picture3.png)
     * ==========================================================================*/

    private const SEG_IMG_FILE = 'assets/img/Picture3.png';
    // Ukuran fisik kartu segitiga: lebar 18,5 cm x tinggi 25 cm.
    private const SEG_BOX_W    = 18.50;
    private const SEG_BOX_H    = 25.00;
    // Kartu diposisikan di tengah halaman A4 (21 x 29,7 cm),
    // sehingga tersisa margin putih di sekeliling kartu.
    private const SEG_BOX_X    = (21.00 - self::SEG_BOX_W) / 2;
    private const SEG_BOX_Y    = 1.00;

    private const SEG_FONTS = [
        'bahnschrift' => ['R' => 'bahnschrift.ttf'],
        'bernardmt'   => ['R' => 'BERNHC.TTF'],
        'arialblack'  => ['R' => 'ariblk.ttf'],
        // 'bookantiqua' dipakai bersama dengan desain "a4" di atas (font & file sama).
    ];

    // teks              family         w      h      x      base   align    warna     outline
    private const SEG_EL = [
        'periode' => ['bahnschrift', 6.98, 0.60,  16.58,  2.08, 'right', '#0000CC', null],
        'alokasi' => ['bahnschrift', 4.61, 0.60,  16.12,  3.02, 'right', '#FF0000', '#FF0000 0.7pt'],
        'nama_produk'   => ['bernardmt',  15.15, 3.37,   9.87,  7.57, 'center', '#101AE0', null],
        'variant'  => ['arialblack', 8.37, 1.05,   9.62,  9.45, 'center', '#000000', null],
        'rp_lama' => ['bookantiqua', 1.00, 1.12,   3.31, 12.77, 'left',  '#000000', null],
        'lama'    => ['bookantiqua', 7.97, 1.91,   4.75, 12.82, 'left',  '#000000', '#000000 1pt'],
        'rp_baru' => ['bookantiqua', 1.40, 1.60,   1.80, 19.20, 'left',  '#000000', null],
        'baru'    => ['arialblack', 12.55, 7.97,   4.19, 21.85, 'left',  '#FF0000', null],
        // PLU dibuat satu elemen terpusat, sama seperti desain "special price".
        'plu'     => ['arialblack',  5.20, 0.66,   9.38, 23.32, 'center', '#000080', null],
    ];

    private const SEG_STRIKE = ['x1' => 3.2, 'y1' => 13.55, 'over' => 0.4, 'slope' => 0.16, 'thick' => 0.18];

    /* ============================================================================
     * DESAIN 3: "special" — kartu Segitiga Special Price
     * Ukuran & posisi kartu di A4 sama dengan desain "segitiga" (SEG_BOX_*),
     * font juga sama (SEG_FONTS). Yang beda hanya gambar latar & posisi teks.
     * ==========================================================================*/

    // Gambar latar (kuning + kotak putih + logo MURAH), tanpa teks.
    // Sementara memakai gambar yang sama dengan segitiga. Kalau punya gambar khusus,
    // ganti misalnya jadi 'assets/img/Picture4.png'.
    private const SPC_IMG_FILE = 'assets/img/Picture3.png';

    // Semua ukuran dalam cm, x/base diukur dari pojok kiri-atas kartu (kotak kuning).
    // teks         family        w      h      x      base    align     warna      outline           maxRatio
    // maxRatio = batas lebar huruf terhadap tinggi (0 = bebas), supaya nama pendek tidak melar.
    private const SPC_EL = [
        'judul'       => ['bernardmt',  12.10, 3.10,  9.80,  7.44, 'center', '#FF0000', '#000000 0.08pt', 0],
        'nama_produk' => ['bernardmt',  15.50, 2.80,  9.20, 10.98, 'center', '#101AE0', '#000000 0.08pt', 0.8],
        'variant'     => ['arialblack',  8.00, 0.95,  9.16, 13.28, 'center', '#000000', null,              0],
        'rp'          => ['bookantiqua', 1.50, 1.00,  1.59, 16.73, 'left',   '#000000', null,              0],
        'harga'       => ['arialblack', 12.90, 7.97,  3.98, 22.22, 'left',   '#FF0000', '#000000 0.04pt', 0],
        'plu'         => ['arialblack',  5.20, 0.66,  9.38, 23.32, 'center', '#000080', null,              0],
    ];


    /* ============================================================================
     * DESAIN 4: "diskon" — kartu DISKON 33% (contoh: GIZZI-MILK WF SELECTION)
     * Ukuran & posisi kartu di A4 sama dengan "segitiga". View: segitiga-diskon.php
     * ==========================================================================*/

    // Gambar latar tanpa teks. Sementara sama dengan segitiga; kalau punya gambar khusus
    // (bingkai lebih tebal, dsb.) ganti misalnya jadi 'assets/img/Picture4.png'.
    private const DSK_IMG_FILE = 'assets/img/Picture3.png';

    // Font tulisan DISKON. false = pakai Book Antiqua (aman, default).
    // true = pakai MingLiU-ExtB dari public/assets/fonts/mingliub.ttc (kalau file ada).
    private const DSK_PAKAI_MINGLIU = false;

    // Jarak antara angka diskon (33) dan tanda % (cm)
    private const DSK_GAP_PERSEN = 0.26;

    // Semua ukuran dalam cm, x/base diukur dari pojok kiri-atas kartu.
    // Family 'MING' = MingLiU-ExtB (kalau file mingliub.ttc tidak ada, otomatis diganti Book Antiqua).
    // Angka outline (mis. 0.03pt) satuannya mengikuti skala huruf, bukan pt sebenarnya; kecil = tipis.
    // teks           family         w      h      x      base    align     warna      outline           maxRatio
    private const DSK_EL = [
        'periode'     => ['bahnschrift', 6.98, 0.60, 15.77,  2.03, 'right',  '#0000CC', null,              0],
        'alokasi'     => ['bahnschrift', 4.61, 0.60, 15.73,  2.82, 'right',  '#FF0000', '#FF0000 0.7pt',   0],
        'nama_produk' => ['bernardmt',  15.13, 2.59,  9.34,  7.00, 'center', '#0000FF', '#000000 0.03pt', 0.6],
        'variant'     => ['berlinsans',  3.18, 0.85,  8.99,  8.33, 'center', '#000000', null,              0.8],
        'label'       => ['MING',        5.98, 1.20,  1.89, 10.00, 'left',   '#FFFF00', '#000000 0.25pt', 0],
        'angka'       => ['arialblack',  9.92, 6.40,  4.19, 16.61, 'left',   '#FF0000', '#000000 0.02pt', 0.85],
        'persen'      => ['arialblack',  2.00, 1.85,  0.00, 12.29, 'left',   '#000000', '#000000 0.1pt',  0],   // x dihitung otomatis
        'rp_lama'     => ['arialblack',  1.05, 0.78,  1.18, 18.58, 'left',   '#000000', null,              0],
        'lama'        => ['bernardmt',   7.35, 1.72,  2.47, 19.46, 'left',   '#000000', '#000000 0.15pt', 0.75],
        'rp_baru'     => ['bookantiqua', 1.59, 1.00,  6.70, 20.84, 'left',   '#000000', null,              0],
        'baru'        => ['bernardmt',   8.15, 4.00,  8.81, 22.33, 'left',   '#FF0000', '#000000 0.1pt',  0.75],
        'plu_lbl'     => ['arialblack',  1.79, 0.66,  6.70, 23.66, 'left',   '#000000', null,              0],
        'plu_val'     => ['arialblack',  2.59, 0.66,  9.25, 23.66, 'left',   '#000000', null,              0],
    ];

    private const DSK_STRIKE = ['x1' => 2.15, 'y1' => 19.45, 'over' => 0.35, 'slope' => 0.20, 'thick' => 0.15];

    /* ========================================================================== */

    private Mpdf $mpdf;

    /** Font untuk tulisan DISKON: MingLiU-ExtB kalau file tersedia, kalau tidak Book Antiqua. */
    private string $diskonFont = 'bookantiqua';

    private array $bulanPanjang = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                                   'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function __construct(string $template = 'a4')
    {
        $d  = (new ConfigVariables())->getDefaults();
        $fd = (new FontVariables())->getDefaults();

        // Font semua desain digabung di sini supaya bisa dipakai mPDF.
        $fontdata = self::A4_FONTS + self::SEG_FONTS;

        // MingLiU-ExtB (opsional, lihat DSK_PAKAI_MINGLIU). File TTC berisi beberapa font; nomor 2 = MingLiU-ExtB.
        // Kalau tampilan hurufnya bukan yang diinginkan, coba ubah nomor 2 menjadi 1 atau 3.
        if (self::DSK_PAKAI_MINGLIU && is_file(FCPATH . 'assets/fonts/mingliub.ttc')) {
            $fontdata['mingliub'] = ['R' => 'mingliub.ttc', 'TTCfontID' => ['R' => 2]];
            $this->diskonFont = 'mingliub';
        }

        $this->mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_left'   => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_header' => 0, 'margin_footer' => 0,
            'autoPageBreak' => false,
            'tempDir'       => WRITEPATH . 'mpdf',
            'fontDir'       => array_merge($d['fontDir'], [FCPATH . 'assets/fonts']),
            'fontdata'      => $fd['fontdata'] + $fontdata,
            'default_font'  => 'arial',
        ]);
    }

    /**
     * @param array<int,array{row:array,qty?:int}> $items
     * @param string $template 'a4' (default) | 'segitiga' | 'special' | 'diskon'
     */
    public function render(array $items, string $template = 'a4'): string
    {
        $first = true;
        foreach ($items as $it) {
            $html = match ($template) {
                'segitiga' => $this->pageHtmlSegitiga($it['row']),
                'special'  => $this->pageHtmlSpecial($it['row']),
                'diskon'   => $this->pageHtmlDiskon($it['row']),
                default    => $this->pageHtmlA4($it['row']),
            };

            $qty = max(1, (int) ($it['qty'] ?? 1));
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

    /* ====================== DESAIN 1: "a4" ====================== */

    private function pageHtmlA4(array $row): string
    {
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

        $F = [
            'note'    => ['family' => 'arial',      'pt' => self::A4_PT_NOTE],
            'plu'     => ['family' => 'arialnarrow', 'pt' => self::A4_PT_PLU],
            'rp'      => ['family' => 'arial'],
            'rp_small'=> ['pt' => self::A4_PT_RP_SMALL],
            'rp_big'  => ['pt' => self::A4_PT_RP_BIG],
            'merk'    => ['family' => 'baskerville', 'pt' => $this->fit('baskerville', '', $T['merk'], self::A4_W_MERK, self::A4_PT_MERK_MAX)],
            'variant' => ['family' => 'berlinsans', 'pt' => $this->fit('berlinsans', '', $T['variant'], self::A4_W_VARIANT, self::A4_PT_VARIANT_MAX)],
            'normal'  => ['family' => 'bookantiqua', 'pt' => self::A4_PT_NORMAL, 'style' => 'B'],
            'big'     => ['family' => 'bookantiqua', 'pt' => $this->fit('bookantiqua', '', $T['big'], self::A4_W_BIG, self::A4_PT_BIG_MAX)],
            'small'   => ['family' => 'bookantiqua', 'pt' => self::A4_PT_SMALL],
        ];

        $P  = self::A4_POS;
        $ox = self::A4_BOX_X;
        $oy = self::A4_BOX_Y;
        $L  = ['box' => ['x' => $ox, 'y' => $oy, 'w' => self::A4_BOX_W, 'h' => self::A4_BOX_H]];

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
                         'top' => $top('arial', 'BI', self::A4_PT_RP_SMALL, $P['rp_small']['base'])];
        $L['rp_big']  = ['left' => round($ox + $P['rp_big']['left'], 3),
                         'top' => $top('arial', 'BI', self::A4_PT_RP_BIG, $P['rp_big']['base'])];
        $L['normal']  = ['left' => round($ox + $P['normal']['left'], 3),
                         'top' => $top('bookantiqua', '', self::A4_PT_NORMAL, $P['normal']['base'])];
        $L['big']     = ['left' => round($ox + $P['big']['left'], 3),
                         'top' => $top('bookantiqua', '', $F['big']['pt'], $P['big']['base'])];
        $L['small']   = ['left' => round($ox + $P['small']['right'] - 12.0, 3), 'w' => 12.0,
                         'top' => $top('bookantiqua', '', self::A4_PT_SMALL, $P['small']['base'])];

        $wNormal = $this->textWidthCm('bookantiqua', '', self::A4_PT_NORMAL, $T['normal']);
        $x2      = $P['normal']['left'] + $wNormal + self::A4_STRIKE['over'];
        $len     = $x2 - self::A4_STRIKE['x1'];
        $rise    = $len * self::A4_STRIKE['slope'];
        $pad     = self::A4_STRIKE['thick'];
        $L['strike'] = [
            'left' => round($ox + self::A4_STRIKE['x1'], 3),
            'top'  => round($oy + self::A4_STRIKE['y1'] - $rise - $pad, 3),
            'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
            'vw'   => round($len, 3), 'vh' => round($rise + 2 * $pad, 3),
            'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
        ];

        $S = [
            'w'       => self::A4_BOX_W * 10,
            'h'       => self::A4_BOX_H * 10,
            'bold'    => self::A4_BIG_BOLD,
            'variant' => $this->stretchBox('berlinsans', $T['variant'], self::A4_VARIANT_BOX, 'center'),
            'big'     => $this->stretchBox('bookantiqua', $T['big'], self::A4_BIG_BOX, 'left'),
        ];

        $img = FCPATH . self::A4_IMG_FILE;
        return view('pricetag/templates/turun-hrg-a4', compact('T', 'L', 'F', 'S', 'img'));
    }

    /* ====================== DESAIN 2: "segitiga" ====================== */

    private function pageHtmlSegitiga(array $row): string
    {
        $promo  = (int) round((float) $row['promo_price']);
        $normal = (int) round((float) $row['normal_price']);

        $text = [
            'periode' => 'Akhir Periode : ' . $this->tanggal($row['end_period'] ?? ''),
            'alokasi' => 'Alokasi : ' . number_format((float) ($row['allocation_pcs'] ?? 0), 0, ',', '.') . ' Pcs',
            'nama_produk'   => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
            'variant'  => mb_strtoupper(trim((string) ($row['variant'] ?? ''))),
            'rp_lama' => 'Rp',
            'lama'    => number_format($normal, 0, ',', '.'),
            'rp_baru' => 'Rp',
            'baru'    => number_format($promo, 0, ',', '.'),
            'plu'     => 'PLU : ' . (string) ($row['sku_plu'] ?? ''),
        ];

        $S = ['w' => self::SEG_BOX_W * 10, 'h' => self::SEG_BOX_H * 10, 'el' => []];
        foreach (self::SEG_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline] = $def;
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        $lama = $S['el']['lama'];
        $x2   = self::SEG_EL['lama'][3] + ($lama['sx'] * $lama['w0']) / 10 + self::SEG_STRIKE['over'];
        $len  = $x2 - self::SEG_STRIKE['x1'];
        $rise = $len * self::SEG_STRIKE['slope'];
        $pad  = self::SEG_STRIKE['thick'];
        $L = ['strike' => [
            'left' => round(self::SEG_BOX_X + self::SEG_STRIKE['x1'], 3),
            'top'  => round(self::SEG_BOX_Y + self::SEG_STRIKE['y1'] - $rise - $pad, 3),
            'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
            'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
        ]];

        $box = ['x' => self::SEG_BOX_X, 'y' => self::SEG_BOX_Y, 'w' => self::SEG_BOX_W, 'h' => self::SEG_BOX_H];
        $img = FCPATH . self::SEG_IMG_FILE;

        return view('pricetag/templates/segitiga-turun-harga', compact('S', 'L', 'box', 'img'));
    }

    /* ====================== DESAIN 3: "special" ====================== */

    private function pageHtmlSpecial(array $row): string
    {
        $promo = (int) round((float) $row['promo_price']);

        $text = [
            'judul'       => 'Special Price',
            'nama_produk' => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
            'variant'     => mb_strtoupper(trim((string) ($row['variant'] ?? ''))),
            'rp'          => 'Rp',
            'harga'       => number_format($promo, 0, ',', '.'),
            'plu'         => 'PLU : ' . (string) ($row['sku_plu'] ?? ''),
        ];

        $S = ['w' => self::SEG_BOX_W * 10, 'h' => self::SEG_BOX_H * 10, 'el' => []];
        foreach (self::SPC_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        $box = ['x' => self::SEG_BOX_X, 'y' => self::SEG_BOX_Y, 'w' => self::SEG_BOX_W, 'h' => self::SEG_BOX_H];
        $img = FCPATH . self::SPC_IMG_FILE;

        return view('pricetag/templates/segitiga-sp', compact('S', 'box', 'img'));
    }

    /* ====================== DESAIN 4: "diskon" ====================== */

    private function pageHtmlDiskon(array $row): string
    {
        $promo  = (int) round((float) $row['promo_price']);
        $normal = (int) round((float) $row['normal_price']);

        // Persen diskon: cari di beberapa kemungkinan nama kolom.
        $pct = 0;
        foreach (['discount_percent', 'discount_pct', 'disc_percent', 'diskon', 'discount', 'percent'] as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && (float) $row[$k] > 0) {
                $pct = (int) round((float) $row[$k]);
                break;
            }
        }

        // Kalau harga promo kosong/0 tapi persen ada -> harga promo = harga normal dikurangi persen.
        if ($promo <= 0 && $pct > 0 && $normal > 0) {
            $promo = (int) round($normal * (1 - $pct / 100));
        }
        // Kalau persen kosong tapi harga promo ada -> persen dihitung dari harga.
        if ($pct <= 0 && $normal > 0 && $promo > 0) {
            $pct = (int) round((1 - $promo / $normal) * 100);
        }

        $text = [
            'periode'     => 'Akhir Periode : ' . mb_strtoupper($this->tanggal($row['end_period'] ?? '')),
            'alokasi'     => 'Alokasi : ' . number_format((float) ($row['allocation_pcs'] ?? 0), 0, ',', '.') . ' Pc',
            'nama_produk' => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
            'variant'     => mb_strtoupper(trim((string) ($row['variant'] ?? ''))),
            'label'       => 'DISKON',
            'angka'       => (string) $pct,
            'persen'      => '%',
            'rp_lama'     => 'Rp.',
            'lama'        => number_format($normal, 0, ',', '.'),
            'rp_baru'     => 'Rp.',
            'baru'        => number_format($promo, 0, ',', '.'),
            'plu_lbl'     => 'PLU :',
            'plu_val'     => (string) ($row['sku_plu'] ?? ''),
        ];

        $S = ['w' => self::SEG_BOX_W * 10, 'h' => self::SEG_BOX_H * 10, 'el' => []];
        foreach (self::DSK_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
            if ($fam === 'MING') {
                $fam = $this->diskonFont;
            }
            if ($key === 'persen') {
                // tanda % menempel di kanan angka diskon, berapa pun lebar angkanya
                $a = $S['el']['angka'];
                $x = ($a['x'] + $a['sx'] * $a['w0']) / 10 + self::DSK_GAP_PERSEN;
            }
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        // Garis coret merah pada harga lama
        $lama = $S['el']['lama'];
        $x2   = ($lama['x'] + $lama['sx'] * $lama['w0']) / 10 + self::DSK_STRIKE['over'];
        $len  = $x2 - self::DSK_STRIKE['x1'];
        $rise = $len * self::DSK_STRIKE['slope'];
        $pad  = self::DSK_STRIKE['thick'];
        $L = ['strike' => [
            'left' => round(self::SEG_BOX_X + self::DSK_STRIKE['x1'], 3),
            'top'  => round(self::SEG_BOX_Y + self::DSK_STRIKE['y1'] - $rise - $pad, 3),
            'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
            'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
        ]];

        $box = ['x' => self::SEG_BOX_X, 'y' => self::SEG_BOX_Y, 'w' => self::SEG_BOX_W, 'h' => self::SEG_BOX_H];
        $img = FCPATH . self::DSK_IMG_FILE;

        return view('pricetag/templates/segitiga-discount', compact('S', 'L', 'box', 'img'));
    }

    /* ---------------- util bersama ---------------- */

    private function tanggal(string $ymd): string
    {
        $ts = strtotime($ymd);
        if (! $ts) {
            return $ymd;
        }
        return date('j', $ts) . ' ' . $this->bulanPanjang[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    private function setFont(string $fam, string $style, float $pt): void
    {
        $this->mpdf->SetFont($fam, $style, $pt);
    }

    private function textWidthCm(string $fam, string $style, float $pt, string $text): float
    {
        $this->setFont($fam, $style, $pt);
        return $this->mpdf->GetStringWidth($text) / 10;
    }

    private function fit(string $fam, string $style, string $text, float $maxCm, float $maxPt): float
    {
        $w100 = $this->textWidthCm($fam, $style, 100, $text);
        if ($w100 <= 0) {
            return $maxPt;
        }
        return round(min($maxPt, 100 * $maxCm / $w100), 1);
    }

    /** Dipakai desain "a4": kotak berformat x/cx + w + h + base + max. */
    private function stretchBox(string $fam, string $text, array $box, string $align): array
    {
        $base = 10.0;
        $this->setFont($fam, '', $base / 0.352778);
        $w0  = $this->mpdf->GetStringWidth($text);
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

    /**
     * Dipakai desain "segitiga" & "special": kotak wCm/hCm/xCm/baseCm terpisah (dari tabel WordArt).
     * $maxRatio > 0 membatasi lebar huruf maksimal (sx <= sy * maxRatio) agar teks pendek tidak melar.
     */
    private function stretchEl(string $fam, string $text, float $wCm, float $hCm, float $xCm, float $baseCm, string $align, float $maxRatio = 0): array
    {
        $base = 10.0;
        $this->setFont($fam, '', $base / 0.352778);
        $w0  = $this->mpdf->GetStringWidth($text);
        $cap = (($this->mpdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * $base;
        if ($w0 <= 0 || $cap <= 0) {
            return ['family' => $fam, 'fs' => $base, 'x' => 0, 'y' => 0, 'sx' => 1, 'sy' => 1, 'w0' => 0, 'text' => $text];
        }
        $sy = ($hCm * 10) / $cap;
        $sx = ($wCm * 10) / $w0;
        if ($maxRatio > 0) {
            $sx = min($sx, $sy * $maxRatio);
        }
        $drawn = $w0 * $sx;
        $xMm = $xCm * 10;
        $x = match ($align) {
            'right'  => $xMm - $drawn,
            'center' => $xMm - $drawn / 2,
            default  => $xMm,
        };

        return [
            'family' => $fam, 'fs' => $base, 'text' => $text,
            'x' => round($x, 2), 'y' => round($baseCm * 10, 2),
            'sx' => round($sx, 4), 'sy' => round($sy, 4), 'w0' => round($w0, 3),
        ];
    }

    private function baselineOffsetCm(string $fam, string $style, float $pt): float
    {
        $this->setFont($fam, $style, $pt);
        $desc = $this->mpdf->CurrentFont['desc'];
        $a = ($desc['Ascent'] ?? 900) / 1000;
        $d = abs($desc['Descent'] ?? -200) / 1000;
        $em = (1 - ($a + $d)) / 2 + $a;
        return $em * $pt * 0.0352778;
    }
}
