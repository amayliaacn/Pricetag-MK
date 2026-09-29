<?php

namespace App\Models;

/**
 * Data access khusus untuk jalur cetak mPDF. Kode cetak DOCX lama tidak disentuh.
 */
class MpdfPriceTagModel extends PriceTagModel
{
    public function findForPrint(int $userId, string $sku, ?int $importId = null): ?array
    {
        $query = $this->where('sku_plu', $sku);

        if ($importId !== null && $importId > 0) {
            $query->where('import_id', $importId);
        } else {
            $query->where('uploaded_by', $userId)
                  ->where('import_date', date('Y-m-d'));
        }

        return $query->first();
    }
}
