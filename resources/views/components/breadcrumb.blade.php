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
    aria-current="page". The trail is daisyUI's d-breadcrumbs, which also draws
    the chevron between crumbs.

    AN ORDERED LIST, NOT A ROW OF SPANS. The order is the meaning — this page is
    inside that one — and <ol> is the only element that says so to anything that
    is not looking at the screen.

    THE LAST CRUMB IS NOT A LINK. shadcn renders it as a span with role="link"
    and aria-disabled, which announces a link that does nothing. A link to the
    page you are already on is the most common breadcrumb defect; here the last
    entry is text, and aria-current="page" is what names it.

    THE SEPARATOR LIVES INSIDE ITS CRUMB'S <li> - d-breadcrumbs draws it as a
    ::before on every crumb after the first. Only <li> may be a child of <ol>,
    and a chevron in its own list item makes a five-crumb trail announce as nine
    items.

    wire:navigate PER ITEM, defaulting on. Every crumb in this application is an
    internal route, so the default is right; an item may pass
    'navigate' => false where it is not.

    THE TRAIL MUST NOT REPEAT THE PAGE TITLE BELOW IT. app-topbar makes the same
    point: a breadcrumb reading "Broodcocks" directly above an <h1> reading
    "Broodcocks" is chrome, not orientation.
--}}
{{-- py-0: d-breadcrumbs pads itself for a standalone bar; this sits in
     whatever row the caller gives it. Plain-text crumbs opt out of the hover
     underline d-breadcrumbs gives every crumb, because they go nowhere. --}}
<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'd-breadcrumbs py-0 text-[14px]']) }}>
    <ol>
        @foreach ($items as $item)
            <li>
                @if ($loop->last)
                    <span class="cursor-default text-base-content hover:no-underline" aria-current="page">{{ $item['label'] }}</span>
                @elseif (! empty($item['href']))
                    <a href="{{ $item['href'] }}"
                       @if ($item['navigate'] ?? true) wire:navigate @endif
                       class="text-muted-foreground hover:text-base-content">{{ $item['label'] }}</a>
                @else
                    {{-- A rung with no route of its own — a grouping level that
                         is not a page. Still shown, because dropping it would
                         make the trail claim the wrong parent. --}}
                    <span class="cursor-default text-muted-foreground hover:no-underline">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
