{{--
    The bracket between one generation and the next: a short arm from the
    bird, a vertical stroke, and two arms out to the pair. Hairlines in the
    border colour; purely decorative, so hidden from assistive technology -
    the "Sire" and "Dam" labels carry the relationship in words.
--}}
<div {{ $attributes->merge(['class' => 'flex h-[60%] min-h-[96px] items-center self-center']) }} aria-hidden="true">
    <span class="h-px w-1/2 bg-border"></span>
    <span class="h-full w-px bg-border"></span>
    <span class="flex h-full w-1/2 flex-col justify-between">
        <span class="h-px w-full bg-border"></span>
        <span class="h-px w-full bg-border"></span>
    </span>
</div>
