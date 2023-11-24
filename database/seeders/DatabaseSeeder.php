<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Adding an admin user
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'email' => 'admin@admin.com',
                'password' => \Hash::make('admin'),
            ]);
        $this->call(PermissionsSeeder::class);

        // $this->call(AdministrationSeeder::class);
        // $this->call(CenterSeeder::class);
        // $this->call(DepartmentSeeder::class);
        // $this->call(EmployeeSeeder::class);
        // $this->call(FlightSeeder::class);
        // $this->call(LocationSeeder::class);
        // $this->call(PassengerSeeder::class);
        // $this->call(ResidenceSeeder::class);
        // $this->call(RoomSeeder::class);
        // $this->call(StockSeeder::class);
        // $this->call(TimeSheetSeeder::class);
        $this->call(UserSeeder::class);
    }
}
