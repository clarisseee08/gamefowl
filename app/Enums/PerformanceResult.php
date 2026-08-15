<?php

declare(strict_types=1);

namespace App\Enums;

enum PerformanceResult: string
{
    case Win = 'win';
    case Loss = 'loss';
    case Draw = 'draw';
    case NotApplicable = 'na';

    public function label(): string
    {
        return match ($this) {
            self::Win => 'Win',
            self::Loss => 'Loss',
            self::Draw => 'Draw',
            self::NotApplicable => 'Not applicable',
        };
    }

    /** Counts toward win-rate statistics. Non-contest events are excluded. */
    public function countsTowardRecord(): bool
    {
        return $this !== self::NotApplicable;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Win => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            self::Loss => 'bg-rose-100 text-rose-800 ring-rose-600/20',
            self::Draw => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            self::NotApplicable => 'bg-gray-100 text-gray-700 ring-gray-500/20',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
