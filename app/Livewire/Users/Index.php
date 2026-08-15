<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: '')]
    public string $status = '';

    public ?int $confirmingId = null;

    /**
     * Feedback is held on the component rather than flashed to the session.
     * A Livewire update re-renders only this component, not the layout that
     * prints flash messages, so a flashed confirmation would not appear until
     * the next full page load.
     */
    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'role', 'status']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->role !== '' || $this->status !== '';
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->search($this->search)
            ->when($this->role !== '', fn ($q) => $q->where('role', $this->role))
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('full_name')
            ->paginate(config('gfms.per_page'));
    }

    #[Computed]
    public function pendingUser(): ?User
    {
        return $this->confirmingId === null ? null : User::find($this->confirmingId);
    }

    public function confirmToggle(int $id): void
    {
        $user = User::findOrFail($id);

        $this->authorize('deactivate', $user);

        $this->confirmingId = $id;
    }

    public function cancel(): void
    {
        $this->confirmingId = null;
    }

    /**
     * Activate or deactivate an account.
     *
     * Deactivation is preferred over deletion: the person keeps their audit
     * history and their name still resolves on every record they created,
     * but they can no longer sign in.
     */
    public function toggleActive(): void
    {
        $user = $this->pendingUser;

        if ($user === null) {
            return;
        }

        $this->authorize('deactivate', $user);

        DB::transaction(fn () => $user->update(['is_active' => ! $user->is_active]));

        $this->statusMessage = $user->is_active
            ? "{$user->full_name} can sign in again."
            : "{$user->full_name} has been deactivated and can no longer sign in. Their records are kept.";

        $this->confirmingId = null;
        unset($this->users, $this->pendingUser);
    }

    public function dismissStatus(): void
    {
        $this->statusMessage = null;
    }

    /** @return array<int, UserRole> */
    public function roleOptions(): array
    {
        return UserRole::cases();
    }

    public function render(): View
    {
        return view('livewire.users.index');
    }
}
