<div>
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">User Accounts</h1>
            <p class="mt-1 text-sm text-gray-600">
                Who can sign in to the system and what they are allowed to do.
            </p>
        </div>

        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn-primary mt-4 w-full sm:mt-0 sm:w-auto">Add User</a>
        @endcan
    </div>

    @if ($statusMessage)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-lg bg-brand-50 p-4 ring-1 ring-brand-200" role="status">
            <p class="text-sm text-brand-800">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus" class="text-sm font-medium text-brand-700 hover:text-brand-900">
                Dismiss
            </button>
        </div>
    @endif

    <div class="card mb-6 p-4">
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
            <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </div>
        @endif
    </div>

    @if ($this->users->isEmpty())
        <div class="card p-12 text-center">
            <h3 class="text-base font-semibold text-gray-900">No accounts match your filters</h3>
            <p class="mt-1 text-sm text-gray-600">Try clearing the filters to see everyone.</p>
            <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach (['Name', 'Email', 'Role', 'Position', 'Contact', 'Status', ''] as $heading)
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($this->users as $person)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $person->full_name }}
                                    @if ($person->is(auth()->user()))
                                        <span class="ml-1 text-xs font-normal text-gray-500">(you)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $person->email }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $person->role->label() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $person->position ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $person->contact_number ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($person->is_active)
                                        <span class="badge bg-emerald-100 text-emerald-800 ring-emerald-600/20">Active</span>
                                    @else
                                        <span class="badge bg-gray-100 text-gray-700 ring-gray-500/20">Deactivated</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    @can('update', $person)
                                        <a href="{{ route('users.edit', $person) }}" class="font-medium text-brand-700 hover:text-brand-800">
                                            Edit<span class="sr-only">, {{ $person->full_name }}</span>
                                        </a>
                                    @endcan
                                    @can('deactivate', $person)
                                        <button type="button" wire:click="confirmToggle({{ $person->id }})"
                                                class="ml-3 font-medium {{ $person->is_active ? 'text-rose-700 hover:text-rose-800' : 'text-brand-700 hover:text-brand-800' }}">
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $this->pendingUser->is_active ? 'Deactivate' : 'Reactivate' }} {{ $this->pendingUser->full_name }}?
                </h2>
                <p class="mt-2 text-sm text-gray-600">
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
