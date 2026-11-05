<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Livewire\Concerns\ChoosesPageSize;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use ChoosesPageSize;
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

    #[Url(except: 'full_name')]
    public string $sortBy = 'full_name';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    /** Columns a user is allowed to sort by - never interpolate raw input into SQL. */
    private const SORTABLE = ['full_name', 'email', 'role', 'created_at'];

    /**
     * One press sets the column and the direction together.
     *
     * Pressing the column already sorted flips it; pressing another takes it
     * ascending, because "show me this column" almost always means "from the
     * top" on first press.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * The add / edit form, open in a dialog over this list (the owner's
     * decision: create and edit never leave the list). The old create and
     * edit URLs still work - they render this list with the form open. The
     * form component itself is unchanged: it authorizes, validates and saves
     * exactly as it did as a page.
     */
    public bool $showForm = false;

    #[Locked]
    public ?int $formRecordId = null;

    /**
     * A new form is built ahead, so Add opens instantly. Closing the dialog
     * drops any edit and re-keys the new form, so the next Add is blank.
     */
    public int $formNonce = 0;

    public function updatedShowForm(bool $open): void
    {
        if ($open) {
            return;
        }

        $this->formRecordId = null;
        $this->formBroodcockId = null;
        $this->formNonce++;
    }

    /** Called by the dialog when a form that was typed in is closed: start the next one blank. */
    public function resetForm(): void
    {
        $this->showForm = false;
        $this->updatedShowForm(false);
    }

    #[Locked]
    public ?int $formBroodcockId = null;

    public function mount(?User $user = null): void
    {
        $this->authorize('viewAny', User::class);

        // The form is only built for someone allowed to create, so it can no
        // longer refuse an /edit or /create address itself: checked here.
        if ($user?->exists) {
            $this->authorize('update', $user);
            $this->openForm($user->id);
        } elseif (request()->routeIs('users.create')) {
            $this->authorize('create', User::class);
            $this->openForm(null, null);
        }
    }

    public function openForm(?int $recordId = null, ?int $broodcockId = null): void
    {
        $this->formRecordId = $recordId;
        $this->formBroodcockId = $broodcockId;
        $this->showForm = true;
    }

    public function updating(string $property): void
    {
        if ($property === 'showForm') {
            return;
        }

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
            // Checked against the allow-list a second time: the URL can set
            // $sortBy directly without ever passing through sort().
            ->orderBy(
                in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'full_name',
                $this->sortDirection === 'asc' ? 'asc' : 'desc'
            )
            ->orderByDesc('id')
            ->paginate($this->pageSize());
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

        $this->dispatch('toast', message: $this->statusMessage, tone: 'ok');

        $this->confirmingId = null;
        unset($this->users, $this->pendingUser);
    }

    public function dismissStatus(): void
    {
        $this->statusMessage = null;
    }

    /**
     * The KPI tiles, from one grouped count: every account, how many can sign
     * in, the farm's own people (owner and staff) and the customers.
     *
     * @return array{total: int, active: int, internal: int, customers: int}
     */
    #[Computed]
    public function stats(): array
    {
        $counts = User::query()
            ->selectRaw('role, is_active, count(*) as aggregate')
            ->groupBy('role', 'is_active')
            ->get();

        $sum = fn (callable $filter): int => (int) $counts->filter($filter)->sum('aggregate');
        $role = fn (User $row): string => $row->role instanceof UserRole ? $row->role->value : (string) $row->role;

        return [
            'total' => $sum(fn (): bool => true),
            'active' => $sum(fn (User $row): bool => (bool) $row->is_active),
            'internal' => $sum(fn (User $row): bool => $role($row) !== UserRole::Customer->value),
            'customers' => $sum(fn (User $row): bool => $role($row) === UserRole::Customer->value),
        ];
    }

    /** @return array<int, UserRole> */
    public function roleOptions(): array
    {
        return UserRole::cases();
    }

    public function render(): View
    {
        return view('livewire.users.index')
            ->title('Users');
    }
}
