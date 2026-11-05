@props([
    /** The page's one h1: the page name, with no app or section prefix. */
    'title',
    /** One line under it, when it adds something the title cannot. */
    'description' => null,
    /** The real number this page is about. Never invented; null hides it. */
    'figure' => null,
    /** What the number counts, in a few words: "birds on record". */
    'figureLabel' => null,
])

{{--
    THE PAGE HEADER (EMS, Part B §6.3) - how every console page opens: the
    title at 16px bold with one short line under it, and the page's actions on
    the right. No eyebrow above it, no hero figure: the owner's call, set
    against EMS's own screens - the old 30px title over a 50px number was
    the loudest thing on every page.

    The page's figure, when it has one, is the first line under the title -
    "30 birds on record" - small, with the number in tabular figures. The
    default slot is anything that qualifies it.

    Actions go in the `actions` slot: at most one primary button (a Plus icon
    and "Add …"), then outline buttons; more than two others belong in a ⋯ menu.
--}}
<header {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between') }}>
    <div class="min-w-0">
        <h1 class="text-base font-bold text-foreground">{{ $title }}</h1>

        @if ($figure !== null || $description || trim($slot) !== '')
            <div class="mt-1 space-y-0.5 text-[13px] leading-snug text-muted-foreground lg:text-xs">
                @if ($figure !== null)
                    <p><span class="datum font-semibold text-foreground">{{ $figure }}</span> @if ($figureLabel)<span>{{ $figureLabel }}</span>@endif</p>
                @endif
                @if ($description)
                    <p>{{ $description }}</p>
                @endif
                @if (trim($slot) !== '')
                    <div>{{ $slot }}</div>
                @endif
            </div>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
