<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\HealthRecord;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for editing an existing health record.
 *
 * The field rules are identical to creating one, so they are inherited from
 * StoreHealthRecordRequest rather than copied - a second copy is a second
 * thing to forget to update. Only the authorization gate differs: creating
 * needs the `create` ability, editing needs `update` on the specific record.
 */
final class UpdateHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('record');

        if (! $record instanceof HealthRecord) {
            return false;
        }

        return $this->user()?->can('update', $record) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public static function rulesFor(): array
    {
        return StoreHealthRecordRequest::rulesFor();
    }

    /** @return array<string, string> */
    public static function attributesFor(): array
    {
        return StoreHealthRecordRequest::attributesFor();
    }

    /** @return array<string, string> */
    public static function messagesFor(): array
    {
        return StoreHealthRecordRequest::messagesFor();
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
