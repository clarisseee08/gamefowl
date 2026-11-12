@props([
    /** How many card shapes to draw. */
    'count' => 6,
])

{{--
    THE CARDS VIEW WHILE ITS ANSWER IS FETCHED - the record-card's shape,
    standing in for the cards a filter, sort or page change is replacing.
    Shown with wire:loading.grid by the page, beside the grid it replaces.
--}}
<div {{ $attributes->class('hidden grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4') }} aria-hidden="true">
    @for ($i = 0; $i < $count; $i++)
        <div class="flex flex-col rounded-md border border-border bg-card">
            <div class="flex items-start gap-3 p-4">
                <x-ui.skeleton class="size-10 shrink-0" />
                <div class="flex-1 space-y-2">
                    <x-ui.skeleton class="h-3.5 {{ ['w-2/3', 'w-1/2', 'w-3/5'][$i % 3] }}" />
                    <x-ui.skeleton class="h-2.5 w-1/3" />
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3 px-4 pb-4">
                @for ($f = 0; $f < 3; $f++)
                    <div class="space-y-1.5">
                        <x-ui.skeleton class="h-2 w-10" />
                        <x-ui.skeleton class="h-3 w-14" />
                    </div>
                @endfor
            </div>
            <div class="h-9 border-t border-border bg-muted/30"></div>
        </div>
    @endfor
</div>
