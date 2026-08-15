<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * Shared rules so the Livewire form and this Form Request cannot drift.
     *
     * @param  User|null  $user  The account being edited, if any.
     * @return array<string, mixed>
     */
    public static function rulesFor(?User $user = null): array
    {
        $editing = $user?->exists ?? false;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id)->whereNull('deleted_at'),
            ],

            // On create a password is required. On edit it is optional - an
            // empty box means "leave the password alone", never "blank it".
            'password' => [
                $editing ? 'nullable' : 'required',
                'confirmed',
                Password::min(8),
            ],

            'role' => ['required', Rule::enum(UserRole::class)],
            'contact_number' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ];
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
            'full_name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'role' => 'role',
            'contact_number' => 'contact number',
            'address' => 'address',
            'position' => 'position',
            'is_active' => 'active status',
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
            'full_name.required' => 'Please enter the person\'s full name.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'That does not look like a valid email address.',
            'email.unique' => 'An account with that email address already exists.',
            'password.required' => 'Please set a password for this account.',
            'password.confirmed' => 'The two passwords do not match.',
            'role.required' => 'Please choose what this person is allowed to do.',
        ];
    }
}
