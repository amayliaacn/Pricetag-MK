<?php

namespace App\Controllers;

use App\Models\PriceTagModel;
use App\Models\ImportHistoryModel;
use App\Libraries\PriceTagTemplates;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PriceTag extends BaseController
{
    public function index()
    {
        $model  = new PriceTagModel();
        $userId = (int) session()->get('id');
        $importId = $this->request->getGet('import');

        // Halaman daftar lama dinonaktifkan. Detail hanya boleh dibuka
        // melalui ID import dari halaman History Import.
        if ($importId === null || ! ctype_digit((string) $importId) || (int) $importId < 1) {
            return redirect()->to('/import-history');
        }

        if ($importId !== null) {
            $history = (new ImportHistoryModel())->findVisibleImport(
                (int) $importId,
                (string) session()->get('role'),
                session()->get('outlet_id') === null ? null : (int) session()->get('outlet_id')
            );

            if (! $history) {
                return redirect()->to('/import-history')->with('error', 'Data impor tidak ditemukan.');
            }

            // Gunakan baris asli agar setiap produk memiliki ID dan dapat diedit.
            $tags = $model->forImport((int) $importId);
            if (empty($tags)) {
                $tags = json_decode($history['snapshot'] ?? '[]', true) ?: [];
            }

            $snapshotRows = json_decode($history['snapshot'] ?? '[]', true) ?: [];
            $snapshotSizes = [];
            foreach ($snapshotRows as $snapshotRow) {
                if (! empty($snapshotRow['sku_plu']) && ! empty($snapshotRow['template_size'])) {
                    $snapshotSizes[(string) $snapshotRow['sku_plu']] = $snapshotRow['template_size'];
                }
            }
            foreach ($tags as &$tag) {
                if (empty($tag['template_size']) && isset($snapshotSizes[(string) $tag['sku_plu']])) {
                    $tag['template_size'] = $snapshotSizes[(string) $tag['sku_plu']];
                }
            }
            unset($tag);
        } else {
            $history = null;
            $tags = $model->forUserAndDate($userId, date('Y-m-d'));
        }

        $data = [
            'title'     => $history ? 'Detail Import - ' . $history['file_name'] : 'Data Price Tag',
            'tags'      => $tags,
            'history'   => $history,
            'templates' => PriceTagTemplates::all(),
        ];

        return view('pricetag/index', $data);
    }

    public function import()
    {
        $returnToHistory = $this->request->getPost('return_to') === 'import-history';
        $redirectPath = '/import-history';
        $outletId = session()->get('outlet_id');

        if (session()->get('role') === 'super_admin' && $this->request->getPost('outlet_id')) {
            $selectedOutlet = (new \App\Models\OutletModel())->findById((int) $this->request->getPost('outlet_id'));
            if ($selectedOutlet && (int) ($selectedOutlet['is_active'] ?? 1) === 1) {
                $outletId = (int) $selectedOutlet['id'];
            } else {
                return redirect()->to($redirectPath)->with('error', 'Pilih outlet aktif yang valid untuk impor.');
            }
        }

        if ($returnToHistory && session()->get('role') === 'super_admin' && $outletId === null) {
            return redirect()->to($redirectPath)->with('error', 'Pilih outlet tujuan sebelum mengimpor file.');
        }

        $file = $this->request->getFile('file_excel');

        if ($file && $file->isValid() && !$file->hasMoved()) {

            $originalName = $file->getClientName();
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);
            $filePath = WRITEPATH . 'uploads/' . $newName;

            $reader = IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            $headerMap = $this->findHeaderMap($sheetData);
            if ($headerMap === null) {
                unlink($filePath);

                return redirect()->to($redirectPath)->with(
                    'error',
                    'Header Excel tidak ditemukan. Minimal gunakan kolom SKU/PLU, Nama Produk, dan Harga Normal.'
                );
            }

            $model      = new PriceTagModel();
            $userId     = (int) session()->get('id');
            $importDate = date('Y-m-d');
            $jumlahData = 0;
            $importRows = [];

            // CATATAN: tidak lagi truncate() semua data. Sekarang tiap baris
            // di-upsert per (user, tanggal hari ini, sku_plu):
            //   - SKU sama, tanggal sama  -> data lama di baris itu di-UPDATE
            //   - SKU baru di tanggal ini -> jadi baris baru
            //   - Data tanggal SEBELUMNYA tidak disentuh sama sekali (riwayat aman)
            foreach (array_slice($sheetData, $headerMap['row'] + 1) as $row) {
                $sku = trim((string) ($row[$headerMap['sku_plu']] ?? ''));
                if ($sku === '') continue;

                // Kolom sumber pada file Excel berisi satu kalimat gabungan,
                // misalnya: PRODUK #VARIAN Sisa Alok= 60pc.
                $parsedProduct = $this->parseProductText(
                    (string) ($row[$headerMap['name']] ?? '')
                );
                $promotionSource = $this->valueFromRow($row, $headerMap, 'discount_percent')
                    ?? $this->valueFromRow($row, $headerMap, 'promo_price');
                $hasPromotionSource = $promotionSource !== null;
                $parsedPromotion = $this->parsePromotionText($promotionSource ?? '');

                $dataInsert = [
                    'sku_plu'          => $sku,
                    'name'             => $parsedProduct['name'],
                    'variant'          => $parsedProduct['variant']
                        ?? $this->valueFromRow($row, $headerMap, 'variant'),
                    'template_size'   => null,
                    'normal_price'     => $this->priceFromRow($row, $headerMap, 'normal_price'),
                    'discount_percent' => $hasPromotionSource
                        ? $parsedPromotion['discount_percent']
                        : $this->percentFromRow($row, $headerMap, 'discount_percent'),
                    'promo_price'      => $hasPromotionSource
                        ? $parsedPromotion['promo_price']
                        : $this->priceFromRow($row, $headerMap, 'promo_price'),
                    'allocation_pcs'   => $parsedProduct['allocation_pcs']
                        ?? $this->intFromRow($row, $headerMap, 'allocation_pcs'),
                    'start_period'     => $this->dateFromRow($row, $headerMap, 'start_period'),
                    'end_period'       => $this->dateFromRow($row, $headerMap, 'end_period'),
                    'uploaded_by'      => $userId,
                    'import_date'      => $importDate,
                ];

                if ($dataInsert['name'] === '' || $dataInsert['normal_price'] === null) continue;

                $importRows[] = $dataInsert;
                $jumlahData++;
            }

            if ($jumlahData > 0 && $outletId !== null) {
                $historyModel = new ImportHistoryModel();
                $historyModel->insert([
                    'file_name'   => $originalName,
                    'snapshot'    => json_encode($importRows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'outlet_id'   => (int) $outletId,
                    'imported_at' => date('Y-m-d H:i:s'),
                    'imported_by' => $userId,
                ]);
                $importId = (int) $historyModel->getInsertID();

                foreach ($importRows as $row) {
                    $row['import_id'] = $importId;
                    $model->insert($row);
                }
            } else {
                foreach ($importRows as $row) {
                    $model->upsertRow($row);
                }
            }

            unlink($filePath);

            return redirect()->to($redirectPath)->with('success', "Berhasil memproses $jumlahData data untuk tanggal $importDate!");
        }

        return redirect()->to($redirectPath)->with('error', 'Gagal mengunggah file Excel.');
    }

    public function setPrinted(int $id)
    {
        if ($id <= 0) {
            return redirect()->back();
        }
        $model = new PriceTagModel();
        $tag = $model->find($id);
        if ($tag) {
            $model->update($id, ['is_printed' => $this->request->getPost('is_printed') ? 1 : 0]);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => (bool) $tag]);
        }
        return redirect()->back();
    }

    public function updateTemplate(int $id)
    {
        $size = (string) $this->request->getPost('template_size');
        if ($size !== '' && ! in_array($size, ['kcl', 'tgg'], true)) return redirect()->back()->with('error', 'Ukuran template tidak valid.');
        $model = new PriceTagModel();
        if (! $model->find($id)) return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        $savedSize = $size !== '' ? $size : null;
        $priceTagsTable = db_connect()->table('price_tags');
        if (! $priceTagsTable->where('id', $id)->update(['template_size' => $savedSize])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Ukuran template gagal disimpan ke database.']);
            }
            return redirect()->back()->with('error', 'Ukuran template gagal disimpan ke database.');
        }

        $savedTag = $priceTagsTable->where('id', $id)->get()->getRowArray();
        if (($savedTag['template_size'] ?? null) !== $savedSize) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Ukuran template belum tersimpan.']);
            }
            return redirect()->back()->with('error', 'Ukuran template belum tersimpan.');
        }

        // Sinkronkan snapshot import juga. Detail import biasanya membaca
        // price_tags, tetapi snapshot menjadi fallback untuk import lama.
        $snapshotImportId = (int) ($savedTag['import_id'] ?? 0);
        if ($snapshotImportId <= 0) {
            $snapshotImportId = (int) $this->request->getGet('import_id');
        }
        if ($snapshotImportId > 0) {
            $historyTable = db_connect()->table('import_history');
            $history = $historyTable->where('id', $snapshotImportId)->get()->getRowArray();
            if ($history && ! empty($history['snapshot'])) {
                $snapshot = json_decode($history['snapshot'], true);
                if (is_array($snapshot)) {
                    foreach ($snapshot as &$snapshotRow) {
                        if ((string) ($snapshotRow['sku_plu'] ?? '') === (string) $savedTag['sku_plu']) {
                            $snapshotRow['template_size'] = $savedSize;
                        }
                    }
                    unset($snapshotRow);
                    $historyTable->where('id', $snapshotImportId)->update([
                        'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'template_size' => $savedSize, 'message' => 'Ukuran template produk berhasil disimpan.']);
        }
        return redirect()->back()->with('success', 'Ukuran template produk berhasil disimpan.');
    }

    public function update(int $id)
    {
        $model = new PriceTagModel();
        $tag = $model->find($id);
        if (! $tag) return redirect()->back()->with('error', 'Produk tidak ditemukan.');

        $data = [
            'sku_plu' => trim((string) $this->request->getPost('sku_plu')),
            'name' => trim((string) $this->request->getPost('name')),
            'variant' => trim((string) $this->request->getPost('variant')) ?: null,
            'normal_price' => (int) $this->request->getPost('normal_price'),
            'discount_percent' => $this->request->getPost('discount_percent') === '' ? null : (float) $this->request->getPost('discount_percent'),
            'promo_price' => $this->request->getPost('promo_price') === '' ? null : (int) $this->request->getPost('promo_price'),
            'allocation_pcs' => $this->request->getPost('allocation_pcs') === '' ? null : (int) $this->request->getPost('allocation_pcs'),
            'start_period' => $this->request->getPost('start_period') ?: null,
            'end_period' => $this->request->getPost('end_period') ?: null,
        ];
        if ($data['sku_plu'] === '' || $data['name'] === '') {
            return redirect()->back()->with('error', 'PLU dan nama produk wajib diisi.');
        }
        $model->update($id, $data);
        return redirect()->back()->with('success', 'Data produk berhasil diperbarui.');
    }

    public function create()
    {
        $sku = trim((string) $this->request->getPost('sku_plu'));
        $name = trim((string) $this->request->getPost('name'));
        $importId = (int) $this->request->getPost('import_id');
        if ($sku === '' || $name === '') return redirect()->back()->with('error', 'PLU dan nama produk wajib diisi.');

        $data = [
            'sku_plu' => $sku,
            'name' => $name,
            'variant' => trim((string) $this->request->getPost('variant')) ?: null,
            'normal_price' => (int) $this->request->getPost('normal_price'),
            'discount_percent' => $this->request->getPost('discount_percent') === '' ? null : (float) $this->request->getPost('discount_percent'),
            'promo_price' => $this->request->getPost('promo_price') === '' ? null : (int) $this->request->getPost('promo_price'),
            'allocation_pcs' => $this->request->getPost('allocation_pcs') === '' ? null : (int) $this->request->getPost('allocation_pcs'),
            'start_period' => $this->request->getPost('start_period') ?: null,
            'end_period' => $this->request->getPost('end_period') ?: null,
            'uploaded_by' => (int) session()->get('id'),
            'import_date' => date('Y-m-d'),
            'import_id' => $importId > 0 ? $importId : null,
            'template_size' => null,
            'is_printed' => 0,
        ];
        (new PriceTagModel())->insert($data);
        return redirect()->back()->with('success', 'Produk berhasil ditambahkan.');
    }

    private function findHeaderMap(array $sheetData): ?array
    {
        $aliases = [
            'sku_plu'          => ['sku', 'plu', 'sku plu', 'sku/plu', 'kode barang', 'kode produk', 'product code'],
            'name'             => [
                'nama produk', 'nama barang', 'nama', 'product name', 'nama product',
                'brand', 'merk', 'merek', 'brand merk', 'brand / merk',
            ],
            'variant'          => ['varian', 'variant', 'rasa', 'ukuran', 'variant deskripsi'],
            'normal_price'     => ['harga normal', 'harga jual', 'harga', 'normal price', 'selling price', 'price', 'harga normal rp'],
            'discount_percent' => [
                'diskon', 'diskon persen', 'discount', 'diskon %',
                'diskon harga promo rp', 'diskon harga promo',
            ],
            'promo_price'      => ['harga promo', 'promo price', 'harga diskon', 'harga sale', 'sale price', 'harga promo rp'],
            'allocation_pcs'   => ['alokasi', 'alokasi pcs', 'allocation', 'stok', 'qty'],
            'start_period'     => ['awal periode', 'periode mulai', 'start period', 'tanggal mulai'],
            'end_period'       => ['akhir periode', 'periode selesai', 'end period', 'tanggal selesai'],
        ];

        foreach (array_slice($sheetData, 0, 20, true) as $rowIndex => $row) {
            $map = ['row' => $rowIndex];
            foreach ($row as $columnIndex => $header) {
                $normalized = $this->normalizeHeader((string) $header);
                foreach ($aliases as $field => $names) {
                    if (in_array($normalized, array_map([$this, 'normalizeHeader'], $names), true)) {
                        $map[$field] = $columnIndex;
                    }
                }
            }

            if (isset($map['sku_plu'], $map['name'], $map['normal_price'])) {
                return $map;
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * Memecah teks produk dari Excel menjadi nama, varian, dan alokasi.
     * Contoh: "PRODUK #VARIAN Sisa Alok= 60pc".
     */
    private function parseProductText(string $text): array
    {
        $text = trim($text);
        $result = [
            'name'           => $text,
            'variant'        => null,
            'allocation_pcs' => null,
        ];

        // Format utama kolom gabungan Excel:
        // "PRODUK # VARIAN Sisa Alok 90Q = 200pc"
        // Bagian setelah kata Alok sebelum '=' (contoh: "90Q") adalah
        // keterangan alokasi, bukan bagian dari varian.
        if (preg_match(
            '/^\s*(?<produk>[^#]+?)\s*#\s*(?<varian>.*?)\s+'
            . '(?:Sisa\s+)?Alok\b[^=\r\n]*=\s*(?<alokasi>\d+)/iu',
            $text,
            $match
        ) === 1) {
            $result['name'] = trim($match['produk']);
            $result['variant'] = trim($match['varian']) !== ''
                ? trim($match['varian'])
                : null;
            $result['allocation_pcs'] = (int) $match['alokasi'];

            return $result;
        }

        $name = $text;
        $variantText = '';

        if (str_contains($text, '#')) {
            [$name, $variantText] = explode('#', $text, 2);
            $variantText = trim($variantText);
        }

        // Alokasi dapat berada setelah variant atau langsung setelah nama produk.
        $allocationText = $variantText !== '' ? $variantText : $name;
        if (preg_match(
            '/\b(?:sisa\s+)?alok(?:\s+[^=]+)?\s*=\s*(\d+)\s*pcs?\b/iu',
            $allocationText,
            $allocation
        )) {
            $result['allocation_pcs'] = (int) $allocation[1];
            $allocationText = preg_replace(
                '/\b(?:sisa\s+)?alok(?:\s+[^=]+)?\s*=\s*\d+\s*pcs?\b/iu',
                '',
                $allocationText
            ) ?? $allocationText;

            if ($variantText !== '') {
                $variantText = $allocationText;
            } else {
                $name = $allocationText;
            }
        }

        $result['name'] = trim($name);
        $result['variant'] = trim($variantText) !== ''
            ? trim($variantText)
            : null;

        return $result;
    }

    /**
     * Memisahkan isi kolom gabungan Diskon / Harga Promo.
     * Contoh: "Set 9%" menjadi diskon, sedangkan "26.288" menjadi harga promo.
     */
    private function parsePromotionText(string $text): array
    {
        $text = trim($text);
        $result = [
            'discount_percent' => null,
            'promo_price'      => null,
        ];

        if ($text === '' || $text === '-') {
            return $result;
        }

        if (str_contains($text, '%')) {
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/u', $text, $discount)) {
                $result['discount_percent'] = (float) str_replace(',', '.', $discount[1]);
            }

            return $result;
        }

        $digits = preg_replace('/[^0-9]/', '', $text);
        if ($digits !== '') {
            $result['promo_price'] = (int) $digits;
        }

        return $result;
    }

    private function valueFromRow(array $row, array $headerMap, string $field): ?string
    {
        if (!isset($headerMap[$field])) return null;

        $value = trim((string) ($row[$headerMap[$field]] ?? ''));

        return $value === '' ? null : $value;
    }

    private function priceFromRow(array $row, array $headerMap, string $field): ?int
    {
        if (!isset($headerMap[$field])) return null;

        $value = trim((string) ($row[$headerMap[$field]] ?? ''));
        if ($value === '') return null;

        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    private function intFromRow(array $row, array $headerMap, string $field): ?int
    {
        if (!isset($headerMap[$field])) return null;

        $value = trim((string) ($row[$headerMap[$field]] ?? ''));
        if ($value === '' || $value === '-') return null;

        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    private function percentFromRow(array $row, array $headerMap, string $field): ?int
    {
        if (!isset($headerMap[$field])) return null;

        $value = trim((string) ($row[$headerMap[$field]] ?? ''));
        if ($value === '' || $value === '-') return null;

        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Ambil tanggal dari cell Excel. Menangani baik format cell tanggal (numeric serial)
     * maupun teks biasa seperti "15 Sep 2026" atau "18 Okt 2026" (nama bulan Indonesia).
     */
    private function dateFromRow(array $row, array $headerMap, string $field): ?string
    {
        if (!isset($headerMap[$field])) return null;

        $raw = $row[$headerMap[$field]] ?? '';
        if ($raw === '' || $raw === null) return null;

        if (is_numeric($raw)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $raw);
                return $date->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $text = trim((string) $raw);
        $text = $this->translateIndonesianMonth($text);

        // Tangani format custom seperti 15/September/2026 atau 15 September 2026.
        // Jangan hanya mengandalkan strtotime(), karena hasilnya tidak konsisten
        // untuk nama bulan lengkap yang dipisahkan dengan slash.
        if (preg_match('/^(\d{1,2})\s*[\/-]\s*([A-Za-z]+)\s*[\/-]\s*(\d{4})$/', $text, $matches)) {
            $text = sprintf('%02d %s %04d', $matches[1], $matches[2], $matches[3]);
        }

        foreach (['!d F Y', '!d M Y', '!d-m-Y', '!d/m/Y', '!Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat($format, $text);
            $errors = \DateTime::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($text);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * Ganti nama/singkatan bulan Indonesia jadi Inggris supaya strtotime() bisa memahaminya.
     * Contoh: "18 Okt 2026" -> "18 Oct 2026"
     */
    private function translateIndonesianMonth(string $value): string
    {
        $months = [
            'januari' => 'January', 'jan' => 'Jan',
            'februari' => 'February', 'feb' => 'Feb',
            'maret' => 'March', 'mar' => 'Mar',
            'april' => 'April', 'apr' => 'Apr',
            'mei' => 'May',
            'juni' => 'June', 'jun' => 'Jun',
            'juli' => 'July', 'jul' => 'Jul',
            'agustus' => 'August', 'agu' => 'Aug', 'ags' => 'Aug',
            'september' => 'September', 'sep' => 'Sep', 'sept' => 'Sep',
            'oktober' => 'October', 'okt' => 'Oct',
            'november' => 'November', 'nov' => 'Nov',
            'desember' => 'December', 'des' => 'Dec',
        ];

        return preg_replace_callback('/[a-zA-Z]+/', function ($match) use ($months) {
            $lower = strtolower($match[0]);
            return $months[$lower] ?? $match[0];
        }, $value);
    }
}
