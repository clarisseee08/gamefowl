@props([
    /** Where the panel hangs from the trigger: 'left' | 'right'. */
    'align' => 'right',
    /** Panel width. Menus are content-sized; a fixed width is usually wrong. */
    'width' => 'min-w-[11rem]',
])

{{--
    A DROPDOWN MENU.

    Structure follows D:/ems/employee-management-system's ui/dropdown-menu -
    a bordered popover surface holding compact rows, a label row, and hairline
    separators. Three things were deliberately NOT carried across, and each one
    would have been a bug here rather than a difference of taste:

      shadcn's item hover is `focus:bg-accent`. In that system `accent` is a
      subtle neutral. In THIS system --color-accent is #ef6905, a strong
      orange, so porting it literally would have put a colour behind every menu
      row - and colour here means bloodline and nothing else. The hover surface
      is bg-muted.

      shadcn's rows are text-xs, 12px. The console floor is 15px body and 44px
      touch targets, because this is read outdoors on a phone. Rows are 15px
      and min-h-11.

      shadcn animates zoom-in-95 with a directional slide. The motion whitelist
      gives a dropdown opacity plus translateY(-4px to 0) at --dur-base, and
      reserves scale for the tooltip and the modal panel. A menu that zooms
      reads as a dialog.

    NO DOUBLE QUOTE MAY APPEAR INSIDE x-data. It is an HTML attribute, so the
    first one ends it and Alpine receives truncated JavaScript with no error.
--}}
<div x-data="{ open: false }"
     x-on:keydown.escape.stop="open = false"
     x-on:click.outside="open = false"
     {{ $attributes->merge(['class' => 'relative inline-flex']) }}>

    <div x-on:click="open = ! open" x-bind:aria-expanded="open ? 'true' : 'false'" aria-haspopup="menu">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition-[opacity,transform] ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition-[opacity,transform] ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         role="menu"
         class="absolute top-full z-50 mt-1.5 {{ $width }} overflow-hidden rounded-[var(--radius-md)] border border-border bg-popover p-1 {{ $align === 'left' ? 'left-0' : 'right-0' }}"
         style="box-shadow: var(--shadow-e2)">
        {{ $slot }}
    </div>
</div>
