<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncManualSnapshotToPriceTags extends Migration
{
    public function up()
    {
        $histories = $this->db->table('import_history')->where('source_type', 1)->get()->getResultArray();
        $tags = $this->db->table('price_tags');
        foreach ($histories as $history) {
            if ($tags->where('import_id', (int) $history['id'])->countAllResults() > 0) continue;
            $rows = json_decode($history['snapshot'] ?? '[]', true) ?: [];
            foreach ($rows as $row) {
                $sku = trim((string) ($row['sku_plu'] ?? '')) ?: 'AUTO-MANUAL-' . $history['id'] . '-' . bin2hex(random_bytes(2));
                $promo = (string) ($row['promo'] ?? '');
                $discount = null;
                if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/', $promo, $match)) $discount = (float) str_replace(',', '.', $match[1]);
                $tags->insert([
                    'sku_plu' => $sku, 'name' => $row['name'] ?? ($row['brand'] ?? ''),
                    'variant' => $row['variant'] ?? null, 'normal_price' => (float) ($row['normal_price'] ?? 0),
                    'discount_percent' => $discount, 'promo_price' => null,
                    'allocation_pcs' => $row['allocation_pcs'] ?? null,
                    'start_period' => $row['start_period'] ?: null, 'end_period' => $row['end_period'] ?: null,
                    'uploaded_by' => (int) $history['imported_by'], 'import_date' => date('Y-m-d', strtotime($history['imported_at'])),
                    'import_id' => (int) $history['id'], 'template_size' => $row['template_size'] ?? null, 'is_printed' => 0,
                ]);
            }
        }
    }

    public function down() {}
}
