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
 * On pageshow (bfcache back/forward restore, or a reload) the buttons are unlocked again.
 */

const FORM_SELECTOR = 'form[data-submit-guard]';
const BUSY_ATTRIBUTE = 'data-submitting';

// form.elements includes buttons that point at this form with a form="" attribute.
const submitButtons = (form) => Array.from(form.elements).filter((element) => element.type === 'submit');

const lock = (form) => {
    form.setAttribute(BUSY_ATTRIBUTE, 'true');

    submitButtons(form).forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('aria-busy', 'true');
    });
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

        lock(form);
    });

    // A restored or reloaded page must never keep disabled buttons.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll(`${FORM_SELECTOR}[${BUSY_ATTRIBUTE}]`).forEach(unlock);
    });
}
