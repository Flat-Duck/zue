<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A `LIKE` search across a model's columns, plus the narrow-by filters the index
 * screens use.
 *
 * Removed from this trait: `scopeWithArchived()` and `scopeWithoutArchived()`.
 * They filtered on `archived_at`, which only `Employee` has — on the other fourteen
 * models using this trait, calling either raised "Unknown column 'archived_at'".
 * On `Employee` they never ran at all: `SoftArchivingScope` registers builder
 * macros of the same names, and a macro takes precedence over a local scope, so the
 * working implementations were always the ones on that scope.
 */
trait Searchable
{
    /**
     * Search the columns named by the model's `$searchableFields`.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            foreach ($this->getSearchableFields() as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeByDepartment(Builder $query, int|string|null $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeByCenter(Builder $query, int|string|null $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeByLocation(Builder $query, int|string|null $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * The fields to search. An unset or empty `$searchableFields`, or one whose first
     * entry is `*`, means every column on the table.
     *
     * @return list<string>
     */
    protected function getSearchableFields(): array
    {
        if (isset($this->searchableFields) && count($this->searchableFields)) {
            return $this->searchableFields[0] === '*'
                ? $this->getAllModelTableFields()
                : array_values($this->searchableFields);
        }

        return $this->getAllModelTableFields();
    }

    /**
     * @return list<string>
     */
    protected function getAllModelTableFields(): array
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());
    }
}
