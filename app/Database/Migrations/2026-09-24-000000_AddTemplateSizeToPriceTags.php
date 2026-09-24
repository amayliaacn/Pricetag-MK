<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddTemplateSizeToPriceTags extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('template_size', 'price_tags')) {
            $this->forge->addColumn('price_tags', ['template_size' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => null, 'null' => true, 'after' => 'variant']]);
        }
    }
    public function down()
    {
        if ($this->db->fieldExists('template_size', 'price_tags')) $this->forge->dropColumn('price_tags', 'template_size');
    }
}
