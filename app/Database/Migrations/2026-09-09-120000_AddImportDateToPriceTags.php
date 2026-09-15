<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration ini TIDAK membuat tabel baru — tabel price_tags Anda sudah ada.
 * Migration ini hanya MENAMBAH kolom import_date + kunci unik, supaya:
 *   - Import hari yang sama + SKU yang sama -> update baris.
 *   - Import hari yang sama + SKU baru      -> insert baris baru.
 *   - Ganti hari  -> baris hari sebelumnya TIDAK disentuh (jadi riwayat).
 */
class AddImportDateToPriceTags extends Migration
{
    public function up()
    {
        // 1) Tambah kolom dulu sebagai NULLABLE, supaya baris lama tidak
        //    memicu error backfill NOT NULL.
        $this->forge->addColumn('price_tags', [
            'import_date' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'uploaded_by',
            ],
        ]);

        // 2) Isi baris-baris lama dengan tanggal hari ini (dianggap "diimport"
        //    pada hari migration ini dijalankan).
        $this->db->query("UPDATE price_tags SET import_date = CURDATE() WHERE import_date IS NULL");

        // 3) Baru sekarang, setelah semua baris punya nilai, kunci jadi NOT NULL.
        $this->db->query("ALTER TABLE price_tags MODIFY import_date DATE NOT NULL");

        // 4) Tambah kunci unik.
        $this->db->query(
            'ALTER TABLE price_tags ADD UNIQUE KEY uniq_user_date_sku (uploaded_by, import_date, sku_plu)'
        );
    }

    public function down()
    {
        $this->db->query('ALTER TABLE price_tags DROP INDEX uniq_user_date_sku');
        $this->forge->dropColumn('price_tags', 'import_date');
    }
}
