<div>
    {{-- Header. Console surface: 32px display, 15px body, no colour beyond ink. --}}
    <div class="mb-8 sm:flex sm:items-end sm:justify-between sm:gap-6">
        <div>
            <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">User Accounts</h1>
            <p class="mt-2 max-w-[60ch] text-[15px] leading-relaxed text-ink-80">
                Who can sign in to the system and what they are allowed to do.
            </p>
        </div>

        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn-primary mt-5 w-full sm:mt-0 sm:w-auto">Add User</a>
        @endcan
    </div>

    @if ($statusMessage)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-[4px] bg-ok-wash px-4 py-3" role="status">
            <p class="text-[15px] leading-snug text-ok">{{ $statusMessage }}</p>
            <button type="button" wire:click="dismissStatus"
                    class="-my-2 shrink-0 px-1 text-[13px] font-medium text-ok hover:underline">
                Dismiss
            </button>
        </div>
    @endif

    {{-- Filters. A well, not a card-in-a-card: the pearl ground says "controls",
         the canvas below says "records". --}}
    <div class="card mb-6 p-5">
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
            <div class="mt-5 border-t border-hairline pt-4 text-right">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
            </div>
        @endif
    </div>

    @if ($this->users->isEmpty())
        <div class="card px-6 py-16 text-center">
            <h3 class="text-[18px] font-medium text-ink">No accounts match your filters</h3>
            <p class="mt-2 text-[15px] leading-relaxed text-ink-80">Try clearing the filters to see everyone.</p>
            <button type="button" wire:click="clearFilters" class="btn-secondary mt-6">Clear filters</button>
        </div>
    @else
        {{-- Phone: one record per card. A seven-column table on a 390px screen
             is a horizontal scrollbar, and this is read one-handed in a pen. --}}
        <ul class="space-y-3 sm:hidden">
            @foreach ($this->users as $person)
                <li wire:key="user-card-{{ $person->id }}" class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[15px] font-medium text-ink">
                                {{ $person->full_name }}
                                @if ($person->is(auth()->user()))
                                    <span class="text-[13px] font-normal text-ink-80">(you)</span>
                                @endif
                            </p>
                            <p class="datum mt-0.5 break-all text-[13px] text-ink-80">{{ $person->email }}</p>
                        </div>
                        @if ($person->is_active)
                            <span class="badge badge-ok shrink-0">Active</span>
                        @else
                            <span class="badge badge-neutral shrink-0">Deactivated</span>
                        @endif
                    </div>

                    <dl class="mt-3 grid grid-cols-[7.5rem_1fr] gap-x-3 gap-y-1.5 border-t border-hairline pt-3">
                        <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Role</dt>
                        <dd class="text-[15px] text-ink">{{ $person->role->label() }}</dd>

                        <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Position</dt>
                        <dd class="text-[15px] text-ink-80">{{ $person->position ?? '—' }}</dd>

                        <dt class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Contact</dt>
                        <dd class="datum text-[15px] text-ink-80">{{ $person->contact_number ?? '—' }}</dd>
                    </dl>

                    @canany(['update', 'deactivate'], $person)
                        <div class="mt-3 flex flex-wrap items-center gap-x-5 border-t border-hairline pt-2">
                            @can('update', $person)
                                <a href="{{ route('users.edit', $person) }}"
                                   class="inline-flex min-h-11 items-center text-[15px] font-medium text-action hover:underline">
                                    Edit<span class="sr-only">, {{ $person->full_name }}</span>
                                </a>
                            @endcan
                            @can('deactivate', $person)
                                <button type="button" wire:click="confirmToggle({{ $person->id }})"
                                        class="inline-flex min-h-11 items-center text-[15px] font-medium {{ $person->is_active ? 'text-alert' : 'text-action' }} hover:underline">
                                    {{ $person->is_active ? 'Deactivate' : 'Reactivate' }}
                                </button>
                            @endcan
                        </div>
                    @endcanany
                </li>
            @endforeach
        </ul>

        {{-- Desktop: the ledger proper. --}}
        <div class="card hidden overflow-hidden sm:block">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-rule-strong bg-pearl">
                        <tr>
                            @foreach (['Name', 'Email', 'Role', 'Position', 'Contact', 'Status'] as $heading)
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                    {{ $heading }}
                                </th>
                            @endforeach
                            <th scope="col" class="px-4 py-3 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="table-hairline">
                        @foreach ($this->users as $person)
                            <tr wire:key="user-row-{{ $person->id }}" class="transition-colors duration-100 hover:bg-pearl">
                                <td class="whitespace-nowrap px-4 py-3 text-[15px] font-medium text-ink">
                                    {{ $person->full_name }}
                                    @if ($person->is(auth()->user()))
                                        <span class="ml-1 text-[13px] font-normal text-ink-80">(you)</span>
                                    @endif
                                </td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-[15px] text-ink-80">{{ $person->email }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[15px] text-ink">{{ $person->role->label() }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[15px] text-ink-80">{{ $person->position ?? '—' }}</td>
                                <td class="datum whitespace-nowrap px-4 py-3 text-[15px] text-ink-80">{{ $person->contact_number ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($person->is_active)
                                        <span class="badge badge-ok">Active</span>
                                    @else
                                        <span class="badge badge-neutral">Deactivated</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @can('update', $person)
                                        <a href="{{ route('users.edit', $person) }}"
                                           class="inline-flex min-h-11 items-center text-[15px] font-medium text-action hover:underline">
                                            Edit<span class="sr-only">, {{ $person->full_name }}</span>
                                        </a>
                                    @endcan
                                    @can('deactivate', $person)
                                        <button type="button" wire:click="confirmToggle({{ $person->id }})"
                                                class="ml-4 inline-flex min-h-11 items-center text-[15px] font-medium {{ $person->is_active ? 'text-alert' : 'text-action' }} hover:underline">
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
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/70 p-4 sm:items-center" x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()" role="dialog" aria-modal="true" aria-labelledby="toggle-user-title">
            <div class="w-full max-w-md rounded-[4px] border border-rule-strong bg-canvas p-6">
                <h2 id="toggle-user-title" class="text-[22px] font-semibold leading-[1.2] tracking-[-0.01em] text-ink">
                    {{ $this->pendingUser->is_active ? 'Deactivate' : 'Reactivate' }} {{ $this->pendingUser->full_name }}?
                </h2>
                <p class="mt-3 text-[15px] leading-relaxed text-ink-80">
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
