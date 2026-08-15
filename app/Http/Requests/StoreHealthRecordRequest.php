<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\HealthRecordType;
use App\Models\HealthRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a new health record.
 *
 * The rule set, the plain-language field names and the plain-language messages
 * all live in static methods so the Livewire form can reuse them verbatim via
 * $this->validate(). One definition, two callers - the HTTP path and the
 * Livewire path can never drift apart.
 *
 * `recorded_by` is deliberately absent from the rules: the acting user is
 * stamped by the Action from the authenticated session, never accepted from
 * request input.
 */
final class StoreHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HealthRecord::class) ?? false;
    }

    /**
     * Shared rules, callable from both this Form Request and the Livewire form.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(): array
    {
        return [
            'broodcock_id' => ['required', 'integer', Rule::exists('broodcocks', 'id')->whereNull('deleted_at')],
            'record_type' => ['required', Rule::enum(HealthRecordType::class)],
            'product_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:120'],
            'checkup_date' => ['required', 'date', 'before_or_equal:today'],
            // Mirrors the Postgres CHECK constraint health_next_due_after_checkup,
            // which SQLite (the test database) cannot express.
            'next_due_date' => ['nullable', 'date', 'after_or_equal:checkup_date'],
            'condition' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Plain-language field names. Farm staff read these, so they must never
     * contain column names or validator jargon.
     *
     * @return array<string, string>
     */
    public static function attributesFor(): array
    {
        return [
            'broodcock_id' => 'bird',
            'record_type' => 'record type',
            'product_name' => 'product name',
            'dosage' => 'dosage',
            'checkup_date' => 'check-up date',
            'next_due_date' => 'next due date',
            'condition' => 'condition',
            'remarks' => 'remarks',
        ];
    }

    /** @return array<string, string> */
    public static function messagesFor(): array
    {
        return [
            'broodcock_id.required' => 'Please choose which bird this record is for.',
            'broodcock_id.exists' => 'That bird is no longer on file. Please choose another one.',
            'record_type.required' => 'Please choose the type of record.',
            'checkup_date.required' => 'Please enter the date of the check-up.',
            'checkup_date.date' => 'Please enter the check-up date as a real date.',
            'checkup_date.before_or_equal' => 'The check-up date cannot be in the future.',
            'next_due_date.date' => 'Please enter the next due date as a real date.',
            'next_due_date.after_or_equal' => 'The next due date must be on or after the check-up date.',
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return self::attributesFor();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::messagesFor();
    }
}
