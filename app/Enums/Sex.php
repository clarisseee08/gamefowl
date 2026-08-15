<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Sex of a bird.
 *
 * The thesis class diagram modelled only males ("broodcock"), but breeding
 * requires a hen. Rather than add a separate `hens` table, every bird is a
 * Broodcock carrying this enum - which keeps pedigree queries on a single
 * table and lets both sire_id and dam_id resolve to the same model.
 */
enum Sex: string
{
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
        };
    }

    /** Farm vocabulary, used in pedigree and breeding screens. */
    public function farmTerm(): string
    {
        return match ($this) {
            self::Male => 'Broodcock',
            self::Female => 'Broodhen',
        };
    }

    /** The parent role this sex fills in a breeding pair. */
    public function parentTerm(): string
    {
        return match ($this) {
            self::Male => 'Sire',
            self::Female => 'Dam',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
