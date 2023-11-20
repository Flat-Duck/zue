<?php

namespace Database\Seeders;

use App\Models\TimeSheet;
use Illuminate\Database\Seeder;

class TimeSheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TimeSheet::factory()
            ->count(5)
            ->create();
    }
}
