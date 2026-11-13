<?php

namespace App\Libraries;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use RuntimeException;

/**
 * Renderer mPDF untuk template yang sebelumnya dibuat melalui LibreOffice.
 *
 * Jalur ini sengaja dipisahkan dari DocxLabelMerger agar migrasi dapat dilakukan
 * bertahap. Jalur Libre tidak diubah dan tetap menjadi default.
 *
 * Template yang punya 'builder' menghitung teks "Fit to Shape" (SVG) di class ini
 * lalu mengirim hasilnya ke view; 'pagePerTag' => true = tiap tag di halaman sendiri.
 */
class MpdfLibreTemplate
{
    private const TEMPLATES = [
        'turun-harga-kcl'          => ['view' => 'turun-hrg-kcl', 'size' => [70, 45]],
        'turun-harga-tgg'          => ['view' => 'turun-hrg-tgg', 'size' => [210, 297], 'builder' => 'buildTgg', 'pagePerTag' => true],
        'turun-harga-tgg-allvar'   => ['view' => 'turun-hrg-tgg-allvar-mpdf', 'size' => [210, 297], 'builder' => 'buildTggAllvar', 'pagePerTag' => true],
        'turun-harga-tgg-allocation' => ['view' => 'turun-hrg-tgg-allocation', 'size' => [210, 297], 'builder' => 'buildTggAllocation', 'pagePerTag' => true],
        'disc-reg-tgg'             => ['view' => 'discount-tgg', 'size' => [210, 297], 'builder' => 'buildTggDiscount', 'pagePerTag' => true],
        'disc-reg-kcl'             => ['view' => 'discount-reg-kcl', 'size' => [70, 45]],
        'disc-reg-kcl-allocation'  => ['view' => 'discount-reg-kcl-allocation', 'size' => [70, 45]],
    ];

    /* ---------- font untuk SVG (file di public/assets/fonts, sama dengan PopA4Pdf) ---------- */
    private const FONTS = [
        'arialblack'     => ['R' => 'ariblk.ttf'],
        'arialnarrow'    => ['R' => 'ARIALN.TTF', 'B' => 'ARIALNB.TTF', 'I' => 'ARIALNI.TTF', 'BI' => 'ARIALNBI.TTF'],
        'arialnarrowb'   => ['R' => 'ARIALNB.TTF'],
        // Samakan dengan PopA4Pdf: A4 memakai BKANT regular untuk satu digit
        // dan ANTQUAB untuk angka besar multi-digit. BOOKOS adalah Bookman,
        // bukan Book Antiqua, sehingga tidak dipakai untuk angka promo.
        'bookantiqua'    => ['R' => 'BKANT.TTF'],
        'bookantiquab'   => ['R' => 'ANTQUAB.TTF'],
        'calistomt'      => ['R' => 'CALIST.TTF'],
        'berlinsansfb'   => ['R' => 'BRLNSB.TTF'],
        'berlinsansfbregular' => ['R' => 'BRLNSR.TTF'],
        'arialb'         => ['R' => 'arialbd.ttf'],     // Arial Bold
        'centurygothicb' => ['R' => 'GOTHICB.TTF'],     // Century Gothic Bold
    ];

    /* ============================================================================
     * 'turun-harga-tgg' — kertas pre-printed (kuning + logo MURAH sudah tercetak), TANPA latar.
     * Halaman = A4 penuh seperti hasil Libre: hanya area kartu yang berisi, sisanya kosong.
     * Semua posisi/ukuran (cm) diukur dari hasil Libre, dari pojok kiri-atas HALAMAN A4.
     * ==========================================================================*/

    // Ubah X/Y (cm) untuk menggeser seluruh isi saat kalibrasi hasil cetak.
    private const TGG_BOX_X = 0.0;
    private const TGG_BOX_Y = 0.0;
    private const TGG_BOX_W = 21.00;
    private const TGG_BOX_H = 29.70;

