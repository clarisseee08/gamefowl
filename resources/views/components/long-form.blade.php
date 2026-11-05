@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
    /** The contents list: section id => heading. */
    'contents' => [],
    /**
     * The page at a glance, above the text: [icon, title, line] each. Every
     * one is a sentence the text below already makes - a summary, never a
     * new promise.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    'highlights' => [],
])

{{--
    LONG-FORM READING (EMS) - the privacy notice, the terms, anything that is
    prose first.

    The text holds a 65-character measure on a card, which is where a line
    stops being easy to track back from. On a wide screen the contents list
    sits to its left and stays in view while the page scrolls, marking the
    section being read; on a phone it sits at the top, so a reader can still
    jump straight to the section they came for. The marking is client-side
    only - reading never talks to the server.
--}}
<div class="sb-wrap pb-16 pt-10 lg:pb-24 lg:pt-14">
    <div class="max-w-[60ch]">
        @if ($eyebrow)
            <p class="sb-eyebrow sb-line" style="--line: 0ms">{{ $eyebrow }}</p>
        @endif
        <h1 class="sb-hero-title sb-line mt-3" style="--line: 60ms">{{ $title }}</h1>
        @if ($lead)
            <p class="sb-lead sb-line mt-4" style="--line: 130ms">{{ $lead }}</p>
        @endif
    </div>

    @if (count($highlights) > 0)
        <div class="sb-line mt-8" style="--line: 200ms">
            <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">At a glance</p>
            <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($highlights as [$icon, $heading, $line])
                    <li class="flex gap-3 rounded-md border border-border bg-card p-4">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-foreground"><x-ui.icon :name="$icon" class="size-4" /></span>
                        <span class="min-w-0">
                            <span class="block text-[15px] font-semibold text-foreground">{{ $heading }}</span>
                            <span class="mt-0.5 block text-sm leading-relaxed text-muted-foreground">{{ $line }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-8 grid gap-6 lg:mt-10 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-10"
         @if (count($contents) > 0)
             x-data="{
                 current: @js(array_key_first($contents)),
                 init() {
                     const watcher = new IntersectionObserver((entries) => {
                         entries.filter((entry) => entry.isIntersecting)
                             .forEach((entry) => { this.current = entry.target.id });
                     }, { rootMargin: '-15% 0px -70% 0px' });
                     @js(array_keys($contents)).forEach((id) => {
                         const heading = document.getElementById(id);
                         if (heading) { watcher.observe(heading) }
                     });
                 },
             }"
         @endif>
        @if (count($contents) > 0)
            <nav aria-label="On this page" class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-md border border-border bg-card p-4">
                    <p class="text-xs font-semibold tracking-wider text-foreground uppercase">On this page</p>
                    <ol class="mt-3 space-y-0.5">
                        @foreach ($contents as $id => $heading)
                            <li>
                                <a href="#{{ $id }}"
                                   x-on:click="current = @js($id)"
                                   x-bind:class="current === @js($id) ? 'bg-muted text-foreground font-medium' : 'text-muted-foreground'"
                                   class="flex min-h-10 items-center rounded-md px-2.5 text-[14px] text-muted-foreground transition-colors hover:bg-muted/60 hover:text-foreground lg:min-h-8 lg:text-[13px]">
                                    {{ $heading }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </nav>
        @endif

        <article class="long-form rounded-md border border-border bg-card px-5 py-6 sm:px-8 sm:py-8 lg:px-10">
            <div class="max-w-[65ch]">
                {{ $slot }}
            </div>
        </article>
    </div>
</div>
