@php
    use App\Http\Requests\StoreBroodcockPhotoRequest;

    $maxMb = round(StoreBroodcockPhotoRequest::maxKilobytes() / 1024, 1);
    $maxPerBird = StoreBroodcockPhotoRequest::maxPerBroodcock();
@endphp

<div class="card p-5 sm:p-6">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Add Photos</h2>
        <p class="mt-1 text-sm text-gray-600">
            Photos of <strong>{{ $broodcock->displayName() }}</strong>.
            @if ($this->isFull)
                This bird already has the most photos allowed ({{ $maxPerBird }}).
                Delete one below before adding another.
            @else
                You can add {{ $this->remainingSlots }} {{ Str::plural('more photo', $this->remainingSlots) }}.
            @endif
        </p>
    </div>

    @if ($status !== '')
        <div class="mb-4 flex items-start gap-2 rounded-lg bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200" role="status">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            <span>{{ $status }}</span>
        </div>
    @endif

    {{-- The whole form is one Alpine scope so the progress bar can listen to
         Livewire's upload events. Alpine ships inside Livewire - no extra JS. --}}
    <form wire:submit="save"
          x-data="{ uploading: false, progress: 0 }"
          x-on:livewire-upload-start="uploading = true; progress = 0"
          x-on:livewire-upload-finish="uploading = false; progress = 100"
          x-on:livewire-upload-cancel="uploading = false"
          x-on:livewire-upload-error="uploading = false"
          x-on:livewire-upload-progress="progress = $event.detail.progress">

        {{-- A <label> wrapping the input gives a full-width, thumb-sized target
             on a phone instead of the browser's small default file button. --}}
        <label @class([
            'flex w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-8 text-center transition',
            'border-gray-300 bg-gray-50 hover:border-brand-500 hover:bg-brand-50' => ! $this->isFull,
            'cursor-not-allowed border-gray-200 bg-gray-100 opacity-60' => $this->isFull,
        ])>
            <svg class="h-10 w-10 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-1.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5"/>
            </svg>

            <span class="text-base font-semibold text-gray-900">
                Tap to choose photos
            </span>
            <span class="text-sm text-gray-600">
                You can pick more than one. JPG, PNG or WEBP, up to {{ $maxMb }} MB each.
            </span>

            <input type="file"
                   class="sr-only"
                   wire:model="photos"
                   multiple
                   accept="{{ StoreBroodcockPhotoRequest::acceptAttribute() }}"
                   @disabled($this->isFull)>
        </label>

        {{-- Upload progress. Farm staff are often on a slow phone connection;
             a page that looks frozen gets tapped again and again. --}}
        <div x-show="uploading" x-cloak class="mt-4" role="status" aria-live="polite">
            <div class="flex items-center justify-between text-sm font-medium text-gray-700">
                <span>Uploading your photos&hellip;</span>
                <span x-text="progress + '%'"></span>
            </div>
            <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-gray-200">
                <div class="h-3 rounded-full bg-brand-600 transition-all" :style="`width: ${progress}%`"></div>
            </div>
            <p class="mt-1 text-xs text-gray-500">Please keep this page open until it finishes.</p>
        </div>

        @error('photos')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror

        {{-- Preview before saving: what you picked, before it is committed. --}}
        @if (count($photos) > 0)
            <div class="mt-5">
                <p class="text-sm font-medium text-gray-900">
                    Ready to save ({{ count($photos) }})
                </p>

                <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($photos as $index => $pending)
                        <li wire:key="pending-{{ $index }}" class="relative">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200">
                                @if ($pending->isPreviewable())
                                    <img src="{{ $pending->temporaryUrl() }}"
                                         alt="Photo waiting to be saved"
                                         class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center px-2 text-center text-xs text-gray-500">
                                        No preview available
                                    </div>
                                @endif
                            </div>

                            <button type="button"
                                    wire:click="removePending({{ $index }})"
                                    class="absolute right-1.5 top-1.5 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-gray-700 shadow ring-1 ring-gray-300 hover:bg-rose-50 hover:text-rose-700"
                                    aria-label="Remove this photo from the list">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            @error('photos.'.$index)
                                <p class="error" role="alert">{{ $message }}</p>
                            @enderror
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-5">
                <label for="photo-caption" class="label">Description (optional)</label>
                <input id="photo-caption"
                       type="text"
                       wire:model="caption"
                       maxlength="255"
                       placeholder="e.g. Side view after conditioning"
                       @class(['input', 'input-error' => $errors->has('caption')])>
                <p class="help">Added to every photo you are saving now. You can change it later.</p>
                @error('caption')
                    <p class="error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <button type="submit"
                        class="btn-primary w-full sm:w-auto"
                        wire:loading.attr="disabled"
                        wire:target="save, photos"
                        x-bind:disabled="uploading">
                    <svg wire:loading wire:target="save" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">
                        Save {{ count($photos) }} {{ Str::plural('Photo', count($photos)) }}
                    </span>
                    <span wire:loading wire:target="save">Saving&hellip;</span>
                </button>

                <button type="button"
                        wire:click="$set('photos', [])"
                        class="btn-secondary w-full sm:w-auto"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    Cancel
                </button>
            </div>
        @endif
    </form>
</div>
