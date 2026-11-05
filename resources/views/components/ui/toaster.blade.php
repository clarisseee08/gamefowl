{{--
    THE TOASTER (EMS, Part B §8.1) - mounted once, in the layout. Behaviour is
    the emsToaster component in app.js.

    Status toasts are vivid gradient cards with white text: a white disc with
    the status glyph, a bold title, the message, a close button and a timer
    strip along the bottom that shrinks over four seconds and stops while the
    pointer is on it. A plain toast is a popover-coloured card.

    A redirect's flash message is written into the page here, hidden, so a
    test can read it from the HTML; the script turns it into a toast.
--}}
<div x-data="emsToaster" x-on:toast.window="push($event.detail)" aria-live="polite"
     class="pointer-events-none fixed top-4 right-4 left-4 z-[100] flex flex-col gap-[14px] sm:top-8 sm:right-8 sm:left-auto sm:w-[356px]">
    @if (session('success'))
        <span hidden data-flash="success">{{ session('success') }}</span>
    @endif
    @if (session('error'))
        <span hidden data-flash="error">{{ session('error') }}</span>
    @endif

    <template x-for="toast in toasts" :key="toast.id">
        <div x-bind:role="toast.kind === 'error' ? 'alert' : 'status'"
             x-on:mouseenter="pause(toast)" x-on:mouseleave="resume(toast)"
             x-on:focusin="pause(toast)" x-on:focusout="resume(toast)"
             class="ems-toast pointer-events-auto relative flex w-full items-center gap-2 overflow-hidden pt-[18px] pr-11 pb-5 pl-6"
             x-bind:class="{
                 'rounded-2xl border border-white/15 text-white ems-toast-success': toast.kind === 'success',
                 'rounded-2xl border border-white/15 text-white ems-toast-info': toast.kind === 'info',
                 'rounded-2xl border border-white/15 text-white ems-toast-warning': toast.kind === 'warning',
                 'rounded-2xl border border-white/15 text-white ems-toast-error': toast.kind === 'error',
                 'rounded-lg border border-border bg-popover text-popover-foreground shadow-xl': ! isStatus(toast),
             }">
            <span x-show="isStatus(toast)"
                  class="mt-2.5 mr-3.5 flex size-[1.375rem] shrink-0 items-center justify-center self-start rounded-full bg-white ring-[10px] ring-white/25">
                <x-ui.icon name="check" :stroke="3" class="ems-toast-glyph size-3.5" x-show="toast.kind === 'success'" />
                <x-ui.icon name="info" :stroke="3" class="ems-toast-glyph size-3.5" x-show="toast.kind === 'info'" />
                <x-ui.icon name="triangle-alert" :stroke="3" class="ems-toast-glyph size-3.5" x-show="toast.kind === 'warning'" />
                <x-ui.icon name="x" :stroke="3" class="ems-toast-glyph size-3.5" x-show="toast.kind === 'error'" />
            </span>
            <x-ui.icon name="loader-circle" class="size-3 shrink-0 animate-spin text-muted-foreground" x-show="toast.kind === 'loading'" />

            <div class="flex min-w-0 flex-col gap-0.5">
                <p class="text-sm leading-snug font-bold tracking-tight"
                   x-bind:class="isStatus(toast) ? 'text-white' : 'text-foreground'" x-text="toast.title"></p>
                <p class="text-xs leading-snug" x-show="toast.description"
                   x-bind:class="isStatus(toast) ? 'text-white' : 'text-muted-foreground'" x-text="toast.description"></p>
            </div>

            <button type="button" aria-label="Close" x-on:click="dismiss(toast.id)"
                    class="absolute top-3.5 right-3.5 opacity-80 transition-opacity hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2"
                    x-bind:class="isStatus(toast) ? 'text-white focus-visible:outline-white' : 'text-muted-foreground focus-visible:outline-ring'">
                <x-ui.icon name="x" class="size-5" />
            </button>

            <template x-if="isStatus(toast) && toast.duration">
                <span>
                    <span class="absolute bottom-0 left-0 h-[4px] w-full bg-black/30"></span>
                    <span class="ems-toast-bar absolute bottom-0 left-0 h-[4px] w-full origin-left rounded-r-full"
                          x-bind:style="`animation: ems-toast-progress ${toast.duration}ms linear forwards; animation-play-state: ${toast.paused ? 'paused' : 'running'}`"></span>
                </span>
            </template>
        </div>
    </template>
</div>
