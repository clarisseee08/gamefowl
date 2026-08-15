<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Form extends Component
{
    /** Which account is being edited is never re-assignable from the browser. */
    #[Locked]
    public ?int $userId = null;

    public string $full_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public ?string $contact_number = null;

    public ?string $address = null;

    public ?string $position = null;

    public bool $is_active = true;

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            $this->authorize('update', $user);

            $this->userId = $user->id;
            $this->fill([
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role->value,
                'contact_number' => $user->contact_number,
                'address' => $user->address,
                'position' => $user->position,
                'is_active' => $user->is_active,
            ]);

            return;
        }

        $this->authorize('create', User::class);
        $this->role = UserRole::Staff->value;
    }

    public function isEditing(): bool
    {
        return $this->userId !== null;
    }

    public function editing(): ?User
    {
        return $this->userId === null ? null : User::find($this->userId);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return StoreUserRequest::rulesFor($this->editing());
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return StoreUserRequest::attributeNames();
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return StoreUserRequest::messageOverrides();
    }

    public function save(): void
    {
        $data = $this->validate();

        $existing = $this->editing();

        // An owner must not be able to strip their own owner role or
        // deactivate themselves - that is how a system ends up with no
        // administrator and nobody able to create one.
        if ($existing !== null && $existing->is(auth()->user())) {
            if ($data['role'] !== UserRole::Owner->value) {
                $this->addError('role', 'You cannot change your own role. Ask another owner to do it.');

                return;
            }

            if (! $data['is_active']) {
                $this->addError('is_active', 'You cannot deactivate your own account.');

                return;
            }
        }

        $attributes = [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'contact_number' => $data['contact_number'] ?: null,
            'address' => $data['address'] ?: null,
            'position' => $data['position'] ?: null,
            'is_active' => $data['is_active'],
        ];

        // An empty password box on edit means "leave it alone", never "blank it".
        if (($data['password'] ?? '') !== '') {
            $attributes['password'] = $data['password'];   // hashed by the model cast
        }

        $saved = DB::transaction(function () use ($existing, $attributes): User {
            if ($existing !== null) {
                $existing->update($attributes);

                return $existing;
            }

            return User::create($attributes);
        });

        session()->flash('success', $this->isEditing()
            ? "{$saved->full_name}'s account has been updated."
            : "{$saved->full_name} can now sign in with the email address you entered.");

        $this->redirectRoute('users.index', navigate: true);
    }

    /** @return array<int, UserRole> */
    public function roleOptions(): array
    {
        return UserRole::cases();
    }

    public function render(): View
    {
        return view('livewire.users.form');
    }
}
