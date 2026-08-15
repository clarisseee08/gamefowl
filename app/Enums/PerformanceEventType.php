<?php

declare(strict_types=1);

namespace App\Enums;

enum PerformanceEventType: string
{
    case Sparring = 'sparring';
    case Conditioning = 'conditioning';
    case WeighIn = 'weigh_in';
    case Derby = 'derby';

    public function label(): string
    {
        return match ($this) {
            self::Sparring => 'Sparring',
            self::Conditioning => 'Conditioning',
            self::WeighIn => 'Weigh-in',
            self::Derby => 'Derby',
        };
    }

    /**
     * Whether a win/loss/draw outcome is meaningful for this event type.
     * A weigh-in or conditioning session has no result, so the form records
     * `na` rather than forcing a false outcome.
     */
    public function hasContestResult(): bool
    {
        return match ($this) {
            self::Sparring, self::Derby => true,
            self::Conditioning, self::WeighIn => false,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Sparring => 'badge-info',
            self::Conditioning => 'badge-ok',
            self::WeighIn => 'badge-neutral',
            self::Derby => 'badge-warn',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
