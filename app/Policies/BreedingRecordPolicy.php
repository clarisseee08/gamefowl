<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BreedingRecord;
use App\Models\User;

/**
 * Breeding data is internal farm information. Customers browsing the catalogue
 * have no business seeing mating records or fertility figures, so viewAny and
 * view are restricted to owners and record keepers.
 */
final class BreedingRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function view(User $user, BreedingRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, BreedingRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function delete(User $user, BreedingRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, BreedingRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function forceDelete(User $user, BreedingRecord $record): bool
    {
        return false;
    }

    /** Generating offspring rows from a hatch writes new broodcocks. */
    public function generateOffspring(User $user, BreedingRecord $record): bool
    {
        return $user->is_active
            && $user->isInternal()
            && $record->hasUnregisteredOffspring();
    }
}
