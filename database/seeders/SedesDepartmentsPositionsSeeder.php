<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SedesDepartmentsPositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SedeSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
        ]);
    }
}
