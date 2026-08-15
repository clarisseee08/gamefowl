<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * User management is owner-only, in full. Record keepers and customers cannot
 * see the account list at all, let alone change roles.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function view(User $user, User $model): bool
    {
        // An owner sees anyone; anyone may see their own account.
        return $user->is_active && ($user->isOwner() || $user->is($model));
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function update(User $user, User $model): bool
    {
        return $user->is_active && $user->isOwner();
    }

    /** Users may edit their own profile without being an owner. */
    public function updateOwnProfile(User $user, User $model): bool
    {
        return $user->is_active && $user->is($model);
    }

    /**
     * An owner cannot delete their own account - that is how a system ends up
     * with no administrator.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->is_active && $user->isOwner() && ! $user->is($model);
    }

    /** Likewise, an owner must not be able to lock themselves out. */
    public function deactivate(User $user, User $model): bool
    {
        return $user->is_active && $user->isOwner() && ! $user->is($model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
