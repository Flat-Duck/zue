<?php

namespace Database\Seeders;

use App\Models\Residence;
use Illuminate\Database\Seeder;

class ResidenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Residence::factory()
            ->count(5)
            ->create();
    }
}
