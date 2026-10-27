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
    ONE MATING.

    The pair first - the two birds side by side, each opening its own page -
    then the hatch as label/value rows, then the chicks it produced as a
    ledger. Nothing boxed except the two birds, which are separate objects
    a keeper will want to open.
--}}
<div>
    <a href="{{ route('breeding.index') }}" wire:navigate
       class="inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-primary hover:underline hover:underline-offset-4">
        <span aria-hidden="true">&larr;</span> Back to breeding records
    </a>

    <header class="mt-2 flex flex-wrap items-end justify-between gap-x-10 gap-y-5 border-b border-border pb-6 sm:pb-8">
        <div class="min-w-0">
            <p class="sb-line sb-eyebrow" style="--line: 0ms">Breeding record</p>
            <h1 class="sb-line mt-2 font-display text-[1.9rem] font-semibold leading-[1.08] tracking-[-0.025em] text-foreground sm:text-[2.3rem]" style="--line: 60ms">
                {{ $breeding->sire->name }} &times; {{ $breeding->dam->name }}
            </h1>
            <p class="sb-line mt-3 text-[15px] text-muted-foreground" style="--line: 120ms">
                Mated on <span class="datum text-foreground">{{ $breeding->mating_date->format('j F Y') }}</span>
                @if ($breeding->recordedBy)
                    &middot; recorded by {{ $breeding->recordedBy->full_name }}
                @endif
            </p>
        </div>

        @can('update', $breeding)
            <div class="sb-line flex flex-wrap items-center gap-3" style="--line: 180ms">
                <a href="{{ route('breeding.edit', $breeding) }}" wire:navigate class="btn-secondary">Edit</a>
            </div>
        @endcan
    </header>

    {{-- ---------------------------------------------------------------
         THE PAIR. Sire and dam side by side, each a card because each is
         a bird with its own page. The band dot and the band number are
         the identifiers a keeper reads; the name is the link.
    --------------------------------------------------------------- --}}
    <section aria-label="The pair" class="mt-8 grid items-center gap-3 sm:grid-cols-[1fr_auto_1fr] sm:gap-4">
        @foreach ([['Sire', $breeding->sire], ['Dam', $breeding->dam]] as [$role, $parent])
            @if (! $loop->first)
                <span class="hidden text-center font-display text-[22px] text-muted-foreground sm:block" aria-hidden="true">&times;</span>
            @endif

            <a href="{{ route('broodcocks.show', $parent) }}" wire:navigate
               class="group block min-h-11 rounded-[12px] border border-border bg-card p-5 elev-1">
                <span class="block text-[14px] font-medium text-muted-foreground">{{ $role }}</span>
                <span class="mt-1 block font-display text-[22px] font-semibold leading-tight tracking-[-0.015em] text-foreground group-hover:underline group-hover:underline-offset-4">
                    {{ $parent->name }}
                </span>
                <span class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[15px]">
                    <span class="inline-flex items-center gap-2 text-foreground">
                        <x-band-dot :bloodline="$parent->bloodline" />
                        @if ($parent->band_number)
                            <span class="datum">{{ $parent->band_number }}</span>
                        @else
                            <span class="text-muted-foreground">Not yet banded</span>
                        @endif
                    </span>
                    <span class="text-muted-foreground">{{ $parent->bloodline ?: 'Bloodline not recorded' }}</span>
                </span>
            </a>
        @endforeach
    </section>

    {{-- ---------------------------------------------------------------
         THE HATCH, as the funnel it is. Set, then fertile, then hatched -
         each a subset of the one before - and each ratio beside the figure
         it describes, naming its own denominator. BreedingRecord::hatchRate
         is measured against FERTILE eggs, not eggs set, so a rate without
         its basis invites exactly the wrong reading.
    --------------------------------------------------------------- --}}
    <section class="mt-12" aria-labelledby="hatch-heading" data-reveal>
        <h2 id="hatch-heading" class="sb-h2">The hatch</h2>

        <dl class="mt-5 max-w-[44rem] border-t border-border">
            <x-field-row label="Eggs set">
                <span class="datum">{{ $breeding->eggs_set }}</span>
            </x-field-row>
            <x-field-row label="Fertile">
                <span class="datum">{{ $breeding->eggs_fertile }}</span>
                @if ($rate($breeding->fertilityRate()))
                    <span class="ml-2 text-[14px] text-muted-foreground"><span class="datum">{{ $rate($breeding->fertilityRate()) }}</span> of eggs set</span>
                @endif
            </x-field-row>
            <x-field-row label="Hatched">
                <span class="datum">{{ $breeding->eggs_hatched }}</span>
                @if ($rate($breeding->hatchRate()))
                    <span class="ml-2 text-[14px] text-muted-foreground"><span class="datum">{{ $rate($breeding->hatchRate()) }}</span> of fertile</span>
                @endif
            </x-field-row>
            <x-field-row label="End to end">
                {{-- The yield: the one number a breeder judges a mating by. --}}
                @if ($breeding->overallHatchRate() !== null)
                    <span class="datum">{{ $breeding->eggs_hatched }}</span>
                    of <span class="datum">{{ $breeding->eggs_set }}</span>
                    {{ Str::plural('egg', $breeding->eggs_set) }} set reached hatch
                    &mdash; <span class="datum">{{ $rate($breeding->overallHatchRate()) }}</span>.
                @else
                    <span class="text-muted-foreground">No eggs have been recorded as set for this mating yet.</span>
                @endif
            </x-field-row>
            @if ($breeding->notes)
                <x-field-row label="Notes">
                    <span class="block max-w-[60ch] whitespace-pre-line leading-relaxed">{{ $breeding->notes }}</span>
                </x-field-row>
            @endif
        </dl>
    </section>

    {{-- ---------------------------------------------------------------
         OFFSPRING. Generating chicks here is what makes the pedigree real:
         each is created with sire_id and dam_id already set, so the family
         tree is a by-product of normal data entry.
    --------------------------------------------------------------- --}}
    <section class="mt-12" aria-labelledby="offspring-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="offspring-heading" class="sb-h2">Offspring</h2>
                <p class="mt-2 text-[15px] text-muted-foreground">
                    <span class="datum text-foreground">{{ $breeding->offspring_count }}</span> of
                    <span class="datum text-foreground">{{ $breeding->eggs_hatched }}</span>
                    {{ Str::plural('chick', $breeding->eggs_hatched) }} registered as bird records.
                </p>
            </div>

            @if ($breeding->hasUnregisteredOffspring() && ! $generating)
                @can('generateOffspring', $breeding)
                    <button type="button" wire:click="startGenerating" class="btn-primary">
                        Register {{ $unregistered }} {{ Str::plural('chick', $unregistered) }}
                    </button>
                @endcan
            @endif
        </div>

        @if ($generating)
            <div class="mt-6 max-w-[44rem] rounded-[12px] border border-border bg-card p-5 sm:p-6" role="group" aria-labelledby="generate-heading">
                <h3 id="generate-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Register chicks from this hatch</h3>
                <p class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                    Each chick will be created with
                    <strong class="font-semibold text-foreground">{{ $breeding->sire->name }}</strong> as its sire and
                    <strong class="font-semibold text-foreground">{{ $breeding->dam->name }}</strong> as its dam, so it appears in the
                    family tree straight away. You can rename and band them afterwards.
                </p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="generateCount" class="label">How many</label>
                        <input id="generateCount" type="number" min="1" max="{{ max(1, $unregistered) }}" inputmode="numeric"
                               wire:model="generateCount"
                               @error('generateCount') aria-invalid="true" aria-describedby="generateCount-error" @enderror
                               class="input datum mt-1 @error('generateCount') input-error @enderror">
                        @error('generateCount') <p id="generateCount-error" class="error" role="alert">{{ $message }}</p> @enderror
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
                               @error('generateDateHatched') aria-invalid="true" aria-describedby="generateDateHatched-error" @enderror
                               class="input datum mt-1 @error('generateDateHatched') input-error @enderror">
                        @error('generateDateHatched') <p id="generateDateHatched-error" class="error" role="alert">{{ $message }}</p> @enderror
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

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('generating', false)" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="generate" class="btn-primary" wire:loading.attr="disabled" wire:target="generate">
                        <span class="btn-spinner" wire:loading wire:target="generate"></span>
                        <span wire:loading.remove wire:target="generate">Register chicks</span>
                        <span wire:loading wire:target="generate">Registering&hellip;</span>
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-6">
            @if ($offspring->isEmpty())
                <x-empty-state title="No chicks registered yet"
                               :description="$offspringEmptyLine"
                               icon="M12 3c-3.3 0-6 4.6-6 9.2A6 6 0 0 0 12 18.5a6 6 0 0 0 6-6.3C18 7.6 15.3 3 12 3Z" />
            @else
                {{-- Phones: one block per chick. --}}
                <ul class="divide-y divide-border border-y border-border md:hidden">
                    @foreach ($offspring as $child)
                        <li wire:key="c-m-{{ $child->id }}">
                            <a href="{{ route('broodcocks.show', $child) }}" wire:navigate class="block py-3">
                                <span class="flex min-h-11 items-center justify-between gap-3">
                                    <span class="min-w-0 truncate text-[16px] font-medium text-foreground">{{ $child->name }}</span>
                                    <span class="badge {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                </span>
                                <span class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[14px] text-muted-foreground">
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

                {{-- Wider screens: a ledger. --}}
                <table class="hidden w-full border-separate border-spacing-0 text-[15px] md:table">
                    <thead class="sticky top-0 z-10 bg-background">
                        <tr>
                            @foreach (['Name', 'Band', 'Sex', 'Hatched', 'Status'] as $heading)
                                <th scope="col" @class([
                                    'border-b border-border py-3 text-left text-[13px] font-semibold text-muted-foreground',
                                    'pr-4' => $loop->first,
                                    'px-4' => ! $loop->first,
                                ])>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($offspring as $child)
                            <tr class="row-hover" wire:key="c-{{ $child->id }}">
                                <td class="border-b border-border py-2 pr-4">
                                    <a href="{{ route('broodcocks.show', $child) }}" wire:navigate
                                       class="inline-flex min-h-11 items-center font-medium text-foreground hover:underline hover:underline-offset-4">
                                        {{ $child->name }}
                                    </a>
                                </td>
                                <td class="border-b border-border px-4 py-2">
                                    <span class="inline-flex items-center gap-2">
                                        <x-band-dot :bloodline="$child->bloodline" />
                                        @if ($child->band_number)
                                            <span class="datum text-foreground">{{ $child->band_number }}</span>
                                        @else
                                            <span class="text-muted-foreground">Not yet banded</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="border-b border-border px-4 py-2 text-muted-foreground">{{ $child->sex->label() }}</td>
                                <td class="border-b border-border px-4 py-2">
                                    @if ($child->date_hatched)
                                        <span class="datum text-foreground">{{ $child->date_hatched->format('j M Y') }}</span>
                                    @else
                                        <span class="text-muted-foreground">Not recorded</span>
                                    @endif
                                </td>
                                <td class="border-b border-border px-4 py-2">
                                    <span class="badge {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>
