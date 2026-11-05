@props([
    /** 0–100. Anything outside that range is clamped, never trusted. */
    'value' => 0,
    /** What is being measured. Omit for a bare bar under a heading that says so. */
    'label' => null,
])

{{--
    A PROPORTION — hatch rate, pedigree completeness, pen occupancy.

    Structure follows D:/ems/employee-management-system's ui/progress: a rounded
    track, a flat bar clipped by it, and the figure stated in text beside the
    label.

    THE BAR IS A NATIVE <progress>, styled by daisyUI's d-progress. The browser
    draws the fill from `value` itself, so there is no inner element to size or
    scale and nothing for this file to animate - which settles the old
    width-versus-scaleX question by not asking it. h-1.5 keeps the thin 6px
    track the meters always had.

    THE FIGURE IS .datum. It is a percentage, so it is monospaced with tabular
    figures like every other number here: a column of these has to align on the
    digit, and a stack of progress rows is precisely where drift shows.

    NO COUNT-UP. The motion whitelist bans number roll-ups: a figure that
    animates cannot be read while it does so.

    THE VALUE IS FORWARDED TO ARIA, which is the bug EMS's own file documents
    fixing — a bar whose value drove only the visual left every assistive
    technology told that a measurement existed but never what it was. A native
    <progress> exposes its value already; the explicit aria-value* attributes
    stay so that holds even where a reader ignores the element's own value.
--}}
@php
    $percent = (int) round(min(100, max(0, (float) $value)));

    $labelId = $label !== null ? 'progress-'.\Illuminate\Support\Str::slug($label).'-label' : null;
@endphp

<div {{ $attributes }}>
    @if ($label !== null)
        <div class="flex items-baseline justify-between gap-3">
            <span id="{{ $labelId }}" class="text-[15px] leading-snug text-foreground">{{ $label }}</span>
            <span class="datum shrink-0 text-[15px] leading-snug text-muted-foreground">{{ $percent }}%</span>
        </div>
    @endif

    <progress class="d-progress d-progress-primary block h-1.5 {{ $label !== null ? 'mt-2' : '' }}"
              value="{{ $percent }}"
              max="100"
              role="progressbar"
              aria-valuenow="{{ $percent }}"
              aria-valuemin="0"
              aria-valuemax="100"
              @if ($labelId) aria-labelledby="{{ $labelId }}" @endif></progress>
</div>
