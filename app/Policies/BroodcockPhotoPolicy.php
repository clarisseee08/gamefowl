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
    public function viewAny(?User $user): bool
    {
        return $user === null || $user->is_active;
    }

    /**
     * A guest may only fetch a photo of a bird they are allowed to see.
     *
     * Photo ids are sequential, so this is the difference between "the public
     * catalogue has pictures" and "anyone can page through every photograph the
     * farm has ever taken, including of birds that died".
     *
     * loadMissing(), not the relation directly: Model::shouldBeStrict() turns an
     * un-eager-loaded relation into an exception, and this policy is reached
     * from a controller that only ever loaded the photo. It costs one indexed
     * primary-key lookup per image request, and only for guests.
     */
    public function view(?User $user, BroodcockPhoto $photo): bool
    {
        if ($user === null) {
            $photo->loadMissing('broodcock');

            return $photo->broodcock?->isPubliclyVisible() ?? false;
        }

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
