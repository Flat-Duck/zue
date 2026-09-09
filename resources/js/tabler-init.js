/*
 * Tabler: styles, behaviour and the Bootstrap 5 bundle it ships with.
 *
 * Importing this module runs Tabler's own initialisers for tooltips, popovers,
 * dropdowns, tabs, the sidebar and so on, and gives us the Bootstrap 5 classes
 * without a separate Bootstrap dependency.
 */
import { bootstrap } from '@tabler/core/js/tabler';

// Tabler bundles Bootstrap 5. Exposing it lets Blade and Alpine drive modals
// with `new bootstrap.Modal(el)` instead of the jQuery plugin API that
// Bootstrap 5 removed.
window.bootstrap = bootstrap;

/**
 * Tabler wires up tooltips and popovers once, when this module is evaluated.
 * Markup that Livewire swaps in afterwards would therefore have none, so
 * re-initialise the elements that do not already have an instance.
 */
function initialiseTooltips(root = document) {
    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        if (bootstrap.Tooltip.getInstance(el)) return;

        new bootstrap.Tooltip(el, {
            delay: { show: 50, hide: 50 },
            html: el.getAttribute('data-bs-html') === 'true',
            placement: el.getAttribute('data-bs-placement') ?? 'auto',
        });
    });

    root.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
        if (bootstrap.Popover.getInstance(el)) return;

        new bootstrap.Popover(el);
    });
}

document.addEventListener('livewire:navigated', () => initialiseTooltips());

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el }) => initialiseTooltips(el));
});

export { initialiseTooltips };
