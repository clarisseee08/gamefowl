@props([
    /** The LengthAwarePaginator the table is showing. */
    'paginator',
    /** The Livewire property holding the page size. */
    'model' => 'perPage',
    /** The page-size control's id, when one screen shows two lists. */
    'id' => null,
    /** @var list<int> The page sizes on offer. */
    'sizes' => [10, 25, 50, 100],
])

{{--
    THE TABLE FOOTER (EMS DataTable, Part B §5.13): rows per page on the
    left; where you are and the way through on the right. It sits under the
    table, where the eye arrives after reading the rows, not above them.

    The buttons call WithPagination's own gotoPage / previousPage / nextPage,
    so it needs nothing from the component beyond the page-size property.
    Phones get previous and next only; numbered pages do not fit at 390px.
--}}
@php
    $pageName = $paginator->getPageName();
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // First, last, and one either side of the current page; a gap becomes an ellipsis.
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn (int $page): bool => $page >= 1 && $page <= $last)
        ->unique()
        ->sort()
        ->values();

    $numberClasses = 'size-11 items-center justify-center rounded-md text-sm font-medium outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:size-8 lg:text-xs';
    $scrollToTop = "document.querySelector('main')?.scrollTo({ top: 0 })";
@endphp

<div {{ $attributes->class('flex flex-col-reverse gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between') }}>
    <div class="flex items-center gap-2">
        <span class="shrink-0 whitespace-nowrap text-sm text-muted-foreground lg:text-xs" aria-hidden="true">Rows per page</span>
        <div class="w-20 shrink-0">
            <x-ui.select :id="$id ?? $model" label="Rows per page" :label-hidden="true" wire:model.live="{{ $model }}"
                         :options="array_combine($sizes, $sizes)" />
        </div>
    </div>

    <div class="flex items-center justify-between gap-4 sm:justify-end">
        <p class="text-sm text-muted-foreground lg:text-xs">
            Page <span class="datum text-foreground">{{ $current }}</span> of <span class="datum text-foreground">{{ $last }}</span>
        </p>

        @if ($paginator->hasPages())
            <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-1">
                <x-ui.button variant="outline" size="icon-sm" aria-label="Previous page"
                             :disabled="$paginator->onFirstPage()"
                             wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scrollToTop }}">
                    <x-ui.icon name="chevron-left" />
                </x-ui.button>

                @foreach ($pages as $index => $page)
                    @if ($index > 0 && $page - $pages[$index - 1] > 1)
                        <span class="hidden size-8 items-center justify-center text-xs text-muted-foreground sm:inline-flex" aria-hidden="true">&hellip;</span>
                    @endif

                    @if ($page === $current)
                        <span aria-current="page" @class([$numberClasses, 'datum hidden border border-border bg-background text-foreground shadow-xs sm:inline-flex'])>{{ $page }}</span>
                    @else
                        <button type="button" aria-label="Page {{ $page }}"
                                wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scrollToTop }}"
                                @class([$numberClasses, 'datum hidden text-foreground hover:bg-muted sm:inline-flex'])>{{ $page }}</button>
                    @endif
                @endforeach

                <x-ui.button variant="outline" size="icon-sm" aria-label="Next page"
                             :disabled="! $paginator->hasMorePages()"
                             wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scrollToTop }}">
                    <x-ui.icon name="chevron-right" />
                </x-ui.button>
            </nav>
        @endif
    </div>
</div>
