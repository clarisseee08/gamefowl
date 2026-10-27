@php
    $editing = $this->isEditing();

    /*
     * aria-describedby for a text field: its help line (when it has one) and
     * its error (when it has one), in reading order. Built here so every input
     * below states the relationship the same way.
     */
    $describedBy = function (string $field, bool $hasHelp = false) use ($errors): ?string {
        $ids = array_filter([
            $hasHelp ? $field.'-help' : null,
            $errors->has($field) ? $field.'-error' : null,
        ]);

        return $ids === [] ? null : implode(' ', $ids);
    };

    $heading = 'font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground';
@endphp

{{--
    ADD OR EDIT A BROODCOCK.

    One column, left-aligned at a reading width, in four groups a keeper
    already thinks in: who the bird is, who its parents are, what it looks
    like, and anything else. Labels above, help and errors under. The Save
    bar is sticky, so on a phone the button is never a long scroll away from
    the field just filled in.
--}}
<div>
    <div class="max-w-[40rem]">
        <a href="{{ $editing ? route('broodcocks.show', $broodcock) : route('broodcocks.index') }}" wire:navigate
           class="inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-primary hover:underline hover:underline-offset-4">
            <span aria-hidden="true">&larr;</span> {{ $editing ? 'Back to '.($broodcock->band_number ?: $broodcock->name) : 'Back to broodcocks' }}
        </a>

        <header class="mt-3 border-b border-border pb-6">
            <p class="sb-line sb-eyebrow" style="--line: 0ms">{{ $editing ? 'Edit bird' : 'Register a bird' }}</p>
            <h1 class="sb-line mt-2 font-display text-[1.9rem] font-semibold leading-[1.08] tracking-[-0.025em] text-foreground sm:text-[2.3rem]" style="--line: 60ms">
                {{ $editing ? 'Edit '.$broodcock->name : 'Add a Broodcock' }}
            </h1>
            <p class="sb-line mt-3 text-[16px] leading-relaxed text-muted-foreground" style="--line: 120ms">
                Fields marked <span class="text-destructive" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span>
                are required. Everything else can be filled in later.
            </p>
        </header>
    </div>

    <form wire:submit="save" novalidate>
        <div class="max-w-[40rem]">
            {{-- IDENTITY --}}
            <section class="pt-8" aria-labelledby="identity-heading">
                <h2 id="identity-heading" class="{{ $heading }}">Identity</h2>
                <p class="mt-1 text-[15px] text-muted-foreground">The details used to recognise this bird.</p>

                <div class="mt-6 space-y-6">
                    <div>
                        <label for="name" class="label">Name <span class="text-destructive">*</span></label>
                        <input id="name" type="text" wire:model.blur="name" autocomplete="off"
                               @if ($describedBy('name')) aria-describedby="{{ $describedBy('name') }}" @endif
                               @error('name') aria-invalid="true" @enderror
                               class="input mt-1 @error('name') input-error @enderror">
                        @error('name') <p id="name-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="band_number" class="label">Band number</label>
                        <input id="band_number" type="text" wire:model.blur="band_number" autocomplete="off"
                               placeholder="e.g. SW-1024"
                               aria-describedby="{{ $describedBy('band_number', true) }}"
                               @error('band_number') aria-invalid="true" @enderror
                               class="input datum mt-1 @error('band_number') input-error @enderror">
                        <p id="band_number-help" class="help">Leave empty if the bird has not been banded yet.</p>
                        @error('band_number') <p id="band_number-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="bloodline" class="label">Bloodline</label>
                        <input id="bloodline" type="text" wire:model.blur="bloodline" autocomplete="off"
                               @if ($describedBy('bloodline')) aria-describedby="{{ $describedBy('bloodline') }}" @endif
                               @error('bloodline') aria-invalid="true" @enderror
                               class="input mt-1 @error('bloodline') input-error @enderror">
                        @error('bloodline') <p id="bloodline-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <x-form-select id="sex" label="Sex" required wire:model.live="sex">
                        @foreach ($this->sexOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }} ({{ $option->farmTerm() }})</option>
                        @endforeach
                    </x-form-select>

                    <x-form-select id="class" label="Class" required wire:model.blur="class">
                        @foreach ($this->classOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }} - {{ $option->description() }}</option>
                        @endforeach
                    </x-form-select>

                    <x-form-select id="status" label="Status" required wire:model.blur="status">
                        @foreach ($this->statusOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </x-form-select>
                </div>
            </section>

            {{-- PARENTS --}}
            <section class="mt-10 border-t border-border pt-8" aria-labelledby="parents-heading">
                <h2 id="parents-heading" class="{{ $heading }}">Parents</h2>
                <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                    The sire and dam are what build this bird's family tree. Only a male can be a sire and
                    only a female a dam. If a parent is not on the list &mdash; bought in, borrowed for a
                    season, never registered &mdash; pick &ldquo;Someone else's bird&rdquo; at the end of the
                    list and type the name. It is recorded as a bird so the tree keeps that branch.
                </p>

                {{-- ONE CONTROL PER PARENT, not two.

                     The way out of the list is the last option IN the list. It used
                     to be a checkbox underneath, and ticking it REPLACED the
                     dropdown - so the keeper lost sight of the bird they had just
                     chosen. The typed fields now appear under the dropdown, which
                     keeps showing what was picked.

                     A typed name becomes a real broodcock row flagged is_external,
                     because sire_id and dam_id are foreign keys: storing the name as
                     free text would leave the id NULL and cut the tree off above
                     that bird. --}}
                <div class="mt-6 space-y-6">
                    <div>
                        <x-form-select id="sire_choice" label="Sire (Father)" error="sire_id"
                                       wire:model.live="sire_choice">
                            <option value="">Not known</option>
                            @foreach ($this->sireOptions as $option)
                                <option value="{{ $option->id }}">
                                    {{ $option->name }}{{ $option->band_number ? ' ('.$option->band_number.')' : '' }}
                                </option>
                            @endforeach
                            <option value="{{ App\Livewire\Broodcocks\Form::OFF_LIST }}">Someone else's bird</option>
                        </x-form-select>

                        @if ($sire_is_external)
                            {{-- Indented off a hairline so the fields read as belonging
                                 to the choice above rather than as two more questions. --}}
                            <div class="mt-4 space-y-4 border-l-2 border-border pl-4">
                                <div>
                                    <label for="sire_external_name" class="label">Name of the outside cock</label>
                                    <input id="sire_external_name" type="text" wire:model.blur="sire_external_name" autocomplete="off"
                                           @if ($describedBy('sire_external_name')) aria-describedby="{{ $describedBy('sire_external_name') }}" @endif
                                           @error('sire_external_name') aria-invalid="true" @enderror
                                           class="input mt-1 @error('sire_external_name') input-error @enderror">
                                    @error('sire_external_name') <p id="sire_external_name-error" class="error" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="sire_external_bloodline" class="label">Bloodline</label>
                                    <input id="sire_external_bloodline" type="text" wire:model.blur="sire_external_bloodline" autocomplete="off"
                                           aria-describedby="{{ $describedBy('sire_external_bloodline', true) }}"
                                           @error('sire_external_bloodline') aria-invalid="true" @enderror
                                           class="input mt-1 @error('sire_external_bloodline') input-error @enderror">
                                    <p id="sire_external_bloodline-help" class="help">Optional. Recorded so the bird carries its bloodline tag.</p>
                                    @error('sire_external_bloodline') <p id="sire_external_bloodline-error" class="error" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <x-form-select id="dam_choice" label="Dam (Mother)" error="dam_id"
                                       wire:model.live="dam_choice">
                            <option value="">Not known</option>
                            @foreach ($this->damOptions as $option)
                                <option value="{{ $option->id }}">
                                    {{ $option->name }}{{ $option->band_number ? ' ('.$option->band_number.')' : '' }}
                                </option>
                            @endforeach
                            <option value="{{ App\Livewire\Broodcocks\Form::OFF_LIST }}">Someone else's bird</option>
                        </x-form-select>

                        @if ($dam_is_external)
                            <div class="mt-4 space-y-4 border-l-2 border-border pl-4">
                                <div>
                                    <label for="dam_external_name" class="label">Name of the outside hen</label>
                                    <input id="dam_external_name" type="text" wire:model.blur="dam_external_name" autocomplete="off"
                                           @if ($describedBy('dam_external_name')) aria-describedby="{{ $describedBy('dam_external_name') }}" @endif
                                           @error('dam_external_name') aria-invalid="true" @enderror
                                           class="input mt-1 @error('dam_external_name') input-error @enderror">
                                    @error('dam_external_name') <p id="dam_external_name-error" class="error" role="alert">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="dam_external_bloodline" class="label">Bloodline</label>
                                    <input id="dam_external_bloodline" type="text" wire:model.blur="dam_external_bloodline" autocomplete="off"
                                           aria-describedby="{{ $describedBy('dam_external_bloodline', true) }}"
                                           @error('dam_external_bloodline') aria-invalid="true" @enderror
                                           class="input mt-1 @error('dam_external_bloodline') input-error @enderror">
                                    <p id="dam_external_bloodline-help" class="help">Optional. Recorded so the bird carries its bloodline tag.</p>
                                    @error('dam_external_bloodline') <p id="dam_external_bloodline-error" class="error" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- PARTICULARS --}}
            <section class="mt-10 border-t border-border pt-8" aria-labelledby="particulars-heading">
                <h2 id="particulars-heading" class="{{ $heading }}">Particulars</h2>
                <p class="mt-1 text-[15px] leading-relaxed text-muted-foreground">
                    Age is worked out from the hatch date, so it is always up to date.
                </p>

                <div class="mt-6 space-y-6">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="date_hatched" class="label">Date hatched</label>
                            <input id="date_hatched" type="date" wire:model.blur="date_hatched" max="{{ today()->toDateString() }}"
                                   @if ($describedBy('date_hatched')) aria-describedby="{{ $describedBy('date_hatched') }}" @endif
                                   @error('date_hatched') aria-invalid="true" @enderror
                                   class="input datum mt-1 @error('date_hatched') input-error @enderror">
                            @error('date_hatched') <p id="date_hatched-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="date_acquired" class="label">Date acquired</label>
                            <input id="date_acquired" type="date" wire:model.blur="date_acquired" max="{{ today()->toDateString() }}"
                                   aria-describedby="{{ $describedBy('date_acquired', true) }}"
                                   @error('date_acquired') aria-invalid="true" @enderror
                                   class="input datum mt-1 @error('date_acquired') input-error @enderror">
                            <p id="date_acquired-help" class="help">When the farm obtained this bird.</p>
                            @error('date_acquired') <p id="date_acquired-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="sm:w-1/2 sm:pr-3">
                        <label for="weight" class="label">Weight (kg)</label>
                        <input id="weight" type="number" step="0.01" min="0" inputmode="decimal" wire:model.blur="weight"
                               @if ($describedBy('weight')) aria-describedby="{{ $describedBy('weight') }}" @endif
                               @error('weight') aria-invalid="true" @enderror
                               class="input datum mt-1 @error('weight') input-error @enderror">
                        @error('weight') <p id="weight-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="color" class="label">Colour</label>
                        <input id="color" type="text" wire:model.blur="color" placeholder="e.g. Red, Black, Spangled"
                               @if ($describedBy('color')) aria-describedby="{{ $describedBy('color') }}" @endif
                               @error('color') aria-invalid="true" @enderror
                               class="input mt-1 @error('color') input-error @enderror">
                        @error('color') <p id="color-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="comb_type" class="label">Comb type</label>
                            <input id="comb_type" type="text" wire:model.blur="comb_type" placeholder="e.g. Straight, Pea"
                                   @if ($describedBy('comb_type')) aria-describedby="{{ $describedBy('comb_type') }}" @endif
                                   @error('comb_type') aria-invalid="true" @enderror
                                   class="input mt-1 @error('comb_type') input-error @enderror">
                            @error('comb_type') <p id="comb_type-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="leg_color" class="label">Leg colour</label>
                            <input id="leg_color" type="text" wire:model.blur="leg_color" placeholder="e.g. Yellow, White"
                                   @if ($describedBy('leg_color')) aria-describedby="{{ $describedBy('leg_color') }}" @endif
                                   @error('leg_color') aria-invalid="true" @enderror
                                   class="input mt-1 @error('leg_color') input-error @enderror">
                            @error('leg_color') <p id="leg_color-error" class="error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="distinguishing_marks" class="label">Distinguishing marks</label>
                        <textarea id="distinguishing_marks" rows="3" wire:model.blur="distinguishing_marks"
                                  placeholder="Anything that helps tell this bird apart from a similar one"
                                  @if ($describedBy('distinguishing_marks')) aria-describedby="{{ $describedBy('distinguishing_marks') }}" @endif
                                  @error('distinguishing_marks') aria-invalid="true" @enderror
                                  class="input mt-1 @error('distinguishing_marks') input-error @enderror"></textarea>
                        @error('distinguishing_marks') <p id="distinguishing_marks-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- NOTES --}}
            <section class="mt-10 border-t border-border pt-8" aria-labelledby="notes-heading">
                <h2 id="notes-heading" class="{{ $heading }}">Notes</h2>

                <div class="mt-6 space-y-6">
                    {{-- The farm tracks this on every bird, so it belongs on the form
                         rather than buried in notes. A checkbox, not a switch: nothing
                         changes until Save. Defaults to not for sale - a bird wrongly
                         listed as available starts a conversation nobody intended. --}}
                    <div>
                        <label for="for_sale" class="flex min-h-11 cursor-pointer items-center gap-3">
                            <input id="for_sale" type="checkbox" wire:model="for_sale" aria-describedby="for_sale-help"
                                   class="h-5 w-5 shrink-0 accent-primary">
                            <span class="text-[16px] text-foreground">This bird is for sale</span>
                        </label>
                        <p id="for_sale-help" class="help pl-8">Shown to customers in the catalogue.</p>
                    </div>

                    <div>
                        <label for="notes" class="label">Notes</label>
                        <textarea id="notes" rows="4" wire:model.blur="notes"
                                  aria-describedby="{{ $describedBy('notes', true) }}"
                                  @error('notes') aria-invalid="true" @enderror
                                  class="input mt-1 @error('notes') input-error @enderror"></textarea>
                        <p id="notes-help" class="help">Internal notes. Customers never see these.</p>
                        @error('notes') <p id="notes-error" class="error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{--
                PHOTOS.

                A photo row needs a broodcock_id, so when adding a NEW bird the
                record cannot exist yet at the moment the file is picked. The files
                are held on the component and attached the instant the insert
                commits, so a keeper photographs and records a bird in one pass.
                On an existing bird this is the "add more" path; the Photos tab on
                the bird's own page is where existing photos are managed.
            --}}
            @can('create', App\Models\BroodcockPhoto::class)
                <section class="mt-10 border-t border-border pt-8" aria-labelledby="photos-heading"
                         x-data="{ uploading: false, progress: 0 }"
                         x-on:livewire-upload-start="uploading = true; progress = 0"
                         x-on:livewire-upload-finish="uploading = false; progress = 100"
                         x-on:livewire-upload-cancel="uploading = false"
                         x-on:livewire-upload-error="uploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress">
                    <h2 id="photos-heading" class="{{ $heading }}">Photos</h2>
                    <p class="mt-1 text-[15px] text-muted-foreground">
                        Optional. JPG, PNG or WEBP, up to <span class="datum">4 MB</span> each.
                        @if ($editing)
                            These are added to the bird's existing photos.
                        @endif
                    </p>

                    {{-- `relative` IS LOAD-BEARING, not layout tidying. Tailwind's
                         sr-only is position:absolute, so with no positioned ancestor
                         the input is placed against the DOCUMENT. Clicking the zone
                         focuses it, the browser scrolls the WINDOW to bring it into
                         view, and because the console shell's only scroll container
                         is <main>, that scroll carries the whole layout off screen
                         with nothing to bring it back. --}}
                    <label x-data="fileDropzone()"
                           x-on:dragover.prevent="onDragOver()"
                           x-on:dragleave="onDragLeave($event)"
                           x-on:drop.prevent="onDrop($event)"
                           :class="dragging && 'border-primary bg-primary-50'"
                           class="relative mt-5 flex min-h-11 cursor-pointer flex-col items-center justify-center rounded-[12px] border border-dashed border-input px-6 py-8 text-center transition-colors hover:border-primary hover:bg-primary-50 focus-within:border-primary focus-within:bg-primary-50">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5"/>
                        </svg>
                        <span class="mt-2 text-[16px] font-medium text-foreground"
                              x-text="dragging ? 'Drop to add them' : 'Tap to choose photos'">Tap to choose photos</span>
                        <span class="mt-0.5 text-[14px] text-muted-foreground">You can pick more than one, or drag them here.</span>
                        <input x-ref="input" type="file" wire:model="photos" accept="image/*" multiple class="sr-only">
                    </label>

                    <div x-show="uploading" x-cloak class="mt-4" role="status" aria-live="polite">
                        <div class="flex items-center justify-between text-[15px] text-foreground">
                            <span>Uploading&hellip;</span>
                            <span class="datum" x-text="progress + '%'"></span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted" role="progressbar"
                             aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progress" aria-label="Upload progress">
                            <div class="h-full bg-foreground transition-[width] duration-150" :style="`width: ${progress}%`"></div>
                        </div>
                    </div>

                    @error('photos') <p id="photos-error" class="error" role="alert">{{ $message }}</p> @enderror
                    @error('photos.*') <p class="error" role="alert">{{ $message }}</p> @enderror

                    @if ($photos)
                        <ul class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-4">
                            @foreach ($photos as $index => $photo)
                                <li wire:key="form-photo-{{ $index }}" class="relative">
                                    <div class="aspect-[4/5] overflow-hidden rounded-[8px] bg-muted">
                                        {{-- isPreviewable() is load-bearing: temporaryUrl()
                                             THROWS on a non-image, so without it choosing a
                                             PDF crashes the page instead of showing the
                                             validation message already waiting for it. --}}
                                        @if ($photo->isPreviewable())
                                            <img src="{{ $photo->temporaryUrl() }}" alt="Selected photo {{ $index + 1 }}"
                                                 class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center px-2 text-center text-[13px] text-muted-foreground">
                                                Not an image
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="removePhoto({{ $index }})"
                                            class="elev-1 absolute right-1 top-1 inline-flex h-11 w-11 items-center justify-center rounded-full bg-card text-foreground hover:bg-muted"
                                            aria-label="Remove selected photo {{ $index + 1 }}" title="Remove this photo">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-2 text-[14px] text-muted-foreground">
                            <span class="datum">{{ count($photos) }}</span>
                            {{ Str::plural('photo', count($photos)) }} will be attached when you save.
                        </p>
                    @endif
                </section>
            @endcan
        </div>

        {{-- THE SAVE BAR. Sticky to the bottom of the content region, so Save
             is always in reach on a phone. Ground, not blur. --}}
        <div class="sticky bottom-0 z-20 -mx-4 mt-10 border-t border-border bg-background/95 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="flex max-w-[40rem] justify-end gap-3">
                <a href="{{ $editing ? route('broodcocks.show', $broodcock) : route('broodcocks.index') }}" wire:navigate
                   class="btn-secondary">Cancel</a>

                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save, photos">
                    <span class="btn-spinner" wire:loading wire:target="save"></span>
                    <span wire:loading.remove wire:target="save">
                        {{ $editing ? 'Save Changes' : 'Add Broodcock' }}
                    </span>
                    <span wire:loading wire:target="save">Saving&hellip;</span>
                </button>
            </div>
        </div>
    </form>
</div>
