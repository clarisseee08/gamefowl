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

    $loadingTargets = 'search,role,status,sort,perPage,gotoPage,nextPage,previousPage';
@endphp

<div x-data="{ view: $persist(gfmsDefaultView()).as('gfms-view-users') }">
    <x-ui.page-header title="User Accounts"
                      :figure="number_format($total)"
                      :figure-label="Str::plural('account', $total).($filtered ? ' matching your filters' : '')">
        Who can sign in to the system and what they are allowed to do.

        @can('create', App\Models\User::class)
            <x-slot:actions>
                <x-ui.button :href="route('users.create')" x-on:click.prevent="$dispatch('ems-dialog', 'users-form')">
                    <x-ui.icon name="plus" /> Add User
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    @php $stats = $this->stats; @endphp
    <x-ui.kpi-row class="mt-5" role="group" aria-label="Account figures">
        <x-ui.kpi-tile label="Accounts" icon="user" :value="number_format($stats['total'])"
                       caption="Everyone who has an account" />
        <x-ui.kpi-tile label="Active" icon="user-check" :value="number_format($stats['active'])"
                       :caption="number_format($stats['total'] - $stats['active']).' deactivated'" />
        <x-ui.kpi-tile label="Owners (Admin)" icon="shield-check" :value="number_format($stats['owners'])"
                       caption="Full access, including user accounts" />
        <x-ui.kpi-tile label="Record Keepers" icon="clipboard-list" :value="number_format($stats['staff'])"
                       caption="Add and edit the farm's records" />
    </x-ui.kpi-row>

    <div class="mt-5 overflow-x-auto" data-scroll-x>
        <x-segmented :segments="$statusSegments" :active="$status" model="status" label="Account status" />
    </div>

    <x-filter-bar :count="collect([$role])->filter(fn ($value) => filled($value))->count()" class="mt-5" :active="$filtered" clear="clearFilters">
        <x-slot:search>
            <label for="search" class="sr-only">Search</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Name, email or position" class="d-input w-full">
        </x-slot:search>

        <x-filter-select id="role" label="Role" wire:model.live="role">
            <option value="">All roles</option>
            @foreach ($this->roleOptions() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </x-filter-select>

        <x-ui.view-toggle />

        <x-slot:loading>
            <span wire:loading wire:target="search,role,status">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($people->isEmpty())
        <x-empty-state title="No accounts match your filters"
                       description="Try clearing the filters to see everyone."
                       icon="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z">
            <button type="button" wire:click="clearFilters" class="d-btn d-btn-outline">Clear filters</button>
        </x-empty-state>
    @else
        {{-- The ledger is tablets and up, in table view only. Phones always
             read the cards below, whichever view was last chosen. md:flex, not
             md:block: the card is a flex column. --}}
        <x-ui.card :flush="true" class="hidden md:flex" x-show="view === 'table'">
        {{-- The ledger. No horizontal scroll wrapper, so the
             header can stick; Position and Contact drop out where there is no
             room for them. --}}
        <table class="d-table hidden w-full rounded-none md:table [&_td]:border-border [&_th]:border-border">
            <thead>
                <tr class="border-b border-border">
                    @foreach ($columns as $column)
                        @php
                            $isSorted = $column['sort'] !== null && $sortBy === $column['sort'];
                            $ariaSort = $isSorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
                        @endphp
                        <th scope="col"
                            @if ($column['sort'] !== null) aria-sort="{{ $ariaSort }}" @endif
                            class="h-10 whitespace-nowrap border-b border-border px-5 text-left text-[13px] font-medium text-muted-foreground lg:h-9 lg:text-xs {{ $column['cell'] }}">
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
                    <th scope="col" class="h-10 border-b border-border px-5 text-right text-[13px] font-medium text-muted-foreground lg:h-9 lg:text-xs">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-border" wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($people as $person)
                    <tr wire:key="user-row-{{ $person->id }}" class="hover:bg-muted/60">
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$person->full_name" class="shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-foreground lg:text-xs">
                                        {{ $person->full_name }}
                                        @if ($person->is(auth()->user()))
                                            <span class="ml-1 text-[13px] lg:text-[11px] font-normal text-muted-foreground">(you)</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 truncate text-[14px] lg:text-xs text-muted-foreground">{{ $person->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-2.5">
                            <span class="d-badge d-badge-soft d-badge-neutral">{{ $person->role->label() }}</span>
                        </td>
                        <td class="hidden whitespace-nowrap px-5 py-2.5 text-[15px] lg:text-xs text-muted-foreground lg:table-cell">{{ $person->position ?? 'Not recorded' }}</td>
                        <td @class([
                            'hidden whitespace-nowrap px-5 py-2.5 text-[15px] lg:text-xs text-muted-foreground xl:table-cell',
                            'datum' => $person->contact_number !== null,
                        ])>{{ $person->contact_number ?? 'Not recorded' }}</td>
                        <td class="whitespace-nowrap px-5 py-2.5">
                            @if ($person->is_active)
                                <span class="d-badge d-badge-soft d-badge-success">Active</span>
                            @else
                                <span class="d-badge d-badge-soft d-badge-neutral">Deactivated</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-2.5 text-right">
                            <div class="flex items-center justify-end gap-0.5">
                                @can('update', $person)
                                    <x-ui.row-action icon="pencil" :label="'Edit '.$person->full_name" :href="route('users.edit', $person)" wire:click.prevent="openForm({{ $person->id }})" x-on:click="$dispatch('ems-dialog', { id: 'users-form', wait: true })" />
                                @endcan
                                @can('deactivate', $person)
                                    <x-ui.row-action :icon="$person->is_active ? 'user-x' : 'user-check'"
                                                     :label="($person->is_active ? 'Deactivate ' : 'Reactivate ').$person->full_name"
                                                     :destructive="$person->is_active" wire:click="confirmToggle({{ $person->id }})" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-table-skeleton :cols="4" wire:loading wire:target="{{ $loadingTargets }}" />
        </table>

        <div class="px-5 pb-5">
            <x-ui.table-pagination :paginator="$people" />
        </div>
        </x-ui.card>

        {{-- AS CARDS: the same people, the same actions. Always on phones
             (one column); from md up, only in cards view. --}}
        <div x-bind:class="view === 'cards' ? '' : 'md:hidden'" x-cloak>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                wire:loading.remove wire:target="{{ $loadingTargets }}">
                @foreach ($people as $person)
                    <li wire:key="user-tile-{{ $person->id }}">
                        <x-ui.record-card>
                            <x-slot:media>
                                <x-ui.avatar :name="$person->full_name" />
                            </x-slot:media>
                            <x-slot:title>
                                {{ $person->full_name }}
                                @if ($person->is(auth()->user()))
                                    <span class="ml-1 text-[13px] lg:text-[11px] font-normal text-muted-foreground">(you)</span>
                                @endif
                            </x-slot:title>
                            <x-slot:subtitle>{{ $person->email }}</x-slot:subtitle>
                            <x-slot:badge>
                                <span class="d-badge d-badge-soft d-badge-neutral">{{ $person->role->label() }}</span>
                                @if ($person->is_active)
                                    <span class="d-badge d-badge-soft d-badge-success">Active</span>
                                @else
                                    <span class="d-badge d-badge-soft d-badge-neutral">Deactivated</span>
                                @endif
                            </x-slot:badge>

                            <dl class="grid grid-cols-2 gap-3">
                                <x-ui.fact label="Position">{{ $person->position ?? 'Not recorded' }}</x-ui.fact>
                                <x-ui.fact label="Contact">
                                    <span @class(['datum' => $person->contact_number !== null])>{{ $person->contact_number ?? 'Not recorded' }}</span>
                                </x-ui.fact>
                            </dl>

                            <x-slot:actions>
                                @can('update', $person)
                                    <x-ui.row-action icon="pencil" :label="'Edit '.$person->full_name" :href="route('users.edit', $person)" wire:click.prevent="openForm({{ $person->id }})" x-on:click="$dispatch('ems-dialog', { id: 'users-form', wait: true })" />
                                @endcan
                                @can('deactivate', $person)
                                    <x-ui.row-action :icon="$person->is_active ? 'user-x' : 'user-check'"
                                                     :label="($person->is_active ? 'Deactivate ' : 'Reactivate ').$person->full_name"
                                                     :destructive="$person->is_active" wire:click="confirmToggle({{ $person->id }})" />
                                @endcan
                            </x-slot:actions>
                        </x-ui.record-card>
                    </li>
                @endforeach
            </ul>
            <x-ui.cards-skeleton class="mt-4" wire:loading.grid wire:target="{{ $loadingTargets }}" />

            <x-ui.table-pagination :paginator="$people" class="mt-4" />
        </div>
    @endif

    {{-- Confirmation. Names the person and says exactly what happens to them
         and to their records, because "Are you sure?" answers nothing. --}}
    @if ($this->pendingUser)
        @php($pending = $this->pendingUser)
        <div class="d-modal d-modal-open z-50 bg-foreground/60 p-4"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.d-btn-outline')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="toggle-user-title" aria-describedby="toggle-user-body">
            <div class="d-modal-box elev-3 w-full max-w-md">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$pending->full_name" size="md" class="shrink-0" />
                    <p class="min-w-0 truncate text-[14px] lg:text-xs text-muted-foreground">{{ $pending->email }}</p>
                </div>

                <h2 id="toggle-user-title" class="mt-4 font-display text-[22px] lg:text-base font-semibold leading-[1.2] tracking-[-0.015em] text-foreground">
                    {{ $pending->is_active ? 'Deactivate' : 'Reactivate' }} {{ $pending->full_name }}?
                </h2>
                <p id="toggle-user-body" class="mt-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    @if ($pending->is_active)
                        They will be signed out immediately and will not be able to sign in again.
                        Everything they have recorded is kept, and their name still appears on those
                        records. You can reactivate them at any time.
                    @else
                        They will be able to sign in again with their existing password.
                    @endif
                </p>
                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="cancel" class="d-btn d-btn-outline">Cancel</button>
                    <button type="button" wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive"
                            class="{{ $pending->is_active ? 'd-btn d-btn-error' : 'd-btn d-btn-primary' }}">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="toggleActive"></span>
                        Yes, {{ $pending->is_active ? 'deactivate' : 'reactivate' }} {{ Str::before($pending->full_name, ' ') ?: $pending->full_name }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add and edit open here, in a dialog over the list, never on a page of their own. --}}
    {{-- The new-record form is built with the page, and it authorizes on
         mount, so it is only built for someone who may create one. --}}
    @can('create', \App\Models\User::class)
        <x-ui.form-dialog id="users-form" model="showForm" size="lg" :wrap="false" :title="$formRecordId ? 'Edit '.(\App\Models\User::find($formRecordId)?->full_name ?? 'user') : 'Add a User'">
            @if ($formRecordId)
                {{-- An edit, or a form for one bird: filled by the server when opened. --}}
                @if ($showForm)
                    <livewire:users.form :user="$formRecordId ? \App\Models\User::find($formRecordId) : null"
                                         :in-dialog="true" :key="'users-form-'.($formRecordId ?? 'new').'-'.($formBroodcockId ?? '')" />
                @else
                    <x-ui.form-skeleton :fields="5" />
                @endif
            @else
                {{-- A new record: built with the page, so Add opens at once with no request. --}}
                <livewire:users.form :user="$formRecordId ? \App\Models\User::find($formRecordId) : null"
                                      :in-dialog="true" :key="'users-form-new-'.$formNonce" />
            @endif
        </x-ui.form-dialog>
    @endcan
</div>
