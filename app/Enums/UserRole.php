<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The two fixed roles in the system. Only the farm's own people have
 * accounts; the public - including would-be buyers - browse the catalogue and
 * every bird's page as guests, without signing in.
 *
 * Authorization is enforced server-side in Policies keyed off this enum -
 * never by hiding buttons in Blade.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';

    /**
     * Plain-language label. The intended users are farm staff with low
     * technical literacy, so these avoid jargon.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner (Admin)',
            self::Staff => 'Record Keeper',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full access, including deleting records and managing user accounts.',
            self::Staff => 'Can add and edit records, but cannot delete anything or manage users.',
        };
    }

    /**
     * Someone who works at the farm. Every role is, now that customers have
     * no accounts; the policies still ask, so the rule reads "farm people
     * only" in each of them and stays true if a role is ever added.
     */
    public function isInternal(): bool
    {
        return $this === self::Owner || $this === self::Staff;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
