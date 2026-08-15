<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MortalityRecord;
use App\Models\User;

/**
 * Mortality is internal farm information - customers do not see it.
 */
final class MortalityRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function view(User $user, MortalityRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, MortalityRecord $record): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function delete(User $user, MortalityRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, MortalityRecord $record): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function forceDelete(User $user, MortalityRecord $record): bool
    {
        return false;
    }
}
