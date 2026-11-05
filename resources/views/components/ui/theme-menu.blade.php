{{--
    THE THEME MENU (EMS, Part B §2.7): Light, Dark or System, the owner's
    choice over a two-state toggle. The trigger's icon is the theme in force,
    picked by the same :root selectors the theme itself uses, so it is right
    on the first paint. The tick beside the current choice is set by app.js,
    which owns the choice; System follows the operating system.

    Needs Alpine (it is a dropdown). The public and sign-in layouts load
    Livewire's bundle explicitly for that reason, so it is the one theme
    control everywhere.
--}}
<x-ui.dropdown-menu align="end" label="Theme" {{ $attributes }}>
    <x-slot:trigger aria-label="Theme" title="Theme">
        <x-ui.icon name="sun" data-theme-icon="light" class="size-[18px]" />
        <x-ui.icon name="moon" data-theme-icon="dark" class="size-[18px]" />
    </x-slot:trigger>

    <x-ui.dropdown-menu.label>Theme</x-ui.dropdown-menu.label>
    @foreach (['light' => ['Light', 'sun'], 'dark' => ['Dark', 'moon'], 'system' => ['System', 'monitor']] as $choice => [$label, $icon])
        <button type="button" role="menuitemradio" tabindex="-1" aria-checked="false" data-theme-choice="{{ $choice }}"
                x-on:mouseenter="$el.focus()" x-on:click="close()"
                class="relative flex min-h-11 w-full cursor-default select-none items-center gap-2 rounded-sm py-1.5 pr-2 pl-8 text-left text-sm text-popover-foreground outline-none focus:bg-muted lg:min-h-0 lg:text-xs">
            <span data-theme-check hidden class="absolute left-2 flex size-3.5 items-center justify-center">
                <x-ui.icon name="check" class="size-3.5" />
            </span>
            <x-ui.icon :name="$icon" class="size-4 text-muted-foreground lg:size-3.5" />
            {{ $label }}
        </button>
    @endforeach
</x-ui.dropdown-menu>
