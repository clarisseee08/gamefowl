<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Quality grade the farm assigns to a bird (see interview Q2 in the thesis).
 */
enum BroodcockClass: string
{
    case ClassA = 'class_a';
    case ClassB = 'class_b';
    case Ordinary = 'ordinary';

    public function label(): string
    {
        return match ($this) {
            self::ClassA => 'Class A',
            self::ClassB => 'Class B',
            self::Ordinary => 'Ordinary',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ClassA => 'Top grade - best bloodline and conformation.',
            self::ClassB => 'Good grade - suitable for breeding.',
            self::Ordinary => 'Standard grade.',
        };
    }

    /** Tailwind badge classes, so grade styling is defined in exactly one place. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::ClassA => 'badge-ok',
            self::ClassB => 'badge-info',
            self::Ordinary => 'badge-neutral',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
