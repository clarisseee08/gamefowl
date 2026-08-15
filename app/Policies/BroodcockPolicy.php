<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Broodcock;
use App\Models\User;

/**
 * Authorization for the central entity.
 *
 * Customers may browse the catalogue, but only owners and record keepers may
 * change anything, and only owners may delete. Every check here runs
 * server-side on every action - hiding a button in Blade is not authorization.
 */
final class BroodcockPolicy
{
    /** Everyone signed in can see the catalogue, including customers. */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active;
    }

    /** Only farm staff record new birds. */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active && $user->isInternal();
    }

    /** Deleting is owner-only - record keepers explicitly cannot delete. */
    public function delete(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active && $user->isOwner();
    }

    /**
     * Never. This system's selling point is a complete audit trail, so nothing
     * is ever removed from the database permanently.
     */
    public function forceDelete(User $user, Broodcock $broodcock): bool
    {
        return false;
    }

    /** Internal remarks and notes are hidden from customers. */
    public function viewInternalNotes(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active && $user->isInternal();
    }

    /** Recording a death is a farm-staff action. */
    public function recordMortality(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active
            && $user->isInternal()
            && ! $broodcock->isDeceased();
    }
}
