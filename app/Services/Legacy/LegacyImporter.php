<?php

namespace App\Services\Legacy;

use App\Models\User;
use App\Services\Employees\ProfileDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the legacy dump into the current schema, reshaping it on the way in.
 *
 * Two things change shape. Users lose their hand-assigned primary key and gain a
 * real `employee_id`, which means everything that pointed at a user id has to be
 * translated. Time sheet `admin_id` held an employee *number* and now holds an
 * employee id. Everything else is carried across as-is, because employee ids are
 * preserved and every other foreign key in the dump ultimately points at one.
 */
class LegacyImporter
{
    /**
     * Owned by PermissionsSeeder or of no lasting value. Importing these would
     * fight the seeder for primary keys.
     */
    private const SKIPPED_TABLES = [
        'roles' => 'roles are created by PermissionsSeeder; assignments are matched by name instead',
        'permissions' => 'permissions are created by PermissionsSeeder',
        'role_has_permissions' => 'derived from PermissionsSeeder',
        'backup_logs' => 'operational log with no historical value',
    ];

    private LegacyIdentityMap $identity;

    /** @var array<string, int> */
    private array $rowCounts = [];

    /** @var list<array{user_id: int, email: string}> */
    private array $emailConflicts = [];

    /** @var array<int, int> */
    private array $matchRankCounts = [];

    /** @var array<int, true> */
    private array $unresolvedReviserNumbers = [];

    /** @var array<string, array<int, int>> "table.column" => [employee id => rows] */
    private array $orphanedReferences = [];

    private int $timeSheetsWithReviser = 0;

    /** @var (callable(string, int): void)|null */
    private $progress = null;

    public function __construct(
        private readonly string $dumpPath,
        private readonly bool $dryRun = false,
        private readonly bool $withPasswords = false,
        private readonly int $chunkSize = 1000,
    ) {
        $this->identity = new LegacyIdentityMap;
    }

    /**
     * @param  (callable(string, int): void)|null  $progress
     */
    public function run(?callable $progress = null): LegacyImportReport
    {
        $this->progress = $progress;

        $this->copy('locations', 'locations');
        $this->copy('administrations', 'administrations');
        $this->copy('centers', 'centers');
        $this->copy('departments', 'departments');

        $this->copy('appraisal_periods', 'appraisal_periods');
        $this->copy('approval_flows', 'approval_flows');
        $this->copy('approval_flow_steps', 'approval_flow_steps');

        $this->importEmployees();
        $this->importUsers();
        $this->importSignatures();
        $this->importRoleAssignments();

        $this->copy('management_scopes', 'management_scopes', requiredEmployees: ['manager_id'], optionalEmployees: ['subordinate_employee_id']);
        $this->copy('management_scope_manager', 'management_scope_manager', uniqueBy: [], requiredEmployees: ['manager_id']);
        $this->copy('scope_policies', 'scope_policies');
        $this->copy('scope_policy_actors', 'scope_policy_actors', requiredEmployees: ['actor_employee_id']);
        $this->copy('timesheet_approval_steps', 'timesheet_approval_steps', requiredEmployees: ['employee_id'], optionalEmployees: ['approved_by_employee_id']);

        $this->importTimeSheets();

        return new LegacyImportReport(
            dryRun: $this->dryRun,
            rowCounts: $this->rowCounts,
            skipped: self::SKIPPED_TABLES,
            unresolvedUsers: $this->identity->unresolvedUsers(),
            identityConflicts: $this->identity->conflicts(),
            emailConflicts: $this->emailConflicts,
            matchRankCounts: $this->matchRankCounts,
            unresolvedReviserNumbers: array_keys($this->unresolvedReviserNumbers),
            timeSheetsWithReviser: $this->timeSheetsWithReviser,
            orphanedReferences: $this->orphanedReferences,
        );
    }

    public function identity(): LegacyIdentityMap
    {
        return $this->identity;
    }

    /**
     * Employee ids are preserved verbatim. `user_id` is dropped: the column no
     * longer exists, and it was NULL on every row in the dump anyway, which is
     * precisely why the actor link had to be rebuilt from numbers.
     *
     * The employee row was split in two after this importer was written: the HR
     * profile moved to `employee_details`. Seven of the dump's columns belong on
     * that side now, so each row is divided as it is read and the profile halves
     * are written once the employees they hang off exist.
     */
    private function importEmployees(): void
    {
        $detailColumns = array_values(array_intersect(
            Schema::getColumnListing('employee_details'),
            ProfileDefinition::detailFields(),
        ));

        $profiles = [];

        $this->copy('employees', 'employees', drop: ['user_id'], transform: function (array $row) use ($detailColumns, &$profiles): array {
            $employeeId = (int) $row['id'];

            $this->identity->registerEmployee($employeeId, $this->intOrNull($row['number'] ?? null));

            $profile = [];

            foreach ($detailColumns as $column) {
                if (array_key_exists($column, $row)) {
                    $profile[$column] = $row[$column];
                    unset($row[$column]);
                }
            }

            if ($profile !== []) {
                $profiles[] = $profile + [
                    'employee_id' => $employeeId,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ];
            }

            return $row;
        });

        $this->importEmployeeDetails($profiles);
    }

