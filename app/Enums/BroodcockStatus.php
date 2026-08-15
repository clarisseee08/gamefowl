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
            self::Active => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            self::Breeding => 'bg-violet-100 text-violet-800 ring-violet-600/20',
            self::Resting => 'bg-sky-100 text-sky-800 ring-sky-600/20',
            self::Retired => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            self::Sold => 'bg-orange-100 text-orange-800 ring-orange-600/20',
            self::Deceased => 'bg-rose-100 text-rose-800 ring-rose-600/20',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
