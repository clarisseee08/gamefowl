<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Pen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a pen.
 *
 * The rule set lives in a static method so the Livewire form component can
 * reuse exactly the same rules without a second, drifting copy. `$penId` is
 * null on create and the pen's key on edit, which is all the unique rule
 * needs to know.
 */
final class StorePenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Pen::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * Shared rules, reused verbatim by App\Livewire\Pens\Form.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(?int $penId = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('pens', 'code')->ignore($penId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            // A pen holding a negative number of birds has no meaning; the
            // Postgres CHECK constraint says the same thing at the database.
            'capacity' => ['required', 'integer', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Plain-language messages. Farm staff read these, not developers.
     *
     * @return array<string, string>
     */
    public static function messagesFor(): array
    {
        return [
            'code.required' => 'Please enter a pen code, for example "P-01".',
            'code.unique' => 'A pen with that code already exists.',
            'code.max' => 'The pen code can be at most 32 characters.',
            'name.required' => 'Please enter a name for this pen.',
            'capacity.required' => 'Please enter how many birds this pen can hold. Enter 0 if there is no limit.',
            'capacity.integer' => 'Capacity must be a whole number of birds.',
            'capacity.min' => 'Capacity cannot be a negative number.',
        ];
    }

    /** @return array<string, string> */
    public static function attributesFor(): array
    {
        return [
            'code' => 'pen code',
            'name' => 'pen name',
            'location' => 'location',
            'capacity' => 'capacity',
            'notes' => 'notes',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::messagesFor();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return self::attributesFor();
    }
}
