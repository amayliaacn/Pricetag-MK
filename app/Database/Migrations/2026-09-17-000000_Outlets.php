<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Outlets extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('outlets')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uniq_outlets_code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('outlets', true);
    }

    public function down()
    {
        $this->forge->dropTable('outlets', true);
    }
}
