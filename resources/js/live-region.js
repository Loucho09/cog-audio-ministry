/**
 * The one polite live region per page for loading announcements.
 *
 * It lives in components/site/page-progress.blade.php (data-live-region). Scripts announce through
 * announce() instead of adding their own regions, so screen readers hear one stream, not duplicates.
 */

const REGION_SELECTOR = '[data-live-region]';

const region = () => document.querySelector(REGION_SELECTOR);

export function announce(message) {
    const element = region();

    if (element) {
        element.textContent = message;
    }
}

export function clearAnnouncement() {
    announce('');
}

// True when a message is already being announced (used so a later "Loading page" does not talk over "Saving…").
export function hasAnnouncement() {
    const element = region();

    return Boolean(element && element.textContent.trim() !== '');
}
