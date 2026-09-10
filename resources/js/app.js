import './bootstrap';
import './tabler-init';

import flatpickr from 'flatpickr';
import TomSelect from 'tom-select';

import './dashboard-chart';

import 'flatpickr/dist/flatpickr.min.css';

/**
 * The date pickers are initialised from Blade, and Alpine `x-init` expressions are
 * evaluated against the global scope. Flatpickr was loaded from a CDN, which meant
 * an outage or a content security policy took the time sheet screens with it; it is
 * part of the build now and reaches Blade the same way it used to.
 */
window.flatpickr = flatpickr;

/**
 * Tabler's "advanced select" is Tom Select. Initialising is idempotent and can
 * be re-run: Livewire replaces DOM nodes, so any select it renders afterwards
 * needs wiring up too.
 */
function initialiseAdvancedSelects(root = document) {
    root.querySelectorAll('select[data-tomselect="tags"]').forEach((el) => {
        if (el.tomselect) return;

        new TomSelect(el, {
            plugins: ['remove_button'],
            persist: false,
            create: false,
        });
    });

    root.querySelectorAll('select[data-tomselect="select"]').forEach((el) => {
        if (el.tomselect) return;

        new TomSelect(el, {
            persist: false,
            create: false,
        });
    });
}

document.addEventListener('DOMContentLoaded', () => initialiseAdvancedSelects());
document.addEventListener('livewire:navigated', () => initialiseAdvancedSelects());

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el }) => initialiseAdvancedSelects(el));
});

window.initialiseAdvancedSelects = initialiseAdvancedSelects;

/**
 * A searchable select that reports its value back to Livewire.
 *
 * Tom Select rewrites the DOM around the original <select>, which Livewire
 * would otherwise fight over on every render, so the markup is wrapped in
 * wire:ignore and the two are bridged explicitly here.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('searchableSelect', (initialValue = null) => ({
        instance: null,

        init() {
            const select = this.$refs.select;

            this.instance = new TomSelect(select, {
                persist: false,
                create: false,
                maxOptions: null,
                placeholder: select.dataset.placeholder || 'Search…',
                onChange: (value) => {
                    this.$wire.set(select.dataset.model, value || null);
                },
            });

            if (initialValue) {
                this.instance.setValue(initialValue, true);
            }

            // Livewire may clear the bound property (for example after a
            // successful booking); follow it without firing onChange again.
            this.$wire.$watch(select.dataset.model, (value) => {
                if (!value) {
                    this.instance.clear(true);
                } else if (String(this.instance.getValue()) !== String(value)) {
                    this.instance.setValue(value, true);
                }
            });
        },

        /**
         * Add an option that did not exist when the page rendered and select it.
         */
        addOption(option) {
            this.instance.addOption(option);
            this.instance.refreshOptions(false);
            this.instance.setValue(option.value);
        },

        destroy() {
            this.instance?.destroy();
        },
    }));
});
