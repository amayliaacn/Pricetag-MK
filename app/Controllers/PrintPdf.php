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

            // Simpan PDF di luar web root agar Chrome dapat mengambil ulang
            // file melalui GET saat pengguna menekan tombol unduh.
            $downloadDir = WRITEPATH . 'pricetag_downloads/';
            if (! is_dir($downloadDir) && ! mkdir($downloadDir, 0775, true) && ! is_dir($downloadDir)) {
                throw new \RuntimeException('Folder penyimpanan PDF tidak dapat dibuat.');
            }

            $downloadToken = bin2hex(random_bytes(16));
            $storedPdfPath = $downloadDir . $downloadToken . '.pdf';
            if (! copy($pdfPath, $storedPdfPath)) {
                throw new \RuntimeException('PDF sementara tidak dapat disimpan.');
            }

            $expiresAt = time() + 3600;
            foreach (glob($downloadDir . '*.json') ?: [] as $metadataPath) {
                $metadata = json_decode((string) @file_get_contents($metadataPath), true);
                if (! is_array($metadata) || ($metadata['expires_at'] ?? 0) < time()) {
                    @unlink($metadataPath);
                    @unlink($downloadDir . basename($metadataPath, '.json') . '.pdf');
                }
            }

            $metadataPath = $downloadDir . $downloadToken . '.json';
            if (file_put_contents($metadataPath, json_encode([
                'user_id' => $userId,
                'expires_at' => $expiresAt,
            ]), LOCK_EX) === false) {
                @unlink($storedPdfPath);
                throw new \RuntimeException('Informasi PDF sementara tidak dapat disimpan.');
            }

            return redirect()->to(site_url('print-pdf/' . $downloadToken));
        } catch (\Throwable $e) {
            log_message('error', 'Cetak price tag gagal: ' . $e->getMessage());
            return redirect()->to('/pricetag')->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        } finally {
            $merger->cleanup();
        }
    }

    public function download(string $token)
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return $this->response->setStatusCode(404);
        }

        $downloadDir = WRITEPATH . 'pricetag_downloads/';
        $metadataPath = $downloadDir . $token . '.json';
        $pdfPath = $downloadDir . $token . '.pdf';
        $metadata = is_file($metadataPath)
            ? json_decode((string) file_get_contents($metadataPath), true)
            : null;

        if (! is_array($metadata)
            || (int) ($metadata['expires_at'] ?? 0) < time()
            || (int) ($metadata['user_id'] ?? 0) !== (int) session()->get('id')
            || ! is_file($pdfPath)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="pop-price-tag.pdf"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody((string) file_get_contents($pdfPath));
    }
}