    // teks        family            w      h      x      base   align     warna      outline           maxRatio
    // (base = garis dasar teks; w/h = lebar & tinggi huruf kapital)
    private const TGG_EL = [
        'pop'      => ['arialnarrow',    2.60, 0.32, 14.80, 1.13, 'left',   '#000000', null,              0],
        // Ukuran diperkecil, posisi tetap sama seperti hasil Libre.
        'nama'     => ['arialblack',    10.60, 0.61, 11.75, 3.33, 'center', '#00B0F0', '#000000 0.08pt', 0.9],
        'plu'      => ['arialnarrow',    2.57, 0.28, 14.64, 3.92, 'left',   '#000000', null,              1.1],
        'rp_lama'  => ['calistomt',      0.60, 0.40,  4.14, 4.10, 'left',   '#FF0000', null,              0],
        'lama'     => ['bookantiqua',    3.58, 1.05,  5.11, 4.71, 'left',   '#000000', '#000000 0.3pt',   1.3],
        'rp_baru'  => ['calistomt',      0.60, 0.40,  8.73, 4.99, 'left',   '#FF0000', null,              0],
        'alokasi'  => ['arialb',         3.54, 0.32,  3.98, 6.76, 'left',   '#FF0000', null,              0],
        'periode1' => ['centurygothicb', 1.97, 0.40,  4.02, 5.71, 'left',   '#000000', null,              0],
    ];
    // Baris kedua "Akhir Periode": rata kiri di x, skala dibuat SAMA dengan baris pertama
    private const TGG_PERIODE2 = ['x' => 4.02, 'h' => 0.40, 'base' => 6.24];

    // Ribuan & ratusan (lebar dibatasi supaya huruf tidak melar)
    // Rasio lebar/tinggi WordArt aktif adalah 127.3/93.45 = 1.362.
    // Gunakan rasio tersebut sebagai batas scaling horizontal agar glyph
    // dapat mengisi proporsi objek DOCX tanpa mengubah tinggi.
    // Tinggi, titik kiri, dan kotak angka tetap; pelebaran hanya berlaku pada
    // elemen Ribuan melalui sx di stretchEl().
    private const TGG_BIG   = ['x' => 9.29,  'w' => 4.67, 'h' => 3.15, 'base' => 7.08, 'max' => 1.60];
    // Kalibrasi khusus Ribuan dua digit; tiga digit tetap memakai TGG_BIG.
    private const TGG_BIG_2 = ['h' => 3.20, 'base' => 6.98];
    private const TGG_SMALL = ['x' => 14.24, 'w' => 2.97, 'h' => 1.13, 'base' => 7.06, 'max' => 1.0];

    // Layout awal Allocation (A4); seluruh koordinat diukur absolut dari kiri-atas halaman.
    private const TGG_ALLOC_EL = [
        'pop'      => ['arialnarrow', 2.10, 0.25, 13.70, 1.13, 'left', '#000000', null, 0],
        'nama'     => ['arialblack', 10.60, 0.61, 11.75, 3.13, 'center', '#00B0F0', '#000000 0.08pt', 0.9],
        'plu'      => ['arialnarrow', 2.57, 0.28, 14.64, 3.82, 'left', '#000000', null, 1.1],
        'rp_lama'  => ['calistomt', 0.60, 0.40, 4.14, 3.98, 'left', '#FF0000', null, 0],
        'lama'     => ['bookantiqua', 3.58, 1.05, 5.11, 4.59, 'left', '#000000', '#000000 0.3pt', 1.3],
        'rp_baru'  => ['calistomt', 0.60, 0.40, 8.73, 5.09, 'left', '#FF0000', null, 0],
        'alokasi'  => ['centurygothicb', 3.30, 0.35, 4.00, 6.77, 'left', '#FF0000', null, 0],
        'periode1' => ['centurygothicb', 2.05, 0.38, 14.70, 4.65, 'left', '#FF0000', null, 0],
        'periode2' => ['centurygothicb', 2.05, 0.38, 14.70, 5.08, 'left', '#FF0000', null, 0],
        'big'      => ['bookantiqua', 4.67, 3.15, 9.29, 6.83, 'left', '#FF0000', '#FF0000 0.25pt', 1.60],
        'small'    => ['bookantiqua', 2.97, 1.13, 14.24, 6.81, 'left', '#FF0000', '#FF0000 0.45pt', 1.0],
    ];
    private const TGG_ALLOC_NOTE = ['x' => 4.00, 'base' => 5.32, 'h' => 0.30];

