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
            self::Vaccination => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            self::Medication => 'bg-sky-100 text-sky-800 ring-sky-600/20',
            self::Checkup => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            self::Treatment => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            self::Deworming => 'bg-violet-100 text-violet-800 ring-violet-600/20',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
