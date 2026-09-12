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

    /** @var array<string, list<string>> */
    private array $schemaColumns = [];

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
        DB::connection()->disableQueryLog();
        DB::connection()->flushQueryLog();

        $this->progress = $progress;

        $this->copy('locations', 'locations');
        $this->copy('administrations', 'administrations');
        $this->copy('centers', 'centers');
        $this->copy('departments', 'departments');

        $this->copy('appraisal_forms', 'appraisal_forms');
        $this->copy('appraisal_items', 'appraisal_items');
        $this->copy('appraisal_form_versions', 'appraisal_form_versions');
        $this->copy('appraisal_form_version_items', 'appraisal_form_version_items');
        $this->copy('appraisal_periods', 'appraisal_periods');
        $this->copy('approval_flows', 'approval_flows');
        $this->copy('approval_flow_steps', 'approval_flow_steps');

        $this->importEmployees();
        $this->importUsers();
        $this->importSignatures();
        $this->importRoleAssignments();

        $this->importScopePolicies();
        $this->importScopePolicyActors();
        $this->copy('appraisal_reviews', 'appraisal_reviews', requiredEmployees: ['employee_id', 'appraiser_id']);
        $this->copy('appraisal_review_scores', 'appraisal_review_scores');
        $this->copy('appraisals_official', 'appraisals_official', requiredEmployees: ['employee_id'], optionalEmployees: ['finalized_by']);
        $this->copy('appraisal_official_scores', 'appraisal_official_scores');
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
            $employeeId = $this->intOrNull(isset($profile['employee_id']) ? (string) $profile['employee_id'] : null);

            if ($employeeId !== null && ! $this->dryRun && ! DB::table('employees')->where('id', $employeeId)->exists()) {
                $this->recordOrphan('employee_details', 'employee_id', $employeeId, $this->stringifyRow($profile));

                continue;
            }

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
     * Legacy backups may carry the pre-context scope shape. The current design
     * stores match values in scope_policy_criteria, so the importer reshapes each
     * old row while preserving policy ids and writing every old filter as a
     * criterion. If a dump already has the current columns, it is copied as-is.
     */
    private function importScopePolicies(): void
    {
        $criteria = [];
        $contexts = DB::table('scope_contexts')->pluck('id', 'key')->all();

        if ($this->files('scope_policies') !== []) {
            $this->copy('scope_policies', 'scope_policies', transform: function (array $row) use (&$criteria, $contexts): array {
                if (array_key_exists('context_id', $row)) {
                    return $row;
                }

                $policyId = (int) $row['id'];
                $context = (string) ($row['context'] ?? 'general');
                $matchType = (string) ($row['match_type'] ?? 'global');

                $this->appendScopeCriteria($criteria, $policyId, $matchType, $row);

                return [
                    'id' => $row['id'],
                    'name' => $row['name'] ?? null,
                    'context_id' => (string) ($contexts[$context] ?? $contexts['general']),
                    'covers_everyone' => $matchType === 'global' ? '1' : '0',
                    'carves_out_managers' => $context === 'time_sheet' ? '1' : '0',
                    'print_location_id' => $row['location_id'] ?? null,
                    'print_department_id' => $row['department_id'] ?? null,
                    'print_center_id' => $row['center_id'] ?? null,
                    'priority' => $row['priority'] ?? '0',
                    'is_active' => $row['is_active'] ?? '1',
                    'settings' => $row['settings'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ];
            });
        } else {
            $this->importPoliciesFromLegacyManagementScopes($criteria, $contexts);
        }

        $this->importScopeCriteria($criteria);
    }

    /**
     * @param  list<array<string, string|int|null>>  $criteria
     * @param  array<string, int>  $contexts
     */
    private function importPoliciesFromLegacyManagementScopes(array &$criteria, array $contexts): void
    {
        $this->copy('management_scopes', 'scope_policies', transform: function (array $row) use (&$criteria, $contexts): array {
            $policyId = (int) $row['id'];
            $context = (string) ($row['context'] ?? 'general');
            $matchType = $this->scopeTypeToMatchType((string) ($row['scope_type'] ?? 'global'));

            $legacyRow = $row + [
                'match_type' => $matchType,
                'target_employee_ids' => $this->targetEmployeesFromManagementScope($row),
            ];

            $this->appendScopeCriteria($criteria, $policyId, $matchType, $legacyRow);

            return [
                'id' => $row['id'],
                'name' => $row['name'] ?? null,
                'context_id' => (string) ($contexts[$context] ?? $contexts['general']),
                'covers_everyone' => $matchType === 'global' ? '1' : '0',
                'carves_out_managers' => $context === 'time_sheet' ? '1' : '0',
                'print_location_id' => $row['location_id'] ?? null,
                'print_department_id' => $row['department_id'] ?? null,
                'print_center_id' => $row['center_id'] ?? null,
                'priority' => '0',
                'is_active' => '1',
                'settings' => $row['settings'] ?? null,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }, requiredEmployees: [], optionalEmployees: []);
    }

    private function importScopePolicyActors(): void
    {
        if ($this->files('scope_policy_actors') !== []) {
            $this->copy('scope_policy_actors', 'scope_policy_actors', requiredEmployees: ['actor_employee_id']);

            return;
        }

        $this->copy('management_scope_manager', 'scope_policy_actors', uniqueBy: ['policy_id', 'actor_employee_id'], transform: fn (array $row): array => [
            'policy_id' => $row['management_scope_id'],
            'actor_employee_id' => $row['manager_id'],
            'can_fill' => '1',
            'can_approve' => '1',
            'can_revise' => '1',
            'role_hint' => 'legacy-manager',
            'created_at' => null,
            'updated_at' => null,
        ], requiredEmployees: ['actor_employee_id']);
    }

    /**
     * @param  list<array<string, string|int|null>>  $criteria
     * @param  array<string, string|null>  $row
     */
    private function appendScopeCriteria(array &$criteria, int $policyId, string $matchType, array $row): void
    {
        $add = function (string $dimension, string|int|null $valueId) use (&$criteria, $policyId, $row): void {
            $id = $this->intOrNull($valueId === null ? null : (string) $valueId);

            if ($id === null || $id <= 0) {
                return;
            }

            $criteria[] = [
                'policy_id' => $policyId,
                'dimension' => $dimension,
                'value_id' => $id,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        };

        if (in_array($matchType, ['location', 'department'], true)) {
            $add('field', $row['location_id'] ?? null);
        }

        if ($matchType === 'department') {
            $add('department', $row['department_id'] ?? null);
        }

        if ($matchType === 'center') {
            $add('center', $row['center_id'] ?? null);
        }

        foreach ($this->decodeEmployeeIds($row['target_employee_ids'] ?? null) as $employeeId) {
            $add('employee', $employeeId);
        }
    }

    /**
     * @param  list<array<string, string|int|null>>  $criteria
     */
    private function importScopeCriteria(array $criteria): void
    {
        $imported = 0;
        $batch = [];

        foreach ($criteria as $criterion) {
            $batch[] = $criterion;
            $imported++;

            if (count($batch) >= $this->chunkSize) {
                $this->flush('scope_policy_criteria', $batch, ['policy_id', 'dimension', 'value_id'], null);
                $batch = [];
                $this->reportProgress('scope_policy_criteria', $imported);
            }
        }

        if ($batch !== []) {
            $this->flush('scope_policy_criteria', $batch, ['policy_id', 'dimension', 'value_id'], null);
        }

        $this->record('scope_policy_criteria', $imported);
    }

    private function scopeTypeToMatchType(string $scopeType): string
    {
        return match ($scopeType) {
            'field', 'location' => 'location',
            'department' => 'department',
            'center' => 'center',
            'employee' => 'employee',
            default => 'global',
        };
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function targetEmployeesFromManagementScope(array $row): ?string
    {
        if (($row['subordinate_employee_id'] ?? null) !== null) {
            return '['.$row['subordinate_employee_id'].']';
        }

        $settings = json_decode((string) ($row['settings'] ?? ''), true);

        if (is_array($settings) && isset($settings['target_employee_ids'])) {
            return json_encode($settings['target_employee_ids']);
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function decodeEmployeeIds(string|int|null $encoded): array
    {
        if ($encoded === null || $encoded === '') {
            return [];
        }

        $decoded = json_decode((string) $encoded, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
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

            $legacyUserId = $this->intOrNull($row['user_id'] ?? null);

            if ($legacyUserId !== null) {
                $newUserId = $this->identity->newUserId($legacyUserId);

                if ($newUserId === null) {
                    $this->recordOrphan('time_sheets', 'user_id', $legacyUserId, $row);
                }

                $row['user_id'] = $newUserId === null ? null : (string) $newUserId;
            }

            return $row;
        }, update: [
            'value',
            'day',
            'employee_id',
            'revised_at',
            'old_value',
            'user_id',
            'timekeeper_id',
            'supervisor_id',
            'superintendent_id',
            'admin_id',
            'over_time',
            'created_at',
            'updated_at',
        ], requiredEmployees: ['employee_id'], optionalEmployees: ['timekeeper_id', 'supervisor_id', 'superintendent_id', 'admin_id']);
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

                $row = $this->onlyCurrentColumns($table, $row);

                if ($row === []) {
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

            if ($employeeId !== null && ! $this->employeeExists($employeeId)) {
                $this->recordOrphan($table, $column, $employeeId, $row);

                return null;
            }
        }

        foreach ($optional as $column) {
            $employeeId = $this->intOrNull($row[$column] ?? null);

            if ($employeeId !== null && ! $this->employeeExists($employeeId)) {
                $this->recordOrphan($table, $column, $employeeId, $row);
                $row[$column] = null;
            }
        }

        return $row;
    }

    /**
     * @param  array<string, string|int|null>  $row
     * @return array<string, string|null>
     */
    private function stringifyRow(array $row): array
    {
        return collect($row)
            ->map(fn (string|int|null $value): ?string => $value === null ? null : (string) $value)
            ->all();
    }

    private function employeeExists(int $employeeId): bool
    {
        if ($this->dryRun) {
            return $this->identity->hasEmployee($employeeId);
        }

        return DB::table('employees')->where('id', $employeeId)->exists();
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function recordOrphan(string $table, string $column, int $employeeId, array $row): void
    {
        $key = $table.'.'.$column;
        $this->orphanedReferences[$key][$employeeId] = ($this->orphanedReferences[$key][$employeeId] ?? 0) + 1;

        if ($this->dryRun || ! Schema::hasTable('legacy_import_orphans')) {
            return;
        }

        $payload = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('legacy_import_orphans')->insertOrIgnore([
            'source_table' => $table,
            'source_column' => $column,
            'missing_employee_id' => $employeeId,
            'legacy_key' => isset($row['id']) ? (string) $row['id'] : null,
            'row_hash' => hash('sha256', $table.'|'.$column.'|'.$employeeId.'|'.($payload ?: '')),
            'row_payload' => $payload ?: '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
            DB::connection()->flushQueryLog();
            gc_collect_cycles();

            return;
        }

        $update ??= array_values(array_diff(array_keys($batch[0]), $uniqueBy));

        DB::table($table)->upsert($batch, $uniqueBy, $update);
        DB::connection()->flushQueryLog();
        gc_collect_cycles();
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

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    private function onlyCurrentColumns(string $table, array $row): array
    {
        if (! isset($this->schemaColumns[$table])) {
            $this->schemaColumns[$table] = Schema::getColumnListing($table);
        }

        return array_intersect_key($row, array_flip($this->schemaColumns[$table]));
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
