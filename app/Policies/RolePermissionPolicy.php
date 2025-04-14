<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        // Prevent modifying core roles
        if ($model instanceof Role && in_array($model->name, ['admin', 'driver', 'customer'])) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        // Prevent deleting core roles
        if ($model instanceof Role && in_array($model->name, ['admin', 'driver', 'customer'])) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $model): bool
    {
        // Prevent deleting core roles
        if ($model instanceof Role && in_array($model->name, ['admin', 'driver', 'customer'])) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
