import './bootstrap';

/**
 * A drag-and-drop wrapper for a file input.
 *
 * Registered once and used by every upload zone, because the interesting part
 * is not the drag styling - it is the two lines that hand the dropped files to
 * Livewire, and those are easy to get subtly wrong in two places.
 *
 * HOW IT REACHES LIVEWIRE: wire:model on a file input is driven by the input's
 * `change` event. A drop does not fire one, so the FileList is assigned to the
 * input and `change` is dispatched by hand. Assigning `input.files` directly is
 * what makes this work at all - it is only possible because a DataTransfer's
 * FileList is the same type the input expects.
 *
 * Alpine ships inside Livewire's bundle, so this registers on alpine:init
 * rather than importing Alpine separately - importing a second copy is the
 * classic way to end up with two Alpine instances fighting over the DOM.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('fileDropzone', (options = {}) => ({
        dragging: false,

        /** Set by the caller when the zone should refuse input, e.g. a full gallery. */
        disabled: options.disabled ?? false,

        onDragOver() {
            if (this.disabled) {
                return;
            }

            this.dragging = true;
        },

        /**
         * dragleave fires when the pointer crosses onto a CHILD element too, so
         * a naive handler flickers the highlight off every time the cursor
         * passes over the icon or the label text inside the zone.
         */
        onDragLeave(event) {
            if (! this.$el.contains(event.relatedTarget)) {
                this.dragging = false;
            }
        },

        onDrop(event) {
            this.dragging = false;

            if (this.disabled) {
                return;
            }

            const input = this.$refs.input;
            const dropped = event.dataTransfer?.files;

            if (! input || ! dropped || dropped.length === 0) {
                return;
            }

            // Respect the input's own contract rather than re-deciding it here:
            // a single-file input takes the first file, and the accept list is
            // still enforced server-side by the Form Request either way.
            if (! input.multiple && dropped.length > 1) {
                const one = new DataTransfer();
                one.items.add(dropped[0]);
                input.files = one.files;
            } else {
                input.files = dropped;
            }

            // The event wire:model is actually listening for.
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },
    }));

    /**
     * The EMS dropdown menu (Part B §5.16) - row actions, the account menu,
     * overflow menus. <x-ui.dropdown-menu> renders the markup; this is the
     * behaviour, shared so every menu in the console answers the keyboard the
     * same way.
     *
     * Focus moves onto the items themselves, so "highlighted" is simply
     * "focused": the arrow keys move it (no wrap), Home and End jump, a typed
     * letter jumps to the next item starting with it, Escape closes and hands
     * focus back to the trigger, Tab closes and lets focus go on.
     *
     * The panel is positioned by x-anchor.fixed, so a menu inside a scrolling
     * table is not clipped by it - the reason EMS renders menus in a portal.
     */
    window.Alpine.data('emsMenu', () => ({
        open: false,

        items() {
            return [...this.$refs.panel.querySelectorAll('[role^="menuitem"]:not([aria-disabled="true"])')];
        },

        show(which = 'first') {
            this.open = true;
            this.$nextTick(() => this.edge(which));
        },

        close(refocus = true) {
            if (! this.open) {
                return;
            }

            this.open = false;

            if (refocus) {
                this.$refs.trigger.focus();
            }
        },

        toggle() {
            this.open ? this.close() : this.show();
        },

        edge(which) {
            const items = this.items();
            (which === 'last' ? items[items.length - 1] : items[0])?.focus();
        },

        move(step) {
            const items = this.items();
            const at = items.indexOf(document.activeElement);
            items[Math.min(items.length - 1, Math.max(0, at + step))]?.focus();
        },

        typeahead(event) {
            if (event.key.length !== 1 || ! /\S/.test(event.key) || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            const items = this.items();
            const at = items.indexOf(document.activeElement);
            const order = [...items.slice(at + 1), ...items.slice(0, at + 1)];

            order.find((item) => item.textContent.trim().toLowerCase().startsWith(event.key.toLowerCase()))?.focus();
        },
    }));

    /**
     * The EMS dialogs (Part B §5.20): <x-ui.form-dialog>, <x-ui.sheet> and the
     * info dialog. A native <dialog> under showModal() gives the focus trap,
     * the inert page behind it, Escape and the top layer; this keeps the
     * element in step with `open`, which is either a plain boolean or a
     * Livewire property entangled through $wire.entangle().
     *
     * The <dialog> carries wire:ignore.self, or a Livewire re-render would
     * strip the `open` attribute showModal() set and close it mid-form.
     *
     * Focus goes to the panel, not the first field (EMS suppresses autofocus);
     * Tab enters the form. Closing returns focus to whatever opened it.
     *
     * A form with unsaved input asks before it is closed - Escape, the X and
     * Cancel all go through requestClose(). Nothing closes while it saves.
     */
    window.Alpine.data('emsDialog', (options = {}) => ({
        open: false,
        dirty: false,
        maximized: false,
        returnFocus: null,

        init() {
            // A Livewire property holds the dialog open: follow it both ways.
            // Watched explicitly - an entangle handed through this factory
            // never linked, so a dialog opened by the server stayed shut.
            if (options.model && this.$wire) {
                this.open = Boolean(this.$wire.get(options.model));
                this.$wire.$watch(options.model, (value) => { this.open = Boolean(value); });
            }

            this.$watch('open', (value) => {
                this.sync(value);

                if (options.model && this.$wire && ! value && this.$wire.get(options.model)) {
                    this.$wire.set(options.model, false);
                }
            });
            this.$nextTick(() => this.sync(this.open));
        },

        sync(value) {
            const dialog = this.$refs.dialog;

            if (value && ! dialog.open) {
                this.returnFocus = document.activeElement;
                this.dirty = false;
                this.maximized = false;
                dialog.showModal();
                this.$nextTick(() => this.$refs.panel?.focus());
            } else if (! value && dialog.open) {
                dialog.close();
                this.returnFocus?.focus?.();
            }
        },

        busy() {
            return this.$refs.dialog.querySelector('[data-loading]') !== null;
        },

        requestClose() {
            if (this.busy()) {
                return;
            }

            if (options.guardDirty && this.dirty) {
                this.$dispatch('ems-confirm', {
                    title: 'Discard unsaved changes?',
                    description: 'You have details that have not been saved. If you close now they will be lost.',
                    cancelLabel: 'Keep editing',
                    confirmLabel: 'Discard',
                    tone: 'danger',
                    run: () => { this.dirty = false; this.open = false; },
                });

                return;
            }

            this.open = false;
        },

        backdropClick(event) {
            if (options.closeOnBackdrop && event.target === this.$refs.dialog) {
                this.requestClose();
            }
        },
    }));

    /**
     * The EMS confirm dialog (Part B §5.20, §7.5) - one per page, in the
     * layout. It opens the moment it is asked, with the words it was given,
     * instead of waiting for a server round trip to render them:
     *
     *   x-on:click="$dispatch('ems-confirm', {
     *       title: 'Delete this health record?',
     *       description: 'It is removed from Tandang\'s history.',
     *       confirmLabel: 'Delete', tone: 'danger',
     *       run: () => $wire.confirmDelete(5).then(() => $wire.delete()),
     *   })"
     *
     * `run` is the component's own Livewire actions, unchanged; the dialog
     * shows "Processing..." until they finish and then closes. Focus starts on
     * the panel, so Enter cannot confirm by accident; Escape is Cancel.
     */
    window.Alpine.data('emsConfirm', () => ({
        open: false,
        processing: false,
        title: '',
        description: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        tone: 'default',
        run: null,
        returnFocus: null,

        ask(detail = {}) {
            Object.assign(this, {
                title: '',
                description: '',
                confirmLabel: 'Confirm',
                cancelLabel: 'Cancel',
                tone: 'default',
                run: null,
            }, detail);

            this.returnFocus = document.activeElement;
            this.open = true;
            this.$refs.dialog.showModal();
            this.$nextTick(() => this.$refs.panel.focus());
        },

        async confirm() {
            if (this.processing) {
                return;
            }

            this.processing = true;

            try {
                await this.run?.();
                this.finish();
            } finally {
                this.processing = false;
            }
        },

        cancel() {
            if (! this.processing) {
                this.finish();
            }
        },

        finish() {
            this.open = false;
            this.$refs.dialog.close();
            this.returnFocus?.focus?.();
        },
    }));

    /**
     * The EMS toaster (Part B §8.1): top right, four seconds, a close button on
     * every toast, the timer paused while the pointer or focus is on it, three
     * at most with the newest on top.
     *
     * It takes the `toast` event every screen already sends -
     * $this->dispatch('toast', message: 'Saved.', tone: 'ok') - where tone ok
     * and bad are the old names for success and error. A single message is
     * lifted under a heading, as EMS does: "Success" over "Health record
     * saved." A toast that brings its own title keeps it.
     */
    window.Alpine.data('emsToaster', () => ({
        toasts: [],
        sequence: 0,

        init() {
            // A redirect's flash arrives in the page itself, in hidden markup a
            // test can read; it becomes a toast here and the markup goes.
            this.$el.querySelectorAll('[data-flash]').forEach((flash) => {
                this.push({ message: flash.textContent.trim(), tone: flash.dataset.flash });
                flash.remove();
            });
        },

        push(detail = {}) {
            const payload = Array.isArray(detail) ? (detail[0] ?? {}) : detail;
            const kind = { ok: 'success', bad: 'error' }[payload.tone] ?? payload.tone ?? 'success';
            const heading = { success: 'Success', error: 'Error', warning: 'Warning', info: 'Info' }[kind];
            const message = payload.message ?? payload.title ?? '';

            if (! message) {
                return;
            }

            const lifted = heading && ! payload.description && ! payload.title;
            const toast = {
                id: ++this.sequence,
                kind,
                title: lifted ? heading : (payload.title ?? message),
                description: lifted ? message : (payload.description ?? (payload.title ? message : '')),
                duration: kind === 'loading' ? 0 : (payload.duration ?? 4000),
                paused: false,
                remaining: 0,
                startedAt: 0,
                timer: null,
            };

            this.toasts = [toast, ...this.toasts].slice(0, 3);
            this.start(toast);
        },

        start(toast) {
            if (! toast.duration) {
                return;
            }

            toast.remaining = toast.remaining || toast.duration;
            toast.startedAt = Date.now();
            toast.timer = setTimeout(() => this.dismiss(toast.id), toast.remaining);
        },

        pause(toast) {
            if (! toast.timer) {
                return;
            }

            clearTimeout(toast.timer);
            toast.timer = null;
            toast.remaining -= Date.now() - toast.startedAt;
            toast.paused = true;
        },

        resume(toast) {
            if (! toast.paused) {
                return;
            }

            toast.paused = false;
            this.start(toast);
        },

        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },

        isStatus(toast) {
            return ['success', 'error', 'warning', 'info'].includes(toast.kind);
        },
    }));

    /**
     * The EMS popover (Part B §5.17): a panel of its own content anchored to a
     * trigger. Focus moves into it on open; Escape or a click outside closes
     * it and returns focus to the trigger.
     */
    window.Alpine.data('emsPopover', () => ({
        open: false,

        show() {
            this.open = true;
            this.$nextTick(() => {
                const target = this.$refs.panel.querySelector('input, select, textarea, button, a[href], [tabindex]:not([tabindex="-1"])') ?? this.$refs.panel;
                target.focus();
            });
        },

        close(refocus = true) {
            if (! this.open) {
                return;
            }

            this.open = false;

            if (refocus) {
                this.$refs.trigger.focus();
            }
        },

        toggle() {
            this.open ? this.close() : this.show();
        },
    }));
});


