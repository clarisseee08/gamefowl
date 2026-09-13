@props([
    /** info | success | warning | danger. Anything else falls back to info. */
    'variant' => 'info',
    /** The one-line statement. Required — an alert with no title is a paragraph. */
    'title',
])

{{--
    A STATED CONDITION — "this saved", "this pen is over capacity", "this failed".

    Structure follows D:/ems/employee-management-system's ui/alert: a glyph in a
    narrow leading column, a title, and an optional body beneath it, all on one
    tinted ground. Four things were changed on the way across, and each is a
    rule of this system rather than a preference.

    THE TINT IS THE ONLY COLOUR, AND ONLY THE GLYPH CARRIES THE INK. The five
    status washes are the single exception to "colour means bloodline", and they
    are desaturated so they never compete with a band tag. Spending the variant
    colour on the title as well would put a second, louder colour on the page for
    no extra information.

    THE TEXT IS text-foreground, NOT text-muted-foreground, and that is a
    contrast decision rather than a stylistic one. muted-foreground is measured
    at 7.40:1 against the PAGE; on the warning ground (the farm cream) it drops
    to 5.95:1, under the console's 7:1 floor. foreground on the same ground is
    13.7:1. Hierarchy comes from weight and size instead — which is what this
    system reaches for whenever colour is not available.

    shadcn marks every alert role="alert". That is an ASSERTIVE live region: it
    interrupts a screen reader mid-sentence. Right for a failure, wrong for a
    confirmation, so only `danger` gets it and the rest are polite role="status".

    NO ICON PACKAGE. The glyphs are inline Heroicons outline path data at the
    1.6 stroke every other icon in this project uses.
--}}
@php
    /*
     * Whole class names, never assembled from parts. Tailwind v4 resolves
     * utilities by scanning this file as text, so 'bg-'.$variant.'-bg' would
     * compile to nothing — and would still survive `npm run dev`, because dev
     * and build disagree about exactly this.
     */
    $tones = [
        'info' => [
            'ground' => 'bg-info-bg',
            'ink' => 'text-info',
            'path' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        ],
        'success' => [
            'ground' => 'bg-success-bg',
            'ink' => 'text-success',
            'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'warning' => [
            'ground' => 'bg-warning-bg',
            'ink' => 'text-warning',
            'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
        'danger' => [
            'ground' => 'bg-destructive-bg',
            'ink' => 'text-destructive',
            'path' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
        ],
    ];

    $tone = $tones[$variant] ?? $tones['info'];
@endphp

<div {{ $attributes->merge([
        'class' => 'flex items-start gap-3 rounded-[var(--radius-md)] border border-border px-4 py-3 '.$tone['ground'],
        'role' => $variant === 'danger' ? 'alert' : 'status',
    ]) }}>

    {{-- translate-y-px optically seats the glyph on the title's first baseline;
         items-start alone hangs it a hair high against a 15px cap height. --}}
    <svg class="mt-px h-5 w-5 shrink-0 translate-y-px {{ $tone['ink'] }}" fill="none" stroke="currentColor"
         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tone['path'] }}"/>
    </svg>

    <div class="min-w-0 flex-1">
        {{-- min-w-0 on the column, not the wrapper: without it a long unbroken
             band number or email in the body forces the flex row wider than the
             card and the console scrolls sideways. --}}
        <p class="text-[15px] font-medium leading-snug text-foreground">{{ $title }}</p>

        @if (trim($slot) !== '')
            <div class="mt-1 text-[15px] leading-snug text-foreground">{{ $slot }}</div>
        @endif
    </div>
</div>
