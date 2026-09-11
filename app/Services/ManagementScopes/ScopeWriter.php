<?php

namespace App\Services\ManagementScopes;

use App\Models\ScopePolicy;
use App\Models\ScopePolicyCriterion;
use Illuminate\Support\Facades\DB;

/**
 * Saving a management scope.
 *
 * A scope is a row plus two sets of children — the values it covers and the
 * people who act under it — and both are replaced wholesale on every save, so
 * what the form shows is exactly what ends up stored. Removing the last field
 * from a scope has to actually remove it, or the scope keeps covering people
 * nobody can see it covering.
 */
class ScopeWriter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScopePolicy
    {
        return DB::transaction(function () use ($data): ScopePolicy {
            $policy = ScopePolicy::query()->create($this->attributes($data));

            $this->syncCriteria($policy, $data);
            $this->syncActors($policy, $data);

            return $policy->load(['criteria', 'actors']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ScopePolicy $policy, array $data): ScopePolicy
    {
        return DB::transaction(function () use ($policy, $data): ScopePolicy {
            $policy->update($this->attributes($data));

            $this->syncCriteria($policy, $data);
            $this->syncActors($policy, $data);

            return $policy->load(['criteria', 'actors']);
        });
    }

    public function delete(ScopePolicy $policy): void
    {
        DB::transaction(fn () => $policy->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $jobTitle = trim((string) ($data['job_title'] ?? ''));

        return [
            'name' => $data['name'] ?? null,
            'context_id' => (int) $data['context_id'],
            'covers_everyone' => (bool) ($data['covers_everyone'] ?? false),
            'carves_out_managers' => (bool) ($data['carves_out_managers'] ?? false),
            'print_location_id' => $data['print_location_id'] ?? null,
            'print_department_id' => $data['print_department_id'] ?? null,
            'print_center_id' => $data['print_center_id'] ?? null,
            'priority' => (int) ($data['priority'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'settings' => $jobTitle === '' ? null : ['job_title' => $jobTitle],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCriteria(ScopePolicy $policy, array $data): void
    {
        $rows = [];

        foreach (self::dimensionInputs() as $dimension => $input) {
            foreach ((array) ($data[$input] ?? []) as $valueId) {
                $valueId = (int) $valueId;

                if ($valueId > 0) {
                    $rows[$dimension.':'.$valueId] = [
                        'policy_id' => $policy->id,
                        'dimension' => $dimension,
                        'value_id' => $valueId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        $policy->criteria()->delete();

        if ($rows !== []) {
            ScopePolicyCriterion::query()->insert(array_values($rows));
        }

        $policy->unsetRelation('criteria');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncActors(ScopePolicy $policy, array $data): void
    {
        $managerIds = collect((array) ($data['manager_ids'] ?? []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique();

        $policy->actors()->whereNotIn('actor_employee_id', $managerIds->all())->delete();

        foreach ($managerIds as $managerId) {
            $policy->actors()->updateOrCreate(
                ['actor_employee_id' => $managerId],
                ['can_fill' => true, 'can_approve' => true, 'can_revise' => true],
            );
        }

        $policy->unsetRelation('actors');
    }

    /**
     * The form input each dimension is collected under.
     *
     * @return array<string, string>
     */
    public static function dimensionInputs(): array
    {
        return [
            ScopePolicyCriterion::FIELD => 'field_ids',
            ScopePolicyCriterion::DEPARTMENT => 'department_ids',
            ScopePolicyCriterion::CENTER => 'center_ids',
            ScopePolicyCriterion::EMPLOYEE => 'employee_ids',
        ];
    }
}