    // Layout khusus Tanggung Diskon Reguler. Koordinat absolut halaman A4.
    private const TGG_DISC_EL = [
        'pop'       => ['arialnarrow', 2.10, 0.25, 14.55, 1.45, 'left',   '#000000', null, 0],
        // Lebar diperluas ke kiri dengan batas kanan tetap: 11.60 + 11.50/2 = 17.35 cm.
        'nama'      => ['arialblack',  11.50, 0.61, 11.60, 3.40, 'center', '#00B0F0', '#000000 0.08pt', 0],
        'plu'       => ['arialnarrow', 2.57, 0.28, 14.64, 4.05, 'left',   '#000000', null, 1.1],
        'diskon'    => ['bookantiquab', 2.25, 0.55, 3.70, 4.40, 'left',   '#FF0000', null, 1.1],
        'program'   => ['bookantiqua', 6.40, 3.30, 9.70, 6.90, 'center', '#FF0000', '#FF0000 0.15', 1.8],
        'percent'   => ['calistomt',   0.72, 0.90, 12.60, 5.00, 'left',   '#FF0000', null, 0],
        'rp_lama'   => ['calistomt',   0.414, 0.261, 14.205, 4.95, 'left',   '#FF0000', null, 0],
        'lama'      => ['bookantiqua', 1.89, 0.486, 15.005, 5.03, 'left',   '#000000', null, 1.3],
        'label_lama'=> ['arialnarrow', 1.9845, 0.189, 15.20, 5.44, 'center', '#000000', null, 1.1],
        'rp_baru'   => ['calistomt',   0.58, 0.38, 14.35, 5.99, 'left',   '#FF0000', null, 0],
        'baru'      => ['berlinsansfbregular', 3.35, 0.82, 15.10, 6.49, 'left',   '#FF0000', null, 1.3],
        'label_baru'=> ['arialnarrow', 3.30, 0.24, 15.15, 6.89, 'center', '#000000', null, 1.1],
        'periode1'  => ['arialnarrowb', 2.35, 0.23, 3.90, 6.50, 'left', '#000000', null, 1.1],
        'periode2'  => ['arialnarrowb', 2.35, 0.23, 3.90, 6.77, 'left', '#000000', null, 1.1],
    ];
    private const TGG_DISC_STRIKE = ['x1' => 14.85, 'y1' => 5.40, 'over' => 0.32, 'slope' => 0.26, 'thick' => 0.06];

    // Garis coret miring: x1,y1 = ujung KIRI-BAWAH; ujung kanan = akhir harga normal + over
    private const TGG_STRIKE = ['x1' => 4.14, 'y1' => 4.67, 'over' => 0.60, 'slope' => 0.212, 'thick' => 0.07];

    /* ========================================================================== */

    private ?Mpdf $pdf = null;

    public function render(array $groups): string
    {
        if ($groups === []) {
            throw new RuntimeException('Tidak ada item untuk renderer mPDF Libre.');
        }

        $first = array_key_first($groups);
        $config = self::TEMPLATES[$first] ?? null;
        if ($config === null) {
            throw new RuntimeException("Template mPDF belum tersedia: {$first}");
        }

        [$width, $height] = $config['size'];

        $d  = (new ConfigVariables())->getDefaults();
        $fd = (new FontVariables())->getDefaults();

        $pdf = new Mpdf([
            'format' => [$width, $height],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'tempDir' => WRITEPATH . 'mpdf',
            'fontDir' => array_merge($d['fontDir'], [FCPATH . 'assets/fonts'], is_dir('C:\\Windows\\Fonts') ? ['C:\\Windows\\Fonts'] : []),
            'fontdata' => $fd['fontdata'] + self::FONTS,
        ]);
        $this->pdf = $pdf;

        $firstPage = true;
        foreach ($groups as $templateKey => $items) {
            $config = self::TEMPLATES[$templateKey] ?? null;
            if ($config === null) {
                throw new RuntimeException("Template mPDF belum tersedia: {$templateKey}");
            }

            $tags = [];
            foreach ($items as $item) {
                $row = $item['row'] ?? $item;
                for ($i = 0; $i < max(1, (int) ($item['qty'] ?? 1)); $i++) {
                    $tags[] = $this->prepareTag($row);
                }
            }

            if (! $firstPage) {
                $pdf->AddPage('', '', '', '', '', 0, 0, 0, 0);
            }
            $firstPage = false;
            $size = ['width' => $config['size'][0], 'height' => $config['size'][1]];
            foreach ($tags as $i => $tag) {
                // Teks diposisikan absolut, jadi tiap tag harus di halaman sendiri.
                if (! empty($config['pagePerTag']) && $i > 0) {
                    $pdf->AddPage('', '', '', '', '', 0, 0, 0, 0);
                }

                $vars = compact('tag', 'size');
                if (! empty($config['builder'])) {
                    $vars += $this->{$config['builder']}($tag);
                }

                $html = view('pricetag/templates/' . $config['view'], $vars);
                try {
                    $pdf->WriteHTML($html);
                } catch (\Throwable $e) {
                    $location = $e->getFile() . ':' . $e->getLine();
                    throw new RuntimeException(
                        'Gagal merender template ' . $templateKey . ' di ' . $location . ': ' . $e->getMessage(),
                        0,
                        $e
                    );
                }
            }
        }

        return $pdf->Output('', 'S');
    }

