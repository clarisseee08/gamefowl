<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Who may work through the visit requests.
 *
 * ASKING is public and has no policy - the form on the front page is open to
 * anyone, which is the point of it. This governs the other half: the queue of
 * everybody's requests is farm business, so it is internal only.
 *
 * A customer is denied deliberately rather than by omission. They may ask to
 * visit; they may not read what other people asked, which would expose names,
 * phone numbers and who is interested in which bird.
 */
final class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->isInternal();
    }

    /** Requests are created by the public, never from inside the console. */
    public function create(User $user): bool
    {
        return false;
    }

    /** Confirming and declining is the whole of updating one. */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->isInternal();
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
