/*
 * The handful of behaviours that used to live in `onclick=` and `onsubmit=`
 * attributes, listened for once at the document instead.
 *
 * An inline handler is a script the browser cannot tell apart from an injected
 * one, so the content security policy refuses them all. These four attributes
 * carry no code, only intent, and cover every case the views had:
 *
 *   data-confirm="…"          ask before a form submits or a link is followed
 *   data-action="print"       window.print()
 *   data-submit-on-change     a select that submits its form when changed
 *   data-submit="#form-id"    a button that submits some other form
 *
 * Listening at the document means Livewire can swap the DOM underneath and
 * nothing needs re-attaching.
 */

document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form instanceof HTMLFormElement ? form.dataset.confirm : null;

    if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element
        ? event.target.closest('[data-confirm], [data-action], [data-submit]')
        : null;

    if (!target || target instanceof HTMLFormElement) {
        return;
    }

    if (target.dataset.confirm !== undefined && !window.confirm(target.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return;
    }

    if (target.dataset.action === 'print') {
        event.preventDefault();
        window.print();
        return;
    }

    if (target.dataset.submit) {
        event.preventDefault();
        document.querySelector(target.dataset.submit)?.submit();
    }
}, true);

document.addEventListener('change', (event) => {
    const field = event.target;

    if (field instanceof Element && field.hasAttribute('data-submit-on-change')) {
        field.closest('form')?.submit();
    }
});
