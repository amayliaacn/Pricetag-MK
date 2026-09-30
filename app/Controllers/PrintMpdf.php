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

        if ($tagIds === []) {
            return redirect()->back()->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $model = new MpdfPriceTagModel();
        $groups = [];
        $sizes  = (array) $this->request->getPost('size');
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
            $template = match ($size) {
                'segitiga' => 'segitiga',
                'special-price' => 'special',
                'diskon' => 'diskon',
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
            $pdfs = [];
            foreach ($groups as $template => $items) {
                $pdfs[] = $this->writeTemporaryPdf(
                    (new PopA4Pdf($template))->render($items, $template)
                );
            }
            $pdf = $this->combinePdfs($pdfs);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="price-tag-mpdf-a4.pdf"')
                ->setBody((string) file_get_contents($pdf));
        } catch (\Throwable $e) {
            log_message('error', 'Cetak mPDF gagal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
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
            'pdftk %s cat output %s 2>&1',
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
