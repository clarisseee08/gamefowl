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
            self::Vaccination => 'd-badge-success',
            self::Medication => 'd-badge-info',
            self::Checkup => 'd-badge-neutral',
            self::Treatment => 'd-badge-warning',
            self::Deworming => 'd-badge-info',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
