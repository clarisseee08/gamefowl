{{--
    LIGHT / DARK. Two states, and two only.

    IT USED TO BE THREE - system, light, dark - on the argument that a
    two-state control cannot express "use whatever my phone is doing". That is
    true, and it is still true; what the three-state version actually produced
    was a button whose third click appeared to do nothing, because returning to
    "system" on a device already set to light looks exactly like light.

    What survives is the part that mattered: a visitor who has never touched
    this still follows their operating system, because the absence of a stored
    choice means the CSS media query is in charge. The first click is what pins
    it. After that the control is a straight flip.

    THE ICON SHOWS WHAT IS IN EFFECT, not what clicking will do. That reasoning
    is unchanged and it is the readable half: a control labelled with its own
    consequence is a coin toss for the reader, half of whom read a sun as "it is
    light now" and half as "make it light". The icon carries the state; the
    label carries the action.

    AND THE ICON IS SWITCHED BY CSS, not by script.

    The previous version used Alpine x-show with x-cloak. That works on a
    console page and does nothing at all on an auth page, because Alpine ships
    inside Livewire's bundle and the guest layout has no Livewire component on
    it - which is why this control could not be put on the sign-in screen. The
    rules in app.css key off exactly the same :root selectors the theme itself
    uses, so the right icon is correct on the very first paint, with no cloak,
    no flash, and no dependency.
--}}
<button type="button"
        data-theme-toggle
        title="Switch between light and dark"
        aria-label="Switch between light and dark"
        {{ $attributes->merge(['class' => 'btn-quiet h-11 min-h-0 w-11 px-0']) }}>

    {{-- Light is in effect. --}}
    <svg data-theme-icon="light" class="h-[18px] w-[18px]" fill="currentColor"
         viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM12 1.5a.75.75 0 0 1 .75.75V4.5a.75.75 0 0 1-1.5 0V2.25A.75.75 0 0 1 12 1.5ZM12 18.75a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V19.5a.75.75 0 0 1 .75-.75ZM22.5 12a.75.75 0 0 1-.75.75H19.5a.75.75 0 0 1 0-1.5h2.25a.75.75 0 0 1 .75.75ZM4.5 12a.75.75 0 0 1-.75.75H1.5a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 4.5 12Z"/>
    </svg>

    {{-- Dark is in effect. --}}
    <svg data-theme-icon="dark" class="h-[18px] w-[18px]" fill="currentColor"
         viewBox="0 0 24 24" aria-hidden="true">
        <path fill-rule="evenodd" clip-rule="evenodd"
              d="M9.528 1.718a.75.75 0 0 1 .162.819A8.97 8.97 0 0 0 9 6a9 9 0 0 0 9 9 8.97 8.97 0 0 0 3.463-.69.75.75 0 0 1 .981.98 10.503 10.503 0 0 1-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 0 1 .818.162Z"/>
    </svg>
</button>
