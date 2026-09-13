<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a request to visit the farm has got to.
 *
 * Four states and no transitions enforced in code, because the real workflow
 * happens on the telephone and the screen only records what the owner decided.
 * Forcing a state machine here would mean the record disagreeing with the
 * conversation the moment a visitor rings back to change their mind.
 */
enum AppointmentStatus: string
{
    /** Nobody has acted on it yet. This is what the queue opens on. */
    case Pending = 'pending';

    /** The farm has agreed a visit. */
    case Confirmed = 'confirmed';

    /** The farm cannot take this one. */
    case Declined = 'declined';

    /** They came. Kept rather than deleted so the farm has a visit history. */
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting reply',
            self::Confirmed => 'Confirmed',
            self::Declined => 'Declined',
            self::Completed => 'Visited',
        };
    }

    /**
     * The badge vocabulary is a contract with resources/css/app.css and must
     * only ever return classes that file defines - Tailwind emits nothing and
     * warns about nothing for one it does not, so a wrong name here renders an
     * unstyled pill and fails no build.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'badge badge-info',
            self::Confirmed => 'badge badge-ok',
            self::Declined => 'badge badge-alert',
            self::Completed => 'badge badge-neutral',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
