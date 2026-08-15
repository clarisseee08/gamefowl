<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\PerformanceRecord;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePerformanceRecordRequest extends FormRequest
{
    /**
     * Editing a record has the same shape as creating one, so the rules are
     * delegated rather than copied - a copy is a rule that will drift.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(): array
    {
        return StorePerformanceRecordRequest::rulesFor();
    }

    /** @return array<string, string> */
    public static function attributeNames(): array
    {
        return StorePerformanceRecordRequest::attributeNames();
    }

    /** @return array<string, string> */
    public static function messageOverrides(): array
    {
        return StorePerformanceRecordRequest::messageOverrides();
    }

    public function authorize(): bool
    {
        $record = $this->route('performance_record');

        if (! $record instanceof PerformanceRecord) {
            return false;
        }

        return $this->user()?->can('update', $record) ?? false;
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

    /** A non-contest event can never carry a win/loss/draw. See the Store request. */
    protected function prepareForValidation(): void
    {
        $type = PerformanceEventType::tryFrom((string) $this->input('event_type'));

        if ($type !== null && ! $type->hasContestResult()) {
            $this->merge(['result' => PerformanceResult::NotApplicable->value]);
        }
    }
}
