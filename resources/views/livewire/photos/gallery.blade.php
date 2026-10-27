@php
    $canManage = auth()->user()?->can('create', \App\Models\BroodcockPhoto::class) ?? false;
    $photos = $this->photos;
    $confirming = $confirmingDeletionOf !== null ? $photos->firstWhere('id', $confirmingDeletionOf) : null;
@endphp

{{-- One Alpine scope for the whole gallery so any photograph can open the
     lightbox. Alpine is bundled with Livewire - no extra JavaScript library. --}}
<section x-data="{ open: false, src: '', label: '' }" aria-labelledby="gallery-heading">

    <div class="flex items-baseline justify-between gap-3">
        <h2 id="gallery-heading" class="font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground">
            Photos
            @if ($photos->isNotEmpty())
                <span class="datum ml-1 text-[15px] font-normal text-muted-foreground">{{ $photos->count() }}</span>
            @endif
        </h2>
    </div>

    @if ($photos->isEmpty())
        {{-- Empty state that says what to do next, never a blank box. --}}
        <x-empty-state class="mt-5"
                       title="No photos of this bird yet"
                       :description="$canManage
                           ? 'Use Add photos above to take or choose a picture. The first one you add becomes the main photo shown in lists.'
                           : 'Photos will appear here once the farm staff add them.'"
                       icon="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 9h.008v.008H18V9Zm2.25 9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v12Z" />
    @else
        <ul class="mt-5 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($photos as $photo)
                @php
                    /*
                     * Two URLs, not one. The grid tile is a ~250px box and gets
                     * the small copy; the lightbox is the whole point of
                     * clicking and gets the original. Serving the original into
                     * both meant a twelve-photo gallery pulled twelve full-size
                     * files out of Tokyo before you had clicked anything.
                     */
                    $thumb = route('photos.show', ['photo' => $photo, 'size' => 'thumb']);
                    $src = route('photos.show', $photo);
                    $label = $photo->caption ?: 'Photo of '.$photo->broodcock->displayName();
                @endphp

                <li wire:key="photo-{{ $photo->id }}" class="flex flex-col">
                    {{-- Fixed 4:5, object-cover. A gallery of mixed aspect ratios
                         goes ragged the moment a portrait shot is added. --}}
                    <button type="button"
                            class="group block w-full rounded-[12px] text-left"
                            x-on:click="open = true; src = @js($src); label = @js($label)"
                            aria-label="Enlarge this photo">
                        <span class="sb-photo block aspect-[4/5]">
                            <img src="{{ $thumb }}"
                                 alt="{{ $label }}"
                                 width="400" height="500"
                                 loading="lazy"
                                 decoding="async">
                            @if ($photo->is_primary)
                                <span class="sb-chip">Main photo</span>
                            @endif
                        </span>
                    </button>

                    <div class="flex flex-1 flex-col pt-3">
                        {{-- Inline caption editing. --}}
                        @if ($canManage && $editingCaptionFor === $photo->id)
                            <div>
                                <label for="caption-{{ $photo->id }}" class="label">Description</label>
                                <input id="caption-{{ $photo->id }}"
                                       type="text"
                                       wire:model="captionDraft"
                                       wire:keydown.enter="saveCaption"
                                       wire:keydown.escape="cancelEditingCaption"
                                       maxlength="255"
                                       placeholder="e.g. Side view"
                                       @error('captionDraft') aria-describedby="caption-{{ $photo->id }}-error" aria-invalid="true" @enderror
                                       @class(['input', 'mt-1', 'input-error' => $errors->has('captionDraft')])>
                                @error('captionDraft')
                                    <p id="caption-{{ $photo->id }}-error" class="error" role="alert">{{ $message }}</p>
                                @enderror

                                <div class="mt-3 flex gap-2">
                                    <button type="button" wire:click="saveCaption" wire:loading.attr="disabled" wire:target="saveCaption"
                                            class="btn-primary flex-1 px-3 text-[14px]">
                                        <span class="btn-spinner" wire:loading wire:target="saveCaption"></span>
                                        Save
                                    </button>
                                    <button type="button" wire:click="cancelEditingCaption" class="btn-secondary flex-1 px-3 text-[14px]">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @else
                            <p @class([
                                'text-[15px] leading-snug',
                                'text-foreground' => $photo->caption,
                                'text-muted-foreground' => ! $photo->caption,
                            ])>
                                {{ $photo->caption ?: 'No description' }}
                            </p>
                        @endif

                        <p class="mt-1 text-[13px] leading-snug text-muted-foreground">
                            Added by {{ $photo->uploadedBy?->full_name ?? 'a removed account' }}
                        </p>

                        @if ($canManage && $editingCaptionFor !== $photo->id)
                            <div class="mt-3 flex flex-col gap-1 border-t border-border pt-2">
                                @unless ($photo->is_primary)
                                    <button type="button"
                                            wire:click="setPrimary({{ $photo->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="setPrimary({{ $photo->id }})"
                                            class="btn-quiet w-full justify-start px-2 text-[14px]">
                                        <span class="btn-spinner" wire:loading wire:target="setPrimary({{ $photo->id }})"></span>
                                        Make this the main photo
                                    </button>
                                @endunless

                                <button type="button"
                                        wire:click="startEditingCaption({{ $photo->id }})"
                                        class="btn-quiet w-full justify-start px-2 text-[14px]">
                                    {{ $photo->caption ? 'Edit description' : 'Add description' }}
                                </button>

                                <button type="button"
                                        wire:click="confirmDelete({{ $photo->id }})"
                                        class="btn-quiet w-full justify-start px-2 text-[14px] text-destructive hover:bg-destructive-bg">
                                    Delete photo
                                </button>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Delete confirmation. Shows the photo and names the bird, and says
         plainly that the file is gone for good - "Are you sure?" tells a
         worried keeper nothing. --}}
    @if ($confirming)
        <div class="dialog-backdrop fixed inset-0 z-50 flex items-center justify-center bg-foreground/60 p-4"
             x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.cancelDelete()"
             role="dialog" aria-modal="true" aria-labelledby="delete-photo-title" aria-describedby="delete-photo-body">
            <div class="dialog-panel elev-3 w-full max-w-md rounded-[12px] bg-card p-6">
                <div class="flex items-start gap-4">
                    <span class="block w-16 shrink-0 overflow-hidden rounded-[8px] bg-muted aspect-[4/5]">
                        <img src="{{ route('photos.show', ['photo' => $confirming, 'size' => 'thumb']) }}" alt=""
                             width="64" height="80" class="h-full w-full object-cover">
                    </span>
                    <div class="min-w-0">
                        <h2 id="delete-photo-title" class="font-display text-[22px] font-semibold tracking-[-0.015em] text-foreground">
                            Delete this photo of {{ $confirming->broodcock->displayName() }}?
                        </h2>
                        <p id="delete-photo-body" class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                            @if ($confirming->caption)
                                &ldquo;{{ $confirming->caption }}&rdquo; will be removed for good.
                            @else
                                The picture file is removed for good.
                            @endif
                            It cannot be brought back.
                            @if ($confirming->is_primary && $photos->count() > 1)
                                It is the main photo, so choose a new one afterwards.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="delete({{ $confirming->id }})"
                            wire:loading.attr="disabled" wire:target="delete({{ $confirming->id }})"
                            class="btn-danger">
                        <span class="btn-spinner" wire:loading wire:target="delete({{ $confirming->id }})"></span>
                        Delete photo
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Lightbox. The trap is bound to `open` rather than a literal, so focus is
         not held inside a hidden dialog and the page can still be scrolled. --}}
    <div x-show="open"
         x-cloak
         x-transition.opacity
         x-trap.noscroll="open"
         x-on:keydown.escape.window="open = false"
         x-on:click.self="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/80 p-4"
         role="dialog"
         aria-modal="true"
         aria-label="Enlarged photo">

        <button type="button" x-on:click="open = false"
                class="absolute right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-card text-foreground hover:bg-muted"
                aria-label="Close" title="Close">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>

        <figure class="flex max-h-full flex-col items-center">
            <img :src="src" :alt="label" class="max-h-[80vh] w-auto rounded-[12px] object-contain">
            <figcaption class="mt-4 text-center text-[15px] leading-snug text-card" x-text="label"></figcaption>
        </figure>
    </div>
</section>
