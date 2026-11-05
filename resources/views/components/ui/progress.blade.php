@props([
    'value' => 0,
    'max' => 100,
    /** primary, or warning / danger when the bar reports a limit being reached. */
    'tone' => 'primary',
    /** What the bar measures, for screen readers: "Pedigree completeness". */
    'label' => null,
])

{{--
    A PROGRESS BAR (EMS, Part B §5.23): an 8px rounded track at 20% of the
    bar's colour. A native <progress>, so its value is announced with no extra
    ARIA; the value is clamped to the range before it is drawn.
--}}
@php
    $clamped = max(0, min((float) $value, (float) $max));
    $toneClass = ['warning' => 'd-progress-warning', 'danger' => 'd-progress-error'][$tone] ?? 'd-progress-primary';
@endphp

<progress value="{{ $clamped }}" max="{{ $max }}" @if ($label) aria-label="{{ $label }}" @endif
          {{ $attributes->class(['d-progress h-2 w-full rounded-full', $toneClass]) }}></progress>
