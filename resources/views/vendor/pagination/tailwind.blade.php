{{--
    Laravel's stock Tailwind paginator ships a white ground, a grey border and
    six shades of grey text from the default palette. None of those exist in this
    design system, and the paginator renders on the catalogue, the broodcock
    table, health, pens, performance and mortality - so the single most-repeated
    component in the app was the one component not using the system.

    Deliberately not naming those classes literally here: Tailwind v4 scans this
    file as a content source and does not know a comment from markup, so writing
    them out would generate the very utilities this file exists to stop using.

    Rewritten on the tokens. Page numbers are .datum so they align, and the
    current page is marked by ink weight and a filled ground rather than colour,
    because colour means bloodline.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between gap-4 border-t border-border pt-4">

        {{-- Phone: previous / next only. Numbered pages do not fit at 390px. --}}
        <div class="flex flex-1 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="d-btn d-btn-outline d-btn-disabled">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="d-btn d-btn-outline">{!! __('pagination.previous') !!}</a>
            @endif

            <span class="datum text-[13px] text-muted-foreground">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="d-btn d-btn-outline">{!! __('pagination.next') !!}</a>
            @else
                <span class="d-btn d-btn-outline d-btn-disabled">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="text-[13px] text-muted-foreground">
                {!! __('Showing') !!}
                <span class="datum text-foreground">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="datum text-foreground">{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span class="datum text-foreground">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <span class="d-join">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                          class="d-join-item d-btn d-btn-disabled min-w-11">
                        <span aria-hidden="true">&lsaquo;</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                       class="d-join-item d-btn min-w-11">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true"
                              class="d-join-item d-btn d-btn-disabled datum min-w-11">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="d-join-item d-btn d-btn-neutral d-btn-active datum min-w-11">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                   class="d-join-item d-btn datum min-w-11">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                       class="d-join-item d-btn min-w-11">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                          class="d-join-item d-btn d-btn-disabled min-w-11">
                        <span aria-hidden="true">&rsaquo;</span>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
