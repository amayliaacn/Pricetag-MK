<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOutletIdToUsers extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('outlet_id', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'outlet_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'role',
            ],
        ]);

        $this->forge->addForeignKey('outlet_id', 'outlets', 'id', 'SET NULL', 'CASCADE');
        $this->forge->processIndexes('users');
    }

    public function down()
    {
        if (! $this->db->fieldExists('outlet_id', 'users')) {
            return;
        }

        $this->forge->dropForeignKey('users', 'users_outlet_id_foreign');
        $this->forge->dropColumn('users', 'outlet_id');
    }
}
