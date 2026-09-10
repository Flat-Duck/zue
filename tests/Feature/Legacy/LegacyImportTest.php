<?php

namespace Tests\Feature\Legacy;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use App\Services\Legacy\LegacyImporter;
use App\Services\Legacy\LegacyImportReport;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    private string $dumpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dumpPath = storage_path('framework/testing/legacy-dump-'.uniqid());
        File::ensureDirectoryExists($this->dumpPath);
        $this->writeFixtureDump();

        $this->seed(PermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dumpPath);

        parent::tearDown();
    }

    public function test_it_preserves_employee_ids_so_every_other_reference_stays_valid(): void
    {
        $this->import();

        $this->assertSame([6718, 9094, 9676], Employee::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame(6716, Employee::query()->find(6718)->number);
    }

    public function test_it_links_every_user_to_an_employee_and_stops_using_the_number_as_a_primary_key(): void
    {
        $report = $this->import();

        $this->assertSame([], $report->unresolvedUsers);

        $mahidwei = User::query()->where('email', 'admin@admin.com')->firstOrFail();
        $this->assertSame(9094, $mahidwei->employee_id);
        $this->assertNotSame(9094, $mahidwei->id, 'User ids are now database-assigned, not copied from the employee number.');
        $this->assertSame(9094, $mahidwei->number, 'The number is read through the employee, not stored on the user.');
    }

    /**
     * The legacy row keyed on the employee's id rather than its number. Resolving
     * by number alone would leave this person without an account.
     */
    public function test_it_rescues_the_user_whose_key_was_an_employee_id_not_a_number(): void
    {
        $this->import();

        $this->assertSame(
            6718,
            User::query()->where('email', 'elfezni@example.test')->firstOrFail()->employee_id,
        );
    }

    public function test_it_keeps_the_stronger_match_when_two_users_claim_one_employee(): void
    {
        $report = $this->import();

        $this->assertCount(1, $report->identityConflicts);
        $this->assertSame([
            'employee_id' => 9676,
            'kept_user_id' => 10841,
            'dropped_user_id' => 9676,
            'dropped_email' => 'abubaker.saud@example.test',
            'dropped_name' => 'Abubaker Saud',
        ], $report->identityConflicts[0]);

        $this->assertSame(1, User::query()->where('employee_id', 9676)->count());
        $this->assertSame('abubker.saud@example.test', User::query()->where('employee_id', 9676)->value('email'));
    }

    public function test_it_translates_the_legacy_reviser_number_into_an_employee_id(): void
    {
        $report = $this->import();

        $this->assertSame([], $report->unresolvedReviserNumbers);
        $this->assertSame(2, $report->timeSheetsWithReviser);

        $revised = DB::table('time_sheets')->where('id', 2)->first();
        $this->assertSame(9094, (int) $revised->admin_id);

        $viaEmployeeIdOnly = DB::table('time_sheets')->where('id', 3)->first();
        $this->assertSame(6718, (int) $viaEmployeeIdOnly->admin_id, 'Number 6716 belongs to employee 6718.');

        $this->assertNull(DB::table('time_sheets')->where('id', 1)->value('admin_id'));
    }

    public function test_the_revised_by_relation_resolves_to_the_employee_who_did_the_revising(): void
    {
        $this->import();

        $this->assertSame(
            'ABDURAHMAN A. ALMHADWI',
            TimeSheet::query()->find(2)->revised_by->english_name,
        );
    }

    public function test_it_drops_rows_that_belong_to_an_employee_the_dump_never_contained(): void
    {
        $report = $this->import();

        $this->assertEqualsCanonicalizing(
            ['time_sheets.employee_id' => [99999 => 1], 'timesheet_approval_steps.approved_by_employee_id' => [99999 => 1]],
            $report->orphanedReferences,
        );

        $this->assertSame(0, DB::table('time_sheets')->where('employee_id', 99999)->count());
        $this->assertSame(3, DB::table('time_sheets')->count());
        $this->assertSame(3, $report->rowCounts['time_sheets'], 'A dropped row must not be counted as imported.');
    }

    /**
     * Losing the approver's name is not a reason to lose the fact that the step was
     * approved, so an optional reference is cleared rather than taking the row with it.
     */
    public function test_it_clears_an_optional_reference_instead_of_dropping_the_row(): void
    {
        $this->import();

        $step = DB::table('timesheet_approval_steps')->where('id', 1)->first();

        $this->assertNotNull($step);
        $this->assertNull($step->approved_by_employee_id);
        $this->assertSame('2025-02-01 00:00:00', $step->approved_at);
    }

    public function test_it_repoints_role_assignments_at_the_seeded_roles_and_the_new_user_ids(): void
    {
        $this->import();

        $mahidwei = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        $this->assertTrue($mahidwei->hasRole('super-admin'));
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', 9094)->count());
    }

    public function test_it_remaps_signatures_onto_the_new_user_ids(): void
    {
        $this->import();

        $mahidwei = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        $this->assertSame(
            'signatures/9094.png',
            DB::table('signatures')->where('user_id', $mahidwei->id)->value('image_path'),
        );
        $this->assertSame(0, DB::table('signatures')->where('user_id', 9094)->count());
    }

    public function test_it_leaves_passwords_unusable_unless_they_are_explicitly_carried_over(): void
    {
        $this->import();

        $hash = User::query()->where('email', 'admin@admin.com')->value('password');
        $this->assertNotSame('$2y$12$legacyhashlegacyhashlegacyhashlegacyhashlegacyhashle', $hash);

        User::query()->delete();
        $this->import(withPasswords: true);

        $this->assertSame(
            '$2y$12$legacyhashlegacyhashlegacyhashlegacyhashlegacyhashle',
            User::query()->where('email', 'admin@admin.com')->value('password'),
        );
    }

    /**
     * The password on an account that already exists was chosen on purpose, so the
     * dump must not quietly replace it and lock the operator out.
     */
    public function test_it_leaves_an_existing_accounts_password_alone(): void
    {
        $employee = Employee::factory()->create(['id' => 9094, 'number' => 9094]);
        $existing = User::factory()->create(['employee_id' => $employee->id, 'password' => Hash::make('chosen-by-the-operator')]);

        $this->import(withPasswords: true);

        $this->assertTrue(Hash::check('chosen-by-the-operator', $existing->fresh()->password));
        $this->assertSame('admin@admin.com', $existing->fresh()->email, 'Everything else is still refreshed from the dump.');
    }

    public function test_it_never_imports_the_roles_or_permissions_owned_by_the_seeder(): void
    {
        $permissionCount = DB::table('permissions')->count();
        $roleCount = DB::table('roles')->count();

        $report = $this->import();

        $this->assertArrayHasKey('roles', $report->skipped);
        $this->assertArrayHasKey('permissions', $report->skipped);
        $this->assertSame($permissionCount, DB::table('permissions')->count());
        $this->assertSame($roleCount, DB::table('roles')->count());
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->import();

        $employees = Employee::query()->count();
        $users = User::query()->count();
        $timeSheets = DB::table('time_sheets')->count();

        $this->import();

        $this->assertSame($employees, Employee::query()->count());
        $this->assertSame($users, User::query()->count());
        $this->assertSame($timeSheets, DB::table('time_sheets')->count());
    }

    public function test_a_dry_run_reports_everything_and_writes_nothing(): void
    {
        $report = (new LegacyImporter($this->dumpPath, dryRun: true))->run();

        $this->assertTrue($report->dryRun);
        $this->assertSame(3, $report->rowCounts['employees']);
        $this->assertSame(3, $report->rowCounts['users']);
        $this->assertCount(1, $report->identityConflicts);
        $this->assertSame(0, Employee::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    public function test_the_command_reports_the_conflict_and_writes_a_json_report(): void
    {
        $reportPath = storage_path('framework/testing/legacy-report-'.uniqid().'.json');

        $this->artisan('legacy:import', [
            '--path' => $this->dumpPath,
            '--report' => $reportPath,
        ])
            ->expectsOutputToContain('Two legacy users resolved to the same employee')
            ->assertSuccessful();

        $payload = json_decode(File::get($reportPath), true);
        $this->assertSame(3, $payload['row_counts']['employees']);
        $this->assertSame(9676, $payload['identity']['conflicts'][0]['employee_id']);

        File::delete($reportPath);
    }

    public function test_strict_mode_fails_when_an_identity_cannot_be_settled(): void
    {
        $reportPath = storage_path('framework/testing/legacy-report-'.uniqid().'.json');

        $this->artisan('legacy:import', [
            '--path' => $this->dumpPath,
            '--report' => $reportPath,
            '--strict' => true,
        ])->assertFailed();

        File::delete($reportPath);
    }

    private function import(bool $withPasswords = false): LegacyImportReport
    {
        return (new LegacyImporter($this->dumpPath, withPasswords: $withPasswords))->run();
    }

    private function writeFixtureDump(): void
    {
        $files = [
            '000009_locations.sql' => "INSERT INTO `locations` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 'Tripoli', NULL, NULL, NULL)\n;\n",

            '000001_administrations.sql' => "INSERT INTO `administrations` (`id`, `name`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 'Operations', NULL, NULL)\n;\n",

            '000006_centers.sql' => "INSERT INTO `centers` (`id`, `name`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 'Admin', NULL, NULL)\n;\n",

            '000007_departments.sql' => "INSERT INTO `departments` (`id`, `name`, `administration_id`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 'HR', 1, NULL, NULL)\n;\n",

            // `user_id` is present and NULL on every row, exactly as in the real dump.
            '000008_employees.sql' => "INSERT INTO `employees` (`id`, `number`, `english_name`, `user_id`, `location_id`, `department_id`, `center_id`, `schedule`) VALUES\n"
                ."  (9094, 9094, 'ABDURAHMAN A. ALMHADWI', NULL, 1, 1, 1, '14/14'),\n"
                ."  (6718, 6716, 'ADULKHALEK ELFEZNI', NULL, 1, 1, 1, '14/14'),\n"
                ."  (9676, 9676, 'ABUBAKER SAUD', NULL, 1, 1, 1, '14/14')\n;\n",

            '000026_users.sql' => "INSERT INTO `users` (`id`, `number`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES\n"
                ."  (9094, 9094, 'Abdulrahman Mahidwei', 'admin@admin.com', NULL, '\$2y\$12\$legacyhashlegacyhashlegacyhashlegacyhashlegacyhashle', NULL, NULL, NULL),\n"
                ."  (6718, 6718, 'Abdulkhalek Elfezni', 'elfezni@example.test', NULL, '\$2y\$12\$legacyhashlegacyhashlegacyhashlegacyhashlegacyhashle', NULL, NULL, NULL),\n"
                ."  (9676, NULL, 'Abubaker Saud', 'abubaker.saud@example.test', NULL, '\$2y\$12\$legacyhashlegacyhashlegacyhashlegacyhashlegacyhashle', NULL, NULL, NULL),\n"
                ."  (10841, 9676, 'Abubaker Saud', 'abubker.saud@example.test', NULL, '\$2y\$12\$legacyhashlegacyhashlegacyhashlegacyhashlegacyhashle', NULL, NULL, NULL)\n;\n",

            '000017_roles.sql' => "INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES\n"
                ."  (2, 'super-admin', 'web', NULL, NULL)\n;\n",

            '000014_model_has_roles.sql' => "INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES\n"
                ."  (2, 'App\\\\Models\\\\User', 9094)\n;\n",

            '000020_signatures.sql' => "INSERT INTO `signatures` (`id`, `user_id`, `image_path`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 9094, 'signatures/9094.png', NULL, NULL)\n;\n",

            // admin_id holds an employee *number*: 9094 is also an id, 6716 is not.
            '000001_time_sheets.sql' => "INSERT INTO `time_sheets` (`id`, `employee_id`, `value`, `day`, `revised_at`, `admin_id`) VALUES\n"
                ."  (1, 9094, 'Y', '2025-01-01', NULL, NULL),\n"
                ."  (2, 9094, 'F', '2025-01-02', '2025-02-01 00:00:00', 9094),\n"
                ."  (3, 6718, 'F', '2025-01-03', '2025-02-01 00:00:00', 6716),\n"
                // An employee the legacy database deleted without clearing their days.
                ."  (4, 99999, 'Y', '2025-01-04', NULL, NULL)\n;\n",

            '000004_approval_flows.sql' => "INSERT INTO `approval_flows` (`id`, `context`, `name`, `is_active`, `applies_to`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 'time_sheet', 'Legacy', 1, NULL, NULL, NULL)\n;\n",

            // The approver is gone, but the step itself still records that it happened.
            '000025_timesheet_approval_steps.sql' => "INSERT INTO `timesheet_approval_steps` (`id`, `employee_id`, `month`, `year`, `flow_id`, `step_order`, `step_key`, `approved_by_employee_id`, `approved_at`, `meta`, `created_at`, `updated_at`) VALUES\n"
                ."  (1, 9094, 1, 2025, 1, 1, 'timekeeper', 99999, '2025-02-01 00:00:00', NULL, NULL, NULL)\n;\n",
        ];

        foreach ($files as $name => $contents) {
            File::put($this->dumpPath.'/'.$name, $contents);
        }
    }
}
