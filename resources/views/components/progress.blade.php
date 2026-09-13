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
    label. The track is this project's existing `.meter`, not a new primitive.

    THE BAR IS DRIVEN BY transform: scaleX, NOT width. Width is a layout
    property: changing it reflows the bar's subtree and repaints on every frame,
    on the low-end Android phones this runs on. A transform is composited and
    costs neither. `origin-left` is what makes it grow from the left edge rather
    than out of the middle.

    THE BAR IS SQUARE-ENDED ON PURPOSE. app.css warns that scaleX distorts a
    rounded cap into an ellipse — true, and the reason the bar carries
    `rounded-none` to override `.meter > span`. The track is already
    `overflow-hidden rounded-full`, so it supplies the left cap by clipping,
    which is exactly how the shadcn original gets its shape too.

    THE FIGURE IS .datum. It is a percentage, so it is monospaced with tabular
    figures like every other number here: a column of these has to align on the
    digit, and a stack of progress rows is precisely where drift shows.

    NO COUNT-UP. The motion whitelist bans number roll-ups: a figure that
    animates cannot be read while it does so.

    THE VALUE IS FORWARDED TO ARIA, which is the bug EMS's own file documents
    fixing — a bar whose value drove only the visual left every assistive
    technology told that a measurement existed but never what it was.
--}}
@php
    $percent = (int) round(min(100, max(0, (float) $value)));

    /* 4dp: at a 320px-wide bar one percent is ~3px, and rounding the scale to
       two places visibly quantises the last few percent of a long bar. */
    $scale = number_format($percent / 100, 4, '.', '');

    $labelId = $label !== null ? 'progress-'.\Illuminate\Support\Str::slug($label).'-label' : null;
@endphp

<div {{ $attributes }}>
    @if ($label !== null)
        <div class="flex items-baseline justify-between gap-3">
            <span id="{{ $labelId }}" class="text-[15px] leading-snug text-foreground">{{ $label }}</span>
            <span class="datum shrink-0 text-[15px] leading-snug text-muted-foreground">{{ $percent }}%</span>
        </div>
    @endif

    <div class="meter {{ $label !== null ? 'mt-2' : '' }}"
         role="progressbar"
         aria-valuenow="{{ $percent }}"
         aria-valuemin="0"
         aria-valuemax="100"
         @if ($labelId) aria-labelledby="{{ $labelId }}" @endif>

        {{-- w-full then scaled down, rather than a percentage width, so the
             browser lays the bar out once and only ever recomposites it. --}}
        <span class="block h-full w-full origin-left rounded-none bg-primary"
              style="transform: scaleX({{ $scale }}); transition: transform var(--dur-base) var(--ease-in-out)"></span>
    </div>
</div>