    private function prepareTag(array $row): array
    {
        $promo = (int) round((float) ($row['promo_price'] ?? 0));
        $hasThousands = $promo >= 1000;

        return $row + [
            'display_name' => trim(($row['name'] ?? '') . ' ' . ($row['variant'] ?? '')),
            'periode_display' => (string) ($row['end_period'] ?? ''),
            // Untuk harga di bawah 1.000, digit ratusan pertama menjadi
            // harga besar dan dua digit sisanya menjadi harga kecil tanpa titik
            // (contoh 990 => 9 dan 90). Harga >= 1.000 tetap memakai titik
            // sebelum tiga digit terakhir (contoh 14.360 => 14 dan .360).
            'promo_thousands' => $hasThousands ? (string) intdiv($promo, 1000) : (string) intdiv($promo, 100),
            'promo_hundreds' => $hasThousands
                ? '.' . str_pad((string) ($promo % 1000), 3, '0', STR_PAD_LEFT)
                : str_pad((string) ($promo % 100), 2, '0', STR_PAD_LEFT),
            // Nominal utuh dipakai oleh renderer tanggung agar separator dan
            // jarak antar digit tidak bergantung pada dua kotak berposisi tetap.
            'promo_display' => number_format($promo, 0, ',', '.'),
            'harga_ribuan' => $hasThousands ? (string) intdiv($promo, 1000) : (string) intdiv($promo, 100),
            'harga_ratusan' => $hasThousands
                ? str_pad((string) ($promo % 1000), 3, '0', STR_PAD_LEFT)
                : str_pad((string) ($promo % 100), 2, '0', STR_PAD_LEFT),
            'alokasi' => (string) ($row['allocation_pcs'] ?? ''),
        ];
    }

    /* ====================== turun-harga-tgg ====================== */

    /** Hitung semua teks SVG + garis coret; hasilnya dikirim ke view turun-hrg-tgg.php */
    private function buildTgg(array $tag): array
    {
        $text = [
            'pop'      => 'JUMLAH POP : 1',
            'nama'     => mb_strtoupper(trim((string) ($tag['display_name'] ?? ''))),
            'plu'      => '( PLU : ' . ($tag['sku_plu'] ?? '') . ' )',
            'rp_lama'  => 'Rp',
            'lama'     => number_format((float) ($tag['normal_price'] ?? 0), 0, ',', '.'),
            'rp_baru'  => 'Rp',
            'alokasi'  => '" TANPA ALOKASI "',
            'periode1' => 'Akhir Periode :',
        ];

        $S = ['w' => self::TGG_BOX_W * 10, 'h' => self::TGG_BOX_H * 10, 'el' => []];
        foreach (self::TGG_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
            $scalingLimit = $key === 'nama' ? 0.0 : (float) $maxRatio;
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, $scalingLimit) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        // Harga promo tetap dipisah menjadi dua objek SVG seperti WordArt DOCX:
        // bagian ribuan dan bagian ratusan memiliki kotak serta skala sendiri.
        // Semua angka Ribuan memakai Book Antiqua Regular agar ketebalan
        // visual dua/tiga digit mengikuti acuan satu digit. Konfigurasi
        // geometrinya tetap dipisahkan: satu digit memakai override kalibrasi,
        // sedangkan dua/tiga digit memakai TGG_BIG.
        $isSingleDigitBig = strlen((string) ($tag['promo_thousands'] ?? '')) === 1;
        $isTwoDigitBig = strlen((string) ($tag['promo_thousands'] ?? '')) === 2;
        $bigFamily = 'bookantiqua';
        // Untuk satu digit, izinkan glyph mengisi rasio kotak yang tersedia.
        // Penyesuaian kecil ini khusus angka Ribuan satu digit: glyph dinaikkan
        // sedikit dan dibuat sedikit lebih tinggi, sementara batas rasio
        // diturunkan proporsional agar lebar visual tetap seperti sebelumnya.
        $bigH = $isSingleDigitBig
            ? 3.27
            : ($isTwoDigitBig ? self::TGG_BIG_2['h'] : self::TGG_BIG['h']);
        $bigBase = $isSingleDigitBig
            ? 7.01
            : ($isTwoDigitBig ? self::TGG_BIG_2['base'] : self::TGG_BIG['base']);
        $bigMaxRatio = $isSingleDigitBig
            ? 2.10 * (self::TGG_BIG['h'] / 3.22) * (3.22 / $bigH)
            : ($isTwoDigitBig
                ? self::TGG_BIG['max'] * (self::TGG_BIG['h'] / $bigH)
                : self::TGG_BIG['max']);
        $S['el']['big'] = $this->stretchEl(
            $bigFamily,
            (string) ($tag['promo_thousands'] ?? ''),
            self::TGG_BIG['w'], $bigH, self::TGG_BIG['x'], $bigBase, 'left', $bigMaxRatio
        ) + ['color' => '#FF0000', 'outline' => '#FF0000 0.25pt'];
        $S['el']['small'] = $this->stretchEl(
            'bookantiqua',
            (string) ($tag['promo_hundreds'] ?? ''),
            self::TGG_SMALL['w'], self::TGG_SMALL['h'], self::TGG_SMALL['x'], self::TGG_SMALL['base'], 'left', self::TGG_SMALL['max']
        ) + ['color' => '#FF0000', 'outline' => '#FF0000 0.45pt'];

        // Baris kedua "Akhir Periode": skala sama dengan baris pertama, rata kiri
        $periode = $this->tanggalPendek((string) ($tag['periode_display'] ?? ''));
        if ($periode !== '') {
            $p2    = self::TGG_PERIODE2;
            $p1    = $S['el']['periode1'];
            $probe = $this->stretchEl('centurygothicb', $periode, 1, $p2['h'], 0, $p2['base'], 'left');
            if ($probe['w0'] > 0) {
                $wCm = $probe['w0'] * $p1['sx'] / 10;
                $S['el']['periode2'] = $this->stretchEl('centurygothicb', $periode, $wCm, $p2['h'], $p2['x'], $p2['base'], 'left')
                    + ['color' => '#000000', 'outline' => null];
            }
        }

        // Garis coret harga normal (ikut lebar angkanya)
        $lama = $S['el']['lama'];
        $x2   = ($lama['x'] + $lama['sx'] * $lama['w0']) / 10 + self::TGG_STRIKE['over'];
        $len  = $x2 - self::TGG_STRIKE['x1'];
        $rise = $len * self::TGG_STRIKE['slope'];
        $pad  = self::TGG_STRIKE['thick'];
        $L = ['strike' => [
            'left' => round(self::TGG_BOX_X + self::TGG_STRIKE['x1'], 3),
            'top'  => round(self::TGG_BOX_Y + self::TGG_STRIKE['y1'] - $rise - $pad, 3),
            'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
            'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
        ]];

        $box = ['x' => self::TGG_BOX_X, 'y' => self::TGG_BOX_Y, 'w' => self::TGG_BOX_W, 'h' => self::TGG_BOX_H];

        return compact('S', 'L', 'box');
    }

