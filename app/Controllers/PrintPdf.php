<?php

namespace App\Controllers;

use App\Libraries\DocxLabelMerger;
use App\Libraries\PriceTagTemplates;
use App\Models\PriceTagModel;

class PrintPdf extends BaseController
{
    public function index()
    {
        $selectedSkus = (array) $this->request->getPost('skus');
        $qtyMap       = (array) $this->request->getPost('qty');
        $templateKey  = (string) $this->request->getPost('template');

        if (empty($selectedSkus)) {
            return redirect()->to('/pricetag')->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $template = PriceTagTemplates::find($templateKey);
        if ($template === null) {
            return redirect()->to('/pricetag')->with('error', 'Template tidak ditemukan.');
        }

        $model      = new PriceTagModel();
        $userId     = (int) session()->get('id');
        $importDate = date('Y-m-d'); // sesuai data yang sedang tampil di halaman list

        // Susun daftar item untuk mesin cetak: tiap produk terpilih + qty-nya,
        // dengan nilai field yang sudah diformat sesuai peta template.
        $items = [];
        foreach ($selectedSkus as $sku) {
            $product = $model->findBySkuForUser($userId, $importDate, $sku);
            if ($product === null) continue;

            $items[] = [
                'sku'          => $sku,
                'qty'          => max(1, (int) ($qtyMap[$sku] ?? 1)),
                'field_values' => DocxLabelMerger::buildFieldValues($product, $template['fields']),
            ];
        }

        if (empty($items)) {
            return redirect()->to('/pricetag')->with('error', 'Produk yang dipilih tidak ditemukan di database.');
        }

        $merger = new DocxLabelMerger($template['docx']);

        try {
            $pdfPath = $merger->generate($items);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="pop-price-tag.pdf"')
                ->setBody(file_get_contents($pdfPath));
        } catch (\Throwable $e) {
            log_message('error', 'Cetak price tag gagal: ' . $e->getMessage());
            return redirect()->to('/pricetag')->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        } finally {
            $merger->cleanup();
        }
    }
}
