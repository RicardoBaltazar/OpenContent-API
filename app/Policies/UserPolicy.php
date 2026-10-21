<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can list users.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Administrador;
    }

    /**
     * Determine whether the user can view the target user.
     */
    public function view(User $user, User $target): bool
    {
        return $user->role === UserRole::Administrador;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Administrador;
    }

    /**
     * Determine whether the user can update the target user.
     */
    public function update(User $user, User $target): bool
    {
        return $user->role === UserRole::Administrador;
    }

    /**
     * Determine whether the user can activate or deactivate the target user.
     */
    public function toggleActive(User $user, User $target): bool
    {
        return $user->role === UserRole::Administrador;
    }
}
