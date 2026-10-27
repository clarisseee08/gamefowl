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