    /**
     * @param  list<array<string, string|int|null>>  $profiles
     */
    private function importEmployeeDetails(array $profiles): void
    {
        $imported = 0;
        $batch = [];

        foreach ($profiles as $profile) {
            $batch[] = $profile;
            $imported++;

            if (count($batch) >= $this->chunkSize) {
                $this->flush('employee_details', $batch, ['employee_id'], null);
                $batch = [];
                $this->reportProgress('employee_details', $imported);
            }
        }

        if ($batch !== []) {
            $this->flush('employee_details', $batch, ['employee_id'], null);
        }

        $this->record('employee_details', $imported);
    }

    private function importUsers(): void
    {
        $files = $this->files('users');
        $rows = [];

        foreach ($files as $file) {
            foreach ((new LegacyDumpReader($file))->rows() as $row) {
                $rows[] = $row;
            }
        }

        foreach ($rows as $row) {
            $this->identity->registerUser(
                (int) $row['id'],
                $this->intOrNull($row['number'] ?? null),
                $row['name'] ?? null,
                $row['email'] ?? null,
            );
        }

        $imported = 0;

        foreach ($rows as $row) {
            $legacyId = (int) $row['id'];
            $employeeId = $this->identity->employeeIdForUser($legacyId);

            if ($employeeId === null) {
                continue;
            }

            $rank = $this->identity->matchRankForUser($legacyId) ?? 0;
            $this->matchRankCounts[$rank] = ($this->matchRankCounts[$rank] ?? 0) + 1;

            $email = $row['email'] ?? null;

            if ($email !== null && $this->emailTakenByAnotherEmployee($email, $employeeId)) {
                $this->emailConflicts[] = ['user_id' => $legacyId, 'email' => $email];

                continue;
            }

            $attributes = [
                'employee_id' => $employeeId,
                'name' => $row['name'] ?? null,
                'email' => $email,
                'email_verified_at' => $row['email_verified_at'] ?? null,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];

            if ($this->withPasswords) {
                $attributes['password'] = $row['password'];
                $attributes['remember_token'] = $row['remember_token'] ?? null;
            }

            $imported++;

            if ($this->dryRun) {
                $this->identity->recordNewUserId($legacyId, $legacyId);

                continue;
            }

            $existingId = DB::table('users')->where('employee_id', $employeeId)->value('id');

            if ($existingId !== null) {
                // An account that already exists was set up deliberately — by the
                // super admin seeder, or by a previous run someone has since changed.
                // Its password is theirs, not the dump's.
                unset($attributes['password'], $attributes['remember_token']);

                DB::table('users')->where('id', $existingId)->update($attributes);
                $this->identity->recordNewUserId($legacyId, (int) $existingId);

                continue;
            }

            $attributes['password'] ??= Hash::make(Str::random(40));

            $this->identity->recordNewUserId($legacyId, DB::table('users')->insertGetId($attributes));
        }

        $this->record('users', $imported);
    }

    private function importSignatures(): void
    {
        $this->copy('signatures', 'signatures', transform: function (array $row): ?array {
            $newUserId = $this->identity->newUserId((int) $row['user_id']);

            if ($newUserId === null) {
                return null;
            }

            $row['user_id'] = (string) $newUserId;

            return $row;
        });
    }

    /**
     * Role assignments are re-pointed at whatever ids PermissionsSeeder produced,
     * matched by role name, so the import never depends on the dump's role ids.
     */
    private function importRoleAssignments(): void
    {
        $legacyRoleNames = [];

        foreach ($this->files('roles') as $file) {
            foreach ((new LegacyDumpReader($file))->rows() as $row) {
                $legacyRoleNames[(int) $row['id']] = $row['name'];
            }
        }

        $currentRoleIds = DB::table('roles')->pluck('id', 'name')->all();

        $this->copy('model_has_roles', 'model_has_roles', uniqueBy: [], transform: function (array $row) use ($legacyRoleNames, $currentRoleIds): ?array {
            $name = $legacyRoleNames[(int) $row['role_id']] ?? null;

            if ($name === null || ! isset($currentRoleIds[$name])) {
                return null;
            }

            if ($row['model_type'] !== User::class) {
                return null;
            }

            $newUserId = $this->identity->newUserId((int) $row['model_id']);

            if ($newUserId === null) {
                return null;
            }

            return [
                'role_id' => (string) $currentRoleIds[$name],
                'model_type' => $row['model_type'],
                'model_id' => (string) $newUserId,
            ];
        });
    }

    /**
     * The one thing the office actually needs out of the old data: who revised a
     * day. `admin_id` held an employee number, and the new foreign key wants an
     * employee id, so every value is translated and anything that fails to
     * translate is reported rather than quietly nulled without trace.
     */
    private function importTimeSheets(): void
    {
        $this->copy('time_sheets', 'time_sheets', transform: function (array $row): array {
            $reviser = $this->intOrNull($row['admin_id'] ?? null);

            if ($reviser !== null) {
                $this->timeSheetsWithReviser++;
                $employeeId = $this->identity->employeeIdForNumber($reviser);

                if ($employeeId === null) {
                    $this->unresolvedReviserNumbers[$reviser] = true;
                }

                $row['admin_id'] = $employeeId === null ? null : (string) $employeeId;
            }

            return $row;
        }, update: ['value', 'day', 'employee_id', 'revised_at', 'admin_id'], requiredEmployees: ['employee_id']);
    }

