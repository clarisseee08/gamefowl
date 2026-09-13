<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBroodcockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Broodcock::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * Shared rule set, so the Livewire form and this Form Request cannot drift
     * apart. Livewire components call this directly in $this->validate().
     *
     * A parent is entered one of two ways, and which one decides the rules:
     * chosen from the birds already on record (an id that must exist and be
     * the right sex), or typed in by hand as a bird the farm does not own (a
     * name, which becomes a real broodcock row before the record is written).
     * The same two-way shape the breeding form already uses.
     *
     * @param  Broodcock|null  $broodcock  The record being edited, if any -
     *                                     used to exempt itself from the
     *                                     unique band-number check and to
     *                                     prevent self-parenting.
     * @return array<string, mixed>
     */
    public static function rulesFor(
        ?Broodcock $broodcock = null,
        bool $sireIsExternal = false,
        bool $damIsExternal = false,
    ): array {
        $selfId = $broodcock?->id;

        return [
            'band_number' => [
                'nullable', 'string', 'max:64',
                Rule::unique('broodcocks', 'band_number')->ignore($selfId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'bloodline' => ['nullable', 'string', 'max:120'],
            'class' => ['required', Rule::enum(BroodcockClass::class)],
            'sex' => ['required', Rule::enum(Sex::class)],
            'date_hatched' => ['nullable', 'date', 'before_or_equal:today'],
            'date_acquired' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:date_hatched'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'color' => ['nullable', 'string', 'max:80'],
            'comb_type' => ['nullable', 'string', 'max:80'],
            'leg_color' => ['nullable', 'string', 'max:80'],
            'distinguishing_marks' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(BroodcockStatus::class)],

            'sire_is_external' => ['boolean'],
            'dam_is_external' => ['boolean'],

            // A sire must be male, a dam must be female, neither may be the
            // bird itself, and neither may be a bird descended from it. The DB
            // enforces the self-reference check too, but catching it here
            // produces a readable message instead of a constraint violation.
            //
            // When the parent is being typed in by hand there is no id yet, so
            // none of that applies - the name is what is validated, and the
            // resolved bird is re-checked for an ancestor loop after it exists.
            'sire_id' => $sireIsExternal ? ['nullable'] : [
                'nullable', 'integer',
                Rule::exists('broodcocks', 'id')->where('sex', Sex::Male->value)->whereNull('deleted_at'),
                $selfId ? Rule::notIn([$selfId]) : '',
                self::noAncestorLoop($selfId, 'sire'),
            ],
            'sire_external_name' => $sireIsExternal
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'sire_external_bloodline' => ['nullable', 'string', 'max:120'],

            'dam_id' => $damIsExternal ? ['nullable'] : [
                'nullable', 'integer',
                Rule::exists('broodcocks', 'id')->where('sex', Sex::Female->value)->whereNull('deleted_at'),
                $selfId ? Rule::notIn([$selfId]) : '',
                self::noAncestorLoop($selfId, 'dam'),
            ],
            'dam_external_name' => $damIsExternal
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'dam_external_bloodline' => ['nullable', 'string', 'max:120'],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Refuses a parent that is descended from the bird being edited.
     *
     * WHAT THIS PREVENTS. `sire_id` and `dam_id` are self-referencing keys, so
     * nothing in the schema stops A being B's sire while B is A's sire. The
     * database CHECK constraints only catch the one-step case - a bird that is
     * its own parent - and the migration that added them claims deeper cycles
     * are "prevented in the application layer, where the full ancestor chain is
     * known". They were not; this is that check.
     *
     * A cycle is not a crash - Pedigree walks a fixed three generations and
     * stops - it is worse than that. It renders a bird as its own
     * great-grandfather, silently, on the one screen the whole system is built
     * to justify.
     *
     * ONLY WHEN EDITING. A bird being created has no id yet, so nothing can be
     * descended from it and no cycle is reachable.
     */
    private static function noAncestorLoop(?int $selfId, string $label): string|Closure
    {
        if ($selfId === null) {
            return '';
        }

        return function (string $attribute, mixed $value, Closure $fail) use ($selfId, $label): void {
            if (blank($value)) {
                return;
            }

            if (self::isSelfOrDescendedFrom((int) $value, $selfId)) {
                $fail("That bird is descended from this one, so it cannot also be its {$label}.");
            }
        };
    }

    /**
     * Is $candidateId the bird $selfId, or descended from it?
     *
     * Walks UP from the candidate one generation per query rather than down
     * from the bird: ancestors are a bounded set that narrows, whereas
     * descendants fan out without limit on a productive cock.
     *
     * The visited set is not defensive padding. If a cycle already exists in
     * the data - written before this rule did - walking it without one never
     * terminates, and the first thing a keeper would do on discovering a cycle
     * is open the form to correct it.
     */
    public static function isSelfOrDescendedFrom(int $candidateId, int $selfId): bool
    {
        $seen = [];
        $frontier = [$candidateId];

        while ($frontier !== []) {
            $frontier = array_values(array_diff(array_unique($frontier), $seen));

            if ($frontier === []) {
                return false;
            }

            if (in_array($selfId, $frontier, true)) {
                return true;
            }

            $seen = [...$seen, ...$frontier];

            $frontier = Broodcock::query()
                ->whereIn('id', $frontier)
                ->get(['sire_id', 'dam_id'])
                ->flatMap(fn (Broodcock $bird): array => array_filter([$bird->sire_id, $bird->dam_id]))
                ->all();
        }

        return false;
    }

    /**
     * Plain-language field names. These appear inside validation messages, and
     * the people reading them are farm staff, not developers.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /** @return array<string, string> */
    public static function attributeNames(): array
    {
        return [
            'band_number' => 'band number',
            'name' => 'name',
            'bloodline' => 'bloodline',
            'class' => 'class',
            'sex' => 'sex',
            'date_hatched' => 'date hatched',
            'date_acquired' => 'date acquired',
            'weight' => 'weight',
            'color' => 'colour',
            'comb_type' => 'comb type',
            'leg_color' => 'leg colour',
            'distinguishing_marks' => 'distinguishing marks',
            'status' => 'status',
            'sire_id' => 'sire (father)',
            'dam_id' => 'dam (mother)',
            'sire_external_name' => 'sire name',
            'dam_external_name' => 'dam name',
            'sire_external_bloodline' => 'sire bloodline',
            'dam_external_bloodline' => 'dam bloodline',
            'notes' => 'notes',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::messageOverrides();
    }

    /** @return array<string, string> */
    public static function messageOverrides(): array
    {
        return [
            'name.required' => 'Please enter a name for this bird.',
            'band_number.unique' => 'Another bird already uses that band number.',
            'sire_id.exists' => 'Please choose a male bird as the sire.',
            'dam_id.exists' => 'Please choose a female bird as the dam.',
            'sire_id.not_in' => 'A bird cannot be its own sire.',
            'dam_id.not_in' => 'A bird cannot be its own dam.',
            'sire_external_name.required' => 'Please enter the name of the sire.',
            'dam_external_name.required' => 'Please enter the name of the dam.',
            'date_hatched.before_or_equal' => 'The hatch date cannot be in the future.',
            'date_acquired.after_or_equal' => 'The bird cannot have been acquired before it hatched.',
            'weight.min' => 'Weight cannot be a negative number.',
        ];
    }
}
