<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the employee number the enforced key it has always been informally.
 *
 * Every actor in the system is an employee identified by their number: it is what
 * `users.employee_id` is derived from during the legacy import, and what the
 * personnel export keys on. Without a unique index a lookup that misses — because
 * a global scope hid an archived person, say — silently creates a second record
 * for someone who already exists, and the two rows then drift apart.
 *
 * Any such pair still present is folded back into one row before the index goes on.
 */
return new class extends Migration
{
    /**
     * Everything that points at an employee. A duplicate row may only be removed
     * once it is certain nothing refers to it.
     *
     * @var array<string, list<string>>
     */
    private const REFERENCES = [
        'time_sheets' => ['employee_id', 'admin_id', 'timekeeper_id', 'supervisor_id', 'superintendent_id'],
        'users' => ['employee_id'],
        'management_scopes' => ['manager_id', 'subordinate_employee_id'],
        'management_scope_manager' => ['manager_id'],
        'scope_policy_actors' => ['actor_employee_id'],
        'timesheet_approval_steps' => ['employee_id', 'approved_by_employee_id'],
    ];

    public function up(): void
    {
        $this->mergeDuplicates();

        Schema::table('employees', function (Blueprint $table): void {
            $table->unique('number');
        });

        // The unique index serves every lookup the plain one did.
        if ($this->hasIndex('employees_number_index')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropIndex('employees_number_index');
            });
        }
    }

    public function down(): void
    {
        if (! $this->hasIndex('employees_number_index')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->index('number', 'employees_number_index');
            });
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique(['number']);
        });
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('employees'))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }

    private function mergeDuplicates(): void
    {
        $duplicatedNumbers = DB::table('employees')
            ->whereNotNull('number')
            ->groupBy('number')
            ->havingRaw('count(*) > 1')
            ->pluck('number');

        foreach ($duplicatedNumbers as $number) {
            $rows = DB::table('employees')->where('number', $number)->orderBy('id')->get();

            $referenced = $rows->filter(fn (stdClass $row): bool => $this->isReferenced((int) $row->id));

            if ($referenced->count() > 1) {
                throw new RuntimeException(sprintf(
                    'Employee number %s is held by more than one referenced record (ids %s). Merge them by hand before running this migration.',
                    $number,
                    $referenced->pluck('id')->implode(', '),
                ));
            }

            $survivor = $referenced->first() ?? $rows->first();
            $discarded = $rows->reject(fn (stdClass $row): bool => $row->id === $survivor->id)->all();

            $this->fillGaps($survivor, $discarded);

            DB::table('employees')->whereIn('id', array_column($discarded, 'id'))->delete();
        }
    }

    private function isReferenced(int $employeeId): bool
    {
        foreach (self::REFERENCES as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column) && DB::table($table)->where($column, $employeeId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The accidental row is the newer one, so anything it knows that the surviving
     * record does not is worth keeping.
     *
     * @param  array<int, stdClass>  $discarded
     */
    private function fillGaps(stdClass $survivor, array $discarded): void
    {
        $updates = [];

        foreach ((array) $survivor as $column => $value) {
            if ($column === 'id' || $value !== null) {
                continue;
            }

            foreach ($discarded as $row) {
                if (($row->{$column} ?? null) !== null) {
                    $updates[$column] = $row->{$column};

                    break;
                }
            }
        }

        if ($updates !== []) {
            DB::table('employees')->where('id', $survivor->id)->update($updates);
        }
    }
};
