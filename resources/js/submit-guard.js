/**
 * Submit guard: the loading state for every form that opts in with `data-submit-guard`.
 *
 * On a real submit (the browser fires `submit` only after constraint validation passes):
 * - a second submit of the same form is blocked (double-submit guard),
 * - every submit button in the form is disabled and gets aria-busy + aria-disabled.
 *   The button's `group-aria-busy:` classes then swap its label for a spinner and loading text
 *   (see components/loading-label.blade.php).
 * - nothing calls preventDefault on a normal submit, so the navigation progress bar still starts.
 *
 * - the loading label is announced once through the shared live region (live-region.js).
 *
 * On pageshow (bfcache back/forward restore, or a reload) the buttons are unlocked again.
 */

import { announce } from './live-region';

const FORM_SELECTOR = 'form[data-submit-guard]';
const BUSY_ATTRIBUTE = 'data-submitting';

// form.elements includes buttons that point at this form with a form="" attribute.
const submitButtons = (form) => Array.from(form.elements).filter((element) => element.type === 'submit');

// Forms that stay on this page (new tab, dialog, opted out) never fire pageshow, so they are not locked.
const staysOnPage = (form, submitter) => {
    const target = (submitter?.getAttribute('formtarget') || form.getAttribute('target') || '').toLowerCase();

    return (
        (target !== '' && target !== '_self')
        || (form.getAttribute('method') || '').toLowerCase() === 'dialog'
        || form.hasAttribute('data-no-progress')
    );
};

const STUCK_TIMEOUT_MS = 30000; // same safety net as page-progress.js (e.g. a download response)

const lock = (form) => {
    const buttons = submitButtons(form);

    buttons.forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('aria-busy', 'true');
    });

    // Announce the first button's loading label ("Saving…") through the page's one live region.
    const loadingLabel = buttons
        .map((button) => button.querySelector('[data-loading-label]'))
        .find(Boolean);

    announce(loadingLabel?.textContent.trim() || 'Submitting');
};

const unlock = (form) => {
    form.removeAttribute(BUSY_ATTRIBUTE);

    submitButtons(form).forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-disabled');
        button.removeAttribute('aria-busy');
    });
};

export function initSubmitGuard() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        // Not a guarded form, or another handler already cancelled this submit.
        if (!(form instanceof HTMLFormElement) || !form.matches(FORM_SELECTOR) || event.defaultPrevented) {
            return;
        }

        // The first submit is still in flight: block this one.
        if (form.hasAttribute(BUSY_ATTRIBUTE)) {
            event.preventDefault();
            return;
        }

        if (staysOnPage(form, event.submitter)) {
            return;
        }

        // Mark busy now so a second submit is blocked, but disable the buttons on the next tick:
        // the browser builds the form data after this event, and disabled buttons (including the one
        // clicked) would drop their name/value from the request.
        form.setAttribute(BUSY_ATTRIBUTE, 'true');
        setTimeout(() => lock(form), 0);
        setTimeout(() => unlock(form), STUCK_TIMEOUT_MS);
    });

    // A restored or reloaded page must never keep disabled buttons.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll(`${FORM_SELECTOR}[${BUSY_ATTRIBUTE}]`).forEach(unlock);
    });
}
