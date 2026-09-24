<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class MakeTemplateSizeOptional extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('price_tags', [
            'template_size' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => null,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('price_tags', [
            'template_size' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'kcl',
                'null'       => false,
            ],
        ]);
    }
}
