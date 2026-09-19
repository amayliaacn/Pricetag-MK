<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run()
    {
        $outlets = [
            'MK1',
            'MK2',
            'MK3',
            'MK4',
            'MK5',
            'MK6',
            'MK7',
            'MK8',
            'Mini1',
            'Mini2',
            'Mini3',
        ];

        $builder = $this->db->table('outlets');
        $now     = date('Y-m-d H:i:s');

        foreach ($outlets as $code) {
            $existing = $builder->where('code', $code)->get()->getRowArray();

            if ($existing) {
                continue;
            }

            $builder->insert([
                'code'       => $code,
                'name'       => $code,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
