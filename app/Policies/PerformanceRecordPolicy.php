<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PerformanceRecord;
use App\Models\User;

/**
 * Customers are promised visibility of performance history, so viewing is open
 * to all active accounts; internal remarks are filtered at the view layer.
 */
final class PerformanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, PerformanceRecord $record): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, PerformanceRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function delete(User $user, PerformanceRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, PerformanceRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function forceDelete(User $user, PerformanceRecord $record): bool
    {
        return false;
    }

    public function viewRemarks(User $user, PerformanceRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }
}
