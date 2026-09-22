<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpgradeImportHistory extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('snapshot', 'import_history')) {
            $this->forge->addColumn('import_history', [
                'snapshot' => ['type' => 'LONGTEXT', 'null' => true],
            ]);
        }

        if (! $this->db->fieldExists('outlet_id', 'import_history')) {
            $this->forge->addColumn('import_history', [
                'outlet_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);

            if ($this->db->fieldExists('outlet', 'import_history')) {
                $this->db->query('UPDATE import_history h JOIN outlets o ON o.name = h.outlet SET h.outlet_id = o.id');
            }

            $this->forge->addKey('outlet_id');
            $this->forge->addForeignKey('outlet_id', 'outlets', 'id', 'SET NULL', 'CASCADE');
            $this->forge->processIndexes('import_history');
        }
    }

    public function down()
    {
        // Data migration is intentionally retained on rollback.
    }
}
