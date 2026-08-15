<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * Deliberately does NOT accept `role` or `is_active` - a user must never
     * be able to promote themselves by posting extra fields. Those are changed
     * only through User Management, which is gated by UserPolicy::update().
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'contact_number' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'full_name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter an email address.',
            'email.unique' => 'That email address is already in use.',
        ])->validateWithBag('updateProfileInformation');

        $user->forceFill([
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'contact_number' => $input['contact_number'] ?? null,
            'address' => $input['address'] ?? null,
        ])->save();
    }
}
