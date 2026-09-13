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
