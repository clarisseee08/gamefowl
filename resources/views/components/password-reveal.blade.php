@props([
    /** The id of the password field this button shows and hides. */
    'for',
    /** The console's 28px fields from lg up; off on the sign-in pages, whose fields stay full size. */
    'compact' => true,
])

{{--
    SHOW / HIDE on a password field - the eye at its right edge.

    Placed inside the field's `relative` wrapper; the field takes pr-12
    (lg:pr-9) so a long password never runs under it. The click is handled
    once, for every field, by the [data-reveals] listener in app.js - vanilla,
    so it also works on the sign-in pages, which have no Alpine.

    Masked by default and masked again after every save: revealing is a
    decision, in a yard where someone else can see the screen. The label says
    what the button will do next, not what it shows now.
--}}
<button type="button"
        {{ $attributes->class(['password-reveal', 'lg:w-8 lg:rounded-r-md' => $compact]) }}
        data-reveals="{{ $for }}"
        aria-controls="{{ $for }}"
        aria-pressed="false"
        aria-label="Show password">
    <svg data-icon="show" @class(['size-5', 'lg:size-4' => $compact]) fill="none" stroke="currentColor"
         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
    </svg>
    <svg data-icon="hide" hidden @class(['size-5', 'lg:size-4' => $compact]) fill="none" stroke="currentColor"
         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243"/>
    </svg>
</button>
