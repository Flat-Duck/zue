<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds the flight dispatching and plane permissions to an existing database.
 *
 * Roles and permissions on the live system come from an imported SQL dump
 * rather than from PermissionsSeeder, so a new permission has to be granted
 * explicitly. Everything here is idempotent and safe to run repeatedly.
 *
 *   php artisan db:seed --class=FlightDispatchPermissionsSeeder
 */
class FlightDispatchPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $dispatching = collect(['dispatch flights', 'manage flight routes'])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        foreach (['flightdispatcher', 'super-admin', 'admin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo($dispatching->all());
                $this->command->info("Granted flight dispatching permissions to [{$roleName}].");
            }
        }

        $this->grantPlanePermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * PlanePolicy used to allow everything, so planes had no permissions at all.
     * Now that it is enforced, existing roles need them or the plane screens
     * would simply disappear for people who legitimately use them.
     *
     * Rather than guessing, access mirrors what a role can already do with
     * flights: anyone who can see flights can see the aircraft, and anyone who
     * can change flights can manage them.
     *
     * Note that super-admin holds almost no explicit permissions in this
     * application - it passes through the Gate::before bypass instead - so it is
     * granted separately rather than by inspecting its permission list.
     */
    private function grantPlanePermissions(): void
    {
        $read = collect(['list planes', 'view planes'])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $write = collect(['create planes', 'update planes', 'delete planes'])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        foreach (Role::query()->with('permissions')->get() as $role) {
            $existing = $role->permissions->pluck('name');

            $canSeeFlights = $existing->intersect(['list flights', 'view flights'])->isNotEmpty();
            $canChangeFlights = $existing->intersect(['create flights', 'update flights', 'delete flights'])->isNotEmpty();

            if ($canSeeFlights) {
                $role->givePermissionTo($read->all());
                $this->command->info("Granted plane read permissions to [{$role->name}].");
            }

            if ($canChangeFlights) {
                $role->givePermissionTo($write->all());
                $this->command->info("Granted plane management permissions to [{$role->name}].");
            }
        }

        // A super-admin keeps everything regardless of how the dump was shaped.
        $superAdmin = Role::query()->where('name', 'super-admin')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($read->merge($write)->all());
        }
    }
}