    /**
     * All Varian memakai geometri Tanggung Reguler yang sudah dikalibrasi.
     * Hanya label identitas yang berbeda: PLU diganti ALL VARIAN.
     */
    private function buildTggAllvar(array $tag): array
    {
        $layout = $this->buildTgg($tag);
        // Override vertikal khusus All Varian. Satuan y SVG adalah mm.
        // Reguler menghasilkan y=70.10 mm (base 7.01 cm). Override awal
        // membawa All Varian ke 68.60 mm, namun masih terlalu rendah pada
        // perbandingan visual; naikkan lagi 0,15 cm menjadi 67.10 mm.
        // x, ukuran horizontal, font, dan outline tetap tidak berubah.
        $layout['S']['el']['big']['y'] = round($layout['S']['el']['big']['y'] - 3.0, 2);
        // Kurangi tinggi visual sekitar 4% agar angka tidak terlalu dekat
        // dengan nama produk; lebar horizontal tetap dipertahankan.
        $layout['S']['el']['big']['sy'] = round($layout['S']['el']['big']['sy'] * 0.96, 4);

        // Angka tidak memiliki descender, sehingga batas bawah visualnya
        // mengikuti baseline translate(y). Samakan baseline .693 dengan
        // baseline angka besar setelah override All Varian.
        $layout['S']['el']['small']['y'] = $layout['S']['el']['big']['y'];

        // Pertahankan transform, ukuran, dan posisi baseline Reguler; hanya
        // isi label identitas yang diganti agar tidak mengubah kalibrasi utama.
        $layout['S']['el']['plu']['text'] = 'All Variant';
        // Label All Variant pada DOCX sedikit lebih rendah daripada PLU.
        $layout['S']['el']['plu']['y'] = round($layout['S']['el']['plu']['y'] + 2.5, 2);
        // Perbesar tinggi visual melampaui ukuran minimum "TANPA ALOKASI".
        // Lebarkan teks secara horizontal; x dan sy tetap dipertahankan.
        $layout['S']['el']['plu']['sx'] = round($layout['S']['el']['plu']['sx'] * 1.15, 4);
        $layout['S']['el']['plu']['sy'] = round($layout['S']['el']['alokasi']['sy'] * 1.40, 4);
        // Nilai ini berada dalam koordinat SVG sebelum transform scale.
        $layout['S']['el']['plu']['letterSpacing'] = 0.35;

        return $layout;
    }

