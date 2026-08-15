<div>
    <div class="mb-10">
        <a href="{{ route('broodcocks.index') }}" class="text-sm font-medium text-action hover:underline">
            &larr; Back to broodcocks
        </a>
        <h1 class="mt-2 text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">
            {{ $this->isEditing() ? 'Edit '.$broodcock->name : 'Add a Broodcock' }}
        </h1>
        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
            Fields marked with <span class="text-alert">*</span> are required. Everything
            else can be filled in later.
        </p>
    </div>

    <form wire:submit="save" class="space-y-10">
        {{-- Identification --}}
        <section class="card p-8">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Identification</h2>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">Basic details used to recognise this bird.</p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="label">Name <span class="text-alert">*</span></label>
                    <input id="name" type="text" wire:model.blur="name"
                           class="input mt-1 @error('name') input-error @enderror">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="band_number" class="label">Band Number</label>
                    <input id="band_number" type="text" wire:model.blur="band_number"
                           placeholder="e.g. SW-1024"
                           class="input mt-1 @error('band_number') input-error @enderror">
                    <p class="help">Leave empty if the bird has not been banded yet.</p>
                    @error('band_number') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sex" class="label">Sex <span class="text-alert">*</span></label>
                    <select id="sex" wire:model.live="sex" class="input mt-1 @error('sex') input-error @enderror">
                        @foreach ($this->sexOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }} ({{ $option->farmTerm() }})</option>
                        @endforeach
                    </select>
                    @error('sex') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="label">Status <span class="text-alert">*</span></label>
                    <select id="status" wire:model.blur="status" class="input mt-1 @error('status') input-error @enderror">
                        @foreach ($this->statusOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="breed" class="label">Breed</label>
                    <input id="breed" type="text" wire:model.blur="breed" list="breed-list"
                           class="input mt-1 @error('breed') input-error @enderror">
                    @error('breed') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="bloodline" class="label">Bloodline</label>
                    <input id="bloodline" type="text" wire:model.blur="bloodline"
                           class="input mt-1 @error('bloodline') input-error @enderror">
                    @error('bloodline') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="class" class="label">Class <span class="text-alert">*</span></label>
                    <select id="class" wire:model.blur="class" class="input mt-1 @error('class') input-error @enderror">
                        @foreach ($this->classOptions() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }} - {{ $option->description() }}</option>
                        @endforeach
                    </select>
                    @error('class') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Dates and appearance --}}
        <section class="card p-8">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Age and Appearance</h2>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                The bird's age is worked out automatically from its hatch date, so it is
                always up to date.
            </p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="date_hatched" class="label">Date Hatched</label>
                    <input id="date_hatched" type="date" wire:model.blur="date_hatched" max="{{ today()->toDateString() }}"
                           class="input mt-1 @error('date_hatched') input-error @enderror">
                    @error('date_hatched') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date_acquired" class="label">Date Acquired</label>
                    <input id="date_acquired" type="date" wire:model.blur="date_acquired" max="{{ today()->toDateString() }}"
                           class="input mt-1 @error('date_acquired') input-error @enderror">
                    <p class="help">When the farm obtained this bird.</p>
                    @error('date_acquired') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="weight" class="label">Weight (kg)</label>
                    <input id="weight" type="number" step="0.01" min="0" inputmode="decimal" wire:model.blur="weight"
                           class="input mt-1 @error('weight') input-error @enderror">
                    @error('weight') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="color" class="label">Colour</label>
                    <input id="color" type="text" wire:model.blur="color" placeholder="e.g. Red, Black, Spangled"
                           class="input mt-1 @error('color') input-error @enderror">
                    @error('color') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="comb_type" class="label">Comb Type</label>
                    <input id="comb_type" type="text" wire:model.blur="comb_type" placeholder="e.g. Straight, Pea"
                           class="input mt-1 @error('comb_type') input-error @enderror">
                    @error('comb_type') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="leg_color" class="label">Leg Colour</label>
                    <input id="leg_color" type="text" wire:model.blur="leg_color" placeholder="e.g. Yellow, White"
                           class="input mt-1 @error('leg_color') input-error @enderror">
                    @error('leg_color') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="distinguishing_marks" class="label">Distinguishing Marks</label>
                    <textarea id="distinguishing_marks" rows="2" wire:model.blur="distinguishing_marks"
                              placeholder="Anything that helps tell this bird apart from a similar one"
                              class="input mt-1 @error('distinguishing_marks') input-error @enderror"></textarea>
                    @error('distinguishing_marks') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Pedigree --}}
        <section class="card p-8">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Parents (Pedigree)</h2>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Recording the sire and dam is what lets the system build this bird's family
                tree. Only male birds can be a sire and only female birds can be a dam.
            </p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="sire_id" class="label">Sire (Father)</label>
                    <select id="sire_id" wire:model.blur="sire_id" class="input mt-1 @error('sire_id') input-error @enderror">
                        <option value="">Not known</option>
                        @foreach ($this->sireOptions as $option)
                            <option value="{{ $option->id }}">
                                {{ $option->name }}{{ $option->band_number ? ' ('.$option->band_number.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('sire_id') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dam_id" class="label">Dam (Mother)</label>
                    <select id="dam_id" wire:model.blur="dam_id" class="input mt-1 @error('dam_id') input-error @enderror">
                        <option value="">Not known</option>
                        @foreach ($this->damOptions as $option)
                            <option value="{{ $option->id }}">
                                {{ $option->name }}{{ $option->band_number ? ' ('.$option->band_number.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('dam_id') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Housing and notes --}}
        <section class="card p-8">
            <h2 class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Housing and Notes</h2>

            <div class="mt-8 space-y-5">
                <div>
                    <label for="pen_id" class="label">Pen</label>
                    <select id="pen_id" wire:model.blur="pen_id" class="input mt-1 @error('pen_id') input-error @enderror">
                        <option value="">Not assigned to a pen</option>
                        @foreach ($this->pens as $pen)
                            <option value="{{ $pen->id }}">{{ $pen->code }} - {{ $pen->name }}</option>
                        @endforeach
                    </select>
                    @error('pen_id') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="label">Notes</label>
                    <textarea id="notes" rows="3" wire:model.blur="notes"
                              class="input mt-1 @error('notes') input-error @enderror"></textarea>
                    <p class="help">Internal notes. Customers never see these.</p>
                    @error('notes') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ $this->isEditing() ? route('broodcocks.show', $broodcock) : route('broodcocks.index') }}"
               class="btn-secondary">Cancel</a>

            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $this->isEditing() ? 'Save Changes' : 'Add Broodcock' }}
                </span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
