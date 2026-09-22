<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImportIdToPriceTags extends Migration
{
    public function up()
    {
        $indexes = $this->db->getIndexData('price_tags');
        if (isset($indexes['uniq_user_date_sku'])) {
            $this->db->query('ALTER TABLE price_tags DROP INDEX uniq_user_date_sku');
        }

        if (! $this->db->fieldExists('import_id', 'price_tags')) {
            $this->forge->addColumn('price_tags', [
                'import_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'import_date',
                ],
            ]);
            $this->forge->addKey('import_id');
            $this->forge->addForeignKey('import_id', 'import_history', 'id', 'SET NULL', 'CASCADE');
            $this->forge->processIndexes('price_tags');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('import_id', 'price_tags')) {
            $this->forge->dropForeignKey('price_tags', 'price_tags_import_id_foreign');
            $this->forge->dropColumn('price_tags', 'import_id');
        }
    }
}
