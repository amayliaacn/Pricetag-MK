<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class ClearDefaultTemplateSize extends Migration
{
    public function up()
    {
        // Data yang sebelumnya otomatis menjadi Kecil dikembalikan ke status
        // belum memilih ukuran agar user menentukan template secara sadar.
        $this->db->table('price_tags')->where('template_size', 'kcl')->update(['template_size' => null]);
    }

    public function down()
    {
        // Tidak mengembalikan pilihan ukuran lama secara otomatis.
    }
}
