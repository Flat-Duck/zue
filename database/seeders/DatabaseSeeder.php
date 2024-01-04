<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // // Adding an admin user
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 9094,
                'name' =>'Abdulrahman Mahidwei',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
            ]);
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 7080,
                'name' =>'Abdulhameed Ghnedi',
                'email' => 'admin2@admin.com',
                'password' => Hash::make('admin'),
            ]);
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 6410,
                'name' =>'Abdudayem Alawi',
                'email' => 'admin3@admin.com',
                'password' => Hash::make('admin'),
            ]);
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 5527,
                'name' =>'Omar Dahim',
                'email' => 'admin4@admin.com',
                'password' => Hash::make('admin'),
            ]);
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 10588,
                'name' =>'Nabil Salem',
                'email' => 'admin5@admin.com',
                'password' => Hash::make('admin'),
            ]);
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 10601,
                'name' =>'Essam Khaleel',
                'email' => 'admin6@admin.com',
                'password' => Hash::make('admin'),
            ]);
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'id' => 8071,
                'name' =>'Omar Dahim',
                'email' => 'admin7@admin.com',
                'password' => Hash::make('admin'),
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
        // $this->call(UserSeeder::class);
        //Eloquent::unguard();

        
        $path = __DIR__ . '/data/centers.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('centers Data seeded!');
        $path = __DIR__ . '/data/departments.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('departments Data seeded!');
        $path = __DIR__ . '/data/locations.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('locations Data seeded!');
        $path = __DIR__ . '/data/employees.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('employees Data seeded!');
        $path = __DIR__ . '/data/timesheets.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('timesheets Data seeded!');
                     
        
    }
}
