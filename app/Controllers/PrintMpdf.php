<?php

namespace App\Controllers;

use App\Models\MpdfPriceTagModel;
use App\Libraries\PopA4Pdf;

class PrintMpdf extends BaseController
{
    public function index()
    {
        $tagIds   = (array) $this->request->getPost('tag_ids');
        $qtyMap   = (array) $this->request->getPost('qty');
        $importId = (int) $this->request->getPost('import_id');
        $discountMode = (array) $this->request->getPost('discount_mode');

        if ($tagIds === []) {
            return redirect()->back()->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $model = new MpdfPriceTagModel();
        $groups = [];
        $sizes  = (array) $this->request->getPost('size');
        $selectedSizes = array_values(array_unique(array_filter(array_map('strval', array_intersect_key($sizes, array_flip($tagIds))))));
        if (count($selectedSizes) > 1) {
            return redirect()->back()->with('error', 'Tidak dapat mencetak beberapa ukuran template sekaligus. Silakan pilih produk dengan ukuran template yang sama.');
        }
        foreach ($tagIds as $tagId) {
            $query = $model->where('id', (int) $tagId);
            if ($importId > 0) {
                $query->where('import_id', $importId);
            } else {
                $query->where('uploaded_by', (int) session()->get('id'))
                      ->where('import_date', date('Y-m-d'));
            }
            $tag = $query->first();
            if ($tag === null) {
                continue;
            }
            $size = (string) ($sizes[$tagId] ?? 'mpdf');
            if ($size === 'a5' && (float) ($tag['promo_price'] ?? 0) <= 0 && (float) ($tag['discount_percent'] ?? 0) <= 0) {
                return redirect()->back()->with('error', 'A5 Discount/Turun Harga memerlukan Diskon atau Harga Promo.');
            }
            if ($size === 'curah' && (float) ($tag['promo_price'] ?? 0) <= 0 && (float) ($tag['discount_percent'] ?? 0) <= 0) {
                return redirect()->back()->with('error', 'Template Vegetable memerlukan Diskon atau Harga Promo.');
            }
            // Saat memakai mode Turun Harga, pastikan template menerima harga
            // hasil perhitungan meskipun promo_price belum tersimpan di database.
            // A4 tidak memiliki template diskon; selalu gunakan Turun Harga.
            $mode = $size === 'mpdf' ? 'auto' : (string) ($discountMode[$tagId] ?? 'auto');
            $showDiscount = $this->showDiscount($tag, $mode);
            // Harga turun hanya memakai hasil hitung untuk diskon < 10%.
            // Mode tampil diskon dan diskon >= 10% tetap memakai data lama.
            if (in_array($size, ['mpdf', 'a5', 'segitiga'], true) && ! $showDiscount && (float) ($tag['discount_percent'] ?? 0) > 0) {
                $tag['promo_price'] = $this->calculatePromoPrice($tag);
            }
            $template = match ($size) {
                'segitiga' => ($showDiscount ? 'diskon' : ((float) ($tag['promo_price'] ?? 0) > 0 ? 'segitiga' : 'special')),
                'a5' => ((float) ($tag['discount_percent'] ?? 0) > 0
                    ? ($showDiscount ? ((float) ($tag['normal_price'] ?? 0) > 0 ? 'disc2' : 'disc') : 'a5')
                    : 'a5'),
                'fresh' => 'fresh',
                'curah' => 'curah',
                default => 'a4',
            };
            $groups[$template][] = [
                'row' => $tag,
                'qty' => max(1, (int) ($qtyMap[$tagId] ?? 1)),
            ];
        }

        if ($groups === []) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        try {
            $pdf = $this->writeTemporaryPdf((new PopA4Pdf())->renderMixed($groups));

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Content-Disposition', 'inline; filename="price-tag-mpdf-a4.pdf"')
                ->setBody((string) file_get_contents($pdf));
        } catch (\Throwable $e) {
            log_message('error', 'Cetak mPDF gagal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }

    private function showDiscount(array $tag, string $mode): bool
    {
        $discount = (float) ($tag['discount_percent'] ?? 0);
        return $discount > 0 && ($discount >= 10 || $mode === 'show');
    }

    private function calculatePromoPrice(array $tag): ?int
    {
        $promo = (float) ($tag['promo_price'] ?? 0);
        $normal = (float) ($tag['normal_price'] ?? 0);
        $discount = (float) ($tag['discount_percent'] ?? 0);

        if ($promo > 0) return (int) round($promo);
        if ($normal <= 0 || $discount <= 0) return null;

        return (int) round($normal * (1 - ($discount / 100)));
    }

    private function writeTemporaryPdf(string $contents): string
    {
        $dir = WRITEPATH . 'pricetag_tmp/';
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Folder PDF sementara tidak dapat dibuat.');
        }
        $path = $dir . 'mpdf_' . bin2hex(random_bytes(8)) . '.pdf';
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new \RuntimeException('PDF sementara tidak dapat disimpan.');
        }
        return $path;
    }

    private function combinePdfs(array $paths): string
    {
        if (count($paths) === 1) return $paths[0];
        $output = WRITEPATH . 'pricetag_tmp/combined_' . bin2hex(random_bytes(8)) . '.pdf';
        $command = sprintf(
            '"%s" %s cat output %s 2>&1',
            'C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe',
            implode(' ', array_map('escapeshellarg', $paths)),
            escapeshellarg($output)
        );
        exec($command, $result, $code);
        if ($code !== 0 || ! is_file($output)) {
            throw new \RuntimeException('Gagal menggabungkan hasil PDF.');
        }
        return $output;
    }
}
