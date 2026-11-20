<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How closely two birds are related, read from the COI their chicks would
 * have (see App\Support\Inbreeding), in the comparisons breeders already use.
 *
 * Line-breeding is deliberate and common in gamefowl, so none of these blocks
 * a mating; the words say how close the pair is and leave the call to the
 * breeder.
 */
enum Relatedness: string
{
    case Unrelated = 'unrelated';
    case Distant = 'distant';
    case Cousins = 'cousins';
    case HalfSiblings = 'half_siblings';
    case Close = 'close';

    public static function fromCoefficient(float $coefficient): self
    {
        return match (true) {
            $coefficient >= 0.25 => self::Close,
            $coefficient >= 0.125 => self::HalfSiblings,
            $coefficient >= 0.0625 => self::Cousins,
            $coefficient > 0.0 => self::Distant,
            default => self::Unrelated,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unrelated => 'No shared ancestors on record',
            self::Distant => 'Distantly related',
            self::Cousins => 'Related',
            self::HalfSiblings => 'Closely related',
            self::Close => 'Very closely related',
        };
    }

    public function explanation(): string
    {
        return match ($this) {
            self::Unrelated => 'None of the recorded ancestors of these two birds are the same bird.',
            self::Distant => 'They share an ancestor several generations back.',
            self::Cousins => 'About as close as first cousins.',
            self::HalfSiblings => 'About as close as half-siblings, or a grandparent and grandchild.',
            self::Close => 'About as close as full siblings, or a parent and its own offspring.',
        };
    }

    /** Full literal strings only, or the production build drops them. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Unrelated => 'd-badge-success',
            self::Distant => 'd-badge-neutral',
            self::Cousins => 'd-badge-info',
            self::HalfSiblings => 'd-badge-warning',
            self::Close => 'd-badge-error',
        };
    }
}
