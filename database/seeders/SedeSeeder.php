<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sede;

class SedeSeeder extends Seeder
{
    public function run()
    {
        $sedes = [
            'DELTA', 'RIO', 'METROPOLITANA', 'MOREÑA', 'ECHEVESTE',
            'MERCED', 'SANTA RITA', '21 DE MARZO', 'BELLAVISTA',
            'ESCOBEDO', 'HILAMAS'
        ];

        foreach ($sedes as $name) {
            Sede::updateOrCreate(
                ['name' => $name],
                [
                    'status' => 1,
                    'address' => null,
                    'phone' => null,
                    'city' => null,
                    'state' => null,
                    'cp' => null,
                    'open_positions' => null,
                    'responsible' => null,
                    'card_id' => null,
                ]
            );
        }
    }
}
