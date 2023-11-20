<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Stock;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the stock can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list stocks');
    }

    /**
     * Determine whether the stock can view the model.
     */
    public function view(User $user, Stock $model): bool
    {
        return $user->hasPermissionTo('view stocks');
    }

    /**
     * Determine whether the stock can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create stocks');
    }

    /**
     * Determine whether the stock can update the model.
     */
    public function update(User $user, Stock $model): bool
    {
        return $user->hasPermissionTo('update stocks');
    }

    /**
     * Determine whether the stock can delete the model.
     */
    public function delete(User $user, Stock $model): bool
    {
        return $user->hasPermissionTo('delete stocks');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete stocks');
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
