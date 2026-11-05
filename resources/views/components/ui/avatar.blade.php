@props([
    /** Whose avatar. The initials come from it: first letters of the first and last names. */
    'name' => '',
    /** A photo URL. Without one the initials show - never a stock silhouette. */
    'src' => null,
    /** sm 32px (default) · header 28px · hero 64px */
    'size' => 'sm',
    /** muted, or primary for the account avatar in the header. */
    'tone' => 'muted',
])

{{--
    THE AVATAR (EMS, Part B §5.12): a round photo, or the person's initials
    on a muted disc. Decorative beside a visible name, so it is hidden from
    screen readers unless it stands alone - then pass an aria-label.
--}}
@php
    $words = preg_split('/\s+/', trim((string) $name)) ?: [];
    $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));

    $box = [
        'sm' => 'size-8 text-[11px]',
        'header' => 'size-7 text-[11px]',
        'hero' => 'size-16 text-lg ring-4 ring-muted',
    ][$size] ?? 'size-8 text-[11px]';

    $fill = $tone === 'primary' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground';
@endphp

<span {{ $attributes->class(['relative flex shrink-0 overflow-hidden rounded-full', $box]) }}
      @unless ($attributes->has('aria-label')) aria-hidden="true" @endunless>
    @if ($src)
        <img src="{{ $src }}" alt="" class="aspect-square size-full object-cover">
    @else
        <span class="{{ $fill }} flex size-full items-center justify-center rounded-full font-semibold">{{ $initials }}</span>
    @endif
</span>
