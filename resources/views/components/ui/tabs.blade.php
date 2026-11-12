@props([
    /**
     * The tabs. Each entry is ['key' => …, 'label' => …], with optional
     * 'count' (a number shown in a pill - omitted rather than shown as 0),
     * 'tone' (danger · warning · success, for a count that needs attention)
     * and 'href' (a tab that navigates instead of setting a property).
     *
     * @var list<array{key:string,label:string,count?:int|null,tone?:string,href?:string}>
     */
    'segments' => [],
    /** The selected key. */
    'active' => null,
    /** The Livewire property a tab writes to: wire:click="$set('<model>', '<key>')". */
    'model' => 'tab',
    /** Accessible name for the strip, e.g. "Bird record sections". */
    'label' => null,
    /** underline (page sections, the default) · segmented (a view switch) · boxed (inside a card or dialog) */
    'variant' => 'underline',
    /** Prefix for the tab and panel ids. Defaults to the model name. */
    'id' => null,
])

{{--
    TABS (EMS, Part B §5.13) in EMS's three styles, chosen by what they do:
    underline for the sections of a page, the segmented pill for switching a
    view, boxed inside a card or a dialog.

    Real buttons with real wire:click handlers, so a tab works with no script
    at all. With Alpine, the strip behaves as one control: the arrow keys move
    and select, Home and End jump to the ends, and only the selected tab is in
    the Tab order. Render the content in an <x-ui.tab-panel> with the same id
    and the tabs point at it.

    Counts and labels never drop under 11px; below lg every tab is a 44px
    target.
--}}
@php
    $id ??= 'tabs-'.$model;

    $countTones = [
        'danger' => 'bg-destructive-bg text-destructive',
        'warning' => 'bg-warning-bg text-warning',
        'success' => 'bg-success-bg text-success',
    ];

    $list = [
        'underline' => 'scrollbar-minimal flex items-end gap-5 overflow-x-auto border-b border-border',
        'segmented' => 'inline-flex h-11 w-fit max-w-full overflow-x-auto scrollbar-minimal whitespace-nowrap rounded-md border border-border bg-card p-0.5 px-0 shadow-sm lg:h-7 dark:bg-muted',
        'boxed' => 'inline-flex h-11 max-w-full items-center justify-center overflow-x-auto scrollbar-minimal rounded-md bg-muted p-1 text-muted-foreground lg:h-10',
    ][$variant] ?? '';
@endphp

<div {{ $attributes->class(['w-full', 'mb-3' => $variant === 'underline']) }}>
    {{-- The selected tab moves the instant it is clicked; the server's answer
         follows. Keyed on the server's choice, so a change made by the
         server (clearing filters, say) re-renders the strip in step. --}}
    <div role="tablist" class="{{ $list }}" @if ($label) aria-label="{{ $label }}" @endif
         wire:key="{{ $id }}-{{ $active }}"
         x-data="{
             current: @js((string) $active),
             {{-- On a phone a strip of tabs is wider than the screen and
                  scrolls. A fade on whichever edge has more tabs behind it
                  says so - a tab cut in half by the edge reads as broken -
                  and the chosen tab is scrolled into view to start with. --}}
             more: { start: false, end: false },
             measure() {
                 const el = $el;
                 this.more.start = el.scrollLeft > 1;
                 this.more.end = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
             },
             move(step) {
                 const tabs = [...$el.querySelectorAll('[role=tab]')];
                 const at = tabs.indexOf(document.activeElement);
                 const next = step === 'first' ? 0 : step === 'last' ? tabs.length - 1 : (at + step + tabs.length) % tabs.length;
                 tabs[next].focus();
                 tabs[next].click();
             }
         }"
         x-init="$nextTick(() => {
             const chosen = $el.querySelector('[role=tab][aria-selected=true]');
             if (chosen) { $el.scrollLeft = Math.max(0, chosen.offsetLeft - ($el.clientWidth - chosen.offsetWidth) / 2); }
             measure();
         })"
         x-on:scroll.passive="measure()"
         x-on:resize.window.debounce.150ms="measure()"
         x-bind:class="{ 'tabs-more-start': more.start, 'tabs-more-end': more.end }"
         x-on:keydown.arrow-right.prevent="move(1)"
         x-on:keydown.arrow-left.prevent="move(-1)"
         x-on:keydown.home.prevent="move('first')"
         x-on:keydown.end.prevent="move('last')">
        @foreach ($segments as $segment)
            @php
                $key = (string) $segment['key'];
                $isActive = (string) $active === $key;
                $count = $segment['count'] ?? null;
                $toneClass = $countTones[$segment['tone'] ?? ''] ?? 'bg-muted text-muted-foreground';

                [$base, $on, $off] = match ($variant) {
                    'segmented' => [
                        'flex h-full shrink-0 items-center gap-2 rounded-md border px-2.5 text-sm font-normal outline-none transition-[background-color,transform,box-shadow,border-color,color] duration-200 ease-out hover:scale-[0.98] focus-visible:ring-2 focus-visible:ring-ring/50 active:scale-[0.96] lg:text-xs',
                        'scale-100 border-primary/20 bg-linear-to-r from-primary to-primary/80 text-primary-foreground shadow-[0_4px_8px_rgba(0,0,0,0.15),0_2px_4px_rgba(0,0,0,0.1),inset_0_1px_0_rgba(255,255,255,0.2)]',
                        'border-transparent bg-transparent text-muted-foreground hover:text-foreground',
                    ],
                    'boxed' => [
                        'inline-flex h-full shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                        'bg-background text-foreground shadow-sm',
                        'hover:text-foreground',
                    ],
                    default => [
                        'group relative flex min-h-11 shrink-0 items-end whitespace-nowrap rounded-sm px-0.5 pb-1.5 text-sm outline-none transition-all duration-200 focus-visible:ring-2 focus-visible:ring-ring/50 lg:min-h-0 lg:text-[12.5px]',
                        'font-bold text-primary',
                        'font-medium text-muted-foreground hover:text-foreground',
                    ],
                };

                $countOn = $variant === 'segmented' ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-primary text-primary-foreground';
                $tag = isset($segment['href']) ? 'a' : 'button';
            @endphp

            <{{ $tag }} @if ($tag === 'a') href="{{ $segment['href'] }}" @else type="button" wire:click="$set('{{ $model }}', '{{ $key }}')" @endif
                x-on:click="current = @js($key)"
                role="tab" id="{{ $id }}-{{ $key }}" aria-controls="{{ $id }}-panel"
                aria-selected="{{ $isActive ? 'true' : 'false' }}" tabindex="{{ $isActive ? '0' : '-1' }}"
                x-bind:aria-selected="current === @js($key) ? 'true' : 'false'"
                x-bind:tabindex="current === @js($key) ? '0' : '-1'"
                class="{{ $base }}"
                x-bind:class="current === @js($key) ? @js($on) : @js($off)">
                <span class="flex items-center gap-2">
                    <span class="leading-none">{{ $segment['label'] }}</span>
                    @if ($count !== null)
                        <span class="datum inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[11px] leading-none font-semibold transition-colors duration-200"
                              x-bind:class="current === @js($key) ? @js($countOn) : @js($toneClass)">{{ (int) $count > 99 ? '99+' : $count }}</span>
                    @endif
                </span>
                @if ($variant === 'underline')
                    <span class="absolute -bottom-px left-0 h-[2px] w-full rounded-full transition-all duration-300"
                          x-bind:class="current === @js($key) ? 'bg-primary' : 'bg-transparent group-hover:bg-border'"></span>
                @endif
            </{{ $tag }}>
        @endforeach
    </div>
</div>
