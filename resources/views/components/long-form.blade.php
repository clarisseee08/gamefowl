@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
    /** The contents list: section id => heading. */
    'contents' => [],
])

{{--
    LONG-FORM READING - the privacy notice, the terms, anything that is prose
    first.

    The text holds a 65-character measure, which is where a line stops being
    easy to track back from. On a wide screen a short contents list sits to
    its left and stays in view while the page scrolls; on a phone it sits at
    the top, so a reader can still jump straight to the section they came for.
--}}
<div class="sb-wrap pb-16 pt-10 lg:pb-28 lg:pt-14">
    <x-page-header :eyebrow="$eyebrow" :title="$title" :lead="$lead" />

    <div class="mt-12 grid gap-10 lg:mt-16 lg:grid-cols-[220px_minmax(0,65ch)] lg:gap-20">
        @if (count($contents) > 0)
            <nav aria-label="On this page" class="lg:sticky lg:top-24 lg:self-start">
                <p class="text-[14px] font-semibold text-foreground">On this page</p>
                <ol class="mt-3 border-l border-border">
                    @foreach ($contents as $id => $heading)
                        <li>
                            <a href="#{{ $id }}"
                               class="-ml-px flex min-h-10 items-center border-l-2 border-transparent pl-4 text-[14px] text-muted-foreground hover:border-primary hover:text-foreground">
                                {{ $heading }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <article class="long-form max-w-[65ch]">
            {{ $slot }}
        </article>
    </div>
</div>
