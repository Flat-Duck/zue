<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Authorization for these endpoints moved out of the controller body and into the
 * form request, so that it is decided before anything is validated rather than
 * after. These tests hold that line: an outsider is refused, and refused with a
 * 403 rather than a 422 that would tell them which values the form accepts.
 */
class RequestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function guardedEndpoints(): array
    {
        return [
            'user import' => ['post', 'users.import', []],
            'archived employee import' => ['post', 'employees.import-archived-employees', []],
            'employee profile import' => ['post', 'employees.import-profiles', []],
            'database export' => ['post', 'maintenance.export', []],
            'maintenance settings' => ['post', 'maintenance.settings.update', []],
            'database import' => ['post', 'maintenance.import', []],
            'timesheet approval sheet' => ['get', 'time-sheets.approve', []],
            'archive employees by number' => ['post', 'operations.archive-by-number', ['employee_numbers' => '1']],
            'archive employees by timesheet' => ['post', 'operations.archive-by-timesheet', ['date' => '2025-01-01']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('guardedEndpoints')]
    public function test_an_outsider_is_refused_before_anything_is_validated(string $method, string $route, array $payload): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}(route($route), $payload)
            ->assertForbidden();
    }

    public function test_an_outsider_cannot_upload_a_signature_for_someone_else(): void
    {
        $victim = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('users.upload-signature', $victim), [
                'signature_file' => UploadedFile::fake()->image('signature.png'),
            ])
            ->assertForbidden();
    }

    public function test_an_outsider_cannot_change_an_employees_appraisal_form(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('appraisals.employees.appraisal-form.update', $employee), ['appraisal_form_id' => null])
            ->assertForbidden();
    }

    /**
     * The gate on the operations screens is registered from the controller
     * constructor, which is easy to break silently.
     */
    public function test_the_operations_gate_actually_stops_a_mass_archive(): void
    {
        $employee = Employee::factory()->create(['number' => 4242, 'archived_at' => null]);

        $this->actingAs(User::factory()->create())
            ->post(route('operations.archive-by-number'), ['employee_numbers' => '4242'])
            ->assertForbidden();

        $this->assertNull($employee->fresh()->archived_at);
    }

    public function test_a_super_admin_still_gets_through_to_validation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->post(route('maintenance.settings.update'), [])
            ->assertSessionHasErrors(['backup_interval', 'backup_time', 'keep_backups_count']);
    }
}
