<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToPriceTags extends Migration
{
    public function up()
    {
        $this->forge->addColumn('price_tags', [
            'start_period' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'id',
            ],
            'end_period' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'start_period',
            ],
            'discount_percent' => [
                'type'       => 'INT',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'normal_price',
            ],
            'allocation_pcs' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'promo_price',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('price_tags', [
            'start_period',
            'end_period',
            'discount_percent',
            'allocation_pcs',
        ]);
    }
}