/**
 * Show / hide on a password field.
 *
 * Vanilla for the same reason the leg band is: Alpine ships inside Livewire's
 * bundle and the auth pages carry no Livewire component, so x-* attributes are
 * dead there. Delegated from the document so it also covers a field rendered
 * after load.
 *
 * The label states what the button will DO next. A toggle labelled with its
 * current state is ambiguous the moment anyone stops to think about it.
 */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-reveals]');

    if (! button) {
        return;
    }

    const field = document.getElementById(button.dataset.reveals);

    if (! field) {
        return;
    }

    const reveal = field.type === 'password';

    field.type = reveal ? 'text' : 'password';
    button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
    button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    /*
     * toggleAttribute, NOT `.hidden = x`.
     *
     * `hidden` is an IDL attribute of HTMLElement, and these are SVGElements.
     * `svg.hidden = true` therefore sets a plain JS property on the object,
     * changes no content attribute, matches no selector, and throws nothing -
     * so the type flipped, the label updated, and the icon silently did not.
     */
    button.querySelector('[data-icon="show"]').toggleAttribute('hidden', reveal);
    button.querySelector('[data-icon="hide"]').toggleAttribute('hidden', ! reveal);

    // Toggling type moves focus to the button; hand it back so the next
    // keystroke lands in the field rather than nowhere.
    field.focus({ preventScroll: true });
});

