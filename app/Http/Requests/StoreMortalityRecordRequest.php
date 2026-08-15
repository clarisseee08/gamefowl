<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Broodcock;
use App\Models\MortalityRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for recording a death.
 *
 * The rules live in a static method so the Livewire form can call
 * $this->validate(StoreMortalityRecordRequest::rulesFor(...)) instead of
 * keeping a second, drifting copy of them.
 */
final class StoreMortalityRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MortalityRecord::class) ?? false;
    }

    /**
     * Rules shared by this request and the Livewire form.
     *
     * @param  string|null  $prefix  Property prefix used by the Livewire form ('form.').
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(?Broodcock $broodcock = null, string $prefix = ''): array
    {
        return [
            $prefix.'broodcock_id' => [
                'required',
                'integer',
                // Only birds that still exist may be chosen.
                Rule::exists('broodcocks', 'id')->whereNull('deleted_at'),
                // A bird can only die once. The column is UNIQUE in Postgres;
                // checking it here turns a 500 into a sentence staff can read.
                Rule::unique('mortality_records', 'broodcock_id')->whereNull('deleted_at'),
            ],
            $prefix.'date_of_death' => array_filter([
                'required',
                'date',
                'before_or_equal:today',
                // A bird cannot die before it hatched.
                $broodcock?->date_hatched !== null
                    ? 'after_or_equal:'.$broodcock->date_hatched->toDateString()
                    : null,
            ]),
            $prefix.'cause_of_death' => ['required', 'string', 'max:255'],
            $prefix.'disposal_method' => ['nullable', 'string', 'max:120'],
            $prefix.'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $broodcock = Broodcock::find($this->input('broodcock_id'));

        return self::rulesFor($broodcock);
    }

    /**
     * Written for farm staff, not for developers.
     *
     * @return array<string, string>
     */
    public static function messagesFor(?Broodcock $broodcock = null, string $prefix = ''): array
    {
        $hatched = $broodcock?->date_hatched?->format('d M Y');

        return [
            $prefix.'broodcock_id.required' => 'Please choose which bird died.',
            $prefix.'broodcock_id.integer' => 'Please choose which bird died.',
            $prefix.'broodcock_id.exists' => 'That bird is no longer in the records. Please choose another.',
            $prefix.'broodcock_id.unique' => 'A death has already been recorded for this bird.',
            $prefix.'date_of_death.required' => 'Please enter the date the bird died.',
            $prefix.'date_of_death.date' => 'Please enter the date of death as a real date.',
            $prefix.'date_of_death.before_or_equal' => 'The date of death cannot be in the future.',
            $prefix.'date_of_death.after_or_equal' => $hatched !== null
                ? "The date of death cannot be before the bird hatched on {$hatched}."
                : 'The date of death cannot be before the bird hatched.',
            $prefix.'cause_of_death.required' => 'Please enter the cause of death.',
            $prefix.'cause_of_death.max' => 'Please keep the cause of death under 255 characters.',
            $prefix.'disposal_method.max' => 'Please keep the disposal method under 120 characters.',
            $prefix.'remarks.max' => 'Please keep the remarks under 2000 characters.',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::messagesFor(Broodcock::find($this->input('broodcock_id')));
    }

    /** @return array<string, string> */
    public static function attributesFor(string $prefix = ''): array
    {
        return [
            $prefix.'broodcock_id' => 'bird',
            $prefix.'date_of_death' => 'date of death',
            $prefix.'cause_of_death' => 'cause of death',
            $prefix.'disposal_method' => 'disposal method',
            $prefix.'remarks' => 'remarks',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return self::attributesFor();
    }
}
