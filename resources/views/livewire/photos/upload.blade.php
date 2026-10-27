@php
    use App\Http\Requests\StoreBroodcockPhotoRequest;

    $maxMb = round(StoreBroodcockPhotoRequest::maxKilobytes() / 1024, 1);
    $maxPerBird = StoreBroodcockPhotoRequest::maxPerBroodcock();
@endphp

{{--
    ADD PHOTOS - sits above the gallery on the bird's Photos tab.

    Pick, see what you picked in the same 4:5 frame the gallery uses, take out
    anything wrong, then save. Nothing is committed until Save, and the saved
    result is confirmed with a toast as well as a line here.
--}}
<section aria-labelledby="upload-heading" class="border-b border-border pb-10">
    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
        <h2 id="upload-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">Add photos</h2>
        <p class="text-[15px] text-muted-foreground">
            @if ($this->isFull)
                This bird already has the most photos allowed (<span class="datum">{{ $maxPerBird }}</span>).
                Delete one below before adding another.
            @else
                {{-- The remaining-slot sentence is asserted verbatim by
                     PhotoUploadTest, so no markup may be interleaved with it. --}}
                You can add {{ $this->remainingSlots }} {{ Str::plural('more photo', $this->remainingSlots) }}.
            @endif
        </p>
    </div>

    {{-- The whole form is one Alpine scope so the progress bar can listen to
         Livewire's upload events. Alpine ships inside Livewire - no extra JS. --}}
    <form wire:submit="save" class="mt-5"
          x-data="{ uploading: false, progress: 0 }"
          x-on:livewire-upload-start="uploading = true; progress = 0"
          x-on:livewire-upload-finish="uploading = false; progress = 100"
          x-on:livewire-upload-cancel="uploading = false"
          x-on:livewire-upload-error="uploading = false"
          x-on:livewire-upload-progress="progress = $event.detail.progress">

        {{-- A <label> wrapping the input gives a full-width, thumb-sized target
             on a phone instead of the browser's small default file button.
             The input itself is sr-only, so the zone carries the keyboard
             focus state on its behalf - hence focus-within.

             `relative` IS LOAD-BEARING, not layout tidying. Tailwind's sr-only
             is `position:absolute`, so without a positioned ancestor the input
             is placed against the DOCUMENT rather than this label. Clicking the
             zone focuses it, the browser scrolls the WINDOW to bring it into
             view - and since the console shell's only scroll container is
             <main>, that moves the entire layout off screen and nothing ever
             scrolls it back. Contained by `relative`, no scroll is needed. --}}
        <label x-data="fileDropzone({ disabled: @js($this->isFull) })"
               x-on:dragover.prevent="onDragOver()"
               x-on:dragleave="onDragLeave($event)"
               x-on:drop.prevent="onDrop($event)"
               :class="dragging && 'border-primary bg-primary-50'"
               @class([
                   'relative flex min-h-11 w-full flex-col items-center justify-center gap-2 rounded-[12px] border border-dashed px-4 py-10 text-center transition-colors duration-150',
                   'cursor-pointer border-input bg-card hover:border-primary hover:bg-primary-50 focus-within:border-primary focus-within:bg-primary-50' => ! $this->isFull,
                   'cursor-not-allowed border-border bg-muted' => $this->isFull,
               ])>
            <svg @class([
                    'h-8 w-8',
                    'text-primary' => ! $this->isFull,
                    'text-muted-foreground' => $this->isFull,
                 ])
                 fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-1.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5"/>
            </svg>

            <span class="text-[17px] font-medium text-foreground"
                  x-text="dragging ? 'Drop to add them' : 'Tap to choose photos'">
                Tap to choose photos
            </span>
            <span class="max-w-[42ch] text-[15px] leading-snug text-muted-foreground">
                You can pick more than one, or drag them here. JPG, PNG or WEBP, up to <span class="datum">{{ $maxMb }} MB</span> each.
            </span>

            <input x-ref="input"
                   type="file"
                   class="sr-only"
                   wire:model="photos"
                   multiple
                   accept="{{ StoreBroodcockPhotoRequest::acceptAttribute() }}"
                   @if ($errors->has('photos')) aria-describedby="photos-error" aria-invalid="true" @endif
                   @disabled($this->isFull)>
        </label>

        {{-- Upload progress. Farm staff are often on a slow phone connection;
             a page that looks frozen gets tapped again and again. --}}
        <div x-show="uploading" x-cloak class="mt-4" role="status" aria-live="polite">
            <div class="flex items-center justify-between text-[15px] text-foreground">
                <span>Uploading your photos&hellip;</span>
                <span class="datum" x-text="progress + '%'"></span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted"
                 role="progressbar"
                 aria-label="Upload progress"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 x-bind:aria-valuenow="progress">
                <div class="h-full rounded-full bg-foreground transition-[width] duration-150" :style="`width: ${progress}%`"></div>
            </div>
            <p class="mt-2 text-[13px] leading-snug text-muted-foreground">Please keep this page open until it finishes.</p>
        </div>

        {{-- A rejected file is the one thing here that must not be missed, so
             it gets the alert wash rather than a line of red text. The tint is
             the only colour; the words stay in ink. --}}
        @error('photos')
            <div id="photos-error" class="mt-4 flex items-start gap-2 rounded-[8px] bg-destructive-bg px-4 py-3 text-[15px] leading-snug text-foreground" role="alert">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.34 3.94l-8.19 14.2A1.5 1.5 0 0 0 3.45 20.4h17.1a1.5 1.5 0 0 0 1.3-2.26l-8.19-14.2a1.5 1.5 0 0 0-2.6 0Z"/>
                </svg>
                <span>{{ $message }}</span>
            </div>
        @enderror

        {{-- The last save, stated here as well as in the toast, so it is still
             on screen after the toast has gone. --}}
        @if ($status !== '' && count($photos) === 0)
            <p class="mt-4 flex items-center gap-2 text-[15px] text-foreground">
                <svg class="h-5 w-5 shrink-0 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                <span>{{ $status }}</span>
            </p>
        @endif

        {{-- Preview before saving: what you picked, before it is committed. --}}
        @if (count($photos) > 0)
            <div class="mt-6">
                <p class="text-[15px] font-semibold text-foreground">
                    Ready to save (<span class="datum">{{ count($photos) }}</span>)
                </p>

                <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($photos as $index => $pending)
                        <li wire:key="pending-{{ $index }}">
                            <div class="relative">
                                {{-- Same fixed 4:5 as the saved gallery below, so the
                                     two grids read as one body of work. --}}
                                <div class="aspect-[4/5] overflow-hidden rounded-[12px] bg-muted">
                                    @if ($pending->isPreviewable())
                                        <img src="{{ $pending->temporaryUrl() }}"
                                             alt="Photo {{ $index + 1 }} waiting to be saved"
                                             class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center px-2 text-center text-[13px] leading-snug text-muted-foreground">
                                            No preview available
                                        </div>
                                    @endif
                                </div>

                                <button type="button"
                                        wire:click="removePending({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                        class="elev-1 absolute right-2 top-2 inline-flex h-11 w-11 items-center justify-center rounded-full bg-card text-foreground hover:bg-muted"
                                        aria-label="Remove this photo from the list"
                                        title="Remove this photo from the list">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            @error('photos.'.$index)
                                <p class="error" role="alert">{{ $message }}</p>
                            @enderror
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-6 max-w-[40rem]">
                <label for="photo-caption" class="label">Description (optional)</label>
                <input id="photo-caption"
                       type="text"
                       wire:model="caption"
                       maxlength="255"
                       placeholder="e.g. Side view after conditioning"
                       aria-describedby="photo-caption-help @error('caption') photo-caption-error @enderror"
                       @class(['input', 'mt-1', 'input-error' => $errors->has('caption')])>
                <p id="photo-caption-help" class="help">Added to every photo you are saving now. You can change it later.</p>
                @error('caption')
                    <p id="photo-caption-error" class="error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button"
                        wire:click="$set('photos', [])"
                        class="btn-secondary w-full sm:w-auto"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    Cancel
                </button>

                <button type="submit"
                        class="btn-primary w-full sm:w-auto"
                        wire:loading.attr="disabled"
                        wire:target="save, photos"
                        x-bind:disabled="uploading">
                    <span class="btn-spinner" wire:loading wire:target="save"></span>
                    <span wire:loading.remove wire:target="save">
                        Save <span class="datum">{{ count($photos) }}</span> {{ Str::plural('photo', count($photos)) }}
                    </span>
                    <span wire:loading wire:target="save">Saving&hellip;</span>
                </button>
            </div>
        @endif
    </form>
</section>
