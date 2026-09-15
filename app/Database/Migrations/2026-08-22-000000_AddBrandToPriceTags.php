<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBrandToPriceTags extends Migration
{
    public function up()
    {
        // Cek apakah kolom 'brand' belum ada di tabel 'price_tags'
        if (! $this->db->fieldExists('brand', 'price_tags')) {
            $this->forge->addColumn('price_tags', [
                'brand' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'name',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('price_tags', 'brand', true);
    }
}
