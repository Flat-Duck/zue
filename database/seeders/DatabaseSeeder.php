<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Brings a freshly migrated database to the point where it can be signed into.
 *
 * Historical data is not seeded. It lives in the legacy SQL dump and is loaded by
 * `php artisan legacy:import`, which reshapes it into the current schema on the
 * way in — seeding cannot, because the dump still carries the old identity layout.
 *
 *   php artisan migrate
 *   php artisan db:seed
 *   php artisan legacy:import --with-passwords
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);

        // Without these a time sheet has no chain to be signed through, so every
        // sheet stalls at the first step with nothing to explain why.
        $this->call(ApprovalFlowSeeder::class);

        $this->call(NavigationSeeder::class);
    }
}
