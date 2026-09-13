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
    /**
     * Everyone can see the catalogue, including visitors who are not signed in.
     *
     * The farm advertises itself publicly, so $user is NULLABLE here and
     * throughout the read side of this policy. A null user is a guest, and a
     * guest is treated as the equivalent of an active customer - the surface
     * that role was already designed and tested against. This changes who can
     * reach a screen, never what the screen shows.
     *
     * Laravel will not even call a policy method for a guest unless the User
     * parameter is nullable; without the `?` it silently denies instead.
     */
    public function viewAny(?User $user): bool
    {
        return $user === null || $user->is_active;
    }

    /**
     * A guest may only see a bird the catalogue would already have shown them.
     *
     * Ids in URLs are guessable, so without isPubliclyVisible() a visitor could
     * walk /broodcocks/1, /broodcocks/2 and find birds that have died or that
     * belong to another farm entirely - neither of which the catalogue lists.
     */
    public function view(?User $user, Broodcock $broodcock): bool
    {
        if ($user === null) {
            return $broodcock->isPubliclyVisible();
        }

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

    /** Internal remarks and notes are hidden from customers - and from guests. */
    public function viewInternalNotes(?User $user, Broodcock $broodcock): bool
    {
        return $user !== null && $user->is_active && $user->isInternal();
    }

    /** Recording a death is a farm-staff action. */
    public function recordMortality(User $user, Broodcock $broodcock): bool
    {
        return $user->is_active
            && $user->isInternal()
            && ! $broodcock->isDeceased();
    }
}
