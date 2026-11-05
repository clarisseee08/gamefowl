@props([
    /** Action + object: "Edit health record". */
    'title',
    /** One line under the title, only when it adds something the title cannot. */
    'description' => null,
    /** A Lucide name drawn before the title. */
    'icon' => null,
    /** sm 384 · md 448 (default) · lg 576 · xl 768 · 2xl 1024 · full */
    'size' => 'md',
    /** The Livewire property that holds the dialog open, entangled both ways. */
    'model' => null,
    /** The Livewire action the form submits to. null keeps the form from submitting anywhere. */
    'submit' => 'save',
    /** The verb on the primary button: Save, Create, Record. */
    'submitLabel' => 'Save',
    'cancelLabel' => 'Cancel',
    /** Ask before a form with unsaved input is closed. */
    'guardDirty' => true,
    /** Ids for the title and description; set one when a page has two dialogs. */
    'id' => null,
    /**
     * false when the body is a Livewire form component that brings its own
     * <form> and buttons - forms cannot nest. The dialog then draws only its
     * chrome; the form's Cancel closes it with $dispatch('ems-close-form').
     */
    'wrap' => true,
])

{{--
    THE FORM DIALOG (EMS FormDialog, Part B §5.20, §7). Every short create
    and edit form in the console opens in one of these over its list, rather
    than on a page of its own - the owner's decision.

    A native <dialog> under showModal(): the page behind is inert, Tab stays
    inside, Escape asks to close. A click on the backdrop does NOT close it -
    a stray click must not lose a half-filled form. Focus lands on the panel,
    not on the first field; Tab enters the form.

    The whole dialog is the form: Enter in a field submits, the primary button
    is type="submit", and while the save is in flight both buttons are
    disabled, the primary reads "Saving..." and the corner controls hide.
    With unsaved input, Escape, the X and Cancel ask "Discard unsaved
    changes?" first; a pristine form just closes.

    Below sm the panel is the screen's width less 1rem a side; the body is the
    one scrolling region, so the title and the buttons stay in view.
--}}
@php
    $id ??= 'dialog-'.($model ?? \Illuminate\Support\Str::random(6));

    $width = [
        'sm' => 'sm:w-[384px]',
        'md' => 'sm:w-[448px]',
        'lg' => 'sm:w-[576px]',
        'xl' => 'sm:w-[768px]',
        '2xl' => 'sm:w-[1024px]',
        'full' => '',
    ][$size] ?? 'sm:w-[448px]';

    $normal = "max-h-[90vh] w-[calc(100vw-2rem)] max-w-[calc(100vw-2rem)] rounded-xl border border-border/60 {$width}";
    $maximized = 'h-[100dvh] max-h-none w-screen max-w-none rounded-none border-0';

@endphp

