{{--
    Laravel's stock Tailwind paginator ships bg-white, border-gray-300 and six
    shades of text-gray-*. None of those exist in this design system, and the
    paginator renders on the catalogue, the broodcock table, health, pens,
    performance and mortality - so the single most-repeated component in the app
    was the one component not using the system.

    Rewritten on the tokens. Page numbers are .datum so they align, and the
    current page is marked by ink weight and a filled ground rather than colour,
    because colour means bloodline.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between gap-4 border-t border-hairline pt-4">

        {{-- Phone: previous / next only. Numbered pages do not fit at 390px. --}}
        <div class="flex flex-1 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary cursor-default opacity-40">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary">{!! __('pagination.previous') !!}</a>
            @endif

            <span class="datum text-[13px] text-ink-80">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary">{!! __('pagination.next') !!}</a>
            @else
                <span class="btn-secondary cursor-default opacity-40">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="text-[13px] text-ink-80">
                {!! __('Showing') !!}
                <span class="datum text-ink">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="datum text-ink">{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span class="datum text-ink">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <span class="inline-flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                          class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-hairline text-ink-48 opacity-40">
                        <span aria-hidden="true">&lsaquo;</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                       class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-hairline text-ink hover:bg-pearl">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true"
                              class="datum inline-flex min-h-11 min-w-11 items-center justify-center text-[13px] text-ink-48">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="datum inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-ink bg-ink text-[13px] font-medium text-canvas">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                   class="datum inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-hairline text-[13px] text-ink hover:bg-pearl">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                       class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-hairline text-ink hover:bg-pearl">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                          class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-hairline text-ink-48 opacity-40">
                        <span aria-hidden="true">&rsaquo;</span>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
