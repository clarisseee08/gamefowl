@php
    $people = $this->users;
    $total = $people->total();
    $filtered = $this->hasActiveFilters();

    $statusSegments = [
        ['key' => '', 'label' => 'Everyone'],
        ['key' => 'active', 'label' => 'Active'],
        ['key' => 'inactive', 'label' => 'Deactivated'],
    ];

    // Name and Role are real columns and sort. Position and Contact are free
    // text nobody orders a staff list by, and Status is a derived flag - they
    // stay plain rather than offering a press that does nothing.
    $columns = [
        ['label' => 'Name', 'sort' => 'full_name', 'cell' => ''],
        ['label' => 'Role', 'sort' => 'role', 'cell' => ''],
        ['label' => 'Position', 'sort' => null, 'cell' => 'hidden lg:table-cell'],
        ['label' => 'Contact', 'sort' => null, 'cell' => 'hidden xl:table-cell'],
        ['label' => 'Status', 'sort' => null, 'cell' => ''],
    ];

    $loadingTargets = 'search,role,status,sort,gotoPage,nextPage,previousPage';
@endphp

<div>
    <x-anchor-block eyebrow="Admin" title="User Accounts"
                    :figure="number_format($total)"
                    :figure-label="Str::plural('account', $total).($filtered ? ' matching your filters' : '')">
        Who can sign in to the system and what they are allowed to do.

        @can('create', App\Models\User::class)
            <x-slot:actions>
                <a href="{{ route('users.create') }}" wire:navigate class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add user
                </a>
            </x-slot:actions>
        @endcan
    </x-anchor-block>

    <div class="mt-8 overflow-x-auto" data-scroll-x>
        <x-segmented :segments="$statusSegments" :active="$status" model="status" label="Account status" />
    </div>

    <x-filter-bar class="mt-5" :active="$filtered" clear="clearFilters">
        <x-slot:search>
            <label for="search" class="sr-only">Search</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name, email or position" class="input">
        </x-slot:search>

        <x-filter-select id="role" label="Role" wire:model.live="role">
            <option value="">All roles</option>
            @foreach ($this->roleOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-slot:loading>
            <span wire:loading wire:target="search,role,status">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($people->isEmpty())
        <x-empty-state title="No accounts match your filters"
                       description="Try clearing the filters to see everyone."
                       icon="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z">
            <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
        </x-empty-state>
    @else
        {{-- Phones: one block per person - name and email, then role and
             status. Read one-handed, so no sideways scroll. The avatar is
             initials only: a photo URL costs a storage round trip per row. --}}
        <ul class="divide-y divide-border border-y border-border md:hidden"
            wire:loading.class="opacity-60" wire:target="{{ $loadingTargets }}">
            @foreach ($people as $person)
                <li wire:key="user-card-{{ $person->id }}" class="py-4">
                    <div class="flex items-start gap-3">
                        <x-avatar :name="$person->full_name" size="md" class="mt-0.5 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[16px] font-medium text-foreground">
                                {{ $person->full_name }}
                                @if ($person->is(auth()->user()))
                                    <span class="text-[14px] font-normal text-muted-foreground">(you)</span>
                                @endif
                            </p>
                            <p class="mt-0.5 break-all text-[14px] text-muted-foreground">{{ $person->email }}</p>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="badge badge-neutral">{{ $person->role->label() }}</span>
                                @if ($person->is_active)
                                    <span class="badge badge-ok">Active</span>
                                @else
                                    <span class="badge badge-neutral">Deactivated</span>
                                @endif
                                <span class="text-[14px] text-muted-foreground">{{ $person->position ?? 'No position recorded' }}</span>
                            </div>

                            @canany(['update', 'deactivate'], $person)
                                <div class="mt-1 flex flex-wrap items-center gap-x-6">
                                    @can('update', $person)
                                        <a href="{{ route('users.edit', $person) }}" wire:navigate
                                           class="inline-flex min-h-11 items-center text-[15px] font-medium text-primary hover:underline">
                                            Edit<span class="sr-only">, {{ $person->full_name }}</span>
                                        </a>
                                    @endcan
                                    @can('deactivate', $person)
                                        <button type="button" wire:click="confirmToggle({{ $person->id }})"
                                                class="inline-flex min-h-11 items-center text-[15px] font-medium {{ $person->is_active ? 'text-destructive' : 'text-primary' }} hover:underline">
                                            {{ $person->is_active ? 'Deactivate' : 'Reactivate' }}<span class="sr-only">, {{ $person->full_name }}</span>
                                        </button>
                                    @endcan
                                </div>
                            @endcanany
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Wider screens: the ledger. No horizontal scroll wrapper, so the
             header can stick; Position and Contact drop out where there is no
             room for them. --}}
        <table class="hidden w-full md:table">
            <thead>
                <tr class="border-b border-border">
                    @foreach ($columns as $column)
                        @php
                            $isSorted = $column['sort'] !== null && $sortBy === $column['sort'];
                            $ariaSort = $isSorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
                        @endphp
                        <th scope="col"
                            @if ($column['sort'] !== null) aria-sort="{{ $ariaSort }}" @endif
                            class="sticky top-0 z-10 whitespace-nowrap bg-background px-3 py-2 text-left text-[13px] font-semibold text-muted-foreground {{ $column['cell'] }}">
                            @if ($column['sort'] !== null)
                                <button type="button" wire:click="sort('{{ $column['sort'] }}')"
                                        @class(['inline-flex items-center gap-1 hover:text-foreground', 'text-foreground' => $isSorted])>
                                    {{ $column['label'] }}
                                    @if ($isSorted)
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                        </svg>
                                        <span class="sr-only">sorted {{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                                    @endif
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                    <th scope="col" class="sticky top-0 z-10 bg-background px-3 py-2 text-right">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-border" wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($people as $person)
                    <tr wire:key="user-row-{{ $person->id }}" class="hover:bg-muted/60">
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$person->full_name" size="md" class="shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-[15px] font-medium text-foreground">
                                        {{ $person->full_name }}
                                        @if ($person->is(auth()->user()))
                                            <span class="ml-1 text-[13px] font-normal text-muted-foreground">(you)</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 truncate text-[14px] text-muted-foreground">{{ $person->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            <span class="badge badge-neutral">{{ $person->role->label() }}</span>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-3 text-[15px] text-muted-foreground lg:table-cell">{{ $person->position ?? 'Not recorded' }}</td>
                        <td @class([
                            'hidden whitespace-nowrap px-3 py-3 text-[15px] text-muted-foreground xl:table-cell',
                            'datum' => $person->contact_number !== null,
                        ])>{{ $person->contact_number ?? 'Not recorded' }}</td>
                        <td class="whitespace-nowrap px-3 py-3">
                            @if ($person->is_active)
                                <span class="badge badge-ok">Active</span>
                            @else
                                <span class="badge badge-neutral">Deactivated</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-right">
                            <div class="flex items-center justify-end gap-5">
                                @can('update', $person)
                                    <a href="{{ route('users.edit', $person) }}" wire:navigate
                                       class="inline-flex min-h-11 items-center text-[15px] font-medium text-primary hover:underline">
                                        Edit<span class="sr-only">, {{ $person->full_name }}</span>
                                    </a>
                                @endcan
                                @can('deactivate', $person)
                                    <button type="button" wire:click="confirmToggle({{ $person->id }})"
                                            class="inline-flex min-h-11 items-center text-[15px] font-medium {{ $person->is_active ? 'text-destructive' : 'text-primary' }} hover:underline">
                                        {{ $person->is_active ? 'Deactivate' : 'Reactivate' }}<span class="sr-only">, {{ $person->full_name }}</span>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="4" wire:loading wire:target="{{ $loadingTargets }}" />
        </table>

        <div class="mt-6 border-t border-border pt-4">{{ $people->links() }}</div>
    @endif

    {{-- Confirmation. Names the person and says exactly what happens to them
         and to their records, because "Are you sure?" answers nothing. --}}
    @if ($this->pendingUser)
        @php($pending = $this->pendingUser)
        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="toggle-user-title" aria-describedby="toggle-user-body">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$pending->full_name" size="md" class="shrink-0" />
                    <p class="min-w-0 truncate text-[14px] text-muted-foreground">{{ $pending->email }}</p>
                </div>

                <h2 id="toggle-user-title" class="mt-4 font-display text-[22px] font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    {{ $pending->is_active ? 'Deactivate' : 'Reactivate' }} {{ $pending->full_name }}?
                </h2>
                <p id="toggle-user-body" class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    @if ($pending->is_active)
                        They will be signed out immediately and will not be able to sign in again.
                        Everything they have recorded is kept, and their name still appears on those
                        records. You can reactivate them at any time.
                    @else
                        They will be able to sign in again with their existing password.
                    @endif
                </p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancel" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive"
                            class="{{ $pending->is_active ? 'btn-danger' : 'btn-primary' }}">
                        <span class="btn-spinner" wire:loading wire:target="toggleActive"></span>
                        Yes, {{ $pending->is_active ? 'deactivate' : 'reactivate' }} {{ Str::before($pending->full_name, ' ') ?: $pending->full_name }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
