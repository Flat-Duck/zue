<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives a management scope more than one value per dimension, and makes the
 * context it applies to a record rather than a string.
 *
 * A scope could only ever name a single field, which is why a dispatcher — who
 * books travellers out of 103A *and* 103D — could not be expressed at all. The
 * single `match_type` forced the same choice again: a scope was a field, or a
 * department, or a list of people, never a field and a department together, even
 * though every department scope in use is exactly that.
 *
 * So the shape inverts. A scope holds criteria: any number of them, in any of the
 * three dimensions. Values within a dimension are alternatives; dimensions narrow
 * each other. Named individuals are added on top rather than replacing the
 * filters, because "all of Gas Plant at 103A, plus these two from elsewhere" is a
 * real thing to want.
 *
 * The field, department and centre that employee-type scopes carried were never
 * used for matching — they are the heading on the printed sheet. They move to
 * columns that say so, where they cannot quietly narrow who a scope covers.
 */
return new class extends Migration
{
    /**
     * The contexts the application already asks for by name, plus the two the
     * company named as coming next. A context is why a scope exists, so the list
     * is expected to grow — it is data, and editable.
     *
     * @var list<array{key: string, name: string, name_ar: string, carves_out_managers: bool}>
     */
    private const CONTEXTS = [
        ['key' => 'time_sheet', 'name' => 'Timesheet', 'name_ar' => 'بطاقة الوقت', 'carves_out_managers' => true],
        ['key' => 'dispatcher', 'name' => 'Dispatcher', 'name_ar' => 'الترحيل', 'carves_out_managers' => false],
        ['key' => 'general', 'name' => 'General', 'name_ar' => 'عام', 'carves_out_managers' => false],
        ['key' => 'leave', 'name' => 'Leave', 'name_ar' => 'الإجازات', 'carves_out_managers' => false],
        ['key' => 'attendance', 'name' => 'Attendance', 'name_ar' => 'الحضور', 'carves_out_managers' => false],
    ];

    public function up(): void
    {
        Schema::create('scope_contexts', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->boolean('carves_out_managers')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        foreach (self::CONTEXTS as $order => $context) {
            DB::table('scope_contexts')->insert($context + [
                'is_active' => true,
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::create('scope_policy_criteria', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->enum('dimension', ['field', 'department', 'center', 'employee']);
            $table->unsignedBigInteger('value_id');
            $table->timestamps();

            $table->unique(['policy_id', 'dimension', 'value_id'], 'scope_policy_criterion_unique');
            $table->index(['dimension', 'value_id'], 'scope_policy_criterion_lookup');

            $table->foreign('policy_id')->references('id')->on('scope_policies')->cascadeOnDelete();
        });

        Schema::table('scope_policies', function (Blueprint $table): void {
            $table->unsignedBigInteger('context_id')->nullable()->after('name');
            $table->boolean('covers_everyone')->default(false)->after('context_id');
            $table->boolean('carves_out_managers')->default(false)->after('covers_everyone');
            $table->unsignedBigInteger('print_location_id')->nullable()->after('carves_out_managers');
            $table->unsignedBigInteger('print_department_id')->nullable()->after('print_location_id');
            $table->unsignedBigInteger('print_center_id')->nullable()->after('print_department_id');
        });

        $this->moveExistingScopes();

        Schema::table('scope_policies', function (Blueprint $table): void {
            $table->foreign('context_id')->references('id')->on('scope_contexts')->restrictOnDelete();
            $table->foreign('print_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('print_department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('print_center_id')->references('id')->on('centers')->nullOnDelete();

            $table->dropForeign(['location_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['center_id']);
            $table->dropColumn(['match_type', 'location_id', 'department_id', 'center_id', 'target_employee_ids', 'context']);
        });
    }

    /**
     * Every scope in use is one of three shapes, and each becomes criteria:
     *
     *   department  field + department        -> two criteria, which narrow each other
     *   center      centre                    -> one criterion
     *   employee    a list of people          -> one criterion per person, and the
     *                                            field/department/centre it carried
     *                                            move to the print-header columns
     */
    private function moveExistingScopes(): void
    {
        $contexts = DB::table('scope_contexts')->pluck('id', 'key');

        foreach (DB::table('scope_policies')->get() as $policy) {
            $criteria = [];

            $add = function (string $dimension, $valueId) use (&$criteria, $policy): void {
                if ($valueId !== null && (int) $valueId > 0) {
                    $criteria[] = [
                        'policy_id' => $policy->id,
                        'dimension' => $dimension,
                        'value_id' => (int) $valueId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            };

            $isEmployeeScope = $policy->match_type === 'employee';

            if (! $isEmployeeScope) {
                if (in_array($policy->match_type, ['location', 'department'], true)) {
                    $add('field', $policy->location_id);
                }

                if ($policy->match_type === 'department') {
                    $add('department', $policy->department_id);
                }

                if ($policy->match_type === 'center') {
                    $add('center', $policy->center_id);
                }
            }

            foreach ((array) json_decode((string) $policy->target_employee_ids, true) as $employeeId) {
                $add('employee', $employeeId);
            }

            if ($criteria !== []) {
                DB::table('scope_policy_criteria')->insert($criteria);
            }

            DB::table('scope_policies')->where('id', $policy->id)->update([
                'context_id' => $contexts[$policy->context] ?? $contexts['general'],
                'covers_everyone' => $policy->match_type === 'global',
                // The rule was hard-wired to the time sheet, so that is where it stays.
                'carves_out_managers' => $policy->context === 'time_sheet',
                // Every scope keeps its heading, not just the employee ones. On a
                // department scope the department was doing both jobs at once —
                // deciding who is covered and titling the printed sheet — and only
                // the first of those becomes a criterion.
                'print_location_id' => $policy->location_id,
                'print_department_id' => $policy->department_id,
                'print_center_id' => $policy->center_id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('scope_policies', function (Blueprint $table): void {
            $table->string('context')->default('general')->after('name');
            $table->enum('match_type', ['global', 'location', 'department', 'center', 'employee'])->default('global')->after('context');
            $table->unsignedBigInteger('location_id')->nullable()->after('match_type');
            $table->unsignedBigInteger('department_id')->nullable()->after('location_id');
            $table->unsignedBigInteger('center_id')->nullable()->after('department_id');
            $table->json('target_employee_ids')->nullable()->after('center_id');
        });

        $this->restoreExistingScopes();

        Schema::table('scope_policies', function (Blueprint $table): void {
            $table->index('context');
            $table->index('match_type');
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();

            $table->dropForeign(['context_id']);
            $table->dropForeign(['print_location_id']);
            $table->dropForeign(['print_department_id']);
            $table->dropForeign(['print_center_id']);
            $table->dropColumn([
                'context_id', 'covers_everyone', 'carves_out_managers',
                'print_location_id', 'print_department_id', 'print_center_id',
            ]);
        });

        Schema::dropIfExists('scope_policy_criteria');
        Schema::dropIfExists('scope_contexts');
    }

    /**
     * Folds the criteria back into the single-value columns. A scope that used the
     * new shape — several fields, or a field and a list of people — cannot be
     * expressed by the old one, so the first value of each dimension is kept and
     * the rest are lost. That is what rolling this back means.
     */
    private function restoreExistingScopes(): void
    {
        $keys = DB::table('scope_contexts')->pluck('key', 'id');

        foreach (DB::table('scope_policies')->get() as $policy) {
            $criteria = DB::table('scope_policy_criteria')->where('policy_id', $policy->id)->get();

            $first = fn (string $dimension) => $criteria->firstWhere('dimension', $dimension)?->value_id;
            $employees = $criteria->where('dimension', 'employee')->pluck('value_id')->map(fn ($id): int => (int) $id)->values();

            $matchType = match (true) {
                (bool) $policy->covers_everyone => 'global',
                $employees->isNotEmpty() => 'employee',
                $first('center') !== null => 'center',
                $first('department') !== null => 'department',
                $first('field') !== null => 'location',
                default => 'global',
            };

            DB::table('scope_policies')->where('id', $policy->id)->update([
                'context' => $keys[$policy->context_id] ?? 'general',
                'match_type' => $matchType,
                // The heading is the fallback: a department scope kept its centre
                // only as a print header, so reading criteria alone would drop it.
                'location_id' => $first('field') ?? $policy->print_location_id,
                'department_id' => $first('department') ?? $policy->print_department_id,
                'center_id' => $first('center') ?? $policy->print_center_id,
                'target_employee_ids' => $employees->isEmpty() ? null : $employees->toJson(),
            ]);
        }
    }
};
