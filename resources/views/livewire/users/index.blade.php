<div>
    <div class="mb-10 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">User Accounts</h1>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Who can sign in to the system and what they are allowed to do.
            </p>
        </div>

        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn-primary mt-4 w-full sm:mt-0 sm:w-auto">Add User</a>
        @endcan
    </div>

    @if ($statusMessage)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-lg bg-ok-wash p-4 ring-1 ring-ok/20" role="status">
            <p class="text-sm text-ok">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus" class="text-sm font-medium text-action hover:text-ok">
                Dismiss
            </button>
        </div>
    @endif

    <div class="card mb-10 p-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="search" class="label">Search</label>
                <input id="search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Name, email or position" class="input mt-1">
            </div>
            <div>
                <label for="role" class="label">Role</label>
                <select id="role" wire:model.live="role" class="input mt-1">
                    <option value="">All roles</option>
                    @foreach ($this->roleOptions() as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" wire:model.live="status" class="input mt-1">
                    <option value="">Active and inactive</option>
                    <option value="active">Active only</option>
                    <option value="inactive">Deactivated only</option>
                </select>
            </div>
        </div>

        @if ($this->hasActiveFilters())
            <div class="mt-4 border-t border-hairline pt-4 text-right">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </div>
        @endif
    </div>

    @if ($this->users->isEmpty())
        <div class="card p-12 text-center">
            <h3 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No accounts match your filters</h3>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">Try clearing the filters to see everyone.</p>
            <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-divider">
                    <thead class="bg-pearl">
                        <tr>
                            @foreach (['Name', 'Email', 'Role', 'Position', 'Contact', 'Status', ''] as $heading)
                                <th scope="col" class="px-6 py-4 text-left text-[12px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-divider bg-white">
                        @foreach ($this->users as $person)
                            <tr class="hover:bg-pearl">
                                <td class="px-6 py-4 text-sm font-medium text-ink">
                                    {{ $person->full_name }}
                                    @if ($person->is(auth()->user()))
                                        <span class="ml-1 text-xs font-normal text-ink-48">(you)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-ink-80">{{ $person->email }}</td>
                                <td class="px-6 py-4 text-sm text-ink">{{ $person->role->label() }}</td>
                                <td class="px-6 py-4 text-sm text-ink-80">{{ $person->position ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-80">{{ $person->contact_number ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($person->is_active)
                                        <span class="badge bg-ok-wash text-ok ring-ok/20">Active</span>
                                    @else
                                        <span class="badge bg-parchment text-ink-80 ring-hairline">Deactivated</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @can('update', $person)
                                        <a href="{{ route('users.edit', $person) }}" class="font-medium text-action hover:underline">
                                            Edit<span class="sr-only">, {{ $person->full_name }}</span>
                                        </a>
                                    @endcan
                                    @can('deactivate', $person)
                                        <button type="button" wire:click="confirmToggle({{ $person->id }})"
                                                class="ml-3 font-medium {{ $person->is_active ? 'text-alert hover:text-alert' : 'text-action hover:underline' }}">
                                            {{ $person->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $this->users->links() }}</div>
    @endif

    {{-- Confirmation. Names the person and says exactly what happens to their
         records, because "Are you sure?" answers nothing. --}}
    @if ($this->pendingUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-[24px] font-semibold tracking-[-0.015em] leading-[1.2] text-ink">
                    {{ $this->pendingUser->is_active ? 'Deactivate' : 'Reactivate' }} {{ $this->pendingUser->full_name }}?
                </h2>
                <p class="mt-2 text-sm text-ink-80">
                    @if ($this->pendingUser->is_active)
                        They will be signed out immediately and will not be able to sign in again.
                        Everything they have recorded is kept, and their name still appears on those
                        records. You can reactivate them at any time.
                    @else
                        They will be able to sign in again with their existing password.
                    @endif
                </p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancel" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="toggleActive"
                            class="{{ $this->pendingUser->is_active ? 'btn-danger' : 'btn-primary' }}">
                        Yes, {{ $this->pendingUser->is_active ? 'deactivate' : 'reactivate' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
