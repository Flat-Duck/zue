<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create default permissions
        Permission::create(['name' => 'list administrations']);
        Permission::create(['name' => 'view administrations']);
        Permission::create(['name' => 'create administrations']);
        Permission::create(['name' => 'update administrations']);
        Permission::create(['name' => 'delete administrations']);

        Permission::create(['name' => 'list centers']);
        Permission::create(['name' => 'view centers']);
        Permission::create(['name' => 'create centers']);
        Permission::create(['name' => 'update centers']);
        Permission::create(['name' => 'delete centers']);

        Permission::create(['name' => 'list departments']);
        Permission::create(['name' => 'view departments']);
        Permission::create(['name' => 'create departments']);
        Permission::create(['name' => 'update departments']);
        Permission::create(['name' => 'delete departments']);

        Permission::create(['name' => 'list employees']);
        Permission::create(['name' => 'view employees']);
        Permission::create(['name' => 'create employees']);
        Permission::create(['name' => 'update employees']);
        Permission::create(['name' => 'delete employees']);

        Permission::create(['name' => 'list flights']);
        Permission::create(['name' => 'view flights']);
        Permission::create(['name' => 'create flights']);
        Permission::create(['name' => 'update flights']);
        Permission::create(['name' => 'delete flights']);

        Permission::create(['name' => 'list locations']);
        Permission::create(['name' => 'view locations']);
        Permission::create(['name' => 'create locations']);
        Permission::create(['name' => 'update locations']);
        Permission::create(['name' => 'delete locations']);

        Permission::create(['name' => 'list passengers']);
        Permission::create(['name' => 'view passengers']);
        Permission::create(['name' => 'create passengers']);
        Permission::create(['name' => 'update passengers']);
        Permission::create(['name' => 'delete passengers']);

        Permission::create(['name' => 'list residences']);
        Permission::create(['name' => 'view residences']);
        Permission::create(['name' => 'create residences']);
        Permission::create(['name' => 'update residences']);
        Permission::create(['name' => 'delete residences']);

        Permission::create(['name' => 'list rooms']);
        Permission::create(['name' => 'view rooms']);
        Permission::create(['name' => 'create rooms']);
        Permission::create(['name' => 'update rooms']);
        Permission::create(['name' => 'delete rooms']);

        Permission::create(['name' => 'list stocks']);
        Permission::create(['name' => 'view stocks']);
        Permission::create(['name' => 'create stocks']);
        Permission::create(['name' => 'update stocks']);
        Permission::create(['name' => 'delete stocks']);

        Permission::create(['name' => 'list timesheets']);
        Permission::create(['name' => 'view timesheets']);
        Permission::create(['name' => 'create timesheets']);
        Permission::create(['name' => 'update timesheets']);
        Permission::create(['name' => 'delete timesheets']);

        // Create user role and assign existing permissions
        $currentPermissions = Permission::all();
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo($currentPermissions);

        // Create admin exclusive permissions
        Permission::create(['name' => 'list roles']);
        Permission::create(['name' => 'view roles']);
        Permission::create(['name' => 'create roles']);
        Permission::create(['name' => 'update roles']);
        Permission::create(['name' => 'delete roles']);

        Permission::create(['name' => 'list permissions']);
        Permission::create(['name' => 'view permissions']);
        Permission::create(['name' => 'create permissions']);
        Permission::create(['name' => 'update permissions']);
        Permission::create(['name' => 'delete permissions']);

        Permission::create(['name' => 'list users']);
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'update users']);
        Permission::create(['name' => 'delete users']);

        // Create admin role and assign all permissions
        $allPermissions = Permission::all();
        $adminRole = Role::create(['name' => 'super-admin']);
        $adminRole->givePermissionTo($allPermissions);

        $user = \App\Models\User::whereEmail('admin@admin.com')->first();

        if ($user) {
            $user->assignRole($adminRole);
        }
    }
}
