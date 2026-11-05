@php
    $breeding = $this->breeding;
    $offspring = $this->offspring;

    $rate = fn (?float $value): ?string => $value === null ? null : number_format($value, 1).'%';
    $unregistered = $breeding->unregisteredOffspring();

    // Built here rather than inside the component attribute, where a long
    // expression is where Blade compilation goes wrong.
    $offspringEmptyLine = $breeding->eggs_hatched > 0
        ? $breeding->eggs_hatched.' '.Str::plural('egg', $breeding->eggs_hatched).' hatched. Register them to add them as bird records.'
        : 'Nothing hatched from this mating.';
@endphp

{{--
    ONE MATING (EMS).

    A trail back to the list, the page header with the one action, then the
    hatch as four figures - each rate beside the count it is taken from,
    naming its own base - and the pair as photo cards, each opening the
    bird's page. The chicks it produced follow as a ledger.
--}}
<div>
    <nav aria-label="Breadcrumb" class="text-[13px] text-muted-foreground lg:text-xs">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('breeding.index') }}" wire:navigate class="hover:text-foreground">Back to breeding records</a></li>
            <li aria-hidden="true"><x-ui.icon name="chevron-right" class="size-3.5" /></li>
            <li class="font-medium text-foreground" aria-current="page">{{ $breeding->sire?->name ?? 'Bird removed' }} &times; {{ $breeding->dam?->name ?? 'Bird removed' }}</li>
        </ol>
    </nav>

    <x-ui.page-header class="mt-3" :title="($breeding->sire?->name ?? 'Bird removed').' × '.($breeding->dam?->name ?? 'Bird removed')">
        <span>
            Mated on <span class="datum text-foreground">{{ $breeding->mating_date->format('j F Y') }}</span>
            @if ($breeding->recordedBy)
                &middot; recorded by {{ $breeding->recordedBy->full_name }}
            @endif
        </span>

        @can('update', $breeding)
            <x-slot:actions>
                <x-ui.button :href="route('breeding.edit', $breeding)" x-on:click.prevent="$dispatch('ems-dialog', 'breeding-form')">
                    <x-ui.icon name="pencil" /> Edit
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    {{-- THE HATCH, as the funnel it is: set, then fertile, then hatched, each
         a subset of the one before. BreedingRecord::hatchRate is measured
         against FERTILE eggs, so every rate names its base. --}}
    <x-ui.kpi-row class="mt-5" role="group" aria-label="The hatch">
        <x-ui.kpi-tile label="Eggs set" icon="clipboard-list" :value="number_format($breeding->eggs_set)"
                       caption="Eggs put under the hen or into the incubator" />
        <x-ui.kpi-tile label="Fertile" icon="circle-check" :value="number_format($breeding->eggs_fertile)"
                       :caption="$rate($breeding->fertilityRate()) ? $rate($breeding->fertilityRate()).' of eggs set' : 'No eggs set yet'" />
        <x-ui.kpi-tile label="Hatched" icon="heart" :value="number_format($breeding->eggs_hatched)"
                       :caption="$rate($breeding->hatchRate()) ? $rate($breeding->hatchRate()).' of fertile' : 'No fertile eggs yet'" />
        <x-ui.kpi-tile label="End to end" icon="arrow-up-right" :value="$rate($breeding->overallHatchRate())"
                       :caption="$breeding->overallHatchRate() !== null
                            ? $breeding->eggs_hatched.' of '.$breeding->eggs_set.' '.Str::plural('egg', $breeding->eggs_set).' set reached hatch'
                            : 'No eggs have been recorded as set for this mating yet.'" />
    </x-ui.kpi-row>

    {{-- THE PAIR: sire and dam as photo cards, each opening its bird's page.
         A deleted parent keeps its place, greyed and unlinked. --}}
    <section aria-label="The pair" class="mt-4 grid items-center gap-3 sm:grid-cols-[1fr_auto_1fr] sm:gap-4">
        @foreach ([['Sire', $breeding->sire], ['Dam', $breeding->dam]] as [$role, $parent])
            @if (! $loop->first)
                <span class="hidden text-center text-base font-semibold text-muted-foreground sm:block" aria-hidden="true">&times;</span>
            @endif

            @php
                $removed = $parent === null || $parent->trashed();
                $photo = (! $removed && $parent->relationLoaded('primaryPhoto')) ? $parent->primaryPhoto : null;
            @endphp

            <{{ $removed ? 'div' : 'a' }} @unless ($removed) href="{{ route('broodcocks.show', $parent) }}" wire:navigate @endunless
                @class([
                    'flex items-center gap-4 rounded-md border border-border bg-card p-3',
                    'transition-colors hover:border-primary/50' => ! $removed,
                ])>
                <span class="relative block h-24 w-20 shrink-0 overflow-hidden rounded-sm bg-muted"
                      @unless ($removed) style="box-shadow: inset 0 3px 0 {{ \App\Support\BandTag::hex($parent->bloodline) }}" @endunless>
                    @if ($photo)
                        <span x-data="{ loaded: false }" x-init="loaded = $refs.photo.complete && $refs.photo.naturalWidth > 0" class="block h-full w-full">
                            <span x-show="! loaded" class="absolute inset-0 animate-pulse bg-muted-foreground/15" aria-hidden="true"></span>
                            <img x-ref="photo" x-on:load="loaded = true" src="{{ route('photos.show', ['photo' => $photo, 'size' => 'thumb']) }}" alt=""
                                 width="80" height="96" decoding="async" class="h-full w-full object-cover transition-opacity duration-300"
                                 x-bind:class="loaded ? 'opacity-100' : 'opacity-0'">
                        </span>
                    @else
                        <span class="flex h-full w-full flex-col items-center justify-center gap-1 text-[11px] text-muted-foreground">
                            @unless ($removed)<x-band-dot :bloodline="$parent->bloodline" />@endunless
                            No photo
                        </span>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-muted-foreground lg:text-[11px]">{{ $role }}</span>
                    <span @class(['mt-0.5 block truncate text-sm font-semibold', 'text-foreground' => ! $removed, 'text-muted-foreground' => $removed])>{{ $parent?->name ?? 'Bird removed' }}</span>
                    <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground lg:text-[11px]">
                        @if ($removed)
                            @if ($parent?->band_number)<span class="datum">{{ $parent->band_number }}</span>@endif
                            <span>Removed from the records</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-foreground">
                                <x-band-dot :bloodline="$parent->bloodline" />
                                @if ($parent->band_number)
                                    <span class="datum">{{ $parent->band_number }}</span>
                                @else
                                    <span class="text-muted-foreground">Not yet banded</span>
                                @endif
                            </span>
                            <span>{{ $parent->bloodline ?: 'Bloodline not recorded' }}</span>
                        @endif
                    </span>
                </span>
            </{{ $removed ? 'div' : 'a' }}>
        @endforeach
    </section>

    @if ($breeding->notes)
        <x-ui.card class="mt-4">
            <x-ui.card.header>
                <x-ui.card.title>Notes</x-ui.card.title>
            </x-ui.card.header>
            <x-ui.card.content>
                <p class="max-w-[70ch] whitespace-pre-line text-sm leading-relaxed text-foreground lg:text-xs">{{ $breeding->notes }}</p>
            </x-ui.card.content>
        </x-ui.card>
    @endif

    {{-- ---------------------------------------------------------------
         OFFSPRING. Generating chicks here is what makes the pedigree real:
         each is created with sire_id and dam_id already set, so the family
         tree is a by-product of normal data entry.
    --------------------------------------------------------------- --}}
    <x-ui.card class="mt-4" :flush="true" aria-labelledby="offspring-heading">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <div>
                <h2 id="offspring-heading" class="text-sm font-semibold text-foreground">Offspring</h2>
                <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs">
                    <span class="datum font-semibold text-foreground">{{ $breeding->offspring_count }}</span> of
                    <span class="datum font-semibold text-foreground">{{ $breeding->eggs_hatched }}</span>
                    {{ Str::plural('chick', $breeding->eggs_hatched) }} registered as bird records.
                </p>
            </div>

            @if ($breeding->hasUnregisteredOffspring() && ! $generating)
                @can('generateOffspring', $breeding)
                    <x-ui.button wire:click="startGenerating">
                        <x-ui.icon name="plus" /> Register {{ $unregistered }} {{ Str::plural('chick', $unregistered) }}
                    </x-ui.button>
                @endcan
            @endif
        </div>

        @if ($generating)
            <div class="border-t border-border bg-muted/40 px-5 py-5" role="group" aria-labelledby="generate-heading">
                <h3 id="generate-heading" class="text-sm font-semibold text-foreground">Register Chicks From This Hatch</h3>
                <p class="mt-2 text-[15px] lg:text-xs leading-relaxed text-muted-foreground">
                    Each chick will be created with
                    <strong class="font-semibold text-foreground">{{ $breeding->sire?->name ?? 'Bird removed' }}</strong> as its sire and
                    <strong class="font-semibold text-foreground">{{ $breeding->dam?->name ?? 'Bird removed' }}</strong> as its dam, so it appears in the
                    family tree straight away. You can rename and band them afterwards.
                </p>

                <div class="mt-4 grid max-w-3xl gap-4 sm:grid-cols-2">
                    <div>
                        <label for="generateCount" class="d-fieldset-legend">How many</label>
                        <input id="generateCount" type="number" min="1" max="{{ max(1, $unregistered) }}" inputmode="numeric"
                               wire:model="generateCount"
                               @error('generateCount') aria-invalid="true" aria-describedby="generateCount-error" @enderror
                               class="d-input w-full datum mt-1 @error('generateCount') d-input-error @enderror">
                        @error('generateCount') <p id="generateCount-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-form-select id="generateSex" label="Sex" wire:model="generateSex"
                                       help="Change individually later if the batch is mixed.">
                            @foreach (App\Enums\Sex::cases() as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </x-form-select>
                    </div>

                    <div>
                        <label for="generateDateHatched" class="d-fieldset-legend">Date hatched</label>
                        <input id="generateDateHatched" type="date" wire:model="generateDateHatched"
                               max="{{ today()->toDateString() }}"
                               @error('generateDateHatched') aria-invalid="true" aria-describedby="generateDateHatched-error" @enderror
                               class="d-input w-full datum mt-1 @error('generateDateHatched') d-input-error @enderror">
                        @error('generateDateHatched') <p id="generateDateHatched-error" class="mt-1.5 text-[13px] lg:text-[11px] font-medium text-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-form-select id="generatePenId" label="Pen" wire:model="generatePenId">
                            <option value="">Not assigned</option>
                            @foreach ($this->pens as $pen)
                                <option value="{{ $pen->id }}">{{ $pen->code }} - {{ $pen->name }}</option>
                            @endforeach
                        </x-form-select>
                    </div>
                </div>

                <div class="mt-5 flex max-w-3xl flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('generating', false)" class="d-btn d-btn-outline">Cancel</button>
                    <button type="button" wire:click="generate" class="d-btn d-btn-primary" wire:loading.attr="disabled" wire:target="generate">
                        <span class="d-loading d-loading-spinner d-loading-sm" wire:loading wire:target="generate"></span>
                        <span wire:loading.remove wire:target="generate">Register chicks</span>
                        <span wire:loading wire:target="generate">Registering&hellip;</span>
                    </button>
                </div>
            </div>
        @endif

        <div class="border-t border-border">
            @if ($offspring->isEmpty())
                <x-empty-state :bare="true" class="py-10" title="No chicks registered yet"
                               :description="$offspringEmptyLine"
                               icon="M12 3c-3.3 0-6 4.6-6 9.2A6 6 0 0 0 12 18.5a6 6 0 0 0 6-6.3C18 7.6 15.3 3 12 3Z" />
            @else
                {{-- Phones: one block per chick. --}}
                <ul class="divide-y divide-border px-5 md:hidden">
                    @foreach ($offspring as $child)
                        <li wire:key="c-m-{{ $child->id }}">
                            <a href="{{ route('broodcocks.show', $child) }}" wire:navigate class="block py-3">
                                <span class="flex min-h-11 items-center justify-between gap-3">
                                    <span class="min-w-0 truncate text-[16px] lg:text-xs font-medium text-foreground">{{ $child->name }}</span>
                                    <span class="d-badge d-badge-soft {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                </span>
                                <span class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[14px] lg:text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-band-dot :bloodline="$child->bloodline" />
                                        @if ($child->band_number)
                                            <span class="datum">{{ $child->band_number }}</span>
                                        @else
                                            Not yet banded
                                        @endif
                                    </span>
                                    <span>{{ $child->sex->label() }}</span>
                                    <span>
                                        @if ($child->date_hatched)
                                            hatched <span class="datum">{{ $child->date_hatched->format('j M Y') }}</span>
                                        @else
                                            hatch date not recorded
                                        @endif
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Wider screens: a ledger. Resting on a row shows the chick's photo. --}}
                <table class="d-table hidden w-full text-left md:table">
                    <thead>
                        <tr class="border-b border-border">
                            @foreach (['Name', 'Band number', 'Sex', 'Hatched', 'Status'] as $heading)
                                <th scope="col" class="h-10 px-5 text-[13px] font-medium text-muted-foreground lg:h-9 lg:text-xs">{{ $heading }}</th>
                            @endforeach
                            <th scope="col" class="h-10 px-5 text-right text-[13px] font-medium text-muted-foreground lg:h-9 lg:text-xs">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($offspring as $child)
                            <tr class="row-hover border-b border-border last:border-b-0" wire:key="c-{{ $child->id }}"
                                x-data="{ preview: false, timer: null }"
                                x-on:mouseenter="if (matchMedia('(hover: hover)').matches) { timer = setTimeout(() => preview = true, 350) }"
                                x-on:mouseleave="clearTimeout(timer); preview = false">
                                <td class="px-5 py-2">
                                    <span class="relative inline-flex">
                                        <a href="{{ route('broodcocks.show', $child) }}" wire:navigate
                                           class="inline-flex min-h-11 items-center text-sm font-semibold text-foreground hover:text-primary lg:min-h-0 lg:text-xs">{{ $child->name }}</a>
                                        <x-bird-hover-card :bird="$child" />
                                    </span>
                                </td>
                                <td class="px-5 py-2">
                                    <span class="inline-flex items-center gap-2 text-sm lg:text-xs">
                                        <x-band-dot :bloodline="$child->bloodline" />
                                        @if ($child->band_number)
                                            <span class="datum text-foreground">{{ $child->band_number }}</span>
                                        @else
                                            <span class="text-muted-foreground">Not yet banded</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-2 text-sm text-muted-foreground lg:text-xs">{{ $child->sex->label() }}</td>
                                <td class="px-5 py-2 text-sm lg:text-xs">
                                    @if ($child->date_hatched)
                                        <span class="datum text-foreground">{{ $child->date_hatched->format('j M Y') }}</span>
                                    @else
                                        <span class="text-muted-foreground">Not recorded</span>
                                    @endif
                                </td>
                                <td class="px-5 py-2">
                                    <span class="d-badge d-badge-soft {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                </td>
                                <td class="px-5 py-2 text-right">
                                    <x-ui.row-action icon="eye" :label="'View '.$child->name" :href="route('broodcocks.show', $child)" wire:navigate />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-ui.card>

    @can('update', $this->record)
        {{-- Edit opens here, in a dialog over this page. --}}
        <x-ui.form-dialog id="breeding-form" model="showForm" size="xl" :wrap="false" :title="'Edit Breeding Record'">
            <livewire:breeding.form :record="$this->record" :in-dialog="true" :key="'breeding-show-form-'.$formNonce" />
        </x-ui.form-dialog>
    @endcan
</div>
