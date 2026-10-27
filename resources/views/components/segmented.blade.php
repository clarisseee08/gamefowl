@props([
    /**
     * The segments. Each entry is ['key' => …, 'label' => …, 'count' => ?int].
     * `count` is optional and omitted rather than shown as 0, because a
     * segment reading "Photos 0" and one reading "Photos" say different
     * things: the first asserts the farm has none, the second declines to
     * claim either way before the section is opened.
     *
     * @var list<array{key:string,label:string,count?:int|null}>
     */
    'segments' => [],

    /** The selected key. */
    'active' => null,

    /**
     * The Livewire property this writes to. Rendered as
     * wire:click="$set('<model>', '<key>')", which is how every other control
     * on these screens changes component state.
     */
    'model' => 'tab',

    /** Accessible name for the strip, e.g. "Bird record sections". */
    'label' => null,
])

{{--
    A SEGMENTED CONTROL — the whole set of views visible before one is chosen.

    THE SLIDE IS ALPINE, NOT CSS, and it has to be: the segments are different
    widths, so the thumb must know the measured geometry of the active button
    rather than a fraction of the track. place() reads the active button's
    offset and width and writes them to two custom properties the stylesheet
    animates.

    IT RE-MEASURES ON THREE EVENTS, and each one is a bug that happened:

      resize      - the labels reflow, so every offset moves.
      livewire:navigated - a soft navigation reuses the DOM, and without this
                    the thumb kept the geometry of the PREVIOUS page's strip.
      $watch on active - Livewire replaces the buttons when the property
                    changes, so the element place() measured is gone by the
                    time the new one paints.

    $nextTick before measuring, because on the first of those the new label
    has been written to the DOM but not laid out, and offsetLeft on an
    unlaid-out element is 0 - which parked the thumb at the far left on every
    first click.

    NO JAVASCRIPT IS REQUIRED FOR IT TO WORK. The buttons are real buttons
    with real wire:click handlers; Alpine only moves a decorative thumb. With
    scripting off the control is a plain row of labels and the active one is
    still bold and still dark.
--}}
<div
    x-data="{
        place() {
            this.$nextTick(() => {
                const on = this.$refs.track?.querySelector('[data-seg-active]');
                if (! on) return;
                this.$refs.track.style.setProperty('--seg-x', on.offsetLeft + 'px');
                this.$refs.track.style.setProperty('--seg-w', on.offsetWidth + 'px');
            });
        },
    }"
    x-init="place(); $watch('$el.dataset.active', () => place())"
    data-active="{{ $active }}"
    {{-- x-on: rather than Alpine's @ shorthand, and this is not style.
         `@livewire:navigated` is swallowed by Blade as the @livewire
         DIRECTIVE - the compiler emits a component call with no arguments and
         the page dies with "Too few arguments to {closure}()", pointing at a
         line of markup that looks nothing like the problem. Both handlers are
         written long-hand so neither can collide with a directive name. --}}
    x-on:resize.window="place()"
    x-on:livewire:navigated.window="place()"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    <div class="segmented" role="tablist" x-ref="track"
         @if ($label) aria-label="{{ $label }}" @endif>
        <span class="segmented-thumb" aria-hidden="true"></span>

        @foreach ($segments as $segment)
            @php $isActive = $active === $segment['key']; @endphp
            <button type="button"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    wire:click="$set('{{ $model }}', '{{ $segment['key'] }}')"
                    @if ($isActive) data-seg-active @endif
                    @class([
                        'segmented-item',
                        'segmented-item-active' => $isActive,
                    ])>
                {{ $segment['label'] }}

                @if (($segment['count'] ?? null) !== null)
                    <span class="segmented-count">{{ $segment['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>
