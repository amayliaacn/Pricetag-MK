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
        $tags  = [];
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
            for ($i = 0, $qty = max(1, (int) ($qtyMap[$tagId] ?? 1)); $i < $qty; $i++) {
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
            $sizes = (array) $this->request->getPost('size');
            $template = in_array('diskon', $sizes, true)
                ? 'diskon'
                : (in_array('special-price', $sizes, true)
                    ? 'special'
                    : (in_array('segitiga', $sizes, true) ? 'segitiga' : ''));
            $pdf = (new PopA4Pdf($template))->render($items, $template);

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
