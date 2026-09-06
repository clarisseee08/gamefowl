<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
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
     * @param  Broodcock|null  $broodcock  The record being edited, if any -
     *                                     used to exempt itself from the
     *                                     unique band-number check and to
     *                                     prevent self-parenting.
     * @return array<string, mixed>
     */
    public static function rulesFor(?Broodcock $broodcock = null): array
    {
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

            // A sire must be male, a dam must be female, and neither may be
            // the bird itself. The DB enforces the self-reference check too,
            // but catching it here produces a readable message instead of a
            // constraint-violation exception.
            'sire_id' => [
                'nullable', 'integer',
                Rule::exists('broodcocks', 'id')->where('sex', Sex::Male->value)->whereNull('deleted_at'),
                $selfId ? Rule::notIn([$selfId]) : '',
            ],
            'dam_id' => [
                'nullable', 'integer',
                Rule::exists('broodcocks', 'id')->where('sex', Sex::Female->value)->whereNull('deleted_at'),
                $selfId ? Rule::notIn([$selfId]) : '',
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
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
            'date_hatched.before_or_equal' => 'The hatch date cannot be in the future.',
            'date_acquired.after_or_equal' => 'The bird cannot have been acquired before it hatched.',
            'weight.min' => 'Weight cannot be a negative number.',
        ];
    }
}
