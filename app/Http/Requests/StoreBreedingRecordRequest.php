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
        return self::rulesFor(
            null,
            $this->boolean('sire_is_external'),
            $this->boolean('dam_is_external'),
        );
    }

    /**
     * Shared rules, used by both this Form Request and the Livewire component
     * so the two cannot drift apart.
     *
     * A parent is entered one of two ways, and which one decides the rules:
     * chosen from the farm's own birds (an id that must exist), or typed as an
     * outside bird the farm does not own (a name, which is turned into a real
     * broodcock row before the record is written). Exactly one of the pair is
     * required on each side.
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(
        ?BreedingRecord $record = null,
        bool $sireIsExternal = false,
        bool $damIsExternal = false,
    ): array {
        return [
            'sire_is_external' => ['boolean'],
            'dam_is_external' => ['boolean'],

            'sire_id' => $sireIsExternal
                ? ['nullable']
                : [
                    'required', 'integer',
                    Rule::exists('broodcocks', 'id')->where('sex', Sex::Male->value)->whereNull('deleted_at'),
                ],

            'sire_external_name' => $sireIsExternal
                ? ['required', 'string', 'max:120']
                : ['nullable', 'string', 'max:120'],

            'sire_external_bloodline' => ['nullable', 'string', 'max:120'],

            'dam_id' => $damIsExternal
                ? ['nullable']
                : [
                    'required', 'integer', 'different:sire_id',
                    Rule::exists('broodcocks', 'id')->where('sex', Sex::Female->value)->whereNull('deleted_at'),
                ],

            'dam_external_name' => $damIsExternal
                ? ['required', 'string', 'max:120']
                : ['nullable', 'string', 'max:120'],

            'dam_external_bloodline' => ['nullable', 'string', 'max:120'],

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
     * An outside parent has no hatch date on file, so there is nothing to check
     * and the loop simply skips it.
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
            'sire_external_name' => 'sire name',
            'dam_external_name' => 'dam name',
            'sire_external_bloodline' => 'sire bloodline',
            'dam_external_bloodline' => 'dam bloodline',
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
            'sire_external_name.required' => 'Please enter the name of the outside sire.',
            'dam_external_name.required' => 'Please enter the name of the outside dam.',
            'eggs_fertile.lte' => 'Fertile eggs cannot be more than the number of eggs set.',
            'eggs_hatched.lte' => 'Eggs hatched cannot be more than the number of fertile eggs.',
            'offspring_count.lte' => 'You cannot register more offspring than the number of eggs that hatched.',
            'mating_date.before_or_equal' => 'The mating date cannot be in the future.',
        ];
    }
}
