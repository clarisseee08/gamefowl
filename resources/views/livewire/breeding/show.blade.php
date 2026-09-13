@php $breeding = $this->breeding; @endphp

<div>
    <div class="mb-10">
        <a href="{{ route('breeding.index') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">
            &larr; Back to breeding records
        </a>

        <div class="mt-2 sm:flex sm:items-start sm:justify-between">
            <div>
                <h1 class="page-title-marked text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-foreground">
                    {{ $breeding->sire->name }} &times; {{ $breeding->dam->name }}
                </h1>
                <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                    Mated on <span class="datum">{{ $breeding->mating_date->format('j F Y') }}</span>
                    @if ($breeding->recordedBy)
                        &middot; recorded by {{ $breeding->recordedBy->full_name }}
                    @endif
                </p>
            </div>

            @can('update', $breeding)
                <a href="{{ route('breeding.edit', $breeding) }}" wire:navigate class="btn-secondary mt-4 sm:mt-0">Edit</a>
            @endcan
        </div>
    </div>

    {{-- ---------------------------------------------------------------
         THE HATCH, as the funnel it actually is.

         This was five identical cards in a row - Eggs Set, Fertile, Hatched,
         Fertility Rate, Hatch Rate - each a 12px label over a 34px figure.
         That is the hero-metric template laid out five times, and it was
         telling two lies about the data by arranging it that way.

         The five are not peers. Three are one funnel (set, then fertile, then
         hatched, each a subset of the one before) and two are ratios DERIVED
         from that funnel. Five equal cards flattens a chain into a row.

         And the denominators were invisible. BreedingRecord::hatchRate is
         deliberately measured against FERTILE eggs rather than eggs set - its
         own docblock says so, because hatchability is a property of a fertile
         egg. A card reading "Hatch Rate 77.8%" next to a card reading "Eggs
         Set 12" invites exactly the wrong reading. Each ratio now sits beside
         the figure it describes and names its own denominator.

         overallHatchRate() is the end-to-end yield and the method already
         existed - the reports use it. This page simply never showed the one
         number a breeder judges a mating by.

         EVERY FIGURE IS .datum. They were set in the display face at 34px,
         which is the one rule this system does not bend: a count, a rate and a
         percentage are registry data and they align in a column.
    --------------------------------------------------------------- --}}
    @php
        $rate = fn (?float $value) => $value === null ? null : number_format($value, 1).'%';

        $funnel = [
            // Four elements on every row, including the one with no ratio:
            // PHP list destructuring raises "Undefined array key 3" on a short
            // row rather than handing back null.
            ['Eggs set', $breeding->eggs_set, null, null],
            ['Fertile', $breeding->eggs_fertile, $rate($breeding->fertilityRate()), 'of eggs set'],
            ['Hatched', $breeding->eggs_hatched, $rate($breeding->hatchRate()), 'of fertile'],
        ];
    @endphp

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <div class="card p-6 sm:p-7 lg:col-span-2">
            <h2 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">The hatch</h2>

            <dl class="mt-3 divide-y divide-border border-t border-border">
                @foreach ($funnel as [$label, $count, $ratio, $basis])
                    <div class="flex items-baseline justify-between gap-4 py-3">
                        <dt class="min-w-0 text-[15px] text-foreground">
                            {{ $label }}
                            @if ($ratio)
                                <span class="ml-2 text-[13px] text-muted-foreground">
                                    <span class="datum">{{ $ratio }}</span> {{ $basis }}
                                </span>
                            @endif
                        </dt>
                        <dd class="datum shrink-0 text-[22px] font-medium leading-none text-foreground">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- The yield, stated once and plainly. This is the number a
                 breeder judges a mating by, and it was the one the page did
                 not have. --}}
            <p class="mt-4 text-[15px] text-muted-foreground">
                @if ($breeding->overallHatchRate() !== null)
                    End to end, <span class="datum text-foreground">{{ $breeding->eggs_hatched }}</span>
                    of <span class="datum text-foreground">{{ $breeding->eggs_set }}</span>
                    {{ Str::plural('egg', $breeding->eggs_set) }} set reached hatch
                    &mdash; <span class="datum text-foreground">{{ $rate($breeding->overallHatchRate()) }}</span>.
                @else
                    No eggs have been recorded as set for this mating yet.
                @endif
            </p>
        </div>

        {{-- ---------------------------------------------------------
             THE PAIR.

             Each bird is a row carrying its own band tag, the same object the
             catalogue, the front page and the bird page use. It was a name and
             a parenthesised band as link text under an uppercase SIRE / DAM
             kicker - which buries the band, and a kicker above a name is the
             one device the craft floor bans outright.

             It also read "no band", a third phrasing of a state the band-tag
             component already states as "Not yet banded". One vocabulary.
        --------------------------------------------------------- --}}
        <div class="card p-6 sm:p-7">
            <h2 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">The pair</h2>

            <dl class="mt-3 divide-y divide-border border-t border-border">
                @foreach ([['Sire', $breeding->sire], ['Dam', $breeding->dam]] as [$role, $parent])
                    <div class="flex items-start gap-3 py-3">
                        <dt class="w-10 shrink-0 pt-px text-[13px] text-muted-foreground">{{ $role }}</dt>
                        <dd class="min-w-0 flex-1">
                            <a href="{{ route('broodcocks.show', $parent) }}" wire:navigate
                               class="group block min-h-11">
                                <span class="block text-[15px] font-medium text-foreground group-hover:underline">{{ $parent->name }}</span>
                                <span class="mt-1.5 block">
                                    <x-band-tag :bloodline="$parent->bloodline" :band="$parent->band_number" size="xs" />
                                </span>
                            </a>
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if ($breeding->notes)
                <div class="mt-6">
                    <h3 class="text-[12px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Notes</h3>
                    <p class="mt-3 whitespace-pre-line border-t border-border pt-3 text-[15px] leading-relaxed text-foreground">
                        {{ $breeding->notes }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Offspring --}}
    <div class="card p-6 sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-foreground">Offspring</h2>
                <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                    <span class="datum">{{ $breeding->offspring_count }}</span> of
                    <span class="datum">{{ $breeding->eggs_hatched }}</span>
                    {{ Str::plural('chick', $breeding->eggs_hatched) }} registered as bird records.
                </p>
            </div>

            @if ($breeding->hasUnregisteredOffspring())
                @can('generateOffspring', $breeding)
                    <button type="button" wire:click="startGenerating" class="btn-primary">
                        Register {{ $breeding->unregisteredOffspring() }} {{ Str::plural('chick', $breeding->unregisteredOffspring()) }}
                    </button>
                @endcan
            @endif
        </div>

        {{-- Generation form. This is what makes the pedigree real: the
             chicks are created with sire_id and dam_id already set, so the
             family tree is a by-product of normal data entry. --}}
        @if ($generating)
            <div class="mt-5 rounded-[var(--radius-md)] bg-success-bg p-4 ring-1 ring-success/20">
                <h3 class="text-sm font-semibold text-foreground">Register chicks from this hatch</h3>
                <p class="mt-1 text-sm text-foreground">
                    Each chick will be created with
                    <strong>{{ $breeding->sire->name }}</strong> as its sire and
                    <strong>{{ $breeding->dam->name }}</strong> as its dam, so it appears in the
                    family tree straight away. You can rename and band them afterwards.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="generateCount" class="label">How many</label>
                        <input id="generateCount" type="number" min="1" max="{{ max(1, $breeding->unregisteredOffspring()) }}"
                               wire:model="generateCount" class="input mt-1 @error('generateCount') input-error @enderror">
                        @error('generateCount') <p class="error">{{ $message }}</p> @enderror
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
                        <label for="generateDateHatched" class="label">Date hatched</label>
                        <input id="generateDateHatched" type="date" wire:model="generateDateHatched"
                               max="{{ today()->toDateString() }}"
                               class="input mt-1 @error('generateDateHatched') input-error @enderror">
                        @error('generateDateHatched') <p class="error">{{ $message }}</p> @enderror
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

                <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('generating', false)" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="generate" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="generate">Register chicks</span>
                        <span wire:loading wire:target="generate">Registering…</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Existing offspring. Each chick carries its band, in the same
             grammar as every other list of birds in the application - it was
             a plain string, so the one identifier a keeper actually uses was
             the only thing on the row with no visual weight at all. --}}
        <div class="mt-5">
            @if ($this->offspring->isEmpty())
                <div class="rounded-[var(--radius-md)] border border-dashed border-border p-8 text-center">
                    <p class="text-sm font-medium text-foreground">No chicks registered yet</p>
                    <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
                        @if ($breeding->eggs_hatched > 0)
                            <span class="datum">{{ $breeding->eggs_hatched }}</span>
                            {{ Str::plural('egg', $breeding->eggs_hatched) }} hatched.
                            Use the button above to add them as bird records.
                        @else
                            Nothing hatched from this mating.
                        @endif
                    </p>
                </div>
            @else
                <ul class="divide-y divide-border border-t border-border">
                    @foreach ($this->offspring as $child)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <a href="{{ route('broodcocks.show', $child) }}" wire:navigate
                               class="group flex min-h-11 min-w-0 flex-1 items-center gap-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-[15px] font-medium text-foreground group-hover:underline">
                                        {{ $child->name }}
                                    </span>
                                    <span class="mt-0.5 block text-[13px] text-muted-foreground">
                                        {{ $child->sex->label() }} &middot;
                                        @if ($child->date_hatched)
                                            hatched <span class="datum">{{ $child->date_hatched->format('j M Y') }}</span>
                                        @else
                                            hatch date not recorded
                                        @endif
                                    </span>
                                </span>
                            </a>

                            <span class="flex shrink-0 items-center gap-3">
                                <x-band-tag :bloodline="$child->bloodline" :band="$child->band_number" size="xs" />
                                <span class="badge {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
