<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FarmSetting;
use App\Models\User;

/**
 * Who may change what the farm tells the public.
 *
 * OWNER ONLY, and a step stricter than it first looks. These five fields are
 * the farm's public identity: its name on every page and PDF, the phone number
 * the public is told to ring, the address it is told to drive to. Staff can
 * create and delete birds, which is a bigger operation on the records and a
 * smaller one on the farm - a wrong phone number is wrong for everybody who
 * visits the site until somebody notices.
 *
 * Authorized against the class rather than an instance, the same shape as
 * UserPolicy::viewAny: there is only ever one row, so which row is never the
 * question being asked.
 */
final class FarmSettingPolicy
{
    public function view(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function update(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    /**
     * Nobody, ever.
     *
     * The row is created by its migration and there must only be one, so a
     * second is not a thing the interface should be able to want. Spelled out
     * rather than left to Laravel's implicit deny, because "there is no create
     * screen" is a fact about today's views and this is a fact about the model.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /** As above: deleting the row would blank the farm's name everywhere. */
    public function delete(User $user, FarmSetting $setting): bool
    {
        return false;
    }
}
