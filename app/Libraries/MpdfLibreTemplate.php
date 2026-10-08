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
        'turun-harga-tgg-allvar'   => ['view' => 'turun-hrg-tgg-allvar', 'size' => [157, 84]],
        'turun-harga-tgg-allocation' => ['view' => 'turun-hrg-tgg-allocation', 'size' => [157, 84]],
        'disc-reg-kcl'             => ['view' => 'discount-reg-kcl', 'size' => [70, 45]],
        'disc-reg-kcl-allocation'  => ['view' => 'discount-reg-kcl-allocation', 'size' => [70, 45]],
    ];

    /* ---------- font untuk SVG (file di public/assets/fonts, sama dengan PopA4Pdf) ---------- */
    private const FONTS = [
        'arialblack'     => ['R' => 'ariblk.ttf'],
        'arialnarrow'    => ['R' => 'ARIALN.TTF', 'B' => 'ARIALNB.TTF', 'I' => 'ARIALNI.TTF', 'BI' => 'ARIALNBI.TTF'],
        'bookantiqua'    => ['R' => 'BOOKOS.TTF'],
        'calistomt'      => ['R' => 'CALIST.TTF'],
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
    private const TGG_BIG   = ['x' => 9.29,  'w' => 4.67, 'h' => 3.15, 'base' => 7.08, 'max' => 1.0];
    private const TGG_SMALL = ['x' => 14.24, 'w' => 2.97, 'h' => 1.13, 'base' => 7.06, 'max' => 1.0];

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
            'fontDir' => array_merge($d['fontDir'], [FCPATH . 'assets/fonts']),
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
        $formatted = str_pad((string) $promo, 4, '0', STR_PAD_LEFT);

        return $row + [
            'display_name' => trim(($row['name'] ?? '') . ' ' . ($row['variant'] ?? '')),
            'periode_display' => (string) ($row['end_period'] ?? ''),
            'promo_thousands' => substr($formatted, 0, -3) ?: '0',
            'promo_hundreds' => '.' . substr($formatted, -3),
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
            $S['el'][$key] = $this->stretchEl($fam, $text[$key], $w, $h, $x, $base, $align, (float) $maxRatio) + [
                'color' => $color, 'outline' => $outline,
            ];
        }

        // Ribuan + ratusan (".991"); sudah disiapkan prepareTag()
        $S['el']['big']   = $this->stretchBox('bookantiqua', (string) $tag['promo_thousands'], self::TGG_BIG)
            + ['color' => '#FF0000', 'outline' => '#FF0000 0.45pt'];
        $S['el']['small'] = $this->stretchBox('bookantiqua', (string) $tag['promo_hundreds'], self::TGG_SMALL)
            + ['color' => '#FF0000', 'outline' => '#FF0000 0.45pt'];

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

    /** "2026-09-15" -> "15 SEP '26" (kalau bukan tanggal valid, dipakai apa adanya) */
    private function tanggalPendek(string $ymd): string
    {
        $ts = strtotime($ymd);
        if (! $ts) {
            return mb_strtoupper(trim($ymd));
        }
        $b = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
        return date('j', $ts) . ' ' . $b[(int) date('n', $ts)] . " '" . date('y', $ts);
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
