<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the stock can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list stocks');
    }

    /**
     * Determine whether the stock can view the model.
     */
    public function view(User $user, Stock $model): bool
    {
        return $user->checkPermissionTo('view stocks');
    }

    /**
     * Determine whether the stock can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create stocks');
    }

    /**
     * Determine whether the stock can update the model.
     */
    public function update(User $user, Stock $model): bool
    {
        return $user->checkPermissionTo('update stocks');
    }

    /**
     * Determine whether the stock can delete the model.
     */
    public function delete(User $user, Stock $model): bool
    {
        return $user->checkPermissionTo('delete stocks');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete stocks');
    }

    /**
     * Determine whether the stock can restore the model.
     */
    public function restore(User $user, Stock $model): bool
    {
        return false;
    }

    /**
     * Determine whether the stock can permanently delete the model.
     */
    public function forceDelete(User $user, Stock $model): bool
    {
        return false;
    }
}
