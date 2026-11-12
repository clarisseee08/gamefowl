@props([
    /** What the switch chooses between, for screen readers. */
    'label' => 'Show the list as',
])

{{--
    TABLE OR CARDS - the two-icon switch beside a list's filters (EMS
    DataTable / DataGrid, as on its recruitment pipeline).

    It writes `view` ('table' or 'cards') in the Alpine scope around it, which
    the page keeps with $persist under a name of its own, so each list
    remembers how it was last read. Pure Alpine: switching asks nothing of the
    server, so it is instant on the slowest connection.

    From md up only. Phones keep their stacked layout, which is already one
    card per row.
--}}
<div role="group" aria-label="{{ $label }}"
     {{ $attributes->class('hidden shrink-0 items-center gap-0.5 rounded-md border border-border bg-card p-0.5 md:inline-flex') }}>
    @foreach (['table' => ['layout-list', 'Table'], 'cards' => ['layout-grid', 'Cards']] as $value => [$icon, $name])
        <button type="button"
                x-on:click="view = @js($value)"
                x-bind:aria-pressed="(view === @js($value)).toString()"
                x-bind:class="view === @js($value) ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                class="inline-flex size-11 items-center justify-center rounded-[5px] outline-none transition-colors focus-visible:ring-2 focus-visible:ring-ring/50 lg:size-7"
                aria-label="{{ $name }}" title="{{ $name }}">
            <x-ui.icon :name="$icon" class="size-4 lg:size-3.5" />
        </button>
    @endforeach
</div>