    /**
     * @param  list<string>  $drop
     * @param  (callable(array<string, string|null>): (array<string, string|null>|null))|null  $transform
     * @param  list<string>  $uniqueBy  empty means the table has no single-row key, so rows are inserted once and never updated
     * @param  list<string>|null  $update
     * @param  list<string>  $requiredEmployees  columns whose employee must exist, or the row is meaningless and is dropped
     * @param  list<string>  $optionalEmployees  columns whose employee may be missing, in which case the reference is cleared
     */
    private function copy(
        string $fileSuffix,
        string $table,
        array $drop = [],
        ?callable $transform = null,
        array $uniqueBy = ['id'],
        ?array $update = null,
        array $requiredEmployees = [],
        array $optionalEmployees = [],
    ): void {
        $files = $this->files($fileSuffix);

        if ($files === []) {
            $this->record($table, 0);

            return;
        }

        $imported = 0;
        $batch = [];

        foreach ($files as $file) {
            foreach ((new LegacyDumpReader($file))->rows() as $row) {
                foreach ($drop as $column) {
                    unset($row[$column]);
                }

                if ($transform !== null) {
                    $row = $transform($row);

                    if ($row === null) {
                        continue;
                    }
                }

                $row = $this->guardEmployeeReferences($table, $row, $requiredEmployees, $optionalEmployees);

                if ($row === null) {
                    continue;
                }

                $batch[] = $row;
                $imported++;

                if (count($batch) >= $this->chunkSize) {
                    $this->flush($table, $batch, $uniqueBy, $update);
                    $batch = [];
                    $this->reportProgress($table, $imported);
                }
            }
        }

        if ($batch !== []) {
            $this->flush($table, $batch, $uniqueBy, $update);
        }

        $this->record($table, $imported);
    }

    /**
     * The legacy database never enforced these foreign keys, so it holds time sheets
     * belonging to employees that were deleted out from under them. Those rows are
     * reported by employee id rather than dropped in silence, because a fuller
     * employee export is usually all that is needed to recover them.
     *
     * @param  array<string, string|null>  $row
     * @param  list<string>  $required
     * @param  list<string>  $optional
     * @return array<string, string|null>|null
     */
    private function guardEmployeeReferences(string $table, array $row, array $required, array $optional): ?array
    {
        foreach ($required as $column) {
            $employeeId = $this->intOrNull($row[$column] ?? null);

            if ($employeeId !== null && ! $this->identity->hasEmployee($employeeId)) {
                $this->recordOrphan($table, $column, $employeeId);

                return null;
            }
        }

        foreach ($optional as $column) {
            $employeeId = $this->intOrNull($row[$column] ?? null);

            if ($employeeId !== null && ! $this->identity->hasEmployee($employeeId)) {
                $this->recordOrphan($table, $column, $employeeId);
                $row[$column] = null;
            }
        }

        return $row;
    }

    private function recordOrphan(string $table, string $column, int $employeeId): void
    {
        $key = $table.'.'.$column;
        $this->orphanedReferences[$key][$employeeId] = ($this->orphanedReferences[$key][$employeeId] ?? 0) + 1;
    }

    /**
     * @param  list<array<string, string|null>>  $batch
     * @param  list<string>  $uniqueBy
     * @param  list<string>|null  $update
     */
    private function flush(string $table, array $batch, array $uniqueBy, ?array $update): void
    {
        if ($this->dryRun || $batch === []) {
            return;
        }

        if ($uniqueBy === []) {
            DB::table($table)->insertOrIgnore($batch);

            return;
        }

        $update ??= array_values(array_diff(array_keys($batch[0]), $uniqueBy));

        DB::table($table)->upsert($batch, $uniqueBy, $update);
    }

    /**
     * @return list<string>
     */
    private function files(string $suffix): array
    {
        $matches = glob($this->dumpPath.'/*_'.$suffix.'.sql') ?: [];

        $matches = array_values(array_filter(
            $matches,
            static fn (string $path): bool => (bool) preg_match('/^\d{6}_'.preg_quote($suffix, '/').'\.sql$/', basename($path)),
        ));

        sort($matches, SORT_NATURAL);

        return $matches;
    }

    private function emailTakenByAnotherEmployee(string $email, int $employeeId): bool
    {
        return DB::table('users')
            ->where('email', $email)
            ->where('employee_id', '!=', $employeeId)
            ->exists();
    }

    private function intOrNull(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("Expected a numeric legacy identifier, got: {$value}");
        }

        return (int) $value;
    }

    private function record(string $table, int $count): void
    {
        $this->rowCounts[$table] = $count;
        $this->reportProgress($table, $count);
    }

    private function reportProgress(string $table, int $count): void
    {
        if ($this->progress !== null) {
            ($this->progress)($table, $count);
        }
    }
}
