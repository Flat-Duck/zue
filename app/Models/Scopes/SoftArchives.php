<?php

namespace App\Models\Scopes;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutTrashed()
 */
trait SoftArchives
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftArchives()
    {
        static::addGlobalScope(new SoftArchivingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftArchives()
    {
        if (! isset($this->casts[$this->getArchivedAtColumn()])) {
            $this->casts[$this->getArchivedAtColumn()] = 'datetime';
        }
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftArchive()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getArchivedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getArchivedAtColumn()} = $time;

        if ($this->usesTimestamps() && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('preserved', false);
    }

    /**
     * Restore a soft-archived model instance.
     *
     * @return bool
     */
    public function unpreserve()
    {
        // If the unpreserving event does not return false, we will proceed with this
        // unpreserve operation. Otherwise, we bail out so the developer will stop
        // the unpreserve totally. We will clear the archived timestamp and save.
        if ($this->fireModelEvent('unpreserving') === false) {
            return false;
        }

        $this->{$this->getArchivedAtColumn()} = null;

        // Once we have saved the model, we will fire the "unpreserved" event so this
        // developer will do anything they need to after a unpreserve operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('unpreserved', false);

        return $result;
    }

    /**
     * Restore a soft-archived model instance without raising any events.
     *
     * @return bool
     */
    public function unpreserveQuietly()
    {
        return static::withoutEvents(fn () => $this->unpreserve());
    }

    /**
     * Determine if the model instance has been soft-archived.
     *
     * @return bool
     */
    public function preserved()
    {
        return ! is_null($this->{$this->getArchivedAtColumn()});
    }

    /**
     * Register a "softArchived" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function softArchived($callback)
    {
        static::registerModelEvent('preserved', $callback);
    }

    /**
     * Register a "unpreserving" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unpreserving($callback)
    {
        static::registerModelEvent('unpreserving', $callback);
    }

    /**
     * Register a "unpreserved" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unpreserved($callback)
    {
        static::registerModelEvent('unpreserved', $callback);
    }

    /**
     * Get the name of the "archived at" column.
     *
     * @return string
     */
    public function getArchivedAtColumn()
    {
        return defined(static::class.'::ARCHIVED_AT') ? static::ARCHIVED_AT : 'archived_at';
    }

    /**
     * Get the fully qualified "archived at" column.
     *
     * @return string
     */
    public function getQualifiedArchivedAtColumn()
    {
        return $this->qualifyColumn($this->getArchivedAtColumn());
    }
}
