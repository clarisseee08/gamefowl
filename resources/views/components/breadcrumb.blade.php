@props([
    /**
     * Ordered, root first: [['label' => 'Broodcocks', 'href' => route(…)], …].
     * The LAST entry is where the user is, and its href is ignored.
     */
    'items' => [],
])

{{--
    WHERE THIS PAGE SITS.

    Structure follows D:/ems/employee-management-system's ui/breadcrumb: nav,
    ordered list, links, chevrons, and the current page as plain text carrying
    aria-current="page". It wraps `.breadcrumb`, `.breadcrumb-link` and
    `.breadcrumb-current`, which app.css already defines.

    AN ORDERED LIST, NOT A ROW OF SPANS. The order is the meaning — this page is
    inside that one — and <ol> is the only element that says so to anything that
    is not looking at the screen.

    THE LAST CRUMB IS NOT A LINK. shadcn renders it as a span with role="link"
    and aria-disabled, which announces a link that does nothing. A link to the
    page you are already on is the most common breadcrumb defect; here the last
    entry is text, and aria-current="page" is what names it.

    THE SEPARATOR LIVES INSIDE ITS CRUMB'S <li>. Only <li> may be a child of
    <ol>, and a chevron in its own list item makes a five-crumb trail announce
    as nine items.

    wire:navigate PER ITEM, defaulting on. Every crumb in this application is an
    internal route, so the default is right; an item may pass
    'navigate' => false where it is not.

    THE TRAIL MUST NOT REPEAT THE PAGE TITLE BELOW IT. app-topbar makes the same
    point: a breadcrumb reading "Broodcocks" directly above an <h1> reading
    "Broodcocks" is chrome, not orientation.
--}}
<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="breadcrumb">
        @foreach ($items as $item)
            <li class="inline-flex items-center gap-1.5">
                @if ($loop->last)
                    <span class="breadcrumb-current" aria-current="page">{{ $item['label'] }}</span>
                @elseif (! empty($item['href']))
                    <a href="{{ $item['href'] }}"
                       @if ($item['navigate'] ?? true) wire:navigate @endif
                       class="breadcrumb-link">{{ $item['label'] }}</a>
                @else
                    {{-- A rung with no route of its own — a grouping level that
                         is not a page. Still shown, because dropping it would
                         make the trail claim the wrong parent. --}}
                    <span class="breadcrumb-link">{{ $item['label'] }}</span>
                @endif

                @unless ($loop->last)
                    <svg class="h-3.5 w-3.5 shrink-0 text-muted-foreground" fill="none" stroke="currentColor"
                         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
