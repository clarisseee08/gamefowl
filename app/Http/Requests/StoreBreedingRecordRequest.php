<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBreedingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BreedingRecord::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * Shared rules, used by both this Form Request and the Livewire component
     * so the two cannot drift apart.
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(?BreedingRecord $record = null): array
    {
        return [
            'sire_id' => [
                'required', 'integer',
                Rule::exists('broodcocks', 'id')->where('sex', Sex::Male->value)->whereNull('deleted_at'),
            ],
            'dam_id' => [
                'required', 'integer', 'different:sire_id',
                Rule::exists('broodcocks', 'id')->where('sex', Sex::Female->value)->whereNull('deleted_at'),
            ],
            'mating_date' => ['required', 'date', 'before_or_equal:today'],

            // The egg funnel. These mirror the DB CHECK constraints exactly -
            // validation gives a readable message, the constraint guarantees
            // the rule even if a future code path forgets to validate.
            'eggs_set' => ['required', 'integer', 'min:0', 'max:1000'],
            'eggs_fertile' => ['required', 'integer', 'min:0', 'lte:eggs_set'],
            'eggs_hatched' => ['required', 'integer', 'min:0', 'lte:eggs_fertile'],
            'offspring_count' => ['required', 'integer', 'min:0', 'lte:eggs_hatched'],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Cross-field checks that need the loaded models rather than raw input.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                self::validateMatingDateAgainstParents($validator, $this->validated());
            },
        ];
    }

    /**
     * A mating cannot predate either parent's hatch date. Shared so the
     * Livewire component can run the same check.
     *
     * @param  array<string, mixed>  $data
     */
    public static function validateMatingDateAgainstParents(Validator $validator, array $data): void
    {
        $matingDate = $data['mating_date'] ?? null;

        if ($matingDate === null) {
            return;
        }

        foreach (['sire_id' => 'sire', 'dam_id' => 'dam'] as $field => $label) {
            $parentId = $data[$field] ?? null;

            if ($parentId === null) {
                continue;
            }

            $parent = Broodcock::query()->find($parentId);

            if ($parent?->date_hatched !== null && $parent->date_hatched->gt($matingDate)) {
                $validator->errors()->add(
                    'mating_date',
                    "The mating date cannot be before the {$label} was hatched ({$parent->date_hatched->format('j M Y')})."
                );
            }
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /** @return array<string, string> */
    public static function attributeNames(): array
    {
        return [
            'sire_id' => 'sire (father)',
            'dam_id' => 'dam (mother)',
            'mating_date' => 'mating date',
            'eggs_set' => 'eggs set',
            'eggs_fertile' => 'fertile eggs',
            'eggs_hatched' => 'eggs hatched',
            'offspring_count' => 'offspring registered',
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
            'sire_id.required' => 'Please choose the sire (father).',
            'sire_id.exists' => 'The sire must be a male bird on record.',
            'dam_id.required' => 'Please choose the dam (mother).',
            'dam_id.exists' => 'The dam must be a female bird on record.',
            'dam_id.different' => 'The sire and dam must be two different birds.',
            'eggs_fertile.lte' => 'Fertile eggs cannot be more than the number of eggs set.',
            'eggs_hatched.lte' => 'Eggs hatched cannot be more than the number of fertile eggs.',
            'offspring_count.lte' => 'You cannot register more offspring than the number of eggs that hatched.',
            'mating_date.before_or_equal' => 'The mating date cannot be in the future.',
        ];
    }
}
