<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Broodcock;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBroodcockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $broodcock = $this->route('broodcock');

        return $broodcock instanceof Broodcock
            && ($this->user()?->can('update', $broodcock) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $broodcock = $this->route('broodcock');

        return StoreBroodcockRequest::rulesFor(
            $broodcock instanceof Broodcock ? $broodcock : null
        );
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return StoreBroodcockRequest::attributeNames();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return StoreBroodcockRequest::messageOverrides();
    }
}
