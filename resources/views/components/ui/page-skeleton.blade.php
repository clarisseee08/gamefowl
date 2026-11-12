@props([
    /** list | detail | form | dashboard - the kind of page on its way. */
    'kind' => 'list',
])

{{--
    A PAGE THAT IS ON ITS WAY (EMS, Part B §5.22).

    Every page change in the console is a wire:navigate: the server builds the
    next page and only then does anything on screen change. On Render's free
    instance that is half a second to a second and a half with the old page
    sitting there, still clickable, looking like the answer. So the moment a
    link is pressed the old page goes and one of these stands in, shaped like
    the page that is coming - a list, a record, a form or the dashboard -
    until the real one lands.

    The layout keeps one of each kind in a <template>; app.js picks one by
    the address the link points at. Nothing here is announced but a single
    "Loading the page…".
--}}
<div {{ $attributes->class('px-4 py-6 sm:px-6 lg:px-8') }} aria-busy="true">
    <span class="sr-only" role="status">Loading the page&hellip;</span>

    @if ($kind === 'detail')
        <x-ui.skeleton class="h-3 w-40" />
    @elseif ($kind === 'form')
        <x-ui.skeleton class="h-3 w-32" />
    @endif

    {{-- The page header: title, the line under it, an action on the right. --}}
    <div @class(['flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between', 'mt-5' => in_array($kind, ['detail', 'form'], true)])>
        <div class="space-y-2">
            <x-ui.skeleton class="h-5 w-44" />
            <x-ui.skeleton class="h-3 w-28" />
            <x-ui.skeleton class="h-3 w-72 max-w-full" />
        </div>
        @if ($kind !== 'form')
            <x-ui.skeleton class="h-11 w-36 lg:h-7" />
        @endif
    </div>

    @switch($kind)
        @case('dashboard')
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @for ($i = 0; $i < 4; $i++)
                    <div class="rounded-md border border-border bg-card p-4">
                        <x-ui.skeleton class="h-2.5 w-24" />
                        <x-ui.skeleton class="mt-3 h-6 w-20" />
                        <x-ui.skeleton class="mt-2.5 h-2.5 w-28" />
                    </div>
                @endfor
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <div class="rounded-md border border-border bg-card p-5 lg:col-span-2">
                    <x-ui.skeleton class="h-3 w-40" />
                    <x-ui.skeleton class="mt-5 h-52 w-full" />
                </div>
                <div class="space-y-4 rounded-md border border-border bg-card p-5">
                    <x-ui.skeleton class="h-3 w-32" />
                    @for ($i = 0; $i < 5; $i++)
                        <div class="flex items-center gap-3">
                            <x-ui.skeleton class="size-8 shrink-0 rounded-full" />
                            <div class="flex-1 space-y-1.5">
                                <x-ui.skeleton class="h-3 {{ ['w-3/4', 'w-1/2', 'w-2/3', 'w-3/5', 'w-1/2'][$i] }}" />
                                <x-ui.skeleton class="h-2.5 w-1/3" />
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            @break

        @case('detail')
            {{-- The photograph a third of the width, the particulars beside it
                 as a grid of tiles - as the bird's page is laid out. --}}
            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                <x-ui.skeleton class="min-h-56 w-full rounded-md" />
                <div class="rounded-md border border-border bg-card p-5 lg:col-span-2">
                    <x-ui.skeleton class="h-3.5 w-28" />
                    <x-ui.skeleton class="mt-2 h-2.5 w-44" />
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @for ($i = 0; $i < 6; $i++)
                            <div class="space-y-2 rounded-md bg-muted/60 p-3">
                                <x-ui.skeleton class="h-2.5 w-12" />
                                <x-ui.skeleton class="h-3.5 {{ ['w-16', 'w-20', 'w-24', 'w-14', 'w-16', 'w-20'][$i] }}" />
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-6 border-b border-border pb-3">
                @for ($i = 0; $i < 5; $i++)
                    <x-ui.skeleton class="h-3 {{ ['w-16', 'w-14', 'w-14', 'w-12', 'w-16'][$i] }}" />
                @endfor
            </div>
            <div class="mt-5 space-y-3 rounded-md border border-border bg-card p-5">
                <x-ui.skeleton class="h-3.5 w-40" />
                <x-ui.skeleton class="h-3 w-3/4" />
                <x-ui.skeleton class="h-3 w-2/3" />
                <x-ui.skeleton class="h-3 w-1/2" />
            </div>
            @break

        @case('form')
            <div class="mt-6 max-w-3xl rounded-md border border-border bg-card pt-5">
                <x-ui.form-skeleton :fields="6" :marker="false" />
            </div>
            @break

        @default
            {{-- A list: its figures, its filters, its rows. --}}
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                @for ($i = 0; $i < 3; $i++)
                    <div @class(['rounded-md border border-border bg-card p-4', 'hidden sm:block' => $i > 0])>
                        <x-ui.skeleton class="h-2.5 w-24" />
                        <x-ui.skeleton class="mt-3 h-6 w-16" />
                        <x-ui.skeleton class="mt-2.5 h-2.5 w-32" />
                    </div>
                @endfor
            </div>
            <div class="mt-8 flex gap-3">
                <x-ui.skeleton class="h-11 flex-1 sm:max-w-sm lg:h-7" />
                <x-ui.skeleton class="hidden h-7 w-28 lg:block" />
                <x-ui.skeleton class="hidden h-7 w-28 lg:block" />
                <x-ui.skeleton class="hidden h-7 w-28 lg:block" />
            </div>
            <x-ui.rows-skeleton class="mt-5" :cols="6" :rows="8" :card="false" />
    @endswitch
</div>
