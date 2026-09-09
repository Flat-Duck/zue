<?php

namespace Tests\Feature;

use App\Imports\EmployeeProfilesImport;
use App\Models\Administration;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The personnel export is the authoritative source for employee data, so the
 * import has to be safe to run repeatedly and must never store a value it
 * could not read correctly.
 */
class EmployeeProfileImportTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): string
    {
        return base_path('tests/Fixtures/employee_profiles.csv');
    }

    private function import(): EmployeeProfilesImport
    {
        $import = new EmployeeProfilesImport;
        Excel::import($import, $this->fixture());

        return $import;
    }

    #[Test]
    public function it_creates_employees_from_the_export(): void
    {
        $import = $this->import();

        $this->assertSame(3, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame(3, Employee::query()->count());
    }

    #[Test]
    public function it_maps_the_arabic_name_and_nationality(): void
    {
        $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        $this->assertSame('صالح خليفة مشري سعيد', $employee->arabic_name);
        $this->assertSame('صالح خليفة مشري سعيد', $employee->arabic_full_name);
        $this->assertSame('ليبي', $employee->nationality);
        $this->assertSame('ذكر', $employee->gender);
        $this->assertSame('متزوج', $employee->marital_status);
    }

    /**
     * The export mixes `1979\03\29` with `10/1/2024`.
     */
    #[Test]
    public function it_understands_both_date_formats(): void
    {
        $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        $this->assertSame('1962-07-01', $employee->birth_date->toDateString());
        $this->assertSame('1979-03-29', $employee->appointment_date->toDateString());
        $this->assertSame('2024-10-01', $employee->job_start_date->toDateString());
    }

    #[Test]
    public function it_imports_payroll_and_job_details(): void
    {
        $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        $this->assertSame('5797.00', $employee->basic_salary);
        $this->assertSame('6097.00', $employee->total_salary);
        $this->assertSame('كبير اخصائيين حركة الزيت والغاز', $employee->job);
    }

    /**
     * A spreadsheet rewrites long account numbers as `4.10E+13`, destroying the
     * real digits. Storing that would put a wrong account on a personnel record.
     */
    #[Test]
    public function it_refuses_bank_account_numbers_corrupted_by_the_spreadsheet(): void
    {
        $import = $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        $this->assertNull($employee->bank_account_number);
        $this->assertNotEmpty($import->errors);
        $this->assertStringContainsString('scientific notation', $import->errors[0]);

        // The rest of the banking detail is still imported.
        $this->assertNotNull($employee->bank_name);
    }

    #[Test]
    public function placeholder_values_become_null_rather_than_text(): void
    {
        $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        // The export writes "/" and "0" where nothing was recorded.
        $this->assertNull($employee->previous_employer);
        $this->assertNull($employee->email);
    }

    #[Test]
    public function re_importing_updates_instead_of_duplicating(): void
    {
        $this->import();
        $second = $this->import();

        $this->assertSame(0, $second->created);
        $this->assertSame(3, $second->updated);
        $this->assertSame(3, Employee::query()->count());
    }

    #[Test]
    public function it_updates_an_employee_that_already_exists(): void
    {
        $existing = Employee::factory()->create([
            'number' => 3438,
            'english_name' => 'Existing Person',
            'nationality' => null,
        ]);

        $import = $this->import();

        $this->assertSame(1, $import->updated);
        $this->assertSame(2, $import->created);

        $existing->refresh();

        $this->assertSame('ليبي', $existing->nationality);
        $this->assertSame('صالح خليفة مشري سعيد', $existing->arabic_name);
        // The employee keeps its identity; only the profile is refreshed.
        $this->assertSame(3438, (int) $existing->number);
    }

    /**
     * Organisational detail belongs to the unit, not to every employee row.
     */
    #[Test]
    public function it_links_the_centre_department_and_location(): void
    {
        $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        $this->assertNotNull($employee->center_id);
        $this->assertNotNull($employee->department_id);
        $this->assertNotNull($employee->location_id);

        $this->assertSame('5M11', $employee->center->code);
        $this->assertSame('وحدة حركة الزيت', $employee->department->arabic_name);
        $this->assertSame('5M11', $employee->department->code);
        $this->assertSame('B099', $employee->location->code);
        $this->assertSame('ميناء الزويتينة', $employee->location->arabic_name);
    }

    #[Test]
    public function it_links_the_department_to_its_administration(): void
    {
        $this->import();

        $employee = Employee::query()->where('number', 3438)->firstOrFail();

        $administration = $employee->department->administration;

        $this->assertNotNull($administration, 'The department should belong to an administration.');
        $this->assertSame('ادارة العمليات', $administration->arabic_name);

        // Employee reads its administration through the department, unchanged.
        $this->assertSame('ادارة العمليات', $employee->administration_name);
    }

    #[Test]
    public function organisational_units_are_reused_rather_than_duplicated(): void
    {
        $this->import();
        $this->import();

        // Three employees, two of them in the operations administration.
        $this->assertSame(
            1,
            Administration::query()->where('arabic_name', 'ادارة العمليات')->count()
        );
        $this->assertSame(
            1,
            Department::query()->where('arabic_name', 'وحدة حركة الزيت')->count()
        );
        $this->assertSame(
            1,
            Center::query()->where('code', '5M11')->count()
        );
    }

    #[Test]
    public function an_existing_centre_is_matched_rather_than_duplicated(): void
    {
        // Legacy rows carry the code in `name` with no `code` value.
        $existing = Center::query()->create(['name' => '5M11']);

        $this->import();

        $this->assertSame(1, Center::query()->where('name', '5M11')->count());
        $this->assertSame('5M11', $existing->fresh()->code, 'The code should be backfilled.');
    }

    #[Test]
    public function the_heading_row_is_not_imported_as_an_employee(): void
    {
        $this->import();

        $this->assertDatabaseMissing('employees', ['arabic_name' => 'الاسم']);
    }

    /**
     * Employee exposes accessors such as department_name that derive their
     * value from the related record. A database column of the same name would
     * be shadowed by the accessor and silently unreadable, which is exactly
     * what happened while this import was being written.
     */
    #[Test]
    public function no_imported_column_is_shadowed_by_an_accessor(): void
    {
        $employee = new Employee;

        $appendsProperty = new \ReflectionProperty($employee, 'appends');
        $appendsProperty->setAccessible(true);
        $appended = $appendsProperty->getValue($employee);

        $columns = array_keys((new \ReflectionClass(EmployeeProfilesImport::class))
            ->getConstant('COLUMNS'));

        foreach ($columns as $column) {
            $this->assertNotContains(
                $column,
                $appended,
                "[{$column}] collides with an appended accessor and would be unreadable."
            );

            $this->assertFalse(
                method_exists($employee, 'get'.Str::studly($column).'Attribute'),
                "[{$column}] collides with an accessor and would be unreadable."
            );
        }
    }

    #[Test]
    public function the_employees_page_offers_the_import_to_users_who_can_run_it(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['list employees', 'view employees', 'create employees', 'update employees'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['list employees', 'view employees', 'create employees', 'update employees']);

        $this->actingAs($user)->get(route('employees.index'))
            ->assertOk()
            ->assertSee(route('employees.imports'), false)
            ->assertSee('Import');

        $this->actingAs($user)->get(route('employees.imports'))->assertOk();
    }

    #[Test]
    public function a_user_who_cannot_import_is_not_offered_it(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['list employees', 'view employees'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['list employees', 'view employees']);

        $this->actingAs($user)->get(route('employees.index'))
            ->assertOk()
            ->assertDontSee(route('employees.imports'), false);

        // And the page itself is refused, not merely hidden.
        $this->actingAs($user)->get(route('employees.imports'))->assertForbidden();
    }

    #[Test]
    public function a_user_without_permission_cannot_import(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('employees.import-profiles'), [
                'file' => UploadedFile::fake()->create('people.csv', 8),
            ])
            ->assertForbidden();
    }

    #[Test]
    public function an_authorised_user_can_import_through_the_page(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['create employees', 'update employees', 'list employees'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['create employees', 'update employees', 'list employees']);

        $response = $this->actingAs($user)->post(route('employees.import-profiles'), [
            'file' => new UploadedFile($this->fixture(), 'employee_profiles.csv', 'text/csv', null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(3, Employee::query()->count());
    }
}
