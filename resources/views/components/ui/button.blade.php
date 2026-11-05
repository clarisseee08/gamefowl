@props([
    /** default · destructive · outline · secondary · ghost · link · success · outline-destructive · outline-success */
    'variant' => 'default',
    /** app (the screen default) · app-circle · default · sm · lg · icon · icon-sm · icon-lg */
    'size' => 'app',
    /** Server-side busy state. Livewire's own data-loading shows the same spinner with no prop. */
    'loading' => false,
    /** Renders a link that looks like the button. */
    'href' => null,
    'type' => 'button',
])

{{--
    THE BUTTON (EMS design system, Part B §5.1).

    Two layers, as in EMS. The APP size is what screens use - page actions,
    toolbars, dialog footers, forms: compact, with the primary button's lift
    and inset highlight. The BASE sizes are for icon buttons, pagination and
    the roomy surfaces (sign-in, the public site).

    Every size is two-step, by the owner's decision: 44px below lg, where the
    console is a phone in a coop yard, and EMS's compact size from lg up.

    BUSY. A Livewire action in flight puts data-loading on the button that
    started it, so the spinner and the dimming below need no wiring at the
    call site. `loading` is for state the server already knows.

    Colours are tokens. EMS's filled secondary is its brand pink and its
    success is a stock green; here secondary is the muted fill and success is
    the success token, because colour means bloodline and status, nothing else.
--}}
@php
    $base = "d-btn group/btn inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md border text-sm font-medium transition-all outline-none disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 data-loading:pointer-events-none data-loading:opacity-80";

    $variants = [
        'default' => 'd-btn-primary border-transparent bg-primary text-primary-foreground hover:bg-primary/90',
        'destructive' => 'd-btn-error border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/90 focus-visible:ring-destructive/20',
        'outline' => 'border-border bg-background text-foreground shadow-xs hover:bg-muted hover:text-foreground',
        'secondary' => 'border-transparent bg-muted text-foreground hover:bg-muted/80',
        'ghost' => 'd-btn-ghost border-transparent bg-transparent text-foreground shadow-none hover:bg-muted hover:text-foreground',
        'link' => 'd-btn-link border-transparent bg-transparent text-primary no-underline shadow-none underline-offset-4',
        'success' => 'border-transparent bg-success text-success-content hover:bg-success/90',
        'outline-destructive' => 'border-destructive bg-transparent text-destructive hover:bg-destructive/10 hover:text-destructive',
        'outline-success' => 'border-success bg-transparent text-success hover:bg-success/10 hover:text-success',
    ];

    $sizes = [
        'app' => "h-11 rounded-lg px-4 text-sm lg:h-7 lg:px-3 lg:py-1 lg:text-xs lg:[&_svg:not([class*='size-'])]:size-3.5",
        'app-circle' => "size-11 rounded-full p-0 lg:size-9 lg:text-xs lg:[&_svg:not([class*='size-'])]:size-3.5",
        'default' => 'h-11 px-4 py-2 has-[>svg]:px-3 lg:h-9',
        'sm' => 'h-11 gap-1.5 px-3 has-[>svg]:px-2.5 lg:h-8',
        'lg' => 'h-11 px-6 has-[>svg]:px-4 lg:h-10',
        'icon' => 'size-11 p-0 lg:size-9',
        'icon-sm' => 'size-11 p-0 lg:size-8',
        'icon-lg' => 'size-11 p-0 lg:size-10',
    ];

    $isApp = in_array($size, ['app', 'app-circle'], true);

    $appLayer = match (true) {
        ! $isApp => '',
        in_array($variant, ['default', 'destructive'], true) => 'border-primary/20 shadow-[0_2px_4px_rgba(0,0,0,0.08),inset_0_1px_0_rgba(255,255,255,0.2)] hover:-translate-y-px hover:scale-[1.02] hover:shadow-[0_3px_6px_rgba(0,0,0,0.12),inset_0_1px_0_rgba(255,255,255,0.3)] active:translate-y-0 active:scale-[0.98] active:shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]',
        $variant === 'outline' => 'border-border shadow-sm dark:border-muted dark:bg-muted',
        in_array($variant, ['secondary', 'success'], true) => 'border-primary/20',
        default => 'shadow-none',
    };

    $classes = [
        $base,
        $variants[$variant] ?? $variants['default'],
        $sizes[$size] ?? $sizes['app'],
        $appLayer,
        $isApp ? 'relative animate-ems-fade-in overflow-hidden duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 focus:ring-offset-background disabled:cursor-not-allowed disabled:transform-none' : '',
        $loading ? 'pointer-events-none opacity-80' : '',
    ];

    $attrs = $attributes->class($classes);

    if ($loading) {
        $attrs = $attrs->merge(['disabled' => true, 'aria-busy' => 'true']);
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attrs }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attrs }}>
        {{-- EMS's 12px spinner: a faint ring and a solid quarter arc. --}}
        <svg @class(['size-3 animate-spin', 'hidden group-data-loading/btn:block' => ! $loading])
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        {{ $slot }}
    </button>
@endif
