@php $breeding = $this->breeding; @endphp

<div>
    <div class="mb-6">
        <a href="{{ route('breeding.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
            &larr; Back to breeding records
        </a>

        <div class="mt-2 sm:flex sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ $breeding->sire->name }} &times; {{ $breeding->dam->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    Mated on {{ $breeding->mating_date->format('j F Y') }}
                    @if ($breeding->recordedBy)
                        &middot; recorded by {{ $breeding->recordedBy->full_name }}
                    @endif
                </p>
            </div>

            @can('update', $breeding)
                <a href="{{ route('breeding.edit', $breeding) }}" class="btn-secondary mt-4 sm:mt-0">Edit</a>
            @endcan
        </div>
    </div>

    {{-- Results --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['Eggs Set', $breeding->eggs_set],
            ['Fertile', $breeding->eggs_fertile],
            ['Hatched', $breeding->eggs_hatched],
            ['Fertility Rate', $breeding->fertilityRate() !== null ? $breeding->fertilityRate().'%' : '—'],
            ['Hatch Rate', $breeding->hatchRate() !== null ? $breeding->hatchRate().'%' : '—'],
        ] as [$label, $value])
            <div class="card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Parents --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">The Pair</h2>
            <div class="mt-4 space-y-4">
                @foreach ([['Sire', $breeding->sire], ['Dam', $breeding->dam]] as [$label, $parent])
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <a href="{{ route('broodcocks.show', $parent) }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                            {{ $parent->name }} ({{ $parent->band_number ?? 'no band' }})
                        </a>
                        <p class="text-xs text-gray-500">{{ $parent->bloodline ?? 'Bloodline not recorded' }}</p>
                    </div>
                @endforeach
            </div>

            @if ($breeding->notes)
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Notes</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-900">{{ $breeding->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Offspring --}}
        <div class="card p-6 lg:col-span-2">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Offspring</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $breeding->offspring_count }} of {{ $breeding->eggs_hatched }}
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
                <div class="mt-4 rounded-lg bg-brand-50 p-4 ring-1 ring-brand-200">
                    <h3 class="text-sm font-semibold text-brand-900">Register chicks from this hatch</h3>
                    <p class="mt-1 text-sm text-brand-800">
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
                            <label for="generateSex" class="label">Sex</label>
                            <select id="generateSex" wire:model="generateSex" class="input mt-1">
                                @foreach (App\Enums\Sex::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            <p class="help">Change individually later if the batch is mixed.</p>
                        </div>

                        <div>
                            <label for="generateDateHatched" class="label">Date hatched</label>
                            <input id="generateDateHatched" type="date" wire:model="generateDateHatched"
                                   max="{{ today()->toDateString() }}"
                                   class="input mt-1 @error('generateDateHatched') input-error @enderror">
                            @error('generateDateHatched') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="generatePenId" class="label">Pen</label>
                            <select id="generatePenId" wire:model="generatePenId" class="input mt-1">
                                <option value="">Not assigned</option>
                                @foreach ($this->pens as $pen)
                                    <option value="{{ $pen->id }}">{{ $pen->code }} - {{ $pen->name }}</option>
                                @endforeach
                            </select>
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

            {{-- Existing offspring --}}
            <div class="mt-4">
                @if ($this->offspring->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center">
                        <p class="text-sm font-medium text-gray-900">No chicks registered yet</p>
                        <p class="mt-1 text-sm text-gray-600">
                            @if ($breeding->eggs_hatched > 0)
                                {{ $breeding->eggs_hatched }} {{ Str::plural('egg', $breeding->eggs_hatched) }} hatched.
                                Use the button above to add them as bird records.
                            @else
                                Nothing hatched from this mating.
                            @endif
                        </p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($this->offspring as $child)
                            <li class="flex items-center justify-between py-3">
                                <div>
                                    <a href="{{ route('broodcocks.show', $child) }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                                        {{ $child->name }}
                                    </a>
                                    <p class="text-xs text-gray-500">
                                        {{ $child->band_number ?? 'Not yet banded' }} &middot;
                                        {{ $child->sex->label() }} &middot;
                                        {{ $child->date_hatched?->format('j M Y') ?? 'Hatch date unknown' }}
                                    </p>
                                </div>
                                <span class="badge {{ $child->status->badgeClasses() }}">{{ $child->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
