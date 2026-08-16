@php
    /*
     * Livewire ships its OWN paginator, separate from Laravel's. Publishing
     * Laravel's was not enough: every Livewire table on this app renders this
     * file, and the stock version carries a white ground, a grey border,
     * blue focus rings and a full set of dark-mode variants from the default
     * palette - none of which exist in this design system. It was the single
     * most-repeated component in the app and the only one outside the system.
     *
     * (Those class names are described rather than spelled out: this file is a
     * Tailwind content source, and writing them literally would compile the
     * very utilities the rewrite exists to remove. PaginationViewTest asserts
     * neither paginator reintroduces them.)
     *
     * Every wire:click, the scroll snippet, wire:loading.attr and the dusk
     * hooks are preserved exactly; only the presentation changed.
     */
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';

    $pageName = $paginator->getPageName();
    $duskSuffix = $pageName === 'page' ? '' : '.'.$pageName;
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
             class="flex items-center justify-between gap-4 border-t border-border pt-4">

            {{-- Phone: previous / next only. Numbered pages do not fit at 390px. --}}
            <div class="flex flex-1 items-center justify-between sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="btn-secondary cursor-default opacity-40">{!! __('pagination.previous') !!}</span>
                @else
                    <button type="button" class="btn-secondary"
                            wire:click="previousPage('{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $duskSuffix }}.before">
                        {!! __('pagination.previous') !!}
                    </button>
                @endif

                <span class="datum text-[13px] text-muted-foreground">
                    {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <button type="button" class="btn-secondary"
                            wire:click="nextPage('{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $duskSuffix }}.before">
                        {!! __('pagination.next') !!}
                    </button>
                @else
                    <span class="btn-secondary cursor-default opacity-40">{!! __('pagination.next') !!}</span>
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

                <span class="inline-flex items-center gap-1">
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                              class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-border text-muted-foreground opacity-40">
                            <span aria-hidden="true">&lsaquo;</span>
                        </span>
                    @else
                        <button type="button" aria-label="{{ __('pagination.previous') }}"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-border text-foreground hover:bg-muted"
                                wire:click="previousPage('{{ $pageName }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                dusk="previousPage{{ $duskSuffix }}.after">
                            <span aria-hidden="true">&lsaquo;</span>
                        </button>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true"
                                  class="datum inline-flex min-h-11 min-w-11 items-center justify-center text-[13px] text-muted-foreground">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page"
                                          class="datum inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-foreground bg-foreground text-[13px] font-medium text-card"
                                          dusk="page{{ $duskSuffix }}.{{ $page }}">{{ $page }}</span>
                                @else
                                    <button type="button" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                            class="datum inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-border text-[13px] text-foreground hover:bg-muted"
                                            wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                            dusk="page{{ $duskSuffix }}.{{ $page }}">{{ $page }}</button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <button type="button" aria-label="{{ __('pagination.next') }}"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-border text-foreground hover:bg-muted"
                                wire:click="nextPage('{{ $pageName }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                dusk="nextPage{{ $duskSuffix }}.after">
                            <span aria-hidden="true">&rsaquo;</span>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                              class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[4px] border border-border text-muted-foreground opacity-40">
                            <span aria-hidden="true">&rsaquo;</span>
                        </span>
                    @endif
                </span>
            </div>
        </nav>
    @endif
</div>
