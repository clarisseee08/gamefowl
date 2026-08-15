<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthRecordType: string
{
    case Vaccination = 'vaccination';
    case Medication = 'medication';
    case Checkup = 'checkup';
    case Treatment = 'treatment';
    case Deworming = 'deworming';

    public function label(): string
    {
        return match ($this) {
            self::Vaccination => 'Vaccination',
            self::Medication => 'Medication',
            self::Checkup => 'Check-up',
            self::Treatment => 'Treatment',
            self::Deworming => 'Deworming',
        };
    }

    /**
     * Types that normally recur on a schedule, so the form should prompt for
     * a next due date. This drives the vaccination compliance report.
     */
    public function expectsNextDueDate(): bool
    {
        return match ($this) {
            self::Vaccination, self::Deworming => true,
            default => false,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Vaccination => 'badge-ok',
            self::Medication => 'badge-info',
            self::Checkup => 'badge-neutral',
            self::Treatment => 'badge-warn',
            self::Deworming => 'badge-info',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
