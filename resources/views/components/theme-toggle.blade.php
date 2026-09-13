{{--
    LIGHT / DARK, with "follow the system" as a real third state.

    A two-state toggle cannot express "use whatever my phone is doing", which
    is what most people actually want and what the page does before anyone
    touches this. So the control cycles: system, light, dark - and clears the
    stored choice when it returns to system, which hands control back to the
    CSS media query rather than freezing today's OS setting in place.

    THE ICON SHOWS WHAT IS IN EFFECT, not what clicking will do. A control
    labelled with its own consequence is a coin toss for the reader: half of
    them read a sun as "it is light now" and half as "make it light". Showing
    state and putting the action in the title is the readable half.

    No double quote may appear inside x-data - it is an HTML attribute, so the
    first one ends it and Alpine silently receives truncated JavaScript.
--}}
<div x-data="{
        choice: 'system',
        init() {
            try { this.choice = localStorage.getItem('gfms-theme') || 'system'; } catch (e) {}
        },
        get effective() {
            if (this.choice !== 'system') return this.choice;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        },
        cycle() {
            this.choice = this.choice === 'system' ? 'light' : (this.choice === 'light' ? 'dark' : 'system');
            try {
                if (this.choice === 'system') {
                    localStorage.removeItem('gfms-theme');
                    document.documentElement.removeAttribute('data-theme');
                } else {
                    localStorage.setItem('gfms-theme', this.choice);
                    document.documentElement.setAttribute('data-theme', this.choice);
                }
            } catch (e) {}
        },
        get title() {
            return this.choice === 'system'
                ? 'Following your device. Switch to light'
                : (this.choice === 'light' ? 'Light. Switch to dark' : 'Dark. Follow your device');
        },
     }"
     class="inline-flex">
    <button type="button"
            x-on:click="cycle()"
            x-bind:title="title"
            x-bind:aria-label="title"
            {{ $attributes->merge(['class' => 'btn-quiet h-11 min-h-0 w-11 px-0']) }}>

        {{-- Sun: in effect and following the device. --}}
        <svg x-show="choice === 'system' && effective === 'light'" x-cloak
             class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.6"
             viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
        </svg>

        {{-- Moon: in effect and following the device. --}}
        <svg x-show="choice === 'system' && effective === 'dark'" x-cloak
             class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.6"
             viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
        </svg>

        {{-- Filled sun: light, chosen explicitly. --}}
        <svg x-show="choice === 'light'" x-cloak
             class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM12 1.5a.75.75 0 0 1 .75.75V4.5a.75.75 0 0 1-1.5 0V2.25A.75.75 0 0 1 12 1.5ZM12 18.75a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V19.5a.75.75 0 0 1 .75-.75ZM22.5 12a.75.75 0 0 1-.75.75H19.5a.75.75 0 0 1 0-1.5h2.25a.75.75 0 0 1 .75.75ZM4.5 12a.75.75 0 0 1-.75.75H1.5a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 4.5 12Z"/>
        </svg>

        {{-- Filled moon: dark, chosen explicitly. --}}
        <svg x-show="choice === 'dark'" x-cloak
             class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M9.528 1.718a.75.75 0 0 1 .162.819A8.97 8.97 0 0 0 9 6a9 9 0 0 0 9 9 8.97 8.97 0 0 0 3.463-.69.75.75 0 0 1 .981.98 10.503 10.503 0 0 1-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 0 1 .818.162Z"/>
        </svg>
    </button>
</div>
