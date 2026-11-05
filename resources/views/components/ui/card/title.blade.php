@props([
    /** The heading level. Cards under a page's h1 are h2; nested ones are h3. */
    'as' => 'h2',
])

<{{ $as }} {{ $attributes->class('text-sm leading-none font-semibold text-card-foreground') }}>{{ $slot }}</{{ $as }}>