    /** Layout SVG khusus Tanggung Diskon Reguler, terpisah dari layout Turun Harga. */
    private function buildTggDiscount(array $tag): array
    {
        $text = [
            'pop'        => 'JUMLAH POP : 1',
            'nama'       => mb_strtoupper(trim((string) ($tag['display_name'] ?? ''))),
            'plu'        => '( PLU : ' . ($tag['sku_plu'] ?? '') . ' )',
            'diskon'     => 'Diskon :',
            'program'    => (string) round((float) ($tag['discount_percent'] ?? 0)),
            'percent'    => '%',
            'rp_lama'    => 'Rp',
            'lama'       => number_format((float) ($tag['normal_price'] ?? 0), 0, ',', '.'),
            'label_lama' => '( Harga Normal )',
            'rp_baru'    => 'Rp',
            'baru'       => number_format((float) ($tag['promo_price'] ?? 0), 0, ',', '.'),
            'label_baru' => '( Harga Sesudah di Diskon )',
            'periode1'   => 'Akhir Periode :',
            'periode2'   => $this->tanggalPendekDiscount((string) ($tag['periode_display'] ?? '')),
        ];

        $S = ['w' => 210, 'h' => 297, 'el' => []];
        foreach (self::TGG_DISC_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $max] = $def;
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $max)
                + ['color' => $color, 'outline' => $outline];
        }

        // Kompresi horizontal lokal periode setelah pergeseran X. Tinggi
        // visual (sy), baseline, dan font tetap; tanggal memakai faktor sama.
        $periodeWidth = $S['el']['periode1']['sx'] * $S['el']['periode1']['w0'];
        $periodeScaleX = min(1.0, 19.8 / max(0.001, $periodeWidth));
        foreach (['periode1', 'periode2'] as $periodeKey) {
            $S['el'][$periodeKey]['sx'] = round(
                $S['el'][$periodeKey]['sx'] * $periodeScaleX,
                6
            );
        }

        // Geser angka diskon secara horizontal hanya bila bounding box hasil
        // scaling tidak menyisakan margin aman dari periode dan simbol %.
        // Ukuran, tinggi, dan scaling angka tetap tidak berubah.
        $program = $S['el']['program'];
        $programWidth = $program['sx'] * $program['w0'];
        $periodRight = max(
            $S['el']['periode1']['x'] + ($S['el']['periode1']['sx'] * $S['el']['periode1']['w0']),
            $S['el']['periode2']['x'] + ($S['el']['periode2']['sx'] * $S['el']['periode2']['w0'])
        );
        $safeLeft = $periodRight + 1.5;
        $safeRight = $S['el']['percent']['x'] - 1.5;
        $currentLeft = $program['x'];
        if (($safeRight - $safeLeft) >= $programWidth) {
            $newLeft = min(max($currentLeft, $safeLeft), $safeRight - $programWidth);
            if ($newLeft !== $currentLeft) {
                $program['x'] = round($newLeft, 2);
                $S['el']['program'] = $program;
            }
        }

        // Alignment lokal disc-reg-tgg berdasarkan lebar glyph hasil stretchEl().
        // Nama tetap menjadi acuan kanan; tidak mengubah posisi atau scaling nama.
        $nama = $S['el']['nama'];
        $namaRight = $nama['x'] + ($nama['sx'] * $nama['w0']);
        $baru = $S['el']['baru'];
        // Rp promo diperkecil 10% secara lokal; font dan baseline tetap.
        $S['el']['rp_baru']['sx'] = round($S['el']['rp_baru']['sx'] * 0.90, 4);
        $S['el']['rp_baru']['sy'] = round($S['el']['rp_baru']['sy'] * 0.90, 4);
        $baruWidth = $baru['sx'] * $baru['w0'];
        $baruX = round($namaRight - $baruWidth, 2);
        $promoShift = $baruX - $baru['x'];
        $S['el']['baru']['x'] = $baruX;
        $S['el']['rp_baru']['x'] = round($S['el']['rp_baru']['x'] + $promoShift, 2);
        $promoLeft = $S['el']['rp_baru']['x'];
        $promoRight = $baruX + $baruWidth;
        $promoCenter = ($promoLeft + $promoRight) / 2;
        $S['el']['label_baru']['x'] = round($promoCenter - (($S['el']['label_baru']['sx'] * $S['el']['label_baru']['w0']) / 2), 2);

        $lama = $S['el']['lama'];
        $normalLeft = $lama['x'];
        $normalRight = $lama['x'] + ($lama['sx'] * $lama['w0']);
        $normalCenter = ($normalLeft + $normalRight) / 2;
        $S['el']['label_lama']['x'] = round($normalCenter - (($S['el']['label_lama']['sx'] * $S['el']['label_lama']['w0']) / 2), 2);

        $x2 = ($lama['x'] + $lama['sx'] * $lama['w0']) / 10 + self::TGG_DISC_STRIKE['over'];
        $left = self::TGG_DISC_STRIKE['x1'];
        $width = max(1.5, $x2 - $left);
        $strikeTop = ($lama['y'] / 10) - 0.35;
        $L = ['strike' => [
            'left' => $left,
            'top' => round($strikeTop, 3),
            'w' => $width,
            'h' => 0.35,
            'y1' => 0.30,
            'y2' => round(0.30 - self::TGG_DISC_STRIKE['slope'], 3),
            'sw' => self::TGG_DISC_STRIKE['thick'],
        ]];
        $box = ['x' => 0.0, 'y' => 0.0, 'w' => 21.00, 'h' => 29.70];

        return compact('S', 'L', 'box');
    }

    /** Layout awal khusus DOCX Tanggung Allocation; tidak berbagi konstanta posisi dengan Reguler. */
    private function buildTggAllocation(array $tag): array
    {
        $text = [
            'pop' => 'JUMLAH POP : 2',
            'nama' => mb_strtoupper(trim((string) ($tag['display_name'] ?? ''))),
            'plu' => '( PLU : ' . ($tag['sku_plu'] ?? '') . ' )',
            'rp_lama' => 'Rp',
            'lama' => number_format((float) ($tag['normal_price'] ?? 0), 0, ',', '.'),
            'rp_baru' => 'Rp',
            'alokasi' => 'Alokasi : ' . (string) ($tag['alokasi'] ?? '') . ' Pcs',
            'periode1' => 'Akhir Periode :',
            'periode2' => $this->tanggalPendek((string) ($tag['periode_display'] ?? '')),
            'big' => (string) ($tag['promo_thousands'] ?? ''),
            'small' => (string) ($tag['promo_hundreds'] ?? ''),
        ];

        // Satuan SVG mengikuti renderer Reguler: 10 user-units = 1 mm.
        // Karena halaman A4 berukuran 210 x 297 mm, viewBox harus 210 x 297.
        $S = ['w' => 210, 'h' => 297, 'el' => []];
        foreach (self::TGG_ALLOC_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $max] = $def;
            $scalingLimit = $key === 'nama' ? 0.0 : (float) $max;
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, $scalingLimit)
                + ['color' => $color, 'outline' => $outline];
        }
        foreach (['*Promo Berlaku', 'Selama Alokasi', 'Masih ada'] as $i => $line) {
            $S['el']['note' . $i] = $this->stretchEl('centurygothicb', $line, 2.55, 0.33, self::TGG_ALLOC_NOTE['x'], self::TGG_ALLOC_NOTE['base'] + $i * 0.38, 'left')
                + ['color' => '#00B0F0', 'outline' => null];
        }

        // Harga promo memakai baseline Reguler, termasuk kalibrasi per jumlah digit.
        $isSingleDigitBig = strlen((string) ($tag['promo_thousands'] ?? '')) === 1;
        $isTwoDigitBig = strlen((string) ($tag['promo_thousands'] ?? '')) === 2;
        $bigH = $isSingleDigitBig ? 3.27 : ($isTwoDigitBig ? self::TGG_BIG_2['h'] : self::TGG_BIG['h']);
        $bigBase = ($isSingleDigitBig ? 7.01 : ($isTwoDigitBig ? self::TGG_BIG_2['base'] : self::TGG_BIG['base'])) - 0.45;
        $bigMax = $isSingleDigitBig
            ? 2.10 * (self::TGG_BIG['h'] / 3.22) * (3.22 / $bigH)
            : ($isTwoDigitBig ? self::TGG_BIG['max'] * (self::TGG_BIG['h'] / $bigH) : self::TGG_BIG['max']);
        $S['el']['big'] = $this->stretchEl('bookantiqua', (string) ($tag['promo_thousands'] ?? ''), self::TGG_BIG['w'], $bigH, self::TGG_BIG['x'], $bigBase, 'left', $bigMax)
            + ['color' => '#FF0000', 'outline' => '#FF0000 0.25pt'];
        $S['el']['small'] = $this->stretchEl('bookantiqua', (string) ($tag['promo_hundreds'] ?? ''), self::TGG_SMALL['w'], self::TGG_SMALL['h'], self::TGG_SMALL['x'], self::TGG_SMALL['base'] - 0.45, 'left', self::TGG_SMALL['max'])
            + ['color' => '#FF0000', 'outline' => '#FF0000 0.45pt'];

        // Translasi vertikal khusus Allocation setelah seluruh layout dihitung.
        // Kelompok A turun 0,20 cm; harga promo (Kelompok B) turun 0,40 cm total.
        $groupAOffset = 0.20;
        $groupBOffset = 0.40;
        foreach (['nama', 'plu', 'rp_lama', 'lama', 'periode1', 'periode2', 'alokasi', 'note0', 'note1', 'note2'] as $key) {
            if (isset($S['el'][$key])) {
                $S['el'][$key]['y'] = round($S['el'][$key]['y'] + ($groupAOffset * 10), 2);
            }
        }
        foreach (['rp_baru', 'big', 'small'] as $key) {
            if (isset($S['el'][$key])) {
                $S['el'][$key]['y'] = round($S['el'][$key]['y'] + ($groupBOffset * 10), 2);
            }
        }

        $lama = $S['el']['lama'];
        $x2 = ($lama['x'] + $lama['sx'] * $lama['w0']) / 10 + self::TGG_STRIKE['over'];
        $left = self::TGG_STRIKE['x1'];
        $width = max(2.0, $x2 - $left);
        $rise = $width * self::TGG_STRIKE['slope'];
        $pad = self::TGG_STRIKE['thick'];
        $strikeY = self::TGG_STRIKE['y1'] - 0.12;
        $box = ['x' => 0.0, 'y' => 0.0, 'w' => 21.00, 'h' => 29.70];
        $L = ['strike' => [
            'left' => $left, 'top' => $strikeY - $rise - $pad, 'w' => $width,
            'h' => $rise + 2 * $pad, 'y1' => $rise + $pad, 'y2' => $pad, 'sw' => $pad,
        ]];
        $L['strike']['top'] = round($L['strike']['top'] + $groupAOffset, 3);

        return compact('S', 'L', 'box');
    }

    /** "2026-09-15" -> "15 SEP 2026" (kalau bukan tanggal valid, dipakai apa adanya) */
    private function tanggalPendekDiscount(string $ymd): string
    {
        $ts = strtotime($ymd);
        if (! $ts) {
            return mb_strtoupper(trim($ymd));
        }

        $months = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
        return date('d', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('y', $ts);
    }

    /** "2026-09-15" -> "15 SEP 2026" (kalau bukan tanggal valid, dipakai apa adanya) */
    private function tanggalPendek(string $ymd): string
    {
        $ts = strtotime($ymd);
        if (! $ts) {
            return mb_strtoupper(trim($ymd));
        }
        $b = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
        return date('j', $ts) . ' ' . $b[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /* ---------- util regang teks (sama dengan PopA4Pdf::stretchEl / stretchBox) ---------- */

    /** Teks diregangkan ke kotak wCm x hCm (tinggi = huruf kapital); base = garis dasar. Satuan SVG: mm. */
    private function stretchEl(string $fam, string $text, float $wCm, float $hCm, float $xCm, float $baseCm, string $align, float $maxRatio = 0): array
    {
        $base = 10.0;
        $this->pdf->SetFont($fam, '', $base / 0.352778);
        $w0  = $this->pdf->GetStringWidth($text);
        $cap = (($this->pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * $base;
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

    /** Kotak berformat x + w + h + base + max (lebar dibatasi sx <= sy * max). Rata kiri. */
    private function stretchBox(string $fam, string $text, array $box): array
    {
        $base = 10.0;
        $this->pdf->SetFont($fam, '', $base / 0.352778);
        $w0  = $this->pdf->GetStringWidth($text);
        $cap = (($this->pdf->CurrentFont['desc']['CapHeight'] ?? 700) / 1000) * $base;
        if ($w0 <= 0 || $cap <= 0) {
            return ['family' => $fam, 'fs' => $base, 'x' => 0, 'y' => 0, 'sx' => 1, 'sy' => 1, 'text' => $text];
        }
        $sy = ($box['h'] * 10) / $cap;
        $sx = min(($box['w'] * 10) / $w0, $sy * $box['max']);

        return ['family' => $fam, 'fs' => $base, 'x' => round($box['x'] * 10, 2), 'y' => round($box['base'] * 10, 2),
                'sx' => round($sx, 4), 'sy' => round($sy, 4), 'text' => $text];
    }
}
