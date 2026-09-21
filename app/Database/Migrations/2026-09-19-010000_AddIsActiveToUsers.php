<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToUsers extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('is_active', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'outlet_id',
            ],
        ]);

        $this->forge->addKey('is_active');
        $this->forge->processIndexes('users');
    }

    public function down()
    {
        if (! $this->db->fieldExists('is_active', 'users')) {
            return;
        }

        $this->forge->dropColumn('users', 'is_active');
    }
}
