<?php

namespace App\Controllers;

use App\Libraries\DocxLabelMerger;
use App\Libraries\PriceTagTemplates;
use App\Libraries\MpdfLibreTemplate;
use App\Models\PriceTagModel;
use App\Models\ImportHistoryModel;

class PrintPdf extends BaseController
{
    public function index()
    {
        $requestId = bin2hex(random_bytes(8));
        $selectedIds  = (array) $this->request->getPost('tag_ids');
        log_message('debug', '[print:{id}] PrintPdf start products={count}', ['id' => $requestId, 'count' => count($selectedIds)]);
        $qtyMap       = (array) $this->request->getPost('qty');
        $sizeMap      = (array) $this->request->getPost('size');
        $allvarMap    = (array) $this->request->getPost('allvar');
        $discountMode = (array) $this->request->getPost('discount_mode');
        $renderer = (string) $this->request->getPost('renderer');
        $importId     = (int) $this->request->getPost('import_id');

        $matchedSizes = array_intersect_key($sizeMap, array_flip($selectedIds));
        log_message('debug', '[print:{id}] size payload tag_ids={ids} size_keys={keys} size_values={values} matched={matched}', [
            'id' => $requestId,
            'ids' => json_encode(array_values($selectedIds)),
            'keys' => json_encode(array_keys($sizeMap)),
            'values' => json_encode($sizeMap),
            'matched' => json_encode($matchedSizes),
        ]);

        if (empty($selectedIds)) {
            return redirect()->to('/import-history')->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $missingSizeIds = array_values(array_filter($selectedIds, static fn ($id): bool => ! array_key_exists($id, $sizeMap) || trim((string) $sizeMap[$id]) === ''));
        if ($missingSizeIds !== []) {
            log_message('debug', '[print:{id}] validation rejected missing size ids={ids}', ['id' => $requestId, 'ids' => json_encode($missingSizeIds)]);
            return redirect()->back()->with('error', 'Pastikan produk sudah memilih ukuran template.');
        }
        $selectedSizes = array_values(array_unique(array_map(static fn ($size): string => strtolower(trim((string) $size)), $matchedSizes)));
        if (count($selectedSizes) > 1) {
            log_message('debug', '[print:{id}] validation rejected mixed sizes={sizes}', ['id' => $requestId, 'sizes' => json_encode($selectedSizes)]);
            return redirect()->back()->with('error', 'Tidak dapat mencetak beberapa ukuran template sekaligus. Silakan pilih produk dengan ukuran template yang sama.');
        }
        log_message('debug', '[print:{id}] size validation passed sizes={sizes}', ['id' => $requestId, 'sizes' => json_encode($selectedSizes)]);

        $model      = new PriceTagModel();
        $userId     = (int) session()->get('id');
        $importDate = date('Y-m-d'); // sesuai data yang sedang tampil di halaman list
        if ($importId > 0) {
            $history = (new ImportHistoryModel())->findVisibleImport(
                $importId,
                (string) session()->get('role'),
                session()->get('outlet_id') === null ? null : (int) session()->get('outlet_id')
            );
            if (! $history) {
                return redirect()->to('/import-history')->with('error', 'Data impor tidak ditemukan.');
            }
        }

        // Ambil seluruh produk terpilih dalam satu query, bukan satu query
        // untuk setiap produk. Setelah itu proses tetap mengikuti urutan ID
        // yang dikirim oleh form agar hasil cetak tidak berubah.
        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', $selectedIds),
            static fn (int $id): bool => $id > 0
        )));
        $productQuery = $model->whereIn('id', $selectedIds);
        if ($importId > 0) {
            $productQuery->where('import_id', $importId);
        } else {
            $productQuery->where('uploaded_by', $userId)
                         ->where('import_date', $importDate);
        }
        $productsById = [];
        foreach ($productQuery->findAll() as $product) {
            $productsById[(int) $product['id']] = $product;
        }

        // Kelompokkan produk berdasarkan template yang sesuai dengan datanya.
        $groups = [];
        $mpdfGroups = [];
        $useMpdf = $renderer === 'mpdf-dev';
        foreach ($selectedIds as $tagId) {
            $product = $productsById[$tagId] ?? null;
            if ($product === null) continue;

            $sku = (string) $product['sku_plu'];

            $size = (string) ($sizeMap[$tagId] ?? '');
            $allvar = isset($allvarMap[$tagId]) && $allvarMap[$tagId] === '1';
            if (! in_array($size, ['kcl', 'tgg'], true)) {
                return redirect()->back()->with('error', 'Pastikan produk sudah memilih ukuran template.');
            }
            if ($size === 'tgg' && $allvar && (int) ($product['allocation_pcs'] ?? 0) > 0) {
                return redirect()->to('/import-history')->with(
                    'error',
                    'Produk dengan alokasi tidak dapat menggunakan template Tanggung - All Varian. '
                    . 'Silakan nonaktifkan All Varian atau pilih template Tanggung biasa.'
                );
            }

            // Tentukan template dari input asli terlebih dahulu. Harga promo
            // hasil perhitungan diskon tidak boleh dianggap sebagai input
            // nominal karena keduanya memiliki aturan template berbeda.
            $templateKey = $this->templateKeyForProduct($product, $size, $allvar, (string) ($discountMode[$tagId] ?? 'auto'));
            $template = PriceTagTemplates::find($templateKey);
            if ($template === null) continue;

            // Untuk promo diskon tanpa harga promo dari Excel, hitung harga
            // promo otomatis agar tetap tercetak pada POP setelah klasifikasi
            // template selesai.
            $product['promo_price'] = $this->calculatePromoPrice($product);

            if ($useMpdf) {
                $requestedRenderer = $renderer !== '' ? $renderer : 'libreoffice(default)';
                $validationReason = in_array($templateKey, ['turun-harga-tgg', 'turun-harga-tgg-allvar', 'turun-harga-tgg-allocation', 'disc-reg-tgg', 'disc-reg-tgg-allocation'], true)
                    ? 'accepted-template-key'
                    : 'unsupported-template-key-for-mpdf-dev';
                log_message('debug', '[print:{id}] renderer diagnostic product_id={product_id} sku={sku} product_import_id={product_import_id} request_import_id={request_import_id} request_size={request_size} db_template_size={db_template_size} normal_price={normal_price} promo_price={promo_price} discount_percent={discount_percent} allocation_pcs={allocation_pcs} allvar={allvar} discount_mode={discount_mode} renderer={renderer} template_key={template_key} validation_reason={reason}', [
                    'id' => $requestId,
                    'product_id' => (int) $tagId,
                    'sku' => (string) ($product['sku_plu'] ?? ''),
                    'product_import_id' => (int) ($product['import_id'] ?? 0),
                    'request_import_id' => $importId,
                    'request_size' => $size,
                    'db_template_size' => (string) ($product['template_size'] ?? ''),
                    'normal_price' => $product['normal_price'] ?? null,
                    'promo_price' => $product['promo_price'] ?? null,
                    'discount_percent' => $product['discount_percent'] ?? null,
                    'allocation_pcs' => $product['allocation_pcs'] ?? null,
                    'allvar' => $allvar ? '1' : '0',
                    'discount_mode' => (string) ($discountMode[$tagId] ?? 'not-sent'),
                    'renderer' => $requestedRenderer,
                    'template_key' => $templateKey,
                    'reason' => $validationReason,
                ]);
            }

            if ($useMpdf) {
                // Development renderer is deliberately allow-listed. The key is
                // recomputed above from trusted product data and never accepted
                // from the browser.
                if (! in_array($templateKey, ['turun-harga-tgg', 'turun-harga-tgg-allvar', 'turun-harga-tgg-allocation', 'disc-reg-tgg', 'disc-reg-tgg-allocation'], true)) {
                    log_message('warning', '[print:{id}] renderer rejected product_id={product_id} sku={sku} template_key={template_key} reason=unsupported-template-key-for-mpdf-dev', [
                        'id' => $requestId,
                        'product_id' => (int) $tagId,
                        'sku' => (string) ($product['sku_plu'] ?? ''),
                        'template_key' => $templateKey,
                    ]);
                    return redirect()->back()->with('error', 'Renderer mPDF Pengembangan saat ini hanya tersedia untuk template Tanggung Turun Harga yang sudah dimigrasikan.');
                }
                $mpdfGroups[$templateKey][] = ['row' => $product, 'qty' => max(1, (int) ($qtyMap[$tagId] ?? 1))];
                continue;
            }

            $groups[$templateKey][] = [
                'sku'          => $sku,
                'qty'          => max(1, (int) ($qtyMap[$tagId] ?? 1)),
                'field_values' => DocxLabelMerger::buildFieldValues($product, $template['fields']),
            ];
        }

        if ($useMpdf && empty($mpdfGroups)) {
            return redirect()->back()->with('error', 'Tidak ada produk yang memenuhi syarat renderer mPDF Pengembangan.');
        }
        if (! $useMpdf && empty($groups)) {
            return redirect()->to('/import-history')->with('error', 'Produk yang dipilih tidak ditemukan di database.');
        }

        $mergers = [];

        try {
            if ($useMpdf) {
                $contents = (new MpdfLibreTemplate())->render($mpdfGroups);
                $downloadToken = bin2hex(random_bytes(16));
                $downloadDir = WRITEPATH . 'pricetag_downloads/';
                if (! is_dir($downloadDir) && ! mkdir($downloadDir, 0775, true) && ! is_dir($downloadDir)) {
                    throw new \RuntimeException('Folder penyimpanan PDF tidak dapat dibuat.');
                }
                $storedPdfPath = $downloadDir . $downloadToken . '.pdf';
                if (file_put_contents($storedPdfPath, $contents, LOCK_EX) === false || ! is_file($storedPdfPath) || filesize($storedPdfPath) <= 0) {
                    throw new \RuntimeException('PDF mPDF tidak valid setelah penyimpanan.');
                }
                if (file_put_contents($downloadDir . $downloadToken . '.json', json_encode(['user_id' => $userId, 'expires_at' => time() + 3600]), LOCK_EX) === false) {
                    @unlink($storedPdfPath);
                    throw new \RuntimeException('Informasi PDF sementara tidak dapat disimpan.');
                }
                return redirect()->to(site_url('print-pdf/' . $downloadToken));
            }
            $groupPdfs = [];
            foreach ($groups as $templateKey => $items) {
                log_message('debug', '[print:{id}] DOCX group template={template} items={count}', ['id' => $requestId, 'template' => $templateKey, 'count' => count($items)]);
                $template = PriceTagTemplates::find($templateKey);
                $mergers[$templateKey] = new DocxLabelMerger($template['docx']);
                $groupPdfs[] = $mergers[$templateKey]->generate($items);
            }
            $pdfPath = $this->combinePdfs($groupPdfs);
            log_message('debug', '[print:{id}] controller combine output={path} bytes={bytes}', ['id' => $requestId, 'path' => basename($pdfPath), 'bytes' => is_file($pdfPath) ? filesize($pdfPath) : 0]);

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
            if (! is_file($storedPdfPath) || filesize($storedPdfPath) <= 0) throw new \RuntimeException('PDF final tidak valid setelah penyimpanan.');

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
            log_message('error', '[print:{id}] PrintPdf failed: {message} at {file}:{line}', ['id' => $requestId, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return redirect()->to('/import-history')->with('error', 'Gagal membuat PDF. Silakan periksa pilihan template dan coba lagi.');
        } finally {
            foreach ($mergers as $merger) {
                $merger->cleanup();
            }
        }
    }

    private function templateKeyForProduct(array $product, string $size, bool $allvar, string $discountMode = 'auto'): string
    {
        // Harga promo nominal adalah sumber harga akhir utama. Jangan
        // mengklasifikasikannya sebagai template Diskon hanya karena ada
        // persentase tersirat/tersimpan pada baris produk.
        $hasPromoPrice = (float) ($product['promo_price'] ?? 0) > 0;
        $discount = $hasPromoPrice ? 0.0 : (float) ($product['discount_percent'] ?? 0);
        $program = ($discount > 0 && ($discount >= 10 || $discountMode === 'show'))
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
            '"%s" %s cat output %s 2>&1',
            'C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe',
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
