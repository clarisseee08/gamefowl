@props([
    'title',
    'description' => null,
    /** sm 384 · md 448 (default) · lg 576 · xl 768 */
    'size' => 'md',
    /** The Livewire property that holds it open. Without one, open it with x-on:click="$dispatch('ems-dialog', '<id>')". */
    'model' => null,
    'id' => null,
])

{{--
    THE INFO DIALOG (EMS, Part B §5.20): something to read, with one OK. No
    form, no unsaved-changes question. Close it with OK, the X or Escape; the
    backdrop does not close it.

    Without a Livewire property, give it an id and open it from anywhere on
    the page with $dispatch('ems-dialog', '<id>').
--}}
@php
    $id ??= 'info-'.($model ?? \Illuminate\Support\Str::random(6));

    $width = [
        'sm' => 'sm:w-[384px]',
        'md' => 'sm:w-[448px]',
        'lg' => 'sm:w-[576px]',
        'xl' => 'sm:w-[768px]',
    ][$size] ?? 'sm:w-[448px]';

@endphp

<div x-data="emsDialog({ model: @js($model) })"
     x-on:ems-dialog.window="if ($event.detail === @js($id)) openNow()"
     {{ $attributes }}>
    <dialog x-ref="dialog" wire:ignore.self class="d-modal ems-modal"
            aria-labelledby="{{ $id }}-title" @if ($description) aria-describedby="{{ $id }}-description" @endif
            x-on:cancel.prevent="requestClose()">
        <div x-ref="panel" tabindex="-1"
             class="ems-modal-panel relative flex max-h-[90vh] w-[calc(100vw-2rem)] max-w-[calc(100vw-2rem)] flex-col gap-0 overflow-hidden rounded-xl border border-border/60 bg-card/95 p-0 text-card-foreground shadow-2xl outline-none backdrop-blur-xl {{ $width }}">
            <button type="button" x-on:click="requestClose()"
                    class="absolute top-2 right-2 inline-flex size-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus:ring-2 focus:ring-ring focus:outline-none lg:top-3 lg:right-3 lg:size-7">
                <x-ui.icon name="x" class="size-3.5" />
                <span class="sr-only">Close</span>
            </button>

            <div class="flex shrink-0 flex-col gap-2 px-5 pt-5 pb-3">
                <h2 id="{{ $id }}-title" class="pr-14 text-base font-bold text-foreground">{{ $title }}</h2>
                @if ($description)
                    <p id="{{ $id }}-description" class="text-xs leading-snug text-muted-foreground">{{ $description }}</p>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto px-5 pb-5 text-sm text-foreground">
                {{ $slot }}
            </div>

            <div class="flex shrink-0 justify-end gap-2.5 border-t border-border/60 bg-background/60 p-3">
                <button type="button" x-on:click="requestClose()"
                        class="inline-flex h-11 min-w-[80px] flex-1 items-center justify-center rounded-lg bg-primary px-4 text-sm text-primary-foreground shadow-lg shadow-primary/20 outline-none transition-transform hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2 focus-visible:ring-offset-card active:scale-95 sm:flex-none lg:h-7 lg:text-xs">OK</button>
            </div>
        </div>
    </dialog>
</div>
