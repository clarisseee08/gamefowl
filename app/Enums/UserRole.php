<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The three fixed roles in the system.
 *
 * Authorization is enforced server-side in Policies keyed off this enum -
 * never by hiding buttons in Blade.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * Plain-language label. The intended users are farm staff with low
     * technical literacy, so these avoid jargon.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner (Admin)',
            self::Staff => 'Record Keeper',
            self::Customer => 'Customer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full access, including deleting records and managing user accounts.',
            self::Staff => 'Can add and edit records, but cannot delete anything or manage users.',
            self::Customer => 'Can only view broodcock profiles, photos, health status and performance.',
        };
    }

    /** Staff who work inside the farm, as opposed to customers. */
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