/**
 * Light / dark, two states.
 *
 * The stored choice is what pins it; with nothing stored the CSS media query
 * is in charge, so a visitor who never touches this still follows their
 * device. head-meta stamps the attribute before first paint, and app.css picks
 * the icon off the same :root selectors, so this only has to flip the state.
 *
 * Vanilla, because the guest layout has no Livewire component on it and
 * therefore no Alpine - which is why the toggle could not previously be put on
 * the sign-in screen at all.
 */
document.addEventListener('click', (event) => {
    if (! event.target.closest('[data-theme-toggle]')) {
        return;
    }

    const root = document.documentElement;
    const chosen = root.getAttribute('data-theme');

    const isDark = chosen
        ? chosen === 'dark'
        : window.matchMedia('(prefers-color-scheme: dark)').matches;

    const next = isDark ? 'light' : 'dark';

    root.setAttribute('data-theme', next);

    try {
        localStorage.setItem('gfms-theme', next);
    } catch (e) {
        // Private windows and some embedded webviews throw outright. The
        // attribute is already set, so the flip still works for this page.
    }
});

/**
 * Caps Lock, announced while typing a password.
 *
 * A masked field is the one place a stuck Caps Lock costs a real attempt, and
 * on the console that attempt counts against a rate limiter.
 *
 * getModifierState only exists on keyboard events, so the state is genuinely
 * unknowable until the first key - which is why this cannot be shown on focus
 * alone and should never pretend otherwise. It clears on blur rather than
 * lingering over a field nobody is typing in.
 */
