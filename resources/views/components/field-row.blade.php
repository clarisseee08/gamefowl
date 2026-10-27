@props(['label'])

{{--
    A label/value row: the label on the left at a fixed width, the value on
    the right, a hairline under each. On a phone the label sits above the
    value. Used for contact details, a bird's particulars and settings
    summaries - anywhere the page is stating facts rather than asking for them.
--}}
<div {{ $attributes->merge(['class' => 'grid gap-1 border-b border-border py-4 sm:grid-cols-[160px_1fr] sm:gap-6']) }}>
    <dt class="text-[14px] font-medium text-muted-foreground">{{ $label }}</dt>
    <dd class="min-w-0 text-[16px] text-foreground">{{ $slot }}</dd>
</div>
