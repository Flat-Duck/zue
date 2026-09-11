<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the first of the two stores that held management scopes.
 *
 * Every scope was written to both `management_scopes` and `scope_policies` on
 * each save, and read from whichever one a feature happened to reach for. Two
 * records of the same decision is one too many when the decision is who may see
 * whose time sheet, so the legacy pair goes and `scope_policies` is the answer.
 *
 * The rows were mirrored, so nothing is lost here that the surviving tables do
 * not already hold.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('management_scope_manager');
        Schema::dropIfExists('management_scopes');
    }

    public function down(): void
    {
        Schema::create('management_scopes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('template')->default('general');
            $table->string('scope_type');
            $table->string('context')->default('general');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('center_id')->nullable();
            $table->unsignedBigInteger('subordinate_employee_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('subordinate_employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('management_scope_manager', function (Blueprint $table): void {
            $table->unsignedBigInteger('management_scope_id');
            $table->unsignedBigInteger('manager_id');

            $table->foreign('management_scope_id')->references('id')->on('management_scopes')->cascadeOnDelete();
            $table->foreign('manager_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->primary(['management_scope_id', 'manager_id'], 'msm_primary');
        });

        $this->rebuildFromPolicies();
    }

    /**
     * Rebuilds the legacy rows from the surviving ones, so a rollback lands on a
     * working pair rather than two empty tables. A scope that uses more than one
     * value in a dimension cannot be said in the old shape, so it keeps the first.
     */
    private function rebuildFromPolicies(): void
    {
        if (! Schema::hasTable('scope_policies') || ! Schema::hasTable('scope_policy_criteria')) {
            return;
        }

        foreach (DB::table('scope_policies')->get() as $policy) {
            $criteria = DB::table('scope_policy_criteria')->where('policy_id', $policy->id)->get();
            $first = fn (string $dimension) => $criteria->firstWhere('dimension', $dimension)?->value_id;
            $employees = $criteria->where('dimension', 'employee')->pluck('value_id')->values();

            $managerIds = DB::table('scope_policy_actors')
                ->where('policy_id', $policy->id)
                ->pluck('actor_employee_id');

            $scopeId = DB::table('management_scopes')->insertGetId([
                'manager_id' => $managerIds->first(),
                'name' => $policy->name,
                'template' => 'general',
                'scope_type' => match (true) {
                    (bool) $policy->covers_everyone => 'global',
                    $employees->isNotEmpty() => 'employee',
                    $first('center') !== null => 'center',
                    $first('department') !== null => 'department',
                    default => 'location',
                },
                'context' => DB::table('scope_contexts')->where('id', $policy->context_id)->value('key') ?? 'general',
                'location_id' => $first('field') ?? $policy->print_location_id,
                'department_id' => $first('department') ?? $policy->print_department_id,
                'center_id' => $first('center') ?? $policy->print_center_id,
                'subordinate_employee_id' => null,
                'settings' => json_encode(array_filter([
                    'job_title' => $policy->settings ? (json_decode($policy->settings, true)['job_title'] ?? null) : null,
                    'target_employee_ids' => $employees->isEmpty() ? null : $employees->all(),
                ], fn ($value) => ! is_null($value))),
                'created_at' => $policy->created_at,
                'updated_at' => $policy->updated_at,
            ]);

            foreach ($managerIds as $managerId) {
                DB::table('management_scope_manager')->insert([
                    'management_scope_id' => $scopeId,
                    'manager_id' => $managerId,
                ]);
            }
        }
    }
};