<div x-data="emsDialog({ model: @js($model), guardDirty: @js((bool) $guardDirty) })"
     x-on:ems-dialog.window="if (($event.detail?.id ?? $event.detail) === @js($id)) openNow($event.detail?.wait === true)"
     x-on:ems-close-form.window="requestClose()"
     {{ $attributes }}>
    <dialog x-ref="dialog" wire:ignore.self class="d-modal ems-modal"
            aria-labelledby="{{ $id }}-title" @if ($description) aria-describedby="{{ $id }}-description" @endif
            x-on:cancel.prevent="requestClose()">
        <div x-ref="panel" tabindex="-1"
             class="ems-modal-panel group/dialog relative flex flex-col gap-0 overflow-hidden bg-card p-0 text-card-foreground shadow-2xl outline-none"
             x-bind:class="maximized ? '{{ $maximized }}' : '{{ $normal }}'">

            <div class="absolute top-2 right-2 z-50 flex items-center gap-1 lg:top-3 lg:right-3"
                 @if ($submit) wire:loading.remove wire:target="{{ $submit }}" @endif>
                <button type="button" x-on:click="maximized = ! maximized"
                        class="hidden rounded-sm p-1 opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-card focus:outline-none sm:inline-flex">
                    <x-ui.icon name="maximize-2" class="size-3.5 text-foreground" x-show="! maximized" />
                    <x-ui.icon name="minimize-2" class="size-3.5 text-foreground" x-show="maximized" x-cloak />
                    <span class="sr-only" x-text="maximized ? 'Restore' : 'Maximize'">Maximize</span>
                </button>
                <button type="button" x-on:click="requestClose()"
                        class="inline-flex size-11 items-center justify-center rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-card focus:outline-none lg:size-auto lg:p-1">
                    <x-ui.icon name="x" class="size-4 text-foreground lg:size-3.5" />
                    <span class="sr-only">Close</span>
                </button>
            </div>

            @if ($wrap)
                <form @if ($submit) wire:submit="{{ $submit }}" @else x-on:submit.prevent @endif
                  x-on:input="dirty = true" x-on:change="dirty = true"
                  class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div class="flex shrink-0 flex-col gap-2 px-5 pt-5 pb-3 text-left">
                    {{-- While the form is still loading its title is not known yet - an
                         edit would read "Add" for a moment - so a bar stands in. --}}
                    <x-ui.skeleton class="hidden h-5 w-48 group-has-[[data-form-skeleton]]/dialog:block" />
                    <h2 id="{{ $id }}-title" class="flex items-center gap-2.5 pr-14 text-base font-bold text-foreground group-has-[[data-form-skeleton]]/dialog:hidden">
                        @if ($icon)
                            <x-ui.icon :name="$icon" class="size-4 text-muted-foreground" />
                        @endif
                        {{ $title }}
                    </h2>
                    @if ($description)
                        <p id="{{ $id }}-description" class="text-[13px] leading-snug text-muted-foreground lg:text-[11px]">{{ $description }}</p>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto px-5 pt-0 pb-5">
                    {{ $slot }}
                </div>

                <div class="flex shrink-0 flex-row flex-wrap items-center justify-end gap-2 border-t border-border/60 bg-background/60 p-3">
                    @isset($footer)
                        {{ $footer }}
                    @else
                        <x-ui.button variant="outline" x-on:click="requestClose()"
                                     wire:loading.attr="disabled" wire:target="{{ $submit }}">{{ $cancelLabel }}</x-ui.button>
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="{{ $submit }}">
                            @if ($submit)
                                <span wire:loading.remove wire:target="{{ $submit }}">{{ $submitLabel }}</span>
                                <span wire:loading.flex wire:target="{{ $submit }}" class="items-center gap-2">
                                    <span class="size-3 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground" aria-hidden="true"></span>
                                    Saving...
                                </span>
                            @else
                                {{ $submitLabel }}
                            @endif
                        </x-ui.button>
                    @endisset
                </div>
            </form>
            @else
                {{-- An edit waits for its record: the shape of a form stands in
                     until the server answers. A new form never waits. --}}
                <div x-show="loading" x-cloak class="flex min-h-0 flex-1 flex-col" aria-busy="true">
                    <div class="px-5 pt-5 pb-3"><x-ui.skeleton class="h-5 w-48" /></div>
                    <x-ui.form-skeleton :fields="5" :marker="false" />
                </div>

                <div x-show="! loading" class="flex min-h-0 flex-1 flex-col overflow-hidden" x-on:input="dirty = true" x-on:change="dirty = true">
                <div class="flex shrink-0 flex-col gap-2 px-5 pt-5 pb-3 text-left">
                    {{-- While the form is still loading its title is not known yet - an
                         edit would read "Add" for a moment - so a bar stands in. --}}
                    <x-ui.skeleton class="hidden h-5 w-48 group-has-[[data-form-skeleton]]/dialog:block" />
                    <h2 id="{{ $id }}-title" class="flex items-center gap-2.5 pr-14 text-base font-bold text-foreground group-has-[[data-form-skeleton]]/dialog:hidden">
                        @if ($icon)
                            <x-ui.icon :name="$icon" class="size-4 text-muted-foreground" />
                        @endif
                        {{ $title }}
                    </h2>
                    @if ($description)
                        <p id="{{ $id }}-description" class="text-[13px] leading-snug text-muted-foreground lg:text-[11px]">{{ $description }}</p>
                    @endif
                </div>

                    <div class="flex min-h-0 flex-1 flex-col">
                        {{ $slot }}
                    </div>
                </div>
            @endif
        </div>
    </dialog>
</div>
