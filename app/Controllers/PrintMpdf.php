<?php

namespace App\Controllers;

use App\Models\MpdfPriceTagModel;
use App\Models\ImportHistoryModel;
use App\Libraries\PopA4Pdf;
use DateTimeImmutable;
use InvalidArgumentException;

class PrintMpdf extends BaseController
{
    public function index()
    {
        $requestId = bin2hex(random_bytes(8));
        $tagIds   = (array) $this->request->getPost('tag_ids');
        log_message('debug', '[print:{id}] PrintMpdf start products={count}', ['id' => $requestId, 'count' => count($tagIds)]);
        $qtyMap   = (array) $this->request->getPost('qty');
        $importId = (int) $this->request->getPost('import_id');
        $discountMode = (array) $this->request->getPost('discount_mode');

        if ($tagIds === []) {
            return redirect()->back()->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        }

        $model = new MpdfPriceTagModel();
        $groups = [];
        $smallTriangleOrdered = [];
        $sizes  = (array) $this->request->getPost('size');
        if (in_array('segitiga_kecil', $sizes, true)) {
            foreach ($tagIds as $id) {
                if ($this->positiveInteger($id) === null) {
                    return $this->rejectSmallTriangle('ID produk Segitiga Kecil tidak valid.');
                }
            }
        }
        $matchedSizes = array_intersect_key($sizes, array_flip($tagIds));
        log_message('debug', '[print:{id}] mPDF size payload tag_ids={ids} size_keys={keys} size_values={values} matched={matched}', [
            'id' => $requestId,
            'ids' => json_encode(array_values($tagIds)),
            'keys' => json_encode(array_keys($sizes)),
            'values' => json_encode($sizes),
            'matched' => json_encode($matchedSizes),
        ]);
        $missingSizeIds = array_values(array_filter($tagIds, static fn ($id): bool => ! array_key_exists($id, $sizes) || trim((string) $sizes[$id]) === ''));
        if ($missingSizeIds !== []) {
            log_message('debug', '[print:{id}] mPDF validation rejected missing size ids={ids}', ['id' => $requestId, 'ids' => json_encode($missingSizeIds)]);
            return redirect()->back()->with('error', 'Pastikan produk sudah memilih ukuran template.');
        }
        $selectedSizes = array_values(array_unique(array_map(static fn ($size): string => strtolower(trim((string) $size)), $matchedSizes)));
        if (count($selectedSizes) > 1) {
            log_message('debug', '[print:{id}] mPDF validation rejected mixed sizes={sizes}', ['id' => $requestId, 'sizes' => json_encode($selectedSizes)]);
            return redirect()->back()->with('error', 'Tidak dapat mencetak beberapa ukuran template sekaligus. Silakan pilih produk dengan ukuran template yang sama.');
        }
        $isSmallTriangle = $selectedSizes === ['segitiga_kecil'];
        if ($isSmallTriangle) {
            $requestedImport = $this->positiveInteger($this->request->getPost('import_id'));
            $outletId = session()->get('outlet_id');
            if ($requestedImport === null || !(new ImportHistoryModel())->findVisibleImport(
                $requestedImport,
                (string) session()->get('role'),
                $outletId === null ? null : (int) $outletId
            )) {
                return $this->rejectSmallTriangle('Riwayat produk Segitiga Kecil tidak ditemukan atau tidak dapat diakses.');
            }
            if (count(array_unique(array_map('intval', $tagIds))) !== count($tagIds)) {
                return $this->rejectSmallTriangle('Produk Segitiga Kecil yang sama tidak boleh dipilih lebih dari sekali.');
            }
        }
        log_message('debug', '[print:{id}] mPDF size validation passed sizes={sizes}', ['id' => $requestId, 'sizes' => json_encode($selectedSizes)]);
        $productsById = [];
        foreach (array_unique(array_map('intval', $tagIds)) as $tagId) {
            $query = $model->where('id', $tagId);
            if ($importId > 0) {
                $query->where('import_id', $importId);
            } else {
                $query->where('uploaded_by', (int) session()->get('id'))
                      ->where('import_date', date('Y-m-d'));
            }
            $tag = $query->first();
            if ($tag !== null) {
                $productsById[$tagId] = $tag;
            }
        }
        if ($rejection = $this->rejectInvalidPrintDiscounts($productsById)) {
            return $rejection;
        }

        $pairItemsByTopId = [];
        $pairedIds = [];
        $rawPairs = $this->request->getPost('vegetable_pairs');
        if ($isSmallTriangle && $rawPairs !== null && $rawPairs !== []) {
            return $this->rejectSmallTriangle('Segitiga Kecil tidak mendukung pasangan Vegetable Tanggung.');
        }
        if ($rawPairs !== null) {
            if (! is_array($rawPairs)) {
                return redirect()->back()->with('error', 'Format pasangan Vegetable Tanggung tidak valid.');
            }
            foreach ($rawPairs as $pair) {
                if (! is_array($pair)) {
                    return redirect()->back()->with('error', 'Pasangan Vegetable Tanggung tidak lengkap.');
                }
                $topId = $pair['top_id'] ?? null;
                $bottomId = $pair['bottom_id'] ?? null;
                if (! ctype_digit((string) $topId) || ! ctype_digit((string) $bottomId)
                    || (int) $topId <= 0 || (int) $bottomId <= 0) {
                    return redirect()->back()->with('error', 'ID produk pasangan Vegetable Tanggung tidak valid.');
                }
                $topId = (int) $topId;
                $bottomId = (int) $bottomId;
                if ($topId === $bottomId || ! in_array((string) $topId, array_map('strval', $tagIds), true)
                    || ! in_array((string) $bottomId, array_map('strval', $tagIds), true)) {
                    return redirect()->back()->with('error', 'Produk pasangan harus berbeda dan dipilih untuk dicetak.');
                }
                if (isset($pairedIds[$topId]) || isset($pairedIds[$bottomId])) {
                    return redirect()->back()->with('error', 'Produk tidak boleh digunakan pada lebih dari satu pasangan atau dicetak ulang.');
                }
                $qty = $pair['qty'] ?? null;
                if (! ctype_digit((string) $qty) || (int) $qty <= 0) {
                    return redirect()->back()->with('error', 'Qty pasangan Vegetable Tanggung harus berupa integer positif.');
                }
                $top = $productsById[$topId] ?? null;
                $bottom = $productsById[$bottomId] ?? null;
                if ($top === null || $bottom === null) {
                    return redirect()->back()->with('error', 'Produk pasangan tidak ditemukan atau tidak dapat diakses.');
                }
                if (($sizes[$topId] ?? '') !== 'vegetable_tgg' || ($sizes[$bottomId] ?? '') !== 'vegetable_tgg'
                    || ($top['template_size'] ?? '') !== 'vegetable_tgg' || ($bottom['template_size'] ?? '') !== 'vegetable_tgg') {
                    return redirect()->back()->with('error', 'Kedua produk pasangan harus menggunakan template Vegetable Tanggung.');
                }
                foreach ([$top, $bottom] as $product) {
                    if (! is_numeric($product['normal_price'] ?? null) || (float) $product['normal_price'] <= 0) {
                        return redirect()->back()->with('error', 'Produk pasangan memerlukan Harga Normal yang valid.');
                    }
                }
                $mapProduct = static fn (array $product): array => [
                    'name' => (string) ($product['name'] ?? ''),
                    'sku_plu' => (string) ($product['sku_plu'] ?? ''),
                    'price' => (int) $product['normal_price'],
                    'unit' => '/100GR',
                ];
                $pairItemsByTopId[$topId] = [
                    'type' => 'pair',
                    'top' => $mapProduct($top),
                    'bottom' => $mapProduct($bottom),
                    'qty' => (int) $qty,
                ];
                $pairedIds[$topId] = true;
                $pairedIds[$bottomId] = true;
            }
        }
        foreach ($tagIds as $tagId) {
            if (isset($pairItemsByTopId[(int) $tagId])) {
                // tag_ids[] mempertahankan urutan Preview. Masukkan pasangan
                // tepat pada posisi top_id, lalu bottom_id dilewati di bawah.
                $groups['vegetable_tgg'][] = $pairItemsByTopId[(int) $tagId];
                continue;
            }
            if (isset($pairedIds[(int) $tagId])) {
                continue;
            }
            $tag = $productsById[(int) $tagId] ?? null;
            if ($isSmallTriangle) {
                if ($tag === null) {
                    return $this->rejectSmallTriangle("Produk ID {$tagId} tidak ditemukan pada riwayat yang dipilih.");
                }
                if (($tag['template_size'] ?? null) !== 'segitiga_kecil' || ($sizes[$tagId] ?? null) !== 'segitiga_kecil') {
                    return $this->rejectSmallTriangle('Ukuran produk harus Segitiga Kecil dan sesuai dengan permintaan.', $tag);
                }
                if ((int) ($tag['discount_percent'] ?? 0) === 0 && (float) ($tag['promo_price'] ?? 0) <= 0) {
                    return $this->rejectSmallTriangle(
                        'Segitiga Kecil saat ini hanya mendukung produk dengan diskon 1–99%.',
                        $tag,
                        true
                    );
                }
                $qty = $this->positiveInteger($qtyMap[$tagId] ?? null);
                if ($qty === null) {
                    return $this->rejectSmallTriangle('Qty Cetak harus berupa bilangan bulat positif.', $tag);
                }
                try {
                    $hasPercentagePromo = (int) ($tag['discount_percent'] ?? 0) > 0;
                    $hasNominalPromo = (float) ($tag['promo_price'] ?? 0) > 0;
                    $useDiscountRenderer = $hasPercentagePromo;
                    $rendererKey = $useDiscountRenderer ? 'segitiga_kecil_diskon' : 'segitiga_kecil_turun_harga';
                    $product = $useDiscountRenderer ? $this->smallTriangleProduct($tag) : $this->smallTriangleTurunHargaProduct($tag);
                    if (!$hasPercentagePromo && !$hasNominalPromo) {
                        throw new InvalidArgumentException('Segitiga Kecil memerlukan Program Promo persentase atau Harga Promo nominal yang valid.');
                    }
                    $smallTriangleOrdered[] = ['renderer' => $rendererKey, 'row' => $product, 'qty' => $qty];
                } catch (InvalidArgumentException $e) {
                    return $this->rejectSmallTriangle($e->getMessage(), $tag);
                }
                continue;
            }
            if ($tag === null) {
                continue;
            }
            $size = (string) ($sizes[$tagId] ?? '');
            if (! in_array($size, ['mpdf', 'pricetag', 'a5', 'fresh', 'curah', 'segitiga', 'vegetable_tgg'], true)) {
                return redirect()->back()->with('error', 'Pastikan produk sudah memilih ukuran template.');
            }

            // Vegetable Tanggung adalah template non-promo: selalu gunakan
            // normal_price untuk menentukan harga yang dirender.
            if ($size === 'vegetable_tgg') {
                $normalPrice = $tag['normal_price'] ?? null;
                if (! is_numeric($normalPrice) || (float) $normalPrice <= 0) {
                    log_message('debug', '[print:{id}] vegetable_tgg rejected invalid normal_price tag_id={tagId}', [
                        'id' => $requestId,
                        'tagId' => (int) $tagId,
                    ]);
                    return redirect()->back()->with('error', 'Vegetable Tanggung memerlukan Harga Normal yang valid.');
                }

                // PLU tetap string agar nol di depan tidak hilang. Field price
                // sengaja dipetakan dari normal_price, bukan promo_price.
                $tag['sku_plu'] = (string) ($tag['sku_plu'] ?? '');
                $tag['price'] = (int) $normalPrice;
                $groups['vegetable_tgg'][] = [
                    'row' => $tag,
                    'qty' => max(1, (int) ($qtyMap[$tagId] ?? 1)),
                ];
                continue;
            }

            if ($size === 'a5' && (float) ($tag['promo_price'] ?? 0) <= 0 && (float) ($tag['discount_percent'] ?? 0) <= 0) {
                return redirect()->back()->with('error', 'A5 Discount/Turun Harga memerlukan Diskon atau Harga Promo.');
            }
            if ($size === 'curah' && (float) ($tag['promo_price'] ?? 0) <= 0 && (float) ($tag['discount_percent'] ?? 0) <= 0) {
                return redirect()->back()->with('error', 'Template Vegetable memerlukan Diskon atau Harga Promo.');
            }
            // Saat memakai mode Turun Harga, pastikan template menerima harga
            // hasil perhitungan meskipun promo_price belum tersimpan di database.
            // Untuk A4, nilai diskon persen memakai template disc-a4 secara
            // otomatis; hanya harga promo nominal yang memakai Turun Harga.
            $mode = (string) ($discountMode[$tagId] ?? 'auto');
            $showDiscount = $this->showDiscount($tag, $mode);
            // Harga turun hanya memakai hasil hitung untuk diskon < 10%.
            // Mode tampil diskon dan diskon >= 10% tetap memakai data lama.
            if ($size !== 'mpdf' && in_array($size, ['a5', 'segitiga'], true) && ! $showDiscount && (float) ($tag['discount_percent'] ?? 0) > 0) {
                $tag['promo_price'] = $this->calculatePromoPrice($tag);
            }
            $template = match ($size) {
                'pricetag' => 'pricetag',
                'segitiga' => ($showDiscount ? 'diskon' : ((float) ($tag['promo_price'] ?? 0) > 0 ? 'segitiga' : 'special')),
                'a5' => ((float) ($tag['discount_percent'] ?? 0) > 0
                    ? ($showDiscount ? ((float) ($tag['normal_price'] ?? 0) > 0 ? 'disc2' : 'disc') : 'a5')
                    : 'a5'),
                'fresh' => 'fresh',
                'curah' => 'curah',
                // A4 memakai desain allocation hanya jika nilai alokasi
                // benar-benar lebih besar dari nol.
                default => (float) ($tag['discount_percent'] ?? 0) > 0
                    ? ((int) ($tag['allocation_pcs'] ?? 0) > 0 ? 'disc-a4-allocation' : 'disc-a4')
                    : ((int) ($tag['allocation_pcs'] ?? 0) > 0 ? 'a4-allocation' : 'a4'),
            };
            $groups[$template][] = [
                'row' => $tag,
                'qty' => max(1, (int) ($qtyMap[$tagId] ?? 1)),
            ];
        }
        if ($groups === []) {
            if ($smallTriangleOrdered !== []) {
                $groups['segitiga_kecil_ordered'] = $smallTriangleOrdered;
            }
        }
        if ($groups === []) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        try {
            $contents = (new PopA4Pdf($isSmallTriangle ? 'segitiga_kecil_diskon' : 'a4'))->renderMixed($groups);
            log_message('debug', '[print:{id}] mPDF rendered groups={groups} bytes={bytes}', ['id' => $requestId, 'groups' => count($groups), 'bytes' => strlen($contents)]);
            $token = bin2hex(random_bytes(16));
            $dir = WRITEPATH . 'pricetag_downloads/';
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Folder penyimpanan PDF tidak dapat dibuat.');
            }
            if (file_put_contents($dir . $token . '.pdf', $contents, LOCK_EX) === false
                || file_put_contents($dir . $token . '.json', json_encode([
                    'user_id' => (int) session()->get('id'),
                    'expires_at' => time() + 3600,
                ]), LOCK_EX) === false) {
                throw new \RuntimeException('PDF hasil cetak tidak dapat disimpan.');
            }
            if (! is_file($dir . $token . '.pdf') || filesize($dir . $token . '.pdf') <= 0) {
                throw new \RuntimeException('PDF final tidak valid setelah penyimpanan.');
            }

            return redirect()->to(site_url('print-mpdf/' . $token));
        } catch (\Throwable $e) {
            log_message('error', '[print:{id}] PrintMpdf failed: {message} at {file}:{line}', ['id' => $requestId, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return redirect()->back()->with('error', 'Gagal membuat PDF. Silakan periksa pilihan template dan coba lagi.');
        }
    }

    private function positiveInteger(mixed $input): ?int
    {
        if ((!is_int($input) && !is_string($input)) || !ctype_digit((string) $input)) {
            return null;
        }
        $value = filter_var(ltrim((string) $input, '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $value === false ? null : $value;
    }

    private function smallTriangleProduct(array $tag): array
    {
        $normal = $this->positiveInteger($tag['normal_price'] ?? null);
        if ($normal === null) {
            throw new InvalidArgumentException('Harga Normal harus berupa bilangan bulat positif.');
        }
        $plu = (string) ($tag['sku_plu'] ?? '');
        if (!ctype_digit($plu)) {
            throw new InvalidArgumentException('PLU harus berisi angka.');
        }
        $brand = trim((string) ($tag['name'] ?? ''));
        $variant = trim((string) ($tag['variant'] ?? ''));
        if (preg_match('/\A\s*\z/u', $variant) === 1) {
            $variant = '';
        }
        if ($brand === '' || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $brand . $variant) !== 0
            || trim(str_replace("\u{00A0}", ' ', $brand)) === '') {
            throw new InvalidArgumentException('Nama produk wajib diisi dan nama serta varian harus berupa teks satu baris.');
        }
        $end = (string) ($tag['end_period'] ?? '');
        $date = preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $end)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $end)
            : false;
        if ($date === false || $date->format('Y-m-d') !== $end || (int) $date->format('Y') < 1) {
            throw new InvalidArgumentException('Akhir Periode harus berupa tanggal yang valid.');
        }
        $months = [1 => 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $discount = (int) $tag['discount_percent']; // Already validated by the global print guard.

        return [
            'brand' => $brand,
            'variant' => $variant,
            'plu' => $plu,
            'normal' => $normal,
            'promo' => (int) round($normal * (1 - $discount / 100)),
            'discount' => $discount,
            'period' => $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y'),
            'allocation' => $tag['allocation_pcs'] ?? null,
        ];
    }

    private function smallTriangleTurunHargaProduct(array $tag): array
    {
        $normal = $this->positiveInteger($tag['normal_price'] ?? null);
        if ($normal === null) throw new InvalidArgumentException('Harga Normal harus berupa bilangan bulat positif.');
        $plu = (string) ($tag['sku_plu'] ?? '');
        if (!ctype_digit($plu)) throw new InvalidArgumentException('PLU harus berisi angka.');
        $brand = trim((string) ($tag['name'] ?? ''));
        $variant = trim((string) ($tag['variant'] ?? ''));
        if ($brand === '' || preg_match('/[\p{C}\p{Zl}\p{Zp}]/u', $brand . $variant) !== 0
            || trim(str_replace("\u{00A0}", ' ', $brand)) === '') {
            throw new InvalidArgumentException('Nama produk wajib diisi dan nama serta varian harus berupa teks satu baris.');
        }
        $end = (string) ($tag['end_period'] ?? '');
        $date = preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $end)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $end) : false;
        if ($date === false || $date->format('Y-m-d') !== $end || (int) $date->format('Y') < 1) {
            throw new InvalidArgumentException('Akhir Periode harus berupa tanggal yang valid.');
        }
        $promo = $this->calculatePromoPrice($tag);
        if ($promo === null) throw new InvalidArgumentException('Segitiga Kecil Turun Harga memerlukan Harga Promo atau Diskon yang valid.');
        $months = [1 => 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        return ['brand'=>$brand,'variant'=>$variant,'sku_plu'=>$plu,'normal_price'=>$normal,'promo_price'=>$promo,'end_period'=>$date->format('j').' '.$months[(int) $date->format('n')].' '.$date->format('Y'),'allocation_pcs'=>$tag['allocation_pcs'] ?? null];
    }

    private function rejectSmallTriangle(string $message, ?array $tag = null, bool $discountRequired = false)
    {
        $products = $tag === null ? [] : [[
            'id' => (int) $tag['id'],
            'plu' => (string) $tag['sku_plu'],
            'name' => (string) $tag['name'],
            'discount_percent' => $tag['discount_percent'],
            'reason' => $message,
        ]];
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'code' => $discountRequired ? 'invalid_print_discount' : 'invalid_small_triangle_print',
                'message' => $message,
                'products' => $products,
                'guidance' => 'Periksa data produk dan pilihan cetak sebelum mencoba kembali.',
            ]);
        }

        if ($tag !== null) {
            $message .= ' PLU ' . $tag['sku_plu'] . ' | ' . $tag['name'] . '.';
        }
        return redirect()->back()->with('error', $message);
    }

    public function download(string $token)
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return $this->response->setStatusCode(404);
        }

        $dir = WRITEPATH . 'pricetag_downloads/';
        $metadataPath = $dir . $token . '.json';
        $pdfPath = $dir . $token . '.pdf';
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
            ->setHeader('Content-Disposition', 'inline; filename="price-tag-mpdf-a4.pdf"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody((string) file_get_contents($pdfPath));
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
