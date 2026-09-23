<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropBrandFromPriceTags extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('brand', 'price_tags')) {
            $this->forge->dropColumn('price_tags', 'brand');
        }
    }

    public function down()
    {
        // Kolom brand sengaja tidak dipulihkan.
    }
}
