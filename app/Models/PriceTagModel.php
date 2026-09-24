<?php

namespace App\Models;

use CodeIgniter\Model;

class PriceTagModel extends Model
{
    protected $table            = 'price_tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'sku_plu',
        'name',
        'variant',
        'template_size',
        'normal_price',
        'discount_percent',
        'promo_price',
        'allocation_pcs',
        'start_period',
        'end_period',
        'uploaded_by',
        'import_date',
        'import_id',
        'is_printed',
    ];

    /**
     * Daftar price tag milik satu user, untuk satu tanggal import.
     * Default: hari ini.
     */
    public function forUserAndDate(int $userId, ?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');

        return $this->where('uploaded_by', $userId)
                    ->where('import_date', $date)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    public function forImport(int $importId): array
    {
        return $this->where('import_id', $importId)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * Ambil satu produk berdasarkan SKU, untuk user & tanggal tertentu.
     * Dipakai saat proses cetak, supaya tidak salah ambil data user/hari lain.
     */
    public function findBySkuForUser(int $userId, string $date, string $skuPlu): ?array
    {
        return $this->where('uploaded_by', $userId)
                    ->where('import_date', $date)
                    ->where('sku_plu', $skuPlu)
                    ->first();
    }

    /**
     * Simpan satu baris hasil import Excel dengan aturan:
     *  - kombinasi (uploaded_by, import_date, sku_plu) sudah ada -> UPDATE
     *  - belum ada -> INSERT
     *
     * Dipanggil satu per satu di dalam loop import (jumlah data price tag
     * per toko biasanya puluhan-ratusan baris, jadi cukup ringan).
     */
    public function upsertRow(array $data): void
    {
        $existing = $this->where('uploaded_by', $data['uploaded_by'])
                         ->where('import_date', $data['import_date'])
                         ->where('sku_plu', $data['sku_plu'])
                         ->first();

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    /**
     * Daftar tanggal-tanggal yang punya data untuk satu user (buat fitur
     * "lihat riwayat" nanti, opsional, belum dipakai di view saat ini).
     */
    public function availableDatesForUser(int $userId): array
    {
        return $this->select('import_date')
                    ->where('uploaded_by', $userId)
                    ->groupBy('import_date')
                    ->orderBy('import_date', 'DESC')
                    ->findAll();
    }
}
