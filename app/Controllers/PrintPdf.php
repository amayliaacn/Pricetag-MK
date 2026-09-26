<?php

namespace App\Controllers;

use App\Libraries\DocxLabelMerger;
use App\Libraries\PriceTagTemplates;
use App\Models\PriceTagModel;
use App\Models\ImportHistoryModel;

class PrintPdf extends BaseController
{
    public function index()
    {
        $selectedSkus = (array) $this->request->getPost('skus');
        $qtyMap       = (array) $this->request->getPost('qty');
        $sizeMap      = (array) $this->request->getPost('size');
        $allvarMap    = (array) $this->request->getPost('allvar');
        $importId     = (int) $this->request->getPost('import_id');

        if (empty($selectedSkus)) {
            return redirect()->to('/import-history')->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $model      = new PriceTagModel();
        $userId     = (int) session()->get('id');
        $importDate = date('Y-m-d'); // sesuai data yang sedang tampil di halaman list
        $importTags = [];
        if ($importId > 0) {
            $history = (new ImportHistoryModel())->findVisibleImport(
                $importId,
                (string) session()->get('role'),
                session()->get('outlet_id') === null ? null : (int) session()->get('outlet_id')
            );
            if (! $history) {
                return redirect()->to('/import-history')->with('error', 'Data impor tidak ditemukan.');
            }
            foreach ($model->forImport($importId) as $tag) {
                $importTags[(string) $tag['sku_plu']] = $tag;
            }
        }

        // Kelompokkan produk berdasarkan template yang sesuai dengan datanya.
        $groups = [];
        foreach ($selectedSkus as $sku) {
            $product = $importId > 0
                ? ($importTags[(string) $sku] ?? null)
                : $model->findBySkuForUser($userId, $importDate, $sku);
            if ($product === null) continue;

            $size = (string) ($sizeMap[$sku] ?? '');
            $allvar = isset($allvarMap[$sku]) && $allvarMap[$sku] === '1';
            if (! in_array($size, ['kcl', 'tgg'], true)) {
                return redirect()->back()->with('error', 'Pastikan semua produk sudah memilih ukuran template.');
            }
            if ($size === 'tgg' && $allvar && (int) ($product['allocation_pcs'] ?? 0) > 0) {
                return redirect()->to('/import-history')->with(
                    'error',
                    'Produk dengan alokasi tidak dapat menggunakan template Tanggung - All Varian. '
                    . 'Silakan nonaktifkan All Varian atau pilih template Tanggung biasa.'
                );
            }

            // Untuk promo diskon tanpa harga promo dari Excel, hitung harga
            // promo otomatis agar tetap tercetak pada POP.
            $product['promo_price'] = $this->calculatePromoPrice($product);

            $templateKey = $this->templateKeyForProduct($product, $size, $allvar);
            $template = PriceTagTemplates::find($templateKey);
            if ($template === null) continue;

            $groups[$templateKey][] = [
                'sku'          => $sku,
                'qty'          => max(1, (int) ($qtyMap[$sku] ?? 1)),
                'field_values' => DocxLabelMerger::buildFieldValues($product, $template['fields']),
            ];
        }

        if (empty($groups)) {
            return redirect()->to('/import-history')->with('error', 'Produk yang dipilih tidak ditemukan di database.');
        }

        $mergers = [];

        try {
            $groupPdfs = [];
            foreach ($groups as $templateKey => $items) {
                $template = PriceTagTemplates::find($templateKey);
                $mergers[$templateKey] = new DocxLabelMerger($template['docx']);
                $groupPdfs[] = $mergers[$templateKey]->generate($items);
            }
            $pdfPath = $this->combinePdfs($groupPdfs);

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
            return redirect()->to('/import-history')->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        } finally {
            foreach ($mergers as $merger) {
                $merger->cleanup();
            }
        }
    }

    private function templateKeyForProduct(array $product, string $size, bool $allvar): string
    {
        $program = ((float) ($product['discount_percent'] ?? 0) > 0)
            ? 'disc-reg'
            : 'turun-harga';
        $allocation = (int) ($product['allocation_pcs'] ?? 0) > 0;

        if ($size === 'tgg') {
            if ($allvar && ! $allocation) {
                return $program . '-tgg-allvar';
            }

            return $program . '-tgg' . ($allocation ? '-allocation' : '');
        }

        return $program . '-kcl' . ($allocation ? '-allocation' : '');
    }

    private function calculatePromoPrice(array $product): ?int
    {
        $normalPrice = (float) ($product['normal_price'] ?? 0);
        $discount    = (float) ($product['discount_percent'] ?? 0);
        $promoPrice  = (float) ($product['promo_price'] ?? 0);

        if ($promoPrice > 0 || $normalPrice <= 0 || $discount <= 0) {
            return $promoPrice > 0 ? (int) round($promoPrice) : null;
        }

        return (int) round($normalPrice * (1 - ($discount / 100)));
    }

    private function combinePdfs(array $pdfPaths): string
    {
        if (count($pdfPaths) === 1) {
            return $pdfPaths[0];
        }

        $outputPath = WRITEPATH . 'pricetag_tmp/combined_' . bin2hex(random_bytes(8)) . '.pdf';
        $files = array_map('escapeshellarg', $pdfPaths);
        $command = sprintf(
            'pdftk %s cat output %s 2>&1',
            implode(' ', $files),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);
        if ($returnCode !== 0 || ! is_file($outputPath)) {
            throw new \RuntimeException('Gagal menggabungkan hasil PDF.');
        }

        return $outputPath;
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
