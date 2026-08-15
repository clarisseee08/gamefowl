@php
    $canManage = auth()->user()?->can('create', \App\Models\BroodcockPhoto::class) ?? false;
@endphp

{{-- One Alpine scope for the whole gallery so any tile can open the lightbox.
     Alpine is bundled with Livewire - no extra JavaScript library. --}}
<div x-data="{ open: false, src: '', label: '' }"
     x-on:keydown.escape.window="open = false">

    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-[24px] font-semibold tracking-[-0.015em] leading-[1.2] text-ink">
            Photos
            @if ($this->photos->isNotEmpty())
                <span class="text-sm font-normal text-ink-48">({{ $this->photos->count() }})</span>
            @endif
        </h2>
    </div>

    @if ($status !== '')
        <div class="mb-4 flex items-start gap-2 rounded-lg bg-ok-wash p-4 text-sm text-ok ring-1 ring-ok/20" role="status">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            <span>{{ $status }}</span>
        </div>
    @endif

    @if ($this->photos->isEmpty())
        {{-- Empty state that says what to do next, never a blank box. --}}
        <div class="card flex flex-col items-center px-6 py-10 text-center">
            <svg class="h-12 w-12 text-ink-48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 9h.008v.008H18V9Zm2.25 9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v12Z"/>
            </svg>
            <p class="mt-3 text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No photos of this bird yet</p>
            <p class="mt-1 max-w-sm text-sm text-ink-80">
                @if ($canManage)
                    Use <strong>Add Photos</strong> above to take or choose a picture.
                    The first one you add becomes the main photo shown in lists.
                @else
                    Photos will appear here once the farm staff add them.
                @endif
            </p>
        </div>
    @else
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($this->photos as $photo)
                @php
                    $src = route('photos.show', $photo);
                    $label = $photo->caption ?: 'Photo of '.$photo->broodcock->displayName();
                @endphp

                <li wire:key="photo-{{ $photo->id }}" class="card overflow-hidden">
                    <button type="button"
                            class="group relative block w-full"
                            x-on:click="open = true; src = @js($src); label = @js($label)"
                            aria-label="Enlarge this photo">
                        <span class="block aspect-square overflow-hidden bg-parchment">
                            <img src="{{ $src }}"
                                 alt="{{ $label }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover transition group-hover:scale-105">
                        </span>

                        @if ($photo->is_primary)
                            <span class="badge absolute left-2 top-2 bg-action text-white ring-action">
                                Main photo
                            </span>
                        @endif
                    </button>

                    <div class="space-y-3 p-3">
                        {{-- Inline caption editing. --}}
                        @if ($canManage && $editingCaptionFor === $photo->id)
                            <div>
                                <label for="caption-{{ $photo->id }}" class="label">Description</label>
                                <input id="caption-{{ $photo->id }}"
                                       type="text"
                                       wire:model="captionDraft"
                                       wire:keydown.enter="saveCaption"
                                       maxlength="255"
                                       placeholder="e.g. Side view"
                                       @class(['input', 'input-error' => $errors->has('captionDraft')])>
                                @error('captionDraft')
                                    <p class="error" role="alert">{{ $message }}</p>
                                @enderror

                                <div class="mt-2 flex gap-2">
                                    <button type="button" wire:click="saveCaption" class="btn-primary flex-1 px-3 py-2 text-xs">
                                        Save
                                    </button>
                                    <button type="button" wire:click="cancelEditingCaption" class="btn-secondary flex-1 px-3 py-2 text-xs">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @else
                            <p class="min-h-5 text-sm text-ink-80">
                                {{ $photo->caption ?: 'No description' }}
                            </p>
                        @endif

                        <p class="text-xs text-ink-48">
                            Added by {{ $photo->uploadedBy?->full_name ?? 'a removed account' }}
                        </p>

                        @if ($canManage && $editingCaptionFor !== $photo->id)
                            <div class="flex flex-col gap-2">
                                @unless ($photo->is_primary)
                                    <button type="button"
                                            wire:click="setPrimary({{ $photo->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="setPrimary({{ $photo->id }})"
                                            class="btn-secondary w-full px-3 py-2.5 text-xs">
                                        Make this the main photo
                                    </button>
                                @endunless

                                <button type="button"
                                        wire:click="startEditingCaption({{ $photo->id }})"
                                        class="btn-secondary w-full px-3 py-2.5 text-xs">
                                    {{ $photo->caption ? 'Edit description' : 'Add description' }}
                                </button>

                                <button type="button"
                                        wire:click="delete({{ $photo->id }})"
                                        wire:confirm="Delete this photo of {{ $photo->broodcock->displayName() }}? The picture file is removed for good and cannot be brought back."
                                        wire:loading.attr="disabled"
                                        wire:target="delete({{ $photo->id }})"
                                        class="btn-danger w-full px-3 py-2.5 text-xs">
                                    Delete photo
                                </button>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Lightbox. --}}
    <div x-show="open"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
         role="dialog"
         aria-modal="true"
         aria-label="Enlarged photo"
         x-on:click.self="open = false">

        <div class="max-h-full w-full max-w-3xl overflow-auto">
            <img :src="src" :alt="label" class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain">

            <p class="mt-3 text-center text-sm text-white" x-text="label"></p>

            <div class="mt-4 flex justify-center">
                <button type="button" x-on:click="open = false" class="btn-secondary px-6">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
