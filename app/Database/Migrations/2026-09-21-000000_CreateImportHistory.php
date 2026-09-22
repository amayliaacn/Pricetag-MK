<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateImportHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'snapshot' => [
                'type' => 'LONGTEXT',
            ],
            'outlet_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'imported_at' => [
                'type' => 'DATETIME',
            ],
            'imported_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('imported_at');
        $this->forge->addKey('imported_by');
        $this->forge->addKey('outlet_id');
        $this->forge->addForeignKey('imported_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('outlet_id', 'outlets', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('import_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('import_history', true);
    }
}
