<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BroodcockPhoto;
use App\Models\User;

/**
 * Photos are part of the customer-facing catalogue, so anyone signed in may
 * view them; only farm staff may upload or change them.
 */
final class BroodcockPhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, BroodcockPhoto $photo): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, BroodcockPhoto $photo): bool
    {
        return $user->is_active && $user->isInternal();
    }

    /**
     * Photos are the one thing record keepers may remove - a wrong or blurry
     * upload is a routine correction, not a loss of farm history, and the
     * activity log still records the removal.
     */
    public function delete(User $user, BroodcockPhoto $photo): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function forceDelete(User $user, BroodcockPhoto $photo): bool
    {
        return false;
    }
}
