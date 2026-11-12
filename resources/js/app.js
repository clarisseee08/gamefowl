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
     * The EMS select (Part B §5.5): <x-ui.select>, <x-form-select> and
     * <x-filter-select>. A native select's open list is drawn by the operating
     * system, which no stylesheet reaches, so the list here is an EMS dropdown
     * panel instead.
     *
     * A hidden native <select> stays as the value store. It carries wire:model
     * and the <option> children, so Livewire binds exactly as before. Choosing
     * writes the select and fires `change` (what wire:model listens for) and
     * `blur` (for wire:model.blur, since nobody can blur a hidden control).
     *
     * Focus never leaves the trigger: it is a combobox, and the highlighted
     * option is announced through aria-activedescendant.
     */
    window.Alpine.data('emsSelect', () => ({
        open: false,
        options: [],
        active: -1,
        selected: -1,
        label: '',
        empty: false,
        width: 0,
        typed: '',
        typedAt: 0,

        init() {
            this.sync();

            // wire:model binds the native select after this component starts,
            // so the first read can see the browser's default option. Read
            // again once the binding exists, and after every morph.
            this.$nextTick(() => this.sync());
            new MutationObserver(() => this.$nextTick(() => this.sync()))
                .observe(this.$refs.native, { childList: true, subtree: true, attributes: true, characterData: true });
        },

        anchor() {
            return this.$refs.anchor ?? this.$refs.trigger;
        },

        sync() {
            const select = this.$refs.native;
            this.options = [...select.options].map((option, index) => ({ value: option.value, label: option.text, index }));
            this.selected = select.selectedIndex;
            this.label = select.selectedIndex > -1 ? select.options[select.selectedIndex].text : '';
            this.empty = select.selectedIndex < 0 || select.value === '';

            if (! this.open) {
                this.active = select.selectedIndex;
            }
        },

        show(which = null) {
            const count = this.options.length;
            this.width = this.anchor().offsetWidth;
            this.active = which === 'first' ? 0 : which === 'last' ? count - 1 : Math.max(0, this.selected);
            this.open = true;
            this.$nextTick(() => this.scrollToActive());
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

        choose(index) {
            const select = this.$refs.native;

            if (index < 0 || index >= select.options.length) {
                return;
            }

            const changed = select.selectedIndex !== index;
            select.selectedIndex = index;
            this.sync();

            if (changed) {
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.dispatchEvent(new Event('blur'));
            }

            this.close();
        },

        move(step) {
            const count = this.options.length;

            if (! count) {
                return;
            }

            this.active = Math.min(count - 1, Math.max(0, this.active + step));
            this.scrollToActive();
        },

        scrollToActive() {
            this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' });
        },

        keydown(event) {
            const key = event.key;

            if (key === 'ArrowDown' || key === 'ArrowUp') {
                event.preventDefault();
                this.open ? this.move(key === 'ArrowDown' ? 1 : -1) : this.show();
            } else if (key === 'Home' || key === 'End') {
                event.preventDefault();
                this.show(key === 'Home' ? 'first' : 'last');
            } else if (key === 'Enter' || key === ' ') {
                event.preventDefault();
                this.open ? this.choose(this.active) : this.show();
            } else if (key === 'Escape' && this.open) {
                // preventDefault as well as stop: inside a <dialog>, Escape
                // would otherwise close the whole form, not just this list.
                event.preventDefault();
                event.stopPropagation();
                this.close();
            } else if (key === 'Tab') {
                this.close(false);
            } else if (key.length === 1 && /\S/.test(key) && ! event.ctrlKey && ! event.metaKey && ! event.altKey) {
                this.typeahead(key);
            }
        },

        typeahead(key) {
            const now = Date.now();
            this.typed = now - this.typedAt > 700 ? key : this.typed + key;
            this.typedAt = now;

            const index = this.options.findIndex((option) => option.label.toLowerCase().startsWith(this.typed.toLowerCase()));

            if (index < 0) {
                return;
            }

            if (this.open) {
                this.active = index;
                this.scrollToActive();
            } else {
                this.choose(index);
            }
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

        // Opened at the click; the server has not confirmed it yet.
        awaiting: false,

        // Typed in and then discarded: the next close still resets the form.
        touched: false,

        // ...and the body is the server's to fill (an edit): show a skeleton.
        loading: false,

        // Closed again while still loading: the server's late "open" is
        // refused instead of reopening the dialog under the person's hand.
        cancelled: false,

        init() {
            // A Livewire property holds the dialog open: follow it both ways.
            // Watched explicitly - an entangle handed through this factory
            // never linked, so a dialog opened by the server stayed shut.
            if (options.model && this.$wire) {
                this.open = Boolean(this.$wire.get(options.model));
                this.$wire.$watch(options.model, (value) => {
                    if (value && this.cancelled) {
                        this.cancelled = false;
                        this.awaiting = false;
                        this.loading = false;
                        this.$wire.set(options.model, false);

                        return;
                    }

                    this.awaiting = false;
                    this.loading = false;
                    this.open = Boolean(value);
                });
            }

            this.$watch('open', (value) => {
                const wasDirty = this.dirty || this.touched;
                this.touched = false;
                this.sync(value);

                if (! options.model || ! this.$wire || value) {
                    return;
                }

                // A form the server opened (an edit) is closed through the
                // same property. A pre-built new form opened with no server
                // call at all; it is reset only if something was typed in it.
                if (this.$wire.get(options.model)) {
                    this.$wire.set(options.model, false);
                } else if (wasDirty && typeof this.$wire.resetForm === 'function') {
                    this.$wire.resetForm();
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

        /**
         * Open at once, from a click. `wait` is for a form the server has to
         * fill first (an edit): a skeleton stands in until it answers. A new,
         * blank form is already on the page and shows straight away.
         */
        openNow(wait = false) {
            this.awaiting = wait && Boolean(options.model);
            this.loading = this.awaiting;
            this.cancelled = false;
            this.open = true;
        },

        requestClose() {
            if (this.awaiting) {
                this.cancelled = true;
                this.awaiting = false;
                this.loading = false;
                this.open = false;

                return;
            }

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
                    // Discarding keeps `touched`, so the close still resets the form.
                    run: () => { this.touched = true; this.dirty = false; this.open = false; },
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
 * Light / Dark / System - the EMS theme menu (Part B §2.7), the owner's choice
 * over a two-state toggle. Light and dark are stored and stamped on <html>;
 * System clears both, which hands the choice back to the CSS media query, so
 * the page follows the operating system live with no listener. Every
 * [data-theme-choice] item on the page shows which one is in force.
 */
const currentThemeChoice = () => {
    try {
        const stored = localStorage.getItem('gfms-theme');

        return stored === 'light' || stored === 'dark' ? stored : 'system';
    } catch (e) {
        return 'system';
    }
};

const markThemeChoice = () => {
    const current = currentThemeChoice();

    document.querySelectorAll('[data-theme-choice]').forEach((item) => {
        const chosen = item.dataset.themeChoice === current;
        item.setAttribute('aria-checked', chosen ? 'true' : 'false');
        item.querySelector('[data-theme-check]')?.toggleAttribute('hidden', ! chosen);
    });
};

document.addEventListener('click', (event) => {
    const item = event.target.closest('[data-theme-choice]');

    if (! item) {
        return;
    }

    const choice = item.dataset.themeChoice;
    const root = document.documentElement;

    try {
        if (choice === 'system') {
            localStorage.removeItem('gfms-theme');
        } else {
            localStorage.setItem('gfms-theme', choice);
        }
    } catch (e) {
        // Storage blocked: the attribute below still applies to this page.
    }

    if (choice === 'system') {
        root.removeAttribute('data-theme');
    } else {
        root.setAttribute('data-theme', choice);
    }

    markThemeChoice();
});

document.addEventListener('DOMContentLoaded', markThemeChoice);
document.addEventListener('livewire:navigated', markThemeChoice);

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

/**
 * The page-loading line along the top edge, for wire:navigate.
 *
 * Livewire's own bar (switched off in config/livewire.php) waits 150ms
 * before it appears and then creeps in 2% steps, so a quick page reads as a
 * slow one. This one starts the moment a link is pressed, covers most of the
 * width at once, then slows to a crawl while the server works and snaps to
 * the end when the page arrives. The waiting happens near the finish, where
 * it feels shortest.
 *
 * It hangs off <html>, not <body>, because navigation swaps the body.
 */
const loadingLine = (() => {
    let bar = null;
    let trickle = null;
    let hide = null;
    let progress = 0;
    let running = false;

    const moveTo = (value, duration) => {
        progress = value;
        bar.style.transitionDuration = `${duration}ms, 200ms`;
        bar.style.transform = `scaleX(${value})`;
    };

    return {
        start() {
            if (! bar?.isConnected) {
                bar = document.createElement('div');
                bar.className = 'ems-loading-line';
                bar.setAttribute('aria-hidden', 'true');
                document.documentElement.appendChild(bar);
            }

            clearInterval(trickle);
            clearTimeout(hide);
            running = true;

            bar.style.transitionDuration = '0ms, 0ms';
            bar.style.transform = 'scaleX(0)';
            bar.style.opacity = '1';
            void bar.offsetWidth;

            moveTo(0.7, 400);
            trickle = setInterval(() => moveTo(progress + (0.94 - progress) * 0.2, 700), 700);
        },

        done() {
            if (! running) {
                return;
            }

            running = false;
            clearInterval(trickle);
            moveTo(1, 150);
            hide = setTimeout(() => { bar.style.opacity = '0'; }, 200);
        },
    };
})();

/**
 * THE PAGE SKELETON, for wire:navigate.
 *
 * The line above says something is happening; this says what is coming. The
 * moment a link is pressed the page being left is hidden and a skeleton shaped
 * like the destination takes its place - a list, a record, a form or the
 * dashboard, picked by the address - so the click is answered at once rather
 * than when the server finishes. The real page replaces the whole body when
 * it lands, skeleton and all.
 *
 * The old page is hidden, not removed: if the page never arrives, it comes
 * back. Back and forward come from Livewire's cache and are instant, so they
 * get no skeleton.
 */
const pageSkeleton = (() => {
    // Generous: a free instance waking from sleep takes up to a minute.
    const GIVE_UP_AFTER_MS = 90_000;
    let giveUp = null;

    const kindFor = (url) => {
        const path = url.pathname.replace(/\/+$/, '') || '/';

        if (path === '/dashboard') {
            return 'dashboard';
        }
        if (/\/(create|edit)(\/|$)/.test(path) || path === '/profile' || path === '/settings') {
            return 'form';
        }
        if (/^\/(broodcocks|breeding)\/\d+(\/pedigree)?$/.test(path)) {
            return 'detail';
        }

        return 'list';
    };

    const restore = () => {
        clearTimeout(giveUp);
        document.querySelector('[data-page-skeleton]')?.remove();
        const content = document.querySelector('[data-page-content]');
        if (content) {
            content.hidden = false;
        }
    };

    return {
        show(url) {
            const content = document.querySelector('[data-page-content]');
            const template = document.querySelector(`template[data-page-skeleton-template="${kindFor(url)}"]`);
            if (! content || ! template) {
                return;
            }

            restore();
            const skeleton = template.content.firstElementChild.cloneNode(true);
            skeleton.setAttribute('data-page-skeleton', '');
            content.hidden = true;
            content.after(skeleton);
            content.closest('main')?.scrollTo({ top: 0 });
            giveUp = setTimeout(restore, GIVE_UP_AFTER_MS);
        },

        done() {
            clearTimeout(giveUp);
        },
    };
})();

/**
 * ONLY THE LAST CLICK COUNTS.
 *
 * Livewire never cancels a page request. Press Health, then Mortality, then
 * Dashboard while the server is slow, and all three pages arrive and are
 * shown in turn - Health, then Mortality, then finally Dashboard - each one
 * replacing the last. So when a page change starts, every other page request
 * still on its way is aborted, and Livewire drops an aborted request without
 * a word (requestWasCancelled).
 *
 * The subtle part: a wire:navigate link starts fetching on mousedown, and
 * the click then waits on that fetch. When it is aborted Livewire falls back
 * to fetching the same page again - which would bring the overtaken page
 * straight back. So a destination that has been overtaken is remembered,
 * and any request for it is aborted the moment it starts, until it is
 * clicked again.
 */
const latestNavigation = (() => {
    const inFlight = new Map();
    const overtaken = new Set();
    let latest = null;

    const keyFor = (url) => url.pathname + url.search + url.hash;

    const watchRequests = () => {
        window.Livewire.hook('navigate.request', ({ uri, options }) => {
            const controller = new AbortController();
            options.signal = AbortSignal.any && options.signal
                ? AbortSignal.any([options.signal, controller.signal])
                : controller.signal;

            if (overtaken.has(uri) && uri !== latest) {
                controller.abort();

                return;
            }

            inFlight.set(uri, controller);
        });
    };

    if (window.Livewire) {
        watchRequests();
    } else {
        document.addEventListener('livewire:init', watchRequests);
    }

    return {
        begin(url) {
            latest = keyFor(url);
            overtaken.delete(latest);

            inFlight.forEach((controller, uri) => {
                if (uri !== latest) {
                    controller.abort();
                    overtaken.add(uri);
                }
            });
            [...inFlight.keys()].forEach((uri) => uri !== latest && inFlight.delete(uri));
        },

        done() {
            latest = null;
            inFlight.clear();

            // A fallback for an overtaken page follows its abort at once, so a
            // second is plenty; after that, hovering the link may prefetch it.
            const stale = [...overtaken];
            setTimeout(() => stale.forEach((uri) => overtaken.delete(uri)), 1000);
        },
    };
})();

document.addEventListener('livewire:navigate', (event) => {
    // Synchronously, before Livewire starts this page's own request.
    if (event.detail?.url instanceof URL) {
        latestNavigation.begin(event.detail.url);
    }

    // Wait a tick: a listener that cancels the navigation runs after this one.
    setTimeout(() => {
        if (event.defaultPrevented) {
            return;
        }

        loadingLine.start();

        if (! event.detail?.cached && event.detail?.url instanceof URL) {
            pageSkeleton.show(event.detail.url);
        }
    });
});
document.addEventListener('livewire:navigated', () => {
    loadingLine.done();
    pageSkeleton.done();
    latestNavigation.done();
});
