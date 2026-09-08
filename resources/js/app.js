import './bootstrap';
import './tabler-init';

import TomSelect from 'tom-select';

// Init all multi-selects for management scopes
document.addEventListener('DOMContentLoaded', () => {
    // Any select where you want TomSelect, mark with data-tomselect="tags"
    // Multi-select for tags/employee groups
    document
        .querySelectorAll('select[data-tomselect="tags"]')
        .forEach((el) => {
            if (el.tomselect) return;
            new TomSelect(el, {
                plugins: ['remove_button'],
                persist: false,
                create: false,
            });
        });

    // Single-select search (Manager dropdown)
    document
        .querySelectorAll('select[data-tomselect="select"]')
        .forEach((el) => {
            if (el.tomselect) return;
            new TomSelect(el, {
                persist: false,
                create: false,
            });
        });
});
