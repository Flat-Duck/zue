<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy dump location
    |--------------------------------------------------------------------------
    |
    | Where `legacy:import` looks for the converted SQL dump files. Each file is
    | a single multi-row INSERT named `NNNNNN_<table>.sql`.
    |
    */

    'dump_path' => env('LEGACY_DUMP_PATH', database_path('seeders/sql_dump/converted')),

    /*
    |--------------------------------------------------------------------------
    | Bootstrap super administrator
    |--------------------------------------------------------------------------
    |
    | The single account SuperAdminSeeder creates so the system can be signed
    | into before any other data exists. Every actor is an employee, so this is
    | keyed by employee number rather than by email.
    |
    */

    'super_admin' => [
        'employee_number' => (int) env('SUPER_ADMIN_EMPLOYEE_NUMBER', 9094),
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@admin.com'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

];
