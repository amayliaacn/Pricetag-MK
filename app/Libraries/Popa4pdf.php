<?php

namespace App\Libraries;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Cetak POP Price Tag A4 langsung dari HTML (mPDF), tanpa Word/LibreOffice.
 * Satu file ini menangani SEMBILAN desain kartu:
 *   - 'a4'       -> kartu "MINYAK GORENG" (Picture1.jpg)
 *   - 'segitiga' -> kartu "Segitiga Turun Harga" / TESSA-TP.06 (Picture3.png)
 *   - 'special'  -> kartu "Segitiga Special Price" (Picture3.png / gambar sendiri)
 *   - 'diskon'   -> kartu "DISKON 33%" (GIZZI-MILK), view: segitiga-discount.php
 *   - 'a5'       -> kartu A5, ukuran cetak fisik 19,5 x 13,5 cm (mis. LE MINERALE GALON 15 LT)
 *   - 'disc'     -> kartu landscape "KAOS WANITA / ARTIKEL TERTENTU / DISC 50%", view: discount-a5.php
 *   - 'disc2'    -> kartu landscape DISC 50% + harga normal dicoret + harga setelah diskon, view: discount2-a5.php
 *   - 'fresh'    -> 6 kartu per halaman A4 (2 x 3) "Manna Kampus FRESH" /100gr, view: butcher.php
 *   - 'curah'    -> 6 kartu per halaman A4 (2 x 3) "BAWANG KATING CURAH" + harga coret, view: curah-6up.php
 * Tiap desain punya konstanta sendiri (A4_*, SEG_*, SPC_*, DSK_*, A5_*, DSC_*, DS2_*, FRS_*, CRH_*) supaya tidak saling bentrok.
 *
 * Pemakaian (controller):
 *   $pdf = (new \App\Libraries\PopA4Pdf())->render([
 *       ['row' => $rowDb, 'qty' => 3],
 *   ], 'diskon');   // 'a4' (default) | 'segitiga' | 'special' | 'diskon' | 'a5' | 'disc' | 'disc2' | 'fresh' | 'curah'
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
     * Ukuran & posisi kartu di A4 sama dengan "segitiga". View: segitiga-discount.php
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

    /* ============================================================================
     * DESAIN 5: "a5" — kartu A5, ukuran cetak fisik 19,5 x 13,5 cm (landscape)
     * Contoh: LE MINERALE GALON 15 LT. Beda dari desain lain: hampir semua teks
     * pakai ukuran WordArt (pt) persis dari Word, dikonversi ke cm.
     * ==========================================================================*/

    private const A5_IMG_FILE = 'assets/img/Picture4.jpg';
    // Ukuran cetak fisik dikonfirmasi user: 19,5 cm (lebar) x 13,5 cm (tinggi).
    // Kalau ternyata kebalik (13,5 lebar x 19,5 tinggi / potret), tukar dua angka di bawah ini.
    private const A5_BOX_W = 19.50;
    private const A5_BOX_H = 13.50;
    private const A5_BOX_X = 0.75;
    private const A5_BOX_Y = 0.75;

    private const A5_FONTS = [
        // 'arialblack', 'arialnarrow', 'bookantiqua' dipakai bersama desain lain di atas.
        'calistomt'     => ['R' => 'CALIST.TTF'],                        // Calisto MT (label "Rp")
        'centurygothic' => ['R' => 'GOTHIC.TTF', 'B' => 'GOTHICB.TTF'],  // Century Gothic (Akhir Periode)
    ];

    // teks               family           w      h      x      base    align  warna     outline          maxSkew
    private const A5_EL = [
        'merk'      => ['arialblack',    17.46, 0.95,  1.05,  3.31,  'left', '#00B0F0', '#000000 0.25pt', 0],
        'plu'       => ['arialnarrow',    4.45, 0.64, 18.70,  4.45,  'right','#000000', null,             0],
        'rp_lama'   => ['calistomt',      0.56, 0.64,  1.73,  5.10,  'left', '#FF0000', null,             0],
        'lama'      => ['bookantiqua',    4.13, 1.27,  2.35,  5.67,  'left', '#FF0000', '#FF0000 0.45pt',  1.3],
        'rp_baru'   => ['calistomt',      0.78, 0.82,  5.90,  7.95,  'left', '#FF0000', null,             0],
        'big'       => ['bookantiqua',    7.62, 6.20,  7.15, 11.90,  'left', '#FF0000', '#FF0000 0.45pt',  1.3],
        'small'     => ['bookantiqua',    4.45, 1.75, 14.05, 11.90,  'left', '#FF0000', '#FF0000 0.45pt',  1.3],
        'periode1'  => ['centurygothic',  2.94, 0.64,  1.29, 11.10,  'left', '#000000', null,             0],
        'periode2'  => ['centurygothic',  2.94, 0.64,  1.29, 11.90,  'left', '#000000', null,             0],
    ];

    // Garis coret pada harga lama (cm, relatif kartu)
    private const A5_STRIKE = ['x1' => 1.60, 'y1' => 5.45, 'over' => 0.35, 'slope' => 0.14, 'thick' => 0.15];

    /* ============================================================================
     * DESAIN 6: "disc" — kartu LANDSCAPE "KAOS WANITA / ARTIKEL TERTENTU / DISC 50%"
     * Kartu 19,5 x 13,7 cm (perkiraan dari contoh), di tengah atas halaman A4.
     * View: disc-artikel.php
     * ==========================================================================*/

    private const DSC_BOX_W = 19.50;
    private const DSC_BOX_H = 13.70;
    private const DSC_BOX_X = (21.00 - self::DSC_BOX_W) / 2;
    private const DSC_BOX_Y = 0.40;

    // Gambar latar (kuning + kotak putih + logo MURAH, tanpa teks) - OPSIONAL.
    // Kalau file ini tidak ada, view menggambar bingkainya sendiri dengan HTML/CSS.
    private const DSC_IMG_FILE = 'assets/img/Picture4.jpg';

    // Bingkai cadangan (cm), dipakai kalau gambar latar tidak ada.
    private const DSC_FRAME = [
        'kuning'  => '#FFED00',
        'in_x'    => 0.55,   // jarak kotak putih dari kiri/kanan
        'in_top'  => 0.50,   // dari atas
        'in_bot'  => 0.53,   // dari bawah
        'radius'  => 0.90,   // sudut rounded
        'badge'   => ['x' => 0.55, 'y' => 0.20, 'w' => 6.50, 'h' => 1.50],
    ];

    // Jarak angka diskon ke tanda % (cm)
    private const DSC_GAP_PERSEN = 0.88;

    // Teks tetap yang bisa diganti
    private const DSC_TEKS_ARTIKEL = 'ARTIKEL TERTENTU';
    private const DSC_TEKS_LABEL   = 'DISC';

    // teks           family         w      h      x      base    align     warna      outline           maxRatio
    private const DSC_EL = [
        'nama_produk' => ['arialblack',  13.45, 1.30,  9.87,  3.55, 'center', '#00B0F0', '#000000 0.08pt', 0.9],
        'artikel'     => ['arialnarrow',  5.07, 0.92, 15.10,  4.77, 'center', '#000000', null,              0],
        'label'       => ['berlinsans',   3.19, 0.76,  2.71,  6.22, 'left',   '#000000', null,              0],
        'angka'       => ['bernardmt',    7.62, 7.10,  7.33, 12.52, 'left',   '#FF0000', '#FF0000 0.05pt', 1.1],
        'persen'      => ['berlinsans',   1.34, 1.60,  0.00,  7.06, 'left',   '#FF0000', null,              0],   // x dihitung otomatis
    ];


    /* ============================================================================
     * DESAIN 6b: "disc2" — kartu LANDSCAPE DISC 50% + harga normal (dicoret) + harga setelah diskon
     * Ukuran, posisi di A4, gambar latar, dan bingkai cadangan SAMA dengan "disc" (DSC_*).
     * Contoh: KAOS WANITA, DISC 50%, Rp 52.900 -> Rp 26.450. View: discount2-a5.php
     * ==========================================================================*/

    // Jarak angka diskon ke tanda % (cm)
    private const DS2_GAP_PERSEN = 0.73;

    // x/base relatif ke pojok kiri-atas kartu (cm).
    // teks           family         w      h      x      base    align     warna      outline           maxRatio
    private const DS2_EL = [
        'nama_produk' => ['arialblack',  13.45, 1.30,  9.87,  3.55, 'center', '#00B0F0', '#000000 0.08pt', 0.9],
        'artikel'     => ['arialnarrow',  5.07, 0.92, 15.08,  4.77, 'center', '#000000', null,              0],
        'label'       => ['berlinsans',   2.75, 0.62,  3.17,  5.99, 'left',   '#000000', null,              0],
        'angka'       => ['bookantiqua',  5.90, 3.50,  6.83,  8.89, 'left',   '#FF0000', '#FF0000 0.3pt',   1.15],
        'persen'      => ['berlinsans',   0.88, 1.26,  0.00,  6.53, 'left',   '#FF0000', null,              0],   // x dihitung otomatis
        // Beri jarak cukup antara label Rp dan angka harga normal agar tidak bertumpuk.
        'rp_lama'     => ['arialblack',   1.00, 0.38,  1.72,  9.69, 'left',   '#000000', null,              0],
        'lama'        => ['bernardmt',    5.08, 1.41,  2.95, 11.07, 'left',   '#000000', '#000000 0.15pt',  1.4],
        'rp_baru'     => ['arialblack',   1.60, 0.70,  9.00, 11.60, 'left',   '#000000', null,              0],
        'baru'        => ['bernardmt',    6.18, 3.20, 11.45, 12.98, 'left',   '#FF0000', '#FF0000 0.08pt',  0.8],
    ];

    // Garis coret merah pada harga normal (cm, relatif kartu)
    private const DS2_STRIKE = ['x1' => 2.93, 'y1' => 11.15, 'over' => 0.80, 'slope' => 0.26, 'thick' => 0.15];

    /* ============================================================================
     * DESAIN 7: "fresh" — 6 kartu per halaman A4 (2 kolom x 3 baris)
     * Contoh: Manna Kampus FRESH, harga per 100gr. Latar = Picture5.jpg (SATU kartu, tanpa teks).
     * View: butcher.php. Satu item = satu kartu; qty = jumlah kartu yang dicetak.
     * Kartu diisi urut kiri-kanan, atas-bawah; tiap 6 kartu pindah halaman.
     * ==========================================================================*/

    private const FRS_IMG_FILE = 'assets/img/Picture5.jpg';

    // Ukuran satu kartu (cm) - perkiraan dari contoh; ubah kalau ukuran aslinya beda.
    private const FRS_CARD_W = 9.85;
    private const FRS_CARD_H = 7.80;
    // Pojok kiri-atas kotak 2 x 3 kartu di halaman A4 (kotak dibuat di tengah horizontal).
    private const FRS_GRID_X = (21.00 - 2 * self::FRS_CARD_W) / 2;
    private const FRS_GRID_Y = 0.30;
    private const FRS_PER_PAGE = 6;

    private const FRS_TEKS_SATUAN = '/100gr';

    // Ukuran dalam cm, x/base relatif ke pojok kiri-atas SATU kartu.
    // teks           family          w      h      x      base   align   warna      outline           maxRatio
    private const FRS_EL = [
        'nama_produk' => ['berlinsans',    4.44, 0.62, 2.77, 4.83, 'left', '#FF0000', '#C00000 0.1pt',  1.0],
        'rp'          => ['calistomt',     0.32, 0.42, 3.12, 5.84, 'left', '#000000', null,             0],
        'harga'       => ['bookantiqua',   5.40, 1.75, 3.87, 7.21, 'left', '#FF0000', '#FF0000 0.75pt', 0.95],
        'satuan'      => ['calistomt',     1.27, 0.38, 7.56, 4.83, 'left', '#000000', null,             0],
        'plu'         => ['centurygothic', 1.75, 0.36, 1.80, 7.05, 'left', '#000000', null,             0],
    ];


    /* ============================================================================
     * DESAIN 8: "curah" — 6 kartu per halaman A4 (2 kolom x 3 baris), ada harga coret
     * Contoh: BAWANG KATING CURAH, harga per 100gr. Latar = Picture6.jpg (SATU kartu, tanpa teks).
     * View: curah-6up.php. Satu item = satu kartu; qty = jumlah kartu yang dicetak.
     * Nama produk dipecah 2 baris otomatis: kata pertama (kecil) + sisanya (besar).
     * Kalau mau atur sendiri, isi $row['line1'] dan/atau $row['line2'].
     * ==========================================================================*/

    private const CRH_IMG_FILE = 'assets/img/Picture6.jpg';

    // Ukuran satu kartu (cm) - perkiraan dari contoh; kartu saling menempel tanpa jarak.
    private const CRH_CARD_W = 10.10;
    private const CRH_CARD_H = 8.50;
    private const CRH_GRID_X = (21.00 - 2 * self::CRH_CARD_W) / 2;
    private const CRH_GRID_Y = 0.30;
    private const CRH_PER_PAGE = 6;

    private const CRH_TEKS_SATUAN = '/100gr';

    // Ukuran dalam cm, x/base relatif ke pojok kiri-atas SATU kartu.
    // teks          family          w      h      x      base   align     warna      outline           maxRatio
    private const CRH_EL = [
        'baris1'   => ['berlinsans',    4.14, 0.68, 4.89, 1.56, 'center', '#FF0000', '#C00000 0.1pt',  1.0],
        'baris2'   => ['berlinsans',    9.66, 0.95, 5.06, 2.81, 'center', '#FF0000', '#C00000 0.1pt',  1.0],
        'plu'      => ['centurygothic', 2.70, 0.36, 0.57, 3.80, 'left',   '#000000', null,             0],
        'lama'     => ['calibri',       2.80, 0.72, 6.31, 4.14, 'left',   '#FF0000', null,             1.1],
        'baru'     => ['calibri',       6.46, 2.00, 2.13, 6.50, 'left',   '#FF0000', '#FF0000 0.3pt',  0.9],
    ];

    // Garis coret hitam pada harga lama (cm, relatif ke kartu)
    private const CRH_STRIKE = ['x1' => 5.93, 'y1' => 4.22, 'over' => 0.30, 'slope' => 0.23, 'thick' => 0.10];

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
        $fontdata = self::A4_FONTS + self::SEG_FONTS + self::A5_FONTS;

        // Calibri Bold (desain 'curah'). Kalau file tidak ditemukan, otomatis pakai Arial Bold.
        $calibri = 'arialbd.ttf';
        foreach (['calibrib.ttf', 'CALIBRIB.TTF', 'Calibrib.ttf'] as $f) {
            if (is_file(FCPATH . 'assets/fonts/' . $f)) {
                $calibri = $f;
                break;
            }
        }
        $fontdata['calibri'] = ['R' => $calibri];

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
     * @param string $template 'a4' (default) | 'segitiga' | 'special' | 'diskon' | 'a5' | 'disc' | 'disc2' | 'fresh' | 'curah'
     */
    public function render(array $items, string $template = 'a4'): string
    {
        // Desain multi-kartu (6 per halaman) punya alur sendiri.
        if ($template === 'fresh') {
            return $this->renderFresh($items);
        }
        if ($template === 'curah') {
            return $this->renderCurah($items);
        }

        $first = true;
        foreach ($items as $it) {
            $html = match ($template) {
                'segitiga' => $this->pageHtmlSegitiga($it['row']),
                'special'  => $this->pageHtmlSpecial($it['row']),
                'diskon'   => $this->pageHtmlDiskon($it['row']),
                'a5'       => $this->pageHtmlA5($it['row']),
                'disc'     => $this->pageHtmlDisc($it['row']),
                'disc2'    => $this->pageHtmlDisc2($it['row']),
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

    /**
     * Render beberapa template mPDF dalam satu dokumen PDF.
     * @param array<string,array<int,array{row:array,qty?:int}>> $groups
     */
    public function renderMixed(array $groups): string
    {
        $first = true;
        foreach ($groups as $template => $items) {
            foreach ($items as $it) {
                if ($template === 'fresh' || $template === 'curah') {
                    $rows = [];
                    $qty = max(1, (int) ($it['qty'] ?? 1));
                    for ($i = 0; $i < $qty; $i++) $rows[] = $it['row'];
                    $perPage = $template === 'fresh' ? self::FRS_PER_PAGE : self::CRH_PER_PAGE;
                    foreach (array_chunk($rows, $perPage) as $chunk) {
                        if (! $first) $this->mpdf->AddPage();
                        $this->mpdf->WriteHTML($template === 'fresh'
                            ? $this->pageHtmlFresh($chunk)
                            : $this->pageHtmlCurah($chunk));
                        $first = false;
                    }
                    continue;
                }
                $html = match ($template) {
                    'segitiga' => $this->pageHtmlSegitiga($it['row']),
                    'special'  => $this->pageHtmlSpecial($it['row']),
                    'diskon'   => $this->pageHtmlDiskon($it['row']),
                    'a5'       => $this->pageHtmlA5($it['row']),
                    'disc'     => $this->pageHtmlDisc($it['row']),
                    'disc2'    => $this->pageHtmlDisc2($it['row']),
                    default    => $this->pageHtmlA4($it['row']),
                };
                $qty = max(1, (int) ($it['qty'] ?? 1));
                for ($i = 0; $i < $qty; $i++) {
                    if (! $first) $this->mpdf->AddPage();
                    $this->mpdf->WriteHTML($html);
                    $first = false;
                }
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
        $promo = (int) round((float) (($row['promo_price'] ?? 0) > 0 ? $row['promo_price'] : ($row['normal_price'] ?? 0)));

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
            'alokasi'     => 'Alokasi : ' . number_format((float) ($row['allocation_pcs'] ?? 0), 0, ',', '.') . ' Pcs',
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

    /* ====================== DESAIN 5: "a5" ====================== */

    private function pageHtmlA5(array $row): string
    {
        $promo  = (int) round((float) $row['promo_price']);
        $normal = (int) round((float) $row['normal_price']);

        $text = [
            'merk'     => mb_strtoupper(trim((string) ($row['name'] ?? '') . ' ' . ($row['variant'] ?? ''))),
            'plu'      => '( PLU : ' . ($row['sku_plu'] ?? '') . ' )',
            'rp_lama'  => 'Rp',
            'lama'     => number_format($normal, 0, ',', '.'),
            'rp_baru'  => 'Rp',
            'big'      => $promo >= 1000 ? (string) intdiv($promo, 1000) : (string) $promo,
            'small'    => $promo >= 1000 ? '.' . str_pad((string) ($promo % 1000), 3, '0', STR_PAD_LEFT) : '',
            'periode1' => 'Akhir Periode :',
            'periode2' => $this->tanggal($row['end_period'] ?? ''),
        ];

        $S = ['w' => self::A5_BOX_W * 10, 'h' => self::A5_BOX_H * 10, 'el' => []];
        foreach (self::A5_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxSkew] = $def;
            $element = in_array($key, ['big', 'small'], true)
                ? $this->stretchBox($fam, $text[$key], ['x' => $x, 'w' => $w, 'h' => $h, 'base' => $base, 'max' => 1.0], $align)
                : $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, 0, (float) $maxSkew);
            $S['el'][$key] = $element + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        $lama = $S['el']['lama'];
        $x2   = self::A5_EL['lama'][3] + ($lama['sx'] * $lama['w0']) / 10 + self::A5_STRIKE['over'];
        $len  = $x2 - self::A5_STRIKE['x1'];
        $rise = $len * self::A5_STRIKE['slope'];
        $pad  = self::A5_STRIKE['thick'];
        $L = ['strike' => [
            'left' => round(self::A5_BOX_X + self::A5_STRIKE['x1'], 3),
            'top'  => round(self::A5_BOX_Y + self::A5_STRIKE['y1'] - $rise - $pad, 3),
            'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
            'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
        ]];

        $box = ['x' => self::A5_BOX_X, 'y' => self::A5_BOX_Y, 'w' => self::A5_BOX_W, 'h' => self::A5_BOX_H];
        $img = FCPATH . self::A5_IMG_FILE;

        return view('pricetag/templates/turun-hrg-a5', compact('S', 'L', 'box', 'img'));
    }

    /* ====================== DESAIN 6: "disc" ====================== */

    private function pageHtmlDisc(array $row): string
    {
        $promo  = (int) round((float) ($row['promo_price'] ?? 0));
        $normal = (int) round((float) ($row['normal_price'] ?? 0));

        // Persen diskon: cari di beberapa kemungkinan nama kolom, atau hitung dari harga.
        $pct = 0;
        foreach (['discount_percent', 'discount_pct', 'disc_percent', 'diskon', 'discount', 'percent'] as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && (float) $row[$k] > 0) {
                $pct = (int) round((float) $row[$k]);
                break;
            }
        }
        if ($pct <= 0 && $normal > 0 && $promo > 0) {
            $pct = (int) round((1 - $promo / $normal) * 100);
        }

        $variant = mb_strtoupper(trim((string) ($row['variant'] ?? '')));
        $text = [
            'nama_produk' => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
            'artikel'     => $variant !== '' ? $variant : self::DSC_TEKS_ARTIKEL,
            'label'       => self::DSC_TEKS_LABEL,
            'angka'       => (string) $pct,
            'persen'      => '%',
        ];

        $S = ['w' => self::DSC_BOX_W * 10, 'h' => self::DSC_BOX_H * 10, 'el' => []];
        foreach (self::DSC_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
            if ($key === 'persen') {
                // tanda % menempel di kanan angka diskon, berapa pun lebar angkanya
                $a = $S['el']['angka'];
                $x = ($a['x'] + $a['sx'] * $a['w0']) / 10 + self::DSC_GAP_PERSEN;
            }
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        $box   = ['x' => self::DSC_BOX_X, 'y' => self::DSC_BOX_Y, 'w' => self::DSC_BOX_W, 'h' => self::DSC_BOX_H];
        $file  = FCPATH . self::DSC_IMG_FILE;
        $img   = is_file($file) ? $file : null;
        $frame = self::DSC_FRAME;

        return view('pricetag/templates/discount-a5', compact('S', 'box', 'img', 'frame'));
    }

    /* ====================== DESAIN 6b: "disc2" ====================== */

    private function pageHtmlDisc2(array $row): string
    {
        $promo  = (int) round((float) ($row['promo_price'] ?? 0));
        $normal = (int) round((float) ($row['normal_price'] ?? 0));

        // Persen diskon: cari di beberapa kemungkinan nama kolom.
        $pct = 0;
        foreach (['discount_percent', 'discount_pct', 'disc_percent', 'diskon', 'discount', 'percent'] as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && (float) $row[$k] > 0) {
                $pct = (int) round((float) $row[$k]);
                break;
            }
        }
        // Harga promo kosong tapi persen ada -> hitung dari harga normal.
        if ($promo <= 0 && $pct > 0 && $normal > 0) {
            $promo = (int) round($normal * (1 - $pct / 100));
        }
        // Persen kosong tapi harga ada -> hitung dari harga.
        if ($pct <= 0 && $normal > 0 && $promo > 0) {
            $pct = (int) round((1 - $promo / $normal) * 100);
        }

        $adaCoret = $normal > 0 && $normal !== $promo;
        $variant  = mb_strtoupper(trim((string) ($row['variant'] ?? '')));

        $text = [
            'nama_produk' => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
            'artikel'     => $variant !== '' ? $variant : self::DSC_TEKS_ARTIKEL,
            'label'       => self::DSC_TEKS_LABEL,
            'angka'       => (string) $pct,
            'persen'      => '%',
            'rp_lama'     => $adaCoret ? 'Rp' : '',
            'lama'        => $adaCoret ? number_format($normal, 0, ',', '.') : '',
            'rp_baru'     => 'Rp',
            'baru'        => number_format($promo, 0, ',', '.'),
        ];

        $S = ['w' => self::DSC_BOX_W * 10, 'h' => self::DSC_BOX_H * 10, 'el' => []];
        foreach (self::DS2_EL as $key => $def) {
            [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
            if ($key === 'persen') {
                // tanda % menempel di kanan angka diskon, berapa pun lebar angkanya
                $a = $S['el']['angka'];
                $x = ($a['x'] + $a['sx'] * $a['w0']) / 10 + self::DS2_GAP_PERSEN;
            }
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        // Garis coret merah pada harga normal (ikut lebar angkanya)
        $L = ['strike' => null];
        if ($adaCoret) {
            $lama = $S['el']['lama'];
            $x2   = ($lama['x'] + $lama['sx'] * $lama['w0']) / 10 + self::DS2_STRIKE['over'];
            $len  = $x2 - self::DS2_STRIKE['x1'];
            $rise = $len * self::DS2_STRIKE['slope'];
            $pad  = self::DS2_STRIKE['thick'];
            $L['strike'] = [
                'left' => round(self::DSC_BOX_X + self::DS2_STRIKE['x1'], 3),
                'top'  => round(self::DSC_BOX_Y + self::DS2_STRIKE['y1'] - $rise - $pad, 3),
                'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
                'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
            ];
        }

        $box   = ['x' => self::DSC_BOX_X, 'y' => self::DSC_BOX_Y, 'w' => self::DSC_BOX_W, 'h' => self::DSC_BOX_H];
        $file  = FCPATH . self::DSC_IMG_FILE;
        $img   = is_file($file) ? $file : null;
        $frame = self::DSC_FRAME;

        return view('pricetag/templates/discount2-a5', compact('S', 'L', 'box', 'img', 'frame'));
    }

    /* ====================== DESAIN 7: "fresh" (6 kartu per halaman) ====================== */

    /** Kumpulkan semua kartu (qty dijabarkan), bagi per 6, satu halaman A4 tiap 6 kartu. */
    private function renderFresh(array $items): string
    {
        $rows = [];
        foreach ($items as $it) {
            $qty = max(1, (int) ($it['qty'] ?? 1));
            for ($i = 0; $i < $qty; $i++) {
                $rows[] = $it['row'];
            }
        }

        $first = true;
        foreach (array_chunk($rows, self::FRS_PER_PAGE) as $chunk) {
            if (! $first) {
                $this->mpdf->AddPage();
            }
            $this->mpdf->WriteHTML($this->pageHtmlFresh($chunk));
            $first = false;
        }
        return $this->mpdf->Output('', 'S');
    }

    /** @param array<int,array> $rows maksimal 6 baris data */
    private function pageHtmlFresh(array $rows): string
    {
        $size  = ['w' => self::FRS_CARD_W, 'h' => self::FRS_CARD_H];
        $cards = [];

        foreach (array_values($rows) as $i => $row) {
            // Butcher hanya memiliki satu harga: selalu gunakan Harga Normal.
            // Harga Promo boleh tersimpan, tetapi tidak dipakai pada desain ini.
            $promo = (int) round((float) ($row['normal_price'] ?? 0));
            $text = [
                'nama_produk' => mb_strtoupper(trim((string) ($row['name'] ?? ''))),
                'rp'          => 'Rp',
                'harga'       => number_format($promo, 0, ',', '.'),
                'satuan'      => self::FRS_TEKS_SATUAN,
                'plu'         => 'PLU : ' . (string) ($row['sku_plu'] ?? ''),
            ];

            $S = ['w' => self::FRS_CARD_W * 10, 'h' => self::FRS_CARD_H * 10, 'el' => []];
            foreach (self::FRS_EL as $key => $def) {
                [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
                $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                    'color' => $color, 'outline' => $outline,
                ];
            }

            $cards[] = [
                'x' => round(self::FRS_GRID_X + ($i % 2) * self::FRS_CARD_W, 3),
                'y' => round(self::FRS_GRID_Y + intdiv($i, 2) * self::FRS_CARD_H, 3),
                'S' => $S,
            ];
        }

        $img = FCPATH . self::FRS_IMG_FILE;

        return view('pricetag/templates/butcher', compact('cards', 'size', 'img'));
    }

    /* ====================== DESAIN 8: "curah" (6 kartu per halaman + harga coret) ====================== */

    /** Kumpulkan semua kartu (qty dijabarkan), bagi per 6, satu halaman A4 tiap 6 kartu. */
    private function renderCurah(array $items): string
    {
        $rows = [];
        foreach ($items as $it) {
            $qty = max(1, (int) ($it['qty'] ?? 1));
            for ($i = 0; $i < $qty; $i++) {
                $rows[] = $it['row'];
            }
        }

        $first = true;
        foreach (array_chunk($rows, self::CRH_PER_PAGE) as $chunk) {
            if (! $first) {
                $this->mpdf->AddPage();
            }
            $this->mpdf->WriteHTML($this->pageHtmlCurah($chunk));
            $first = false;
        }
        return $this->mpdf->Output('', 'S');
    }

    /** @param array<int,array> $rows maksimal 6 baris data */
    private function pageHtmlCurah(array $rows): string
    {
        $size  = ['w' => self::CRH_CARD_W, 'h' => self::CRH_CARD_H];
        $cards = [];

        foreach (array_values($rows) as $i => $row) {
            $promo  = (int) round((float) ($row['promo_price'] ?? 0));
            $normal = (int) round((float) ($row['normal_price'] ?? 0));
            $adaCoret = $normal > 0 && $normal !== $promo;

            // Nama produk -> 2 baris: kata pertama (kecil) + sisanya (besar)
            $nama = mb_strtoupper(trim((string) ($row['name'] ?? '')));
            $pos  = mb_strpos($nama, ' ');
            $l1   = $pos === false ? '' : mb_substr($nama, 0, $pos);
            $l2   = $pos === false ? $nama : trim(mb_substr($nama, $pos + 1));
            if (isset($row['line1'])) {
                $l1 = mb_strtoupper(trim((string) $row['line1']));
            }
            if (isset($row['line2'])) {
                $l2 = mb_strtoupper(trim((string) $row['line2']));
            }

            $text = [
                'baris1'  => $l1,
                'baris2'  => $l2,
                'plu'     => 'PLU : ' . (string) ($row['sku_plu'] ?? ''),
                'rp_lama' => $adaCoret ? 'Rp.' : '',
                'lama'    => $adaCoret ? number_format($normal, 0, ',', '.') : '',
                'rp_baru' => 'Rp.',
                'satuan'  => self::CRH_TEKS_SATUAN,
                'baru'    => number_format($promo, 0, ',', '.'),
            ];

            $S = ['w' => self::CRH_CARD_W * 10, 'h' => self::CRH_CARD_H * 10, 'el' => []];
            foreach (self::CRH_EL as $key => $def) {
                [$fam, $w, $h, $x, $base, $align, $color, $outline, $maxRatio] = $def;
                $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                    'color' => $color, 'outline' => $outline,
                ];
            }

            $cx = round(self::CRH_GRID_X + ($i % 2) * self::CRH_CARD_W, 3);
            $cy = round(self::CRH_GRID_Y + intdiv($i, 2) * self::CRH_CARD_H, 3);

            // Garis coret hitam pada harga lama (ikut lebar angkanya)
            $strike = null;
            if ($adaCoret) {
                $lama = $S['el']['lama'];
                $x2   = ($lama['x'] + $lama['sx'] * $lama['w0']) / 10 + self::CRH_STRIKE['over'];
                $len  = $x2 - self::CRH_STRIKE['x1'];
                $rise = $len * self::CRH_STRIKE['slope'];
                $pad  = self::CRH_STRIKE['thick'];
                $strike = [
                    'left' => round($cx + self::CRH_STRIKE['x1'], 3),
                    'top'  => round($cy + self::CRH_STRIKE['y1'] - $rise - $pad, 3),
                    'w'    => round($len, 3), 'h' => round($rise + 2 * $pad, 3),
                    'y1'   => round($rise + $pad, 3), 'y2' => round($pad, 3), 'sw' => $pad,
                ];
            }

            $cards[] = ['x' => $cx, 'y' => $cy, 'S' => $S, 'strike' => $strike];
        }

        $img = FCPATH . self::CRH_IMG_FILE;

        return view('pricetag/templates/vegetable', compact('cards', 'size', 'img'));
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
     * Dipakai desain "segitiga", "special", "diskon", "a5" & "disc": kotak wCm/hCm/xCm/baseCm terpisah
     * (dari tabel WordArt). $maxRatio > 0 membatasi lebar huruf maksimal (sx <= sy * maxRatio)
     * agar teks pendek tidak melar. $maxSkew > 0 membatasi PERBEDAAN skala tinggi vs lebar
     * (dipakai kalau font asli membuat huruf jadi kurus/gepeng ekstrem saat diregangkan) -
     * skala yang lebih besar dipangkas supaya tidak lebih dari $maxSkew kali skala yang lebih kecil.
     */
    private function stretchEl(string $fam, string $text, float $wCm, float $hCm, float $xCm, float $baseCm, string $align, float $maxRatio = 0, float $maxSkew = 0): array
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
        if ($maxSkew > 0) {
            if ($sy > $sx * $maxSkew) {
                $sy = $sx * $maxSkew;
            } elseif ($sx > $sy * $maxSkew) {
                $sx = $sy * $maxSkew;
            }
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
