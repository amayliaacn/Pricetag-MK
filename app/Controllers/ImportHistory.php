<?php

namespace App\Controllers;

use App\Models\ImportHistoryModel;
use App\Models\OutletModel;
use App\Libraries\ProductTextParser;
use App\Models\PriceTagModel;

class ImportHistory extends BaseController
{
    public function index()
    {
        $role = (string) session()->get('role');
        $outletId = session()->get('outlet_id');
        $mode = $this->request->getGet('mode') === 'manual' ? 'manual' : 'excel';
        $sourceType = $mode === 'manual' ? 1 : 0;
        $filters = [
            'outlet_id' => $this->request->getGet('outlet_id'),
            'month'     => $this->request->getGet('month'),
            'year'      => $this->request->getGet('year'),
            'source_type' => $sourceType,
        ];

        $yearQuery = db_connect()->table('import_history')
                                 ->select('YEAR(imported_at) AS year', false);
        $yearQuery->where('source_type', $sourceType);

        if ($role !== 'super_admin') {
            $yearQuery->where('outlet_id', (int) $outletId);
        }

        $years = $yearQuery->groupBy('YEAR(imported_at)', false)
                           ->orderBy('year', 'DESC')
                           ->get()->getResultArray();

        return view('import_history/index', [
            'title'   => 'History Import',
            'history' => (new ImportHistoryModel())->filteredHistory(
                $role,
                $outletId === null ? null : (int) $outletId,
                $filters
            ),
            'outlets' => $role === 'super_admin' ? (new OutletModel())->orderBy('code')->findAll() : [],
            'activeOutlets' => $role === 'super_admin' ? (new OutletModel())->activeOutlets() : [],
            'years'   => array_column($years, 'year'),
            'filters' => $filters,
            'mode' => $mode,
        ]);
    }

    public function delete(int $id)
    {
        $role = (string) session()->get('role');
        $outletId = session()->get('outlet_id');
        $historyModel = new ImportHistoryModel();
        $history = $historyModel->findVisibleImport(
            $id,
            $role,
            $outletId === null ? null : (int) $outletId
        );

        if (! $history) {
            return redirect()->to(base_url('import-history'))->with('error', 'Data import tidak ditemukan atau tidak dapat dihapus.');
        }

        $db = db_connect();
        $db->transStart();
        $db->table('price_tags')->where('import_id', $id)->delete();
        $historyModel->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(base_url('import-history'))->with('error', 'Data import gagal dihapus.');
        }

