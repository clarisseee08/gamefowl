<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The jobs people do on a game farm, offered as a dropdown wherever a
 * position is entered (your profile, and the owner's Users form).
 *
 * A position is still free text in the database: "Other" lets anyone type
 * their own, and a position typed before this list existed keeps showing as
 * it was. The list only makes the usual answers one tap and spelled the same.
 */
final class FarmPositions
{
    /** The dropdown value that opens a text box for a position of one's own. */
    public const OTHER = '__other';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            'Farm Owner',
            'Farm Manager',
            'Farm Record Keeper',
            'Caretaker',
            'Breeder',
            'Trainer',
            'Veterinarian',
        ];
    }

    /**
     * What the dropdown should show for a saved position: the position itself
     * when it is on the list, Other for one typed by hand, nothing when blank.
     */
    public static function choiceFor(?string $position): string
    {
        $position = trim((string) $position);

        if ($position === '') {
            return '';
        }

        return in_array($position, self::all(), true) ? $position : self::OTHER;
    }
}
