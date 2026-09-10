<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the one account the system can be signed into before it holds any data.
 *
 * Every actor is an employee, so this seeder is keyed by employee number, not by
 * email. It is safe to run before or after `legacy:import`: run afterwards it
 * simply promotes the employee the dump already carries, and run beforehand it
 * creates a placeholder employee under the same id the dump uses, so the import
 * later fills in the real record instead of duplicating the person.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (Role::query()->count() === 0) {
            $this->call(PermissionsSeeder::class);
        }

        $role = Role::query()->where('name', 'super-admin')->firstOrFail();

        $number = (int) config('legacy.super_admin.employee_number');
        $name = (string) config('legacy.super_admin.name');
        $email = (string) config('legacy.super_admin.email');

        $employee = Employee::query()->where('number', $number)->first()
            ?? $this->createPlaceholderEmployee($number, $name);

        $password = config('legacy.super_admin.password');
        $generated = $password === null;
        $password = $generated ? Str::password(16) : (string) $password;

        $user = User::query()->where('employee_id', $employee->id)->first();

        if ($user === null) {
            $user = new User;
            $user->employee_id = $employee->id;
        }

        $user->name = $user->exists ? $user->name : $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        $user->syncRoles([$role]);

        $this->say("Super admin ready: employee {$number} (id {$employee->id}), user id {$user->id}, {$email}");

        if ($generated) {
            $this->say("Generated password: {$password}", warning: true);
            $this->say('Set SUPER_ADMIN_PASSWORD to choose it yourself. Change it after the first sign-in.', warning: true);
        }
    }

    private function say(string $message, bool $warning = false): void
    {
        $warning ? $this->command->warn($message) : $this->command->info($message);
    }

    /**
     * The id is forced to match the employee number because that is the convention
     * the legacy dump was written with. Landing on the same id means a later
     * `legacy:import` updates this row rather than inserting the person twice.
     */
    private function createPlaceholderEmployee(int $number, string $name): Employee
    {
        $employee = new Employee;
        $employee->id = $number;
        $employee->number = $number;
        $employee->english_name = $name;
        $employee->location_id = Location::query()->firstOrCreate(['name' => 'Unassigned'])->id;
        $employee->center_id = Center::query()->firstOrCreate(['name' => 'Unassigned'])->id;
        $employee->department_id = Department::query()->firstOrCreate(['name' => 'Unassigned'])->id;
        $employee->save();

        $this->say("No employee {$number} found; created a placeholder. Run legacy:import to fill in the real record.", warning: true);

        return $employee;
    }
}