        return redirect()->to(base_url('import-history'))->with('success', 'Data file import dan produk terkait berhasil dihapus.');
    }

    public function createManual()
    {
        $role = (string) session()->get('role');
        $outletId = (int) $this->request->getPost('outlet_id');
        if ($role !== 'super_admin') {
            $outletId = (int) session()->get('outlet_id');
        }

        $fileName = trim((string) $this->request->getPost('file_name'));
        if ($fileName === '' || $outletId <= 0) {
            return redirect()->to(base_url('import-history?mode=manual'))->with('error', 'Nama file dan outlet wajib diisi.');
        }

        $historyModel = new ImportHistoryModel();
        $historyModel->insert([
            'file_name'   => $fileName,
            'source_type' => 1,
            'snapshot'    => json_encode([], JSON_UNESCAPED_UNICODE),
            'outlet_id'   => $outletId,
            'imported_at' => date('Y-m-d H:i:s'),
            'imported_by' => (int) session()->get('id'),
        ]);

        return redirect()->to(base_url('import-history?mode=manual'))->with('success', 'Data manual berhasil dibuat.');
    }

    public function manual(int $id)
    {
        $history = $this->visibleManual($id);
        if (! $history) {
            return redirect()->to(base_url('import-history?mode=manual'))->with('error', 'Data manual tidak ditemukan.');
        }

        $rows = json_decode($history['snapshot'] ?? '[]', true);
        return view('import_history/manual', [
            'title' => 'Isi Data Manual',
            'history' => $history,
            'rows' => is_array($rows) ? $rows : [],
        ]);
    }

    public function saveManual(int $id)
    {
        $history = $this->visibleManual($id);
        if (! $history) {
            return redirect()->to(base_url('import-history?mode=manual'))->with('error', 'Data manual tidak ditemukan.');
        }

        $rows = $this->request->getPost('rows');
        $rows = is_array($rows) ? array_values($rows) : [];
        $cleanRows = [];
        $parser = new ProductTextParser();
        foreach ($rows as $row) {
            if (! is_array($row)) continue;
            $brandText = trim((string) ($row['brand'] ?? ''));
            $parsedProduct = $parser->parse($brandText);
            $clean = [
                'start_period' => $this->normalizeManualDate($row['start_period'] ?? ''),
                'end_period'   => $this->normalizeManualDate($row['end_period'] ?? ''),
                'sku_plu'      => trim((string) ($row['sku_plu'] ?? '')),
                'brand'        => trim((string) ($row['brand'] ?? '')),
                'name'         => $parsedProduct['name'],
                'variant'      => $parsedProduct['variant'],
                'allocation_pcs' => $parsedProduct['allocation_pcs'],
                'normal_price' => (float) ($row['normal_price'] ?? 0),
                'promo'        => trim((string) ($row['promo'] ?? '')),
            ];
            if (implode('', [$clean['start_period'], $clean['end_period'], $clean['sku_plu'], $clean['brand'], $clean['promo']]) !== '' || $clean['normal_price'] > 0) {
                $cleanRows[] = $clean;
            }
        }

        (new ImportHistoryModel())->update($id, [
            'snapshot' => json_encode($cleanRows, JSON_UNESCAPED_UNICODE),
        ]);

        // Sinkronkan produk manual ke price_tags agar setiap produk memiliki
        // ID valid dan dapat dipilih untuk dicetak seperti produk Excel.
        $priceTagModel = new PriceTagModel();
        $existingTags = $priceTagModel->where('import_id', $id)->findAll();
        $templateBySku = [];
        foreach ($existingTags as $existingTag) {
            if (! empty($existingTag['sku_plu']) && ! empty($existingTag['template_size'])) {
                $templateBySku[(string) $existingTag['sku_plu']] = $existingTag['template_size'];
            }
        }
        $priceTagModel->where('import_id', $id)->delete();
        foreach ($cleanRows as $row) {
            $sku = $row['sku_plu'] !== '' ? $row['sku_plu'] : 'AUTO-MANUAL-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $promo = trim((string) ($row['promo'] ?? ''));
            $discount = null;
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/', $promo, $promoMatch)) {
                $discount = (float) str_replace(',', '.', $promoMatch[1]);
            }
            $promoPrice = null;
            if ($discount === null && preg_match('/\d/', $promo)) {
                // Nilai nominal seperti "11.900" atau "Rp 11.900"
                // disimpan sebagai harga promo, bukan diskon.
                $promoDigits = preg_replace('/[^0-9]/', '', $promo);
                $promoPrice = $promoDigits !== '' ? (int) $promoDigits : null;
            }
            $priceTagModel->insert([
                'sku_plu'          => $sku,
                'name'             => $row['name'],
                'variant'          => $row['variant'],
                'normal_price'     => (float) $row['normal_price'],
                'discount_percent' => $discount,
                'promo_price'      => $promoPrice,
                'allocation_pcs'   => $row['allocation_pcs'],
                'start_period'     => $row['start_period'] ?: null,
                'end_period'       => $row['end_period'] ?: null,
                'uploaded_by'      => (int) session()->get('id'),
                'import_date'      => date('Y-m-d'),
                'import_id'        => $id,
                'template_size'    => $templateBySku[(string) $sku] ?? null,
                'is_printed'       => 0,
            ]);
        }

        return redirect()->to(base_url('import-history/manual/' . $id))->with('success', 'Data manual berhasil disimpan.');
    }

    private function visibleManual(int $id): ?array
    {
        $role = (string) session()->get('role');
        $outletId = session()->get('outlet_id');
        $history = (new ImportHistoryModel())->findVisibleImport($id, $role, $outletId === null ? null : (int) $outletId);
        return $history && (int) ($history['source_type'] ?? 0) === 1 ? $history : null;
    }

    private function normalizeManualDate($value): string
    {
        $value = trim((string) $value);
        foreach (['Y-m-d', 'd-M-y', 'd-M-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
        }
        return '';
    }
}
