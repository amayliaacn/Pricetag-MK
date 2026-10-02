<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSourceTypeToImportHistory extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('source_type', 'import_history')) {
            $this->forge->addColumn('import_history', [
                'source_type' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'file_name',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('source_type', 'import_history')) {
            $this->forge->dropColumn('import_history', 'source_type');
        }
    }
}
