<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Reports aggregate farm-wide data, including breeding and mortality figures
 * customers must never see. Generation and history are internal-only.
 */
final class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->is_active && $user->isInternal();
    }

    /** Running a report writes an audit row, so this is the "generate" gate. */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function update(User $user, Report $report): bool
    {
        return false;   // Generated audit rows are immutable by design.
    }

    public function delete(User $user, Report $report): bool
    {
        return false;   // Deleting the audit trail would defeat its purpose.
    }

    public function forceDelete(User $user, Report $report): bool
    {
        return false;
    }
}
