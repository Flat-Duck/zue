<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A management scope: which employees somebody may see, and why.
 *
 * Coverage reads as one sentence. An employee is covered when the scope covers
 * everyone, or when they are named on it, or when they match every dimension the
 * scope filters on. Values inside a dimension are alternatives — `field in (103A,
 * 103D)` is both fields — while the dimensions narrow each other, so `field in
 * (103A)` with `department in (PROD)` is production staff at 103A and nobody else.
 *
 * A scope with no criteria and no flag covers nobody. That way a half-finished
 * form exposes nothing, which is the failure worth being careful about.
 */
class ScopePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'context_id',
        'covers_everyone',
        'carves_out_managers',
        'print_location_id',
        'print_department_id',
        'print_center_id',
        'priority',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'covers_everyone' => 'bool',
        'carves_out_managers' => 'bool',
        'is_active' => 'bool',
        'priority' => 'int',
        'settings' => 'array',
    ];

    /**
     * @return BelongsTo<ScopeContext, $this>
     */
    public function context(): BelongsTo
    {
        return $this->belongsTo(ScopeContext::class, 'context_id');
    }

    /**
     * @return HasMany<ScopePolicyCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(ScopePolicyCriterion::class, 'policy_id');
    }

    /**
     * @return HasMany<ScopePolicyActor, $this>
     */
    public function actors(): HasMany
    {
        return $this->hasMany(ScopePolicyActor::class, 'policy_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function printLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'print_location_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function printDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'print_department_id');
    }

    /**
     * @return BelongsTo<Center, $this>
     */
    public function printCenter(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'print_center_id');
    }

    /**
     * The values this scope covers in one dimension.
     *
     * @return list<int>
     */
    public function valuesFor(string $dimension): array
    {
        return $this->criteria
            ->where('dimension', $dimension)
            ->pluck('value_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The dimensions that actually narrow this scope, and what they narrow to.
     *
     * @return array<string, list<int>>
     */
    public function filters(): array
    {
        $filters = [];

        foreach (ScopePolicyCriterion::FILTERING_DIMENSIONS as $dimension) {
            $values = $this->valuesFor($dimension);

            if ($values !== []) {
                $filters[$dimension] = $values;
            }
        }

        return $filters;
    }

    /**
     * @return list<int>
     */
    public function namedEmployeeIds(): array
    {
        return $this->valuesFor(ScopePolicyCriterion::EMPLOYEE);
    }

    /**
     * Whether this scope carries a job title restriction, and what it is.
     */
    public function jobTitle(): ?string
    {
        $jobTitle = trim((string) (($this->settings['job_title'] ?? null)));

        return $jobTitle === '' ? null : $jobTitle;
    }

    public function coversEveryone(): bool
    {
        return $this->covers_everyone && $this->jobTitle() === null;
    }

    /**
     * Does this scope cover the given employee?
     */
    public function covers(Employee $employee): bool
    {
        if ($employee->archived_at) {
            return false;
        }

        if (in_array((int) $employee->id, $this->namedEmployeeIds(), true)) {
            return true;
        }

        $jobTitle = $this->jobTitle();

        if ($jobTitle !== null && trim((string) $employee->job) !== $jobTitle) {
            return false;
        }

        if ($this->covers_everyone) {
            return true;
        }

        $filters = $this->filters();

        if ($filters === []) {
            return false;
        }

        foreach ($filters as $dimension => $values) {
            $column = ScopePolicyCriterion::EMPLOYEE_COLUMNS[$dimension];

            if (! in_array((int) $employee->{$column}, $values, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The same rule as {@see covers()}, expressed as a query so the whole set can
     * be fetched at once rather than an employee at a time.
     *
     * @return Builder<Employee>
     */
    public function coveredEmployeesQuery(): Builder
    {
        $named = $this->namedEmployeeIds();
        $filters = $this->filters();
        $jobTitle = $this->jobTitle();

        $query = Employee::query()->whereNull('archived_at');

        if (! $this->covers_everyone && $filters === [] && $named === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $outer) use ($named, $filters, $jobTitle): void {
            if ($named !== []) {
                $outer->orWhereIn('id', $named);
            }

            if (! $this->covers_everyone && $filters === []) {
                return;
            }

            $outer->orWhere(function (Builder $matching) use ($filters, $jobTitle): void {
                foreach ($filters as $dimension => $values) {
                    $matching->whereIn(ScopePolicyCriterion::EMPLOYEE_COLUMNS[$dimension], $values);
                }

                if ($jobTitle !== null) {
                    $matching->where('job', $jobTitle);
                }
            });
        });
    }
}
