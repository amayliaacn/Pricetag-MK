<?php

namespace App\Controllers;

use App\Models\MpdfPriceTagModel;
use App\Libraries\PopA4Pdf;

class PrintMpdf extends BaseController
{
    public function index()
    {
        $skus     = (array) $this->request->getPost('skus');
        $qtyMap   = (array) $this->request->getPost('qty');
        $importId = (int) $this->request->getPost('import_id');

        if ($skus === []) {
            return redirect()->back()->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $model = new MpdfPriceTagModel();
        $tags  = [];
        foreach ($skus as $sku) {
            $tag = $model->findForPrint((int) session()->get('id'), (string) $sku, $importId ?: null);
            if ($tag === null) {
                continue;
            }
            for ($i = 0, $qty = max(1, (int) ($qtyMap[$sku] ?? 1)); $i < $qty; $i++) {
                $tags[] = $tag;
            }
        }

        if ($tags === []) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        try {
            $items = array_map(static fn (array $tag): array => [
                'row' => $tag,
                'qty' => 1,
            ], $tags);
            $pdf = (new PopA4Pdf())->render($items);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="price-tag-mpdf-a4.pdf"')
                ->setBody($pdf);
        } catch (\Throwable $e) {
            log_message('error', 'Cetak mPDF gagal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }
}
