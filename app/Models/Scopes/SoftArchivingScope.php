<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SoftArchivingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['Unpreserve', 'UnpreserveOrCreate', 'CreateOrUnpreserve', 'WithArchived', 'WithoutArchived', 'OnlyArchived'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereNull($model->getQualifiedArchivedAtColumn());
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getArchivedAtColumn($builder);

            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Get the "archived at" column for the builder.
     *
     * @return string
     */
    protected function getArchivedAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedArchivedAtColumn();
        }

        return $builder->getModel()->getArchivedAtColumn();
    }

    /**
     * Add the unpreserve extension to the builder.
     *
     * @return void
     */
    protected function addUnpreserve(Builder $builder)
    {
        $builder->macro('unpreserve', function (Builder $builder) {
            $builder->withArchived();

            return $builder->update([$builder->getModel()->getArchivedAtColumn() => null]);
        });
    }

    /**
     * Add the unpreserve-or-create extension to the builder.
     *
     * @return void
     */
    protected function addUnpreserveOrCreate(Builder $builder)
    {
        $builder->macro('unpreserveOrCreate', function (Builder $builder, array $attributes = [], array $values = []) {
            $builder->withArchived();

            return tap($builder->firstOrCreate($attributes, $values), function ($instance) {
                $instance->unpreserve();
            });
        });
    }

    /**
     * Add the create-or-unpreserve extension to the builder.
     *
     * @return void
     */
    protected function addCreateOrUnpreserve(Builder $builder)
    {
        $builder->macro('createOrUnpreserve', function (Builder $builder, array $attributes = [], array $values = []) {
            $builder->withArchived();

            return tap($builder->createOrFirst($attributes, $values), function ($instance) {
                $instance->unpreserve();
            });
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @return void
     */
    protected function addWithArchived(Builder $builder)
    {
        $builder->macro('withArchived', function (Builder $builder, $withArchived = true) {
            if (! $withArchived) {
                return $builder->withoutArchived();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @return void
     */
    protected function addWithoutArchived(Builder $builder)
    {
        $builder->macro('withoutArchived', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNull(
                $model->getQualifiedArchivedAtColumn()
            );

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @return void
     */
    protected function addOnlyArchived(Builder $builder)
    {
        $builder->macro('onlyArchived', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNotNull(
                $model->getQualifiedArchivedAtColumn()
            );

            return $builder;
        });
    }
}
