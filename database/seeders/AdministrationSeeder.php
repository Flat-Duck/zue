<?php

namespace Database\Seeders;

use App\Models\Administration;
use Illuminate\Database\Seeder;

class AdministrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Administration::factory()
            ->count(5)
            ->create();
    }
}
