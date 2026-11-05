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

    It is daisyUI's boxed tab strip, d-tabs d-tabs-box, on the muted track the
    old pill control used. The selected segment is the raised card-coloured
    tab with a semibold label and a dark count badge; the rest are muted.

    THE SLIDING THUMB IS GONE, and with it the Alpine that measured the active
    button's offset on resize, on livewire:navigated and on every change of
    `active`. d-tab-active paints the selected segment itself, so there is no
    geometry to measure and none of those three re-measuring bugs can come
    back.

    NO JAVASCRIPT IS REQUIRED FOR IT TO WORK. The buttons are real buttons
    with real wire:click handlers. With scripting off the control is a plain
    row of labels and the active one is still bold and still dark.

    The track scrolls sideways inside itself on a phone when the labels do not
    fit (flex-nowrap overrides d-tabs' wrapping); the page never does.
--}}
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="d-tabs d-tabs-box w-fit max-w-full flex-nowrap overflow-x-auto bg-base-300 [scrollbar-width:none]"
         role="tablist"
         @if ($label) aria-label="{{ $label }}" @endif>
        @foreach ($segments as $segment)
            @php $isActive = $active === $segment['key']; @endphp
            <button type="button"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    wire:click="$set('{{ $model }}', '{{ $segment['key'] }}')"
                    @class([
                        'd-tab shrink-0 grow gap-2 whitespace-nowrap text-[14px]',
                        'd-tab-active font-semibold text-base-content' => $isActive,
                        'font-medium text-muted-foreground hover:text-base-content' => ! $isActive,
                    ])>
                {{ $segment['label'] }}

                @if (($segment['count'] ?? null) !== null)
                    <span @class([
                        'd-badge d-badge-sm datum',
                        'd-badge-neutral' => $isActive,
                    ])>{{ $segment['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>
