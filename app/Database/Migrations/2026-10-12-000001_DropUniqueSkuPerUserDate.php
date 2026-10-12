<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUniqueSkuPerUserDate extends Migration
{
    public function up()
    {
        $index = $this->db->query(
            "SHOW INDEX FROM price_tags WHERE Key_name = 'uniq_user_date_sku'"
        )->getRowArray();

        if ($index) {
            $this->db->query(
                'ALTER TABLE price_tags DROP INDEX uniq_user_date_sku'
            );
        }
    }

    public function down()
    {
        $this->db->query(
            'ALTER TABLE price_tags ADD UNIQUE KEY uniq_user_date_sku (uploaded_by, import_date, sku_plu)'
        );
    }
}
