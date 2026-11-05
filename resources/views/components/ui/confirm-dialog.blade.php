{{--
    THE CONFIRM DIALOG (EMS, Part B §5.20, §7.5) - one per page, mounted in
    the layout, opened by an `ems-confirm` event that carries its own words.
    See the emsConfirm component in app.js for the call shape.

    It opens the instant it is asked: the question and the consequence come
    with the event, so nothing waits on the server until the person has
    actually decided. The action then runs the screen's own Livewire methods
    and the dialog reads "Processing..." until they finish.

    Wording (§7.5): the title is a question - "Delete this health record?";
    the description says what is lost and that it cannot be undone; the
    action button is the verb alone - "Delete". Cancel sits on the left, the
    action on the right, both the same width, neither lifting on hover.

    Focus starts on the panel, so a stray Enter cannot confirm; Tab reaches
    Cancel first. Escape is Cancel, except while the action runs. The backdrop
    does not close it.
--}}
<div x-data="emsConfirm" x-on:ems-confirm.window="ask($event.detail)">
    <dialog x-ref="dialog" class="d-modal ems-modal" role="alertdialog"
            aria-labelledby="ems-confirm-title" aria-describedby="ems-confirm-description"
            x-on:cancel.prevent="cancel()">
        <div x-ref="panel" tabindex="-1"
             class="ems-modal-panel flex w-[calc(100vw-2rem)] max-w-[calc(100vw-2rem)] flex-col gap-0 overflow-hidden rounded-xl border border-border/60 bg-card p-0 text-card-foreground shadow-2xl outline-none sm:w-[448px]">
            <div class="p-5">
                <h2 id="ems-confirm-title" class="block text-base font-semibold text-foreground" x-text="title"></h2>
                <p id="ems-confirm-description" class="mt-1.5 block text-sm leading-snug text-foreground"
                   x-show="description" x-text="description"></p>
            </div>

            <div class="flex gap-2.5 border-t border-border/60 bg-background/60 p-3">
                <button type="button" x-on:click="cancel()" x-bind:disabled="processing"
                        class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground shadow-sm outline-none transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2 focus-visible:ring-offset-card disabled:pointer-events-none disabled:opacity-50 lg:h-7 lg:text-xs"
                        x-text="cancelLabel">Cancel</button>

                <button type="button" x-on:click="confirm()" x-bind:disabled="processing"
                        class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium shadow-sm outline-none transition-colors focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2 focus-visible:ring-offset-card disabled:pointer-events-none disabled:opacity-50 lg:h-7 lg:text-xs"
                        x-bind:class="{
                            'border-transparent bg-destructive text-destructive-foreground shadow-destructive/20 hover:bg-destructive/90': tone === 'danger',
                            'border-transparent bg-success text-success-content shadow-success/20 hover:bg-success/90': tone === 'success',
                            'border-transparent bg-warning text-warning-content shadow-warning/20 hover:bg-warning/90': tone === 'warning',
                            'border-primary/20 bg-primary text-primary-foreground hover:bg-primary/90': tone !== 'danger' && tone !== 'success' && tone !== 'warning',
                        }">
                    <span x-show="! processing" x-text="confirmLabel">Confirm</span>
                    <span x-show="processing" x-cloak class="flex items-center gap-2">
                        <span class="size-3 animate-spin rounded-full border-2 border-current/30 border-t-current" aria-hidden="true"></span>
                        Processing...
                    </span>
                </button>
            </div>
        </div>
    </dialog>
</div>
