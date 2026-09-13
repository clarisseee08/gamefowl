<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthRecord;
use App\Models\User;

/**
 * Customers are explicitly promised visibility of "health status", so they may
 * view health records - but the internal `remarks` field is filtered out for
 * them at the view layer.
 */
final class HealthRecordPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user === null || $user->is_active;
    }

    public function view(?User $user, HealthRecord $record): bool
    {
        return $user === null || $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, HealthRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function delete(User $user, HealthRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, HealthRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function forceDelete(User $user, HealthRecord $record): bool
    {
        return false;
    }

    /** The vaccination schedule is a farm-management screen. */
    public function viewSchedule(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    /** Internal remarks are never shown to customers. */
    public function viewRemarks(?User $user, HealthRecord $record): bool
    {
        return $user !== null && $user->is_active && $user->isInternal();
    }
}
