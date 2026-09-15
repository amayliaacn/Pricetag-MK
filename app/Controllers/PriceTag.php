<?php

namespace App\Controllers;

use App\Models\PriceTagModel;
use App\Libraries\PriceTagTemplates;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PriceTag extends BaseController
{
    public function index()
    {
        $model  = new PriceTagModel();
        $userId = (int) session()->get('id');

        $data = [
            'title'     => 'Data Price Tag',
            // Hanya tampilkan data milik user yang sedang login, untuk HARI INI.
            // Data hari-hari sebelumnya tetap tersimpan di database sebagai riwayat.
            'tags'      => $model->forUserAndDate($userId, date('Y-m-d')),
            'templates' => PriceTagTemplates::all(),
        ];

        return view('pricetag/index', $data);
    }

    public function import()
    {
        $file = $this->request->getFile('file_excel');

        if ($file && $file->isValid() && !$file->hasMoved()) {

            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);
            $filePath = WRITEPATH . 'uploads/' . $newName;

            $reader = IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            $headerMap = $this->findHeaderMap($sheetData);
            if ($headerMap === null) {
                unlink($filePath);

                return redirect()->to('/pricetag')->with(
                    'error',
                    'Header Excel tidak ditemukan. Minimal gunakan kolom SKU/PLU, Nama Produk, dan Harga Normal.'
                );
            }

            $model      = new PriceTagModel();
            $userId     = (int) session()->get('id');
            $importDate = date('Y-m-d');
            $jumlahData = 0;

            // CATATAN: tidak lagi truncate() semua data. Sekarang tiap baris
            // di-upsert per (user, tanggal hari ini, sku_plu):
            //   - SKU sama, tanggal sama  -> data lama di baris itu di-UPDATE
            //   - SKU baru di tanggal ini -> jadi baris baru
            //   - Data tanggal SEBELUMNYA tidak disentuh sama sekali (riwayat aman)
            foreach (array_slice($sheetData, $headerMap['row'] + 1) as $row) {
                $sku = trim((string) ($row[$headerMap['sku_plu']] ?? ''));
                if ($sku === '') continue;

                $dataInsert = [
                    'sku_plu'          => $sku,
                    'name'             => trim((string) ($row[$headerMap['name']] ?? '')),
                    'brand'            => $this->valueFromRow($row, $headerMap, 'brand'),
                    'variant'          => $this->valueFromRow($row, $headerMap, 'variant'),
                    'normal_price'     => $this->priceFromRow($row, $headerMap, 'normal_price'),
                    'discount_percent' => $this->percentFromRow($row, $headerMap, 'discount_percent'),
                    'promo_price'      => $this->priceFromRow($row, $headerMap, 'promo_price'),
                    'allocation_pcs'   => $this->intFromRow($row, $headerMap, 'allocation_pcs'),
                    'start_period'     => $this->dateFromRow($row, $headerMap, 'start_period'),
                    'end_period'       => $this->dateFromRow($row, $headerMap, 'end_period'),
                    'uploaded_by'      => $userId,
                    'import_date'      => $importDate,
                ];

                if ($dataInsert['name'] === '' || $dataInsert['normal_price'] === null) continue;

                $model->upsertRow($dataInsert);
                $jumlahData++;
            }

            unlink($filePath);

            return redirect()->to('/pricetag')->with('success', "Berhasil memproses $jumlahData data untuk tanggal $importDate!");
        }

        return redirect()->to('/pricetag')->with('error', 'Gagal mengunggah file Excel.');
    }

    private function findHeaderMap(array $sheetData): ?array
    {
        $aliases = [
            'sku_plu'          => ['sku', 'plu', 'sku plu', 'sku/plu', 'kode barang', 'kode produk', 'product code'],
            'name'             => ['nama produk', 'nama barang', 'nama', 'product name', 'nama product'],
            'brand'            => ['merk', 'merek', 'brand', 'brand merk'],
            'variant'          => ['varian', 'variant', 'rasa', 'ukuran', 'variant deskripsi'],
            'normal_price'     => ['harga normal', 'harga jual', 'harga', 'normal price', 'selling price', 'price', 'harga normal rp'],
            'discount_percent' => ['diskon', 'diskon persen', 'discount', 'diskon %'],
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

        $text = $this->translateIndonesianMonth((string) $raw);

        $timestamp = strtotime($text);
        if ($timestamp === false) return null;

        return date('Y-m-d', $timestamp);
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