document.querySelectorAll('[data-capslock-for]').forEach((warning) => {
    const field = document.getElementById(warning.dataset.capslockFor);

    if (! field) {
        return;
    }

    const sync = (event) => {
        if (typeof event.getModifierState !== 'function') {
            return;
        }

        warning.toggleAttribute('hidden', ! event.getModifierState('CapsLock'));
    };

    field.addEventListener('keydown', sync);
    field.addEventListener('keyup', sync);
    field.addEventListener('blur', () => warning.toggleAttribute('hidden', true));
});

/**
 * Re-stamp the chosen theme after every Livewire navigation.
 *
 * wire:navigate copies the fetched document's <html> attributes over the live
 * ones, and the server cannot know which theme THIS browser chose - so
 * data-theme is wiped on every soft navigation and the page snaps back to
 * whatever prefers-color-scheme says. Measured, not guessed: click the toggle
 * to light on /mortality and navigate, and the attribute is null on arrival
 * while localStorage still says light.
 *
 * The inline script in head-meta still runs first on a cold load, because that
 * one has to beat first paint. This is the soft-navigation half of the same
 * job, and livewire:navigated fires on the initial load too, so the two agree.
 */
const applyStoredTheme = () => {
    try {
        const choice = localStorage.getItem('gfms-theme');

        if (choice === 'light' || choice === 'dark') {
            document.documentElement.setAttribute('data-theme', choice);
        }
    } catch (e) {
        // Private windows and some embedded webviews throw on localStorage.
        // The media query is a correct fallback, so there is nothing to do.
    }
};

document.addEventListener('livewire:navigated', applyStoredTheme);

/**
 * The public site's entrance: each [data-reveal] rises and fades in once, as it
 * enters the viewport.
 *
 * ARMED BY TAGGING <html>, never by hiding things in CSS up front. The hidden
 * starting state only exists under .js-reveal, which this script adds - so if
 * the script never runs, nothing is hidden and the page is simply still.
 *
 * Re-armed on every soft navigation, because wire:navigate swaps the page body
 * without reloading this script, and the new page's elements would otherwise
 * sit at opacity 0 forever.
 */
