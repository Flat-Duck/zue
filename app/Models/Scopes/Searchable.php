<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Search paginated items ordering by ID descending
     */
    public function scopeSearchLatestPaginated(
        Builder $query,
        string $search,
        int $paginationQuantity = 10
    ): Builder
    {
        return $query
            ->search($search)
            ->orderBy('updated_at', 'desc')
            ->paginate($paginationQuantity);
    }

    /**
     * Adds a scope to search the table based on the
     * $searchableFields array inside the model
     */
    public function scopeWithArchived(Builder $query): Builder
    {
        return $query->orWhereNotNull('archived_at');
    }
    /**
     * Adds a scope to search the table based on the
     * $searchableFields array inside the model
     */
    public function scopeWithoutArchived(Builder $query): Builder
    {
        return $query->WhereNull('archived_at');
    }

    /**
     * Adds a scope to search the table based on the
     * $searchableFields array inside the model
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $query->where(function ($query) use ($search) {
            foreach ($this->getSearchableFields() as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });

        return $query;
    }

    /**
     * Returns the searchable fields. If $searchableFields is undefined,
     * or is an empty array, or its first element is '*', it will search
     * in all table fields
     */
    protected function getSearchableFields(): array
    {
        if (isset($this->searchableFields) && count($this->searchableFields)) {
            return $this->searchableFields[0] === '*'
                ? $this->getAllModelTableFields()
                : $this->searchableFields;
        }

        return $this->getAllModelTableFields();
    }

    /**
     * Gets all fields from Model's table
     */
    protected function getAllModelTableFields(): array
    {
        $tableName = $this->getTable();

        return $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($tableName);
    }

        /**
     * Adds a scope to search the table based on the
     * $searchableFields array inside the model
     */
    public function scopeByDepartment(Builder $query, $department_id): Builder
    {
        return $query->Where('department_id',$department_id);
    }
        /**
     * Adds a scope to search the table based on the
     * $searchableFields array inside the model
     */
    public function scopeByCenter(Builder $query, $center_id): Builder
    {
        return $query->Where('center_id',$center_id);
    }
        /**
     * Adds a scope to search the table based on the
     * $searchableFields array inside the model
     */
    public function scopeByLocation(Builder $query, $location_id): Builder
    {
        return $query->Where('location_id',$location_id);
    }
}
