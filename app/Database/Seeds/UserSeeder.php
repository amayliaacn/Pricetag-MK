<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $outletCodes = [
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

        $outlets = $this->db->table('outlets')
                            ->whereIn('code', $outletCodes)
                            ->get()
                            ->getResultArray();

        $outletMap = [];
        foreach ($outlets as $outlet) {
            $outletMap[$outlet['code']] = $outlet;
        }

        $userBuilder = $this->db->table('users');
        $now         = date('Y-m-d H:i:s');

        foreach ($outletCodes as $code) {
            if (! isset($outletMap[$code])) {
                continue;
            }

            foreach (['', 'b'] as $suffix) {
                $username = strtolower($code) . $suffix;
                $existing = $userBuilder->where('username', $username)->get()->getRowArray();

                if ($existing) {
                    continue;
                }

                $userBuilder->insert([
                    'username'   => $username,
                    'password'   => password_hash('password123', PASSWORD_DEFAULT),
                    'role'       => 'user',
                    'outlet_id'  => $outletMap[$code]['id'],
                    'is_active'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
