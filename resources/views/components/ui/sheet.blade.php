@props([
    'title',
    'description' => null,
    /** right (default) or left. */
    'side' => 'right',
    /** sm · md · lg · xl (default - EMS's edit panels) · 2xl */
    'size' => 'xl',
    /** The Livewire property that holds the sheet open, entangled both ways. */
    'model' => null,
    /** The Livewire action its form submits to. null for a sheet with no form. */
    'submit' => null,
    'submitLabel' => 'Save',
    'cancelLabel' => 'Cancel',
    'guardDirty' => true,
    'id' => null,
])

{{--
    THE SHEET (EMS, Part B §5.20): a panel that slides in from the edge of
    the screen - 500ms in, 300ms out. The long forms open in one (a gamefowl:
    five sections and its photos; a mating, with its hatch registration),
    because a centred dialog that tall would scroll inside a scroll.

    Unlike a form dialog, a click on the backdrop does close a sheet - after
    the same unsaved-changes question, when its form has input in it.
    Full width on a phone; capped from sm up.
--}}
@php
    $id ??= 'sheet-'.($model ?? \Illuminate\Support\Str::random(6));

    $width = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$size] ?? 'sm:max-w-xl';

    $state = $model ? "\$wire.entangle('{$model}')" : 'false';
@endphp

<div x-data="emsDialog({ open: {!! $state !!}, guardDirty: @js((bool) $guardDirty), closeOnBackdrop: true })"
     x-on:ems-dialog.window="if ($event.detail === @js($id)) open = true"
     {{ $attributes }}>
    <dialog x-ref="dialog" wire:ignore.self data-side="{{ $side }}"
            class="ems-sheet m-0 h-full max-h-none w-full max-w-none bg-transparent p-0"
            aria-labelledby="{{ $id }}-title"
            x-on:cancel.prevent="requestClose()" x-on:click="backdropClick($event)">
        <div x-ref="panel" tabindex="-1"
             @class([
                 'ems-sheet-panel fixed inset-y-0 z-50 flex h-full w-full flex-col bg-background text-foreground shadow-lg outline-none',
                 $width,
                 $side === 'left' ? 'left-0 border-r border-border' : 'right-0 border-l border-border',
             ])>
            <button type="button" x-on:click="requestClose()"
                    class="absolute top-2 right-2 z-10 inline-flex size-11 items-center justify-center rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background focus:outline-none lg:top-4 lg:right-4 lg:size-auto"
                    @if ($submit) wire:loading.remove wire:target="{{ $submit }}" @endif>
                <x-ui.icon name="x" class="size-4" />
                <span class="sr-only">Close</span>
            </button>

            <form @if ($submit) wire:submit="{{ $submit }}" @else x-on:submit.prevent @endif
                  x-on:input="dirty = true" x-on:change="dirty = true"
                  class="flex min-h-0 flex-1 flex-col">
                <div class="flex shrink-0 flex-col gap-1.5 p-4 pr-14">
                    <h2 id="{{ $id }}-title" class="text-base font-semibold text-foreground">{{ $title }}</h2>
                    @if ($description)
                        <p class="text-sm text-muted-foreground">{{ $description }}</p>
                    @endif
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-4">
                    {{ $slot }}
                </div>

                @if ($submit || isset($footer))
                    <div class="flex shrink-0 flex-row flex-wrap items-center justify-end gap-2 border-t border-border/60 bg-card p-3">
                        @isset($footer)
                            {{ $footer }}
                        @else
                            <x-ui.button variant="outline" x-on:click="requestClose()"
                                         wire:loading.attr="disabled" wire:target="{{ $submit }}">{{ $cancelLabel }}</x-ui.button>
                            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="{{ $submit }}">
                                <span wire:loading.remove wire:target="{{ $submit }}">{{ $submitLabel }}</span>
                                <span wire:loading.flex wire:target="{{ $submit }}" class="items-center gap-2">
                                    <span class="size-3 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground" aria-hidden="true"></span>
                                    Saving...
                                </span>
                            </x-ui.button>
                        @endisset
                    </div>
                @endif
            </form>
        </div>
    </dialog>
</div>
