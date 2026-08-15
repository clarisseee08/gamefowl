<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Pen;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for editing an existing pen.
 *
 * Identical to StorePenRequest except that the pen being edited is excluded
 * from the unique check on `code` - otherwise a pen could never be saved
 * twice under its own code.
 */
final class UpdatePenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pen = $this->route('pen');

        return $pen instanceof Pen
            ? ($this->user()?->can('update', $pen) ?? false)
            : ($this->user()?->can('create', Pen::class) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $pen = $this->route('pen');

        return self::rulesFor($pen instanceof Pen ? $pen->id : null);
    }

    /**
     * Shared rules, reused verbatim by App\Livewire\Pens\Form.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(?int $penId = null): array
    {
        return StorePenRequest::rulesFor($penId);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return StorePenRequest::messagesFor();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return StorePenRequest::attributesFor();
    }
}
