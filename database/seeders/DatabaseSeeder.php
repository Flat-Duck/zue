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
        // DB::unprepared(file_get_contents($path));
        // $this->command->info('timesheets Data seeded!');
                 
        



        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8881,
        'name' =>'المهدي محمد المرخي ',
        'email' => 'elmehdi8881@gmail.com',
        'password' => Hash::make('917750233'),
        ]);
        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8915,
        'name' =>'حسن علي قرضاب',
        'email' => 'hasangordap@gmail.com',
        'password' => Hash::make('914873513'),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 7621,
        'name' =>'محمد الهادي زهير',
        'email' => 'zohair7621.mz@gmail.com',
        'password' => Hash::make("913551925"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8823,
        'name' =>'عبدالكريم مسعود أبوضاوية ',
        'email' => 'marwanfirebird@gmail.com',
        'password' => Hash::make("917414895"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 6716,
        'name' =>'عبدالخالق محمد الفزانى',
        'email' => 'Khaldalfzany29@gmai.com',
        'password' => Hash::make("913463118"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 6849,
        'name' =>'عمر أحمد العقار',
        'email' => 'Omaralaggar@hotmail.com',
        'password' => Hash::make("913454304"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 5774,
        'name' =>'صالح العربي العماري',
        'email' => 'Salehelarabi881@gmail.com',
        'password' => Hash::make("913513329"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 9812,
        'name' =>'مصطفى الفيتوري صحة ',
        'email' => 'Must.888888m@gmail.com',
        'password' => Hash::make("912204286"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 7827,
        'name' =>' عبدالله جبران الدخلي ',
        'email' => 'abd.jobran@yahoo.com',
        'password' => Hash::make("913573722"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8139,
        'name' =>'محمد فرج ميلود',
        'email' => 'Mohammedfaraj8139@yahoo.com',
        'password' => Hash::make("911768220"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 5416,
        'name' =>'حسين حميدة',
        'email' => 'hussein.75747@gmail.com',
        'password' => Hash::make("913221233"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 7275,
        'name' =>'مختار ابوالعزوم ',
        'email' => 'abuazoom59abuazoom@gmail.com',
        'password' => Hash::make("913606908"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 9038,
        'name' =>'أحمد التركي',
        'email' => 'alturki2142010@gmail.com',
        'password' => Hash::make("918728651"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 7275,
        'name' =>'ارسينو بالو',
        'email' => 'balo91241970@gmail.com',
        'password' => Hash::make("913011447"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 6669,
        'name' =>'احمد صالح عبدالمالك',
        'email' => 'Asa6@hotmail.com',
        'password' => Hash::make("913232732"),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8056,
        'name' =>'صلاح سلطان بيري ',
        'email' => 'Berysalah253@gmail.com',
        'password' => Hash::make('913502919'),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8474,
        'name' =>'ناجي ميلاد أبوعقرب',
        'email' => 'najiateegabuagrab@gmail.com',
        'password' => Hash::make('913613183'),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8596,
        'name' =>'ابراهيم بلعيد الوحيشي ',
        'email' => ' sniper8596@proton.me',
        'password' => Hash::make('911791491'),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 8620,
        'name' => 'عبدالباري زيدان درويش',
        'email' => 'themaker87@hotmail.com',
        'password' => Hash::make('913958445'),
        ]);

        $user = \App\Models\User::factory()
        ->count(1)
        ->create([
        'id' => 9020,
        'name' =>'أحمد محمدعلي أعظيم ',
        'email' => 'ahmededeem81@gmail.com',
        'password' => Hash::make('914805876'),
        ]);
            
        }
}
