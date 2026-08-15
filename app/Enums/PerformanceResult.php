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
            self::Win => 'badge-ok',
            self::Loss => 'badge-alert',
            self::Draw => 'badge-warn',
            self::NotApplicable => 'badge-neutral',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