const armReveals = () => {
    const targets = document.querySelectorAll('[data-reveal]:not(.is-in)');

    if (! ('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) {
        targets.forEach((el) => el.classList.add('is-in'));
        return;
    }

    document.documentElement.classList.add('js-reveal');

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    targets.forEach((el) => io.observe(el));
};

document.addEventListener('livewire:navigated', armReveals);

// The plain pages (About, Visit, the legal pages) carry no Livewire component,
// so livewire:navigated never fires there. Arm on load as well; arming twice
// is harmless, because armed elements are skipped by the :not(.is-in) query.
document.addEventListener('DOMContentLoaded', armReveals);

// A Livewire update (a tab switch, a filter) morphs new [data-reveal] elements
// into the page without a navigation. Without this they would sit at opacity 0
// until the next page load. One frame's debounce folds a morph's many
// mutations into a single arming pass.
let revealFrame = null;
new MutationObserver((mutations) => {
    if (revealFrame || ! mutations.some((m) => m.addedNodes.length)) {
        return;
    }
    revealFrame = requestAnimationFrame(() => {
        revealFrame = null;
        armReveals();
    });
}).observe(document.documentElement, { childList: true, subtree: true });

/**
 * Keep revealed cards revealed through Livewire updates.
 *
 * A search, sort or page change on the catalogue redraws the grid from the
 * server's HTML, which never carries `is-in`. Without this, every card that had
 * faded in was reset to opacity 0 and nothing re-armed it - no node was added -
 * so the page read "14 birds found" above an empty grid.
 *
 * Cards that are new to the grid show at once instead of fading: the entrance is
 * for a page's first paint, and replaying it on every keystroke is noise.
 */
const keepRevealsThroughUpdates = () => {
    window.Livewire.hook('morph.updating', ({ el, toEl }) => {
        if (el.classList?.contains('is-in') && toEl.classList) {
            toEl.classList.add('is-in');
        }
    });

    window.Livewire.hook('morph.added', ({ el }) => {
        if (el.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        if (el.matches('[data-reveal]')) {
            el.classList.add('is-in');
        }

        el.querySelectorAll('[data-reveal]').forEach((card) => card.classList.add('is-in'));
    });
};

if (window.Livewire) {
    keepRevealsThroughUpdates();
} else {
    document.addEventListener('livewire:init', keepRevealsThroughUpdates);
}

/**
 * After a form fails validation, focus its first invalid field (EMS, Part B
 * §8.5). Remembered per form in a WeakSet rather than a data attribute: the
 * server's HTML never carries the attribute, so a Livewire update would strip
 * it before it could be read. aria-invalid is what every field wrapper sets.
 */
const formsAwaitingValidation = new WeakSet();

document.addEventListener('submit', (event) => {
    if (event.target instanceof HTMLFormElement && event.target.hasAttribute('wire:submit')) {
        formsAwaitingValidation.add(event.target);
    }
}, true);

const focusFirstInvalidField = () => {
    window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => requestAnimationFrame(() => requestAnimationFrame(() => {
            document.querySelectorAll('form[wire\\:submit]').forEach((form) => {
                if (! formsAwaitingValidation.has(form)) {
                    return;
                }

                formsAwaitingValidation.delete(form);
                form.querySelector('[aria-invalid="true"]')?.focus();
            });
        })));
    });
};

/**
 * Indeterminate is a DOM property with no attribute, so the server cannot
 * set it. <x-ui.checkbox :indeterminate="true"> renders data-indeterminate;
 * this applies it on load and after every Livewire update.
 */
const applyIndeterminate = (root = document) => {
    root.querySelectorAll('input[type="checkbox"]').forEach((box) => {
        box.indeterminate = box.dataset.indeterminate === 'true';
    });
};

document.addEventListener('DOMContentLoaded', () => applyIndeterminate());
document.addEventListener('livewire:navigated', () => applyIndeterminate());

const watchLivewireUpdates = () => {
    focusFirstInvalidField();
    window.Livewire.hook('morph.updated', ({ el }) => {
        if (el instanceof HTMLInputElement && el.type === 'checkbox') {
            el.indeterminate = el.dataset.indeterminate === 'true';
        }
    });
};

if (window.Livewire) {
    watchLivewireUpdates();
} else {
    document.addEventListener('livewire:init', watchLivewireUpdates);
}

/**
 * PAGE TRANSITIONS. On a soft navigation the header and the sidebar are
 * re-rendered identically and so appear to stay; only <main> fades up into
 * place. The class is removed when the animation ends so it can run again.
 */
document.addEventListener('livewire:navigated', () => {
    const main = document.querySelector('main');
    if (! main || matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }
    main.classList.remove('page-enter');
    void main.offsetWidth;
    main.classList.add('page-enter');
    main.addEventListener('animationend', () => main.classList.remove('page-enter'), { once: true });
});
