<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pen;
use App\Models\User;

/**
 * Pen assignments are internal farm logistics - not part of the customer
 * catalogue.
 */
final class PenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function view(User $user, Pen $pen): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, Pen $pen): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function delete(User $user, Pen $pen): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, Pen $pen): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function forceDelete(User $user, Pen $pen): bool
    {
        return false;
    }
}
