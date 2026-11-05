@props([
    /** How many label-and-field pairs to draw. */
    'fields' => 5,
    /**
     * Marks this as "the form has not arrived yet", which hides the dialog's
     * title behind a bar (form-dialog). Off for a skeleton that is always in
     * the dialog and only shown on demand - or the title would never appear.
     */
    'marker' => true,
])

{{--
    THE SHAPE OF A FORM, while it loads (EMS, Part B §8.2). A dialog opens the
    moment its button is pressed and shows this until the form arrives from
    the server, so the click is answered at once and nothing jumps when the
    fields land. Label bars are short, field bars full width, as the real
    form is laid out.
--}}
<div @if ($marker) data-form-skeleton @endif {{ $attributes->class('space-y-5 px-5 pt-1 pb-5') }} aria-busy="true">
    <span class="sr-only" role="status">Loading the form&hellip;</span>
    @for ($i = 0; $i < $fields; $i++)
        <div class="space-y-2">
            <x-ui.skeleton :class="'h-3 '.['w-24', 'w-32', 'w-20', 'w-28', 'w-16'][$i % 5]" />
            <x-ui.skeleton class="h-11 w-full lg:h-7" />
        </div>
    @endfor
    <div class="flex justify-end gap-2 pt-2">
        <x-ui.skeleton class="h-11 w-20 lg:h-7" />
        <x-ui.skeleton class="h-11 w-28 lg:h-7" />
    </div>
</div>
