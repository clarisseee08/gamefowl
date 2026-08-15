<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Used by Fortify's registration route.
 *
 * Public self-registration is DISABLED for this system (see the `features`
 * array in config/fortify.php) - accounts are created by an owner through
 * User Management. This action is retained because Fortify's contract binding
 * expects it, and because the user-management screen reuses the same rules.
 *
 * Note the role: anyone arriving through self-registration would become a
 * read-only `customer`, never staff. Privilege is granted deliberately by an
 * owner, never claimed by the person signing up.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ], [
            // Plain language - these are read by farm staff, not developers.
            'full_name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'That does not look like a valid email address.',
            'email.unique' => 'An account with that email address already exists.',
        ])->validate();

        return User::create([
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);
    }
}
