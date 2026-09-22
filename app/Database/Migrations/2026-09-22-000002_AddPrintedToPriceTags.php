<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintedToPriceTags extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('is_printed', 'price_tags')) {
            $this->forge->addColumn('price_tags', [
                'is_printed' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'import_id',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_printed', 'price_tags')) {
            $this->forge->dropColumn('price_tags', 'is_printed');
        }
    }
}
