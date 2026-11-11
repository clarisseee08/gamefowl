@php
    $rows = $this->rows;
    $counts = $this->counts;
    $archivedBy = $this->archivedBy;
    $segments = collect(App\Livewire\Archive\Index::TABS)
        ->map(fn (array $tab, string $key) => ['key' => $key, 'label' => $tab[0], 'count' => $counts[$key]])
        ->values()
        ->all();
    $total = array_sum($counts);
    $restoring = $this->restoring;
    $th = 'px-4 py-2.5 text-left text-[13px] lg:text-[11px] font-semibold text-muted-foreground';
    $td = 'px-4 py-3 align-middle text-[15px] lg:text-xs text-foreground';
@endphp
<div>
    <x-ui.page-header title="Archive"
                      :figure="number_format($total)"
                      :figure-label="Str::plural('archived record', $total)">
        Everything that was archived. Nothing here is ever erased; restore a record to bring it back exactly as it was.
    </x-ui.page-header>

    @if ($statusMessage)
        <div class="d-alert d-alert-soft d-alert-success mt-5 text-[15px] lg:text-xs" role="status">{{ $statusMessage }}</div>
    @endif
    @if ($errorMessage)
        <div class="d-alert d-alert-soft d-alert-error mt-5 text-[15px] lg:text-xs" role="alert">{{ $errorMessage }}</div>
    @endif

    <div class="mt-5 overflow-x-auto" data-scroll-x>
        <x-segmented :segments="$segments" :active="$tab" model="tab" label="Kind of record" />
    </div>

    <x-filter-bar class="mt-5" :active="$search !== ''" clear="clearSearch">
        <x-slot:search>
            <label for="archive-search" class="sr-only">Search</label>
            <input id="archive-search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="{{ $tab === 'gamefowl' ? 'Name, band number or bloodline' : 'Bird name or band number' }}"
                   class="d-input w-full">
        </x-slot:search>
        <x-slot:loading>
            <span wire:loading wire:target="search,tab">Searching&hellip;</span>
        </x-slot:loading>
    </x-filter-bar>

    @if ($rows->isEmpty())
        <x-empty-state :title="$search !== '' ? 'Nothing archived matches your search' : 'Nothing archived here'"
                       :description="$search !== '' ? 'Try another name or band number.' : 'Records you archive appear here, where they can be restored.'"
                       icon="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
    @else
        <x-ui.card :flush="true" class="mt-5">
            <div class="overflow-x-auto" data-scroll-x>
                <table class="w-full" wire:loading.class="opacity-60" wire:target="search,tab,gotoPage,nextPage,previousPage">
                    <thead>
                        <tr class="border-b border-border">
                            @switch($tab)
                                @case('gamefowl')
                                    <th class="{{ $th }}">Band number</th><th class="{{ $th }}">Name</th><th class="{{ $th }}">Bloodline</th>
                                    @break
                                @case('photos')
                                    <th class="{{ $th }}">Photo</th><th class="{{ $th }}">Bird</th><th class="{{ $th }}">Description</th>
                                    @break
                                @case('breeding')
                                    <th class="{{ $th }}">Pair</th><th class="{{ $th }}">Mating date</th><th class="{{ $th }}">Eggs set / hatched</th>
                                    @break
                                @case('health')
                                    <th class="{{ $th }}">Bird</th><th class="{{ $th }}">Record</th><th class="{{ $th }}">Check-up date</th>
                                    @break
                                @case('performance')
                                    <th class="{{ $th }}">Bird</th><th class="{{ $th }}">Event</th><th class="{{ $th }}">Date</th>
                                    @break
                                @case('mortality')
                                    <th class="{{ $th }}">Bird</th><th class="{{ $th }}">Date of death</th><th class="{{ $th }}">Cause</th>
                                    @break
                            @endswitch
                            <th class="{{ $th }}">Archived</th>
                            <th class="{{ $th }}">By</th>
                            <th class="{{ $th }}"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($rows as $row)
                            <tr wire:key="archive-{{ $tab }}-{{ $row->getKey() }}">
                                @switch($tab)
                                    @case('gamefowl')
                                        <td class="{{ $td }} datum font-semibold">{{ $row->displayBand() }}</td>
                                        <td class="{{ $td }}">{{ $row->name }}</td>
                                        <td class="{{ $td }}">{{ $row->bloodline ?? 'Not recorded' }}</td>
                                        @break
                                    @case('photos')
                                        <td class="{{ $td }}">
                                            <img src="{{ route('photos.show', ['photo' => $row, 'size' => 'thumb']) }}" alt=""
                                                 width="48" height="60" loading="lazy" class="h-[60px] w-12 rounded-[6px] bg-muted object-cover">
                                        </td>
                                        <td class="{{ $td }}">{{ $row->broodcock?->displayName() ?? 'A removed bird' }}</td>
                                        <td class="{{ $td }} text-muted-foreground">{{ $row->caption ?? 'No description' }}</td>
                                        @break
                                    @case('breeding')
                                        <td class="{{ $td }}">{{ $row->sire?->name ?? 'A removed bird' }} &times; {{ $row->dam?->name ?? 'A removed bird' }}</td>
                                        <td class="{{ $td }} datum">{{ $row->mating_date->format('j M Y') }}</td>
                                        <td class="{{ $td }} datum">{{ $row->eggs_set }} / {{ $row->eggs_hatched }}</td>
                                        @break
                                    @case('health')
                                        <td class="{{ $td }}">{{ $row->broodcock?->displayName() ?? 'A removed bird' }}</td>
                                        <td class="{{ $td }}">{{ $row->record_type->label() }}@if ($row->product_name) &middot; {{ $row->product_name }}@endif</td>
                                        <td class="{{ $td }} datum">{{ $row->checkup_date->format('j M Y') }}</td>
                                        @break
                                    @case('performance')
                                        <td class="{{ $td }}">{{ $row->broodcock?->displayName() ?? 'A removed bird' }}</td>
                                        <td class="{{ $td }}">{{ $row->event_type->label() }}</td>
                                        <td class="{{ $td }} datum">{{ $row->event_date->format('j M Y') }}</td>
                                        @break
                                    @case('mortality')
                                        <td class="{{ $td }}">{{ $row->broodcock?->displayName() ?? 'A removed bird' }}</td>
                                        <td class="{{ $td }} datum">{{ $row->date_of_death->format('j M Y') }}</td>
                                        <td class="{{ $td }}">{{ $row->cause_of_death }}</td>
                                        @break
                                @endswitch
                                <td class="{{ $td }} datum">{{ $row->deleted_at->format('j M Y') }}</td>
                                <td class="{{ $td }} text-muted-foreground">{{ $archivedBy[$row->getKey()] ?? 'Unknown' }}</td>
                                <td class="{{ $td }} text-right">
                                    <button type="button" wire:click="confirmRestore('{{ $tab }}', {{ $row->getKey() }})"
                                            class="d-btn d-btn-outline d-btn-sm">
                                        Restore<span class="sr-only">, {{ $this->describe($row) }}</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.table-pagination :paginator="$rows" />
        </x-ui.card>
    @endif

    {{-- Restore confirmation. Names the record and says what coming back means. --}}
    @if ($restoring)
        <div class="d-modal d-modal-open" x-data x-trap.noscroll="true"
             x-on:keydown.escape.window="$wire.cancelRestore()"
             role="dialog" aria-modal="true" aria-labelledby="restore-title">
            <div class="d-modal-box elev-3 w-full max-w-md">
                <h2 id="restore-title" class="font-display text-[22px] lg:text-base font-semibold tracking-[-0.015em] text-foreground">
                    Restore {{ $this->describe($restoring) }}?
                </h2>
                <p class="mt-3 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    @switch($restoringType)
                        @case('gamefowl')
                            It goes back on the gamefowl list, the catalogue and the reports exactly as it was archived, with its photos and history.
                            @break
                        @case('photos')
                            It goes back into the bird&rsquo;s photos, and becomes the main photo if the bird has none.
                            @break
                        @case('mortality')
                            The death goes back on the register and the bird is marked Deceased again.
                            @break
                        @default
                            It goes back on its list and counts in the dashboard and the reports again, exactly as it was archived.
                    @endswitch
                </p>
                <div class="d-modal-action flex-col-reverse gap-3 sm:flex-row">
                    <button type="button" wire:click="cancelRestore" class="d-btn d-btn-outline">Cancel</button>
                    <button type="button" wire:click="restore" class="d-btn d-btn-primary" wire:loading.attr="disabled" wire:target="restore">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="restore"></span>
                        Yes, restore
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
