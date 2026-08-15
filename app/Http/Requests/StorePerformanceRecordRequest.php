<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\PerformanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePerformanceRecordRequest extends FormRequest
{
    /**
     * The single definition of what a valid performance record is.
     *
     * Exposed statically so App\Livewire\Performance\Form validates against
     * exactly these rules instead of a second copy that would drift.
     *
     * NOTE: `recorded_by` is deliberately absent. It is taken from the
     * authenticated user inside the Action and is never accepted from input.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(): array
    {
        return [
            'broodcock_id' => ['required', 'integer', Rule::exists('broodcocks', 'id')->whereNull('deleted_at')],
            'event_date' => ['required', 'date', 'before_or_equal:today'],
            'event_type' => ['required', Rule::enum(PerformanceEventType::class)],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'result' => ['required', Rule::enum(PerformanceResult::class)],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            // Mirrors the Postgres CHECK constraint `performance_rating_range`.
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Plain field names, so staff read "Please enter the date of the event"
     * rather than "The event_date field is required."
     *
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'broodcock_id' => 'bird',
            'event_date' => 'date of the event',
            'event_type' => 'type of event',
            'weight' => 'weight',
            'result' => 'result',
            'duration_seconds' => 'duration',
            'rating' => 'rating',
            'remarks' => 'remarks',
        ];
    }

    /** @return array<string, string> */
    public static function messageOverrides(): array
    {
        return [
            'broodcock_id.required' => 'Please choose which bird this record is for.',
            'broodcock_id.exists' => 'That bird is no longer on file. Please choose another.',
            'event_date.required' => 'Please enter the date of the event.',
            'event_date.before_or_equal' => 'The date of the event cannot be in the future.',
            'event_type.required' => 'Please choose the type of event.',
            'weight.numeric' => 'Please enter the weight in kilograms, for example 2.10.',
            'weight.min' => 'The weight cannot be less than zero.',
            'result.required' => 'Please choose the result.',
            'duration_seconds.integer' => 'Please enter the duration as a whole number of seconds.',
            'duration_seconds.min' => 'The duration cannot be less than zero.',
            'rating.between' => 'Please give a rating from 1 to 5 stars, or leave it blank.',
            'remarks.max' => 'Please keep the remarks under 2,000 characters.',
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', PerformanceRecord::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::messageOverrides();
    }

    /**
     * A weigh-in or conditioning session has no winner. Normalising here as
     * well as in the UI means a hand-crafted POST cannot record a weigh-in as
     * a "win" just because the selector was hidden client-side.
     */
    protected function prepareForValidation(): void
    {
        $type = PerformanceEventType::tryFrom((string) $this->input('event_type'));

        if ($type !== null && ! $type->hasContestResult()) {
            $this->merge(['result' => PerformanceResult::NotApplicable->value]);
        }
    }
}
