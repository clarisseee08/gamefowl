<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle state of a bird.
 *
 * `Deceased` is only ever set through the mortality-recording action, which
 * writes the mortality row and flips this status inside one DB transaction.
 */
enum BroodcockStatus: string
{
    case Active = 'active';
    case Breeding = 'breeding';
    case Resting = 'resting';
    case Retired = 'retired';
    case Sold = 'sold';
    case Deceased = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Breeding => 'Breeding',
            self::Resting => 'Resting',
            self::Retired => 'Retired',
            self::Sold => 'Sold',
            self::Deceased => 'Deceased',
        };
    }

    /** Still physically on the farm and part of the live inventory. */
    public function isOnFarm(): bool
    {
        return match ($this) {
            self::Active, self::Breeding, self::Resting, self::Retired => true,
            self::Sold, self::Deceased => false,
        };
    }

    /** Eligible to be selected as a sire or dam on a new breeding record. */
    public function isBreedingEligible(): bool
    {
        return match ($this) {
            self::Active, self::Breeding, self::Resting => true,
            default => false,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'badge-ok',
            self::Breeding => 'badge-info',
            self::Resting => 'badge-neutral',
            self::Retired => 'badge-neutral',
            self::Sold => 'badge-warn',
            self::Deceased => 'badge-alert',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
