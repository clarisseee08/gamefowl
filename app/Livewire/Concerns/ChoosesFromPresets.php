<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * A form field offered as a dropdown of the farm's usual values, with an
 * "Others" entry that opens a box for anything not listed.
 *
 * The saved value stays a plain column (`color`, `weight`, ...). Beside it the
 * component keeps a `*_choice` property for the dropdown: a listed value
 * copies itself into the column, and OTHER leaves the box to fill it. An old
 * record whose value is not on the list opens under "Others", text intact.
 */
trait ChoosesFromPresets
{
    /** The "Others" entry's value. Not a value anyone would type. */
    public static string $presetOther = '__other__';

    /**
     * Which dropdown entry a saved value belongs under.
     *
     * @param  list<string>  $options
     */
    protected function presetChoiceFor(?string $value, array $options, bool $numeric = false): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        foreach ($options as $option) {
            // A weight of "2.00" from the database is the listed "2.0".
            if ($numeric ? (is_numeric($value) && (float) $option === (float) $value) : $option === $value) {
                return $option;
            }
        }

        return self::$presetOther;
    }

    /**
     * The value a dropdown change writes into the column. "Others" empties the
     * box for typing - unless it already holds custom text, which switching
     * back to "Others" must not throw away.
     *
     * @param  list<string>  $options
     */
    protected function presetValueFor(string $choice, ?string $current, array $options): ?string
    {
        if ($choice === self::$presetOther) {
            return in_array((string) $current, $options, true) ? null : $current;
        }

        return $choice === '' ? null : $choice;
    }
}
