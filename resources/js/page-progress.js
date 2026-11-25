/**
 * Navigation progress bar (components/site/page-progress.blade.php).
 *
 * Starts when the user follows an internal link or submits a form, trickles toward 90%,
 * and completes when the next page shows (pageshow). Written by hand (no package) on purpose.
 * Its "Loading page" message goes through the shared live region (live-region.js).
 */

import { announce, clearAnnouncement, hasAnnouncement } from './live-region';

const SHOW_DELAY_MS = 150; // fast navigations never flash the bar
const TRICKLE_INTERVAL_MS = 200;
const TRICKLE_MAX = 0.9;
const COMPLETE_FADE_MS = 200; // matches the opacity transition in the Blade component
const STUCK_TIMEOUT_MS = 30000; // safety net: a navigation that never finishes (e.g. a download) hides the bar

export function initPageProgress() {
    const root = document.querySelector('[data-page-progress]');
    if (!root) {
        return;
    }

    const bar = root.querySelector('[data-page-progress-bar]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let active = false;
    let progress = 0;
    let showTimer = null;
    let trickleTimer = null;
    let fadeTimer = null;
    let stuckTimer = null;
    let ownsAnnouncement = false; // only clear the live region message this script wrote

    // Clears "Loading page" only. A message from another script (e.g. "Saving…") is left alone.
    const releaseAnnouncement = () => {
        if (ownsAnnouncement) {
            clearAnnouncement();
            ownsAnnouncement = false;
        }
    };

    const setWidth = (value) => {
        bar.style.width = `${Math.round(value * 100)}%`;
    };

    const clearTimers = () => {
        clearTimeout(showTimer);
        clearInterval(trickleTimer);
        clearTimeout(fadeTimer);
        clearTimeout(stuckTimer);
        showTimer = trickleTimer = fadeTimer = stuckTimer = null;
    };

    // Instant reset, no animation. Used when a navigation starts again or the page comes back from bfcache.
    const reset = () => {
        clearTimers();
        active = false;
        progress = 0;
        delete root.dataset.active;
        releaseAnnouncement();
        bar.style.transition = 'none';
        setWidth(0);
        void bar.offsetWidth; // force a reflow so the next width change animates from 0
        bar.style.transition = '';
    };

    const show = () => {
        showTimer = null;
        root.dataset.active = 'true';

        if (!hasAnnouncement()) {
            announce('Loading page');
            ownsAnnouncement = true;
        }

        // Reduced motion: no trickle. The bar appears at its final-looking state straight away.
        if (reducedMotion.matches) {
            setWidth(TRICKLE_MAX);
            return;
        }

        progress = 0.08;
        setWidth(progress);
        trickleTimer = setInterval(() => {
            progress += (TRICKLE_MAX - progress) * 0.1;
            setWidth(progress);
        }, TRICKLE_INTERVAL_MS);
    };

    const start = () => {
        if (active) {
            return;
        }

        reset();
        active = true;
        showTimer = setTimeout(show, SHOW_DELAY_MS);
        stuckTimer = setTimeout(finish, STUCK_TIMEOUT_MS);
    };

    // Complete: jump to 100%, fade out, then reset. If the bar never showed (fast navigation), just cancel.
    const finish = () => {
        const wasShown = 'active' in root.dataset;

        clearTimers();
        active = false;

        if (!wasShown) {
            releaseAnnouncement();
            return;
        }

        if (reducedMotion.matches) {
            reset();
            return;
        }

        progress = 1;
        setWidth(1);
        releaseAnnouncement();
        fadeTimer = setTimeout(reset, COMPLETE_FADE_MS);
    };

    const isPlainClick = (event) =>
        event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;

    const isTrackableLink = (link) => {
        if (link.hasAttribute('download') || link.hasAttribute('data-no-progress')) {
            return false;
        }

        if (link.target && link.target !== '_self') {
            return false;
        }

        // In-page anchors ("#" or "#section") never load a new page.
        if ((link.getAttribute('href') || '').startsWith('#')) {
            return false;
        }

        const url = new URL(link.href, window.location.href);

        // Only http(s) links on this origin. Drops mailto:, tel:, and external sites.
        if ((url.protocol !== 'http:' && url.protocol !== 'https:') || url.origin !== window.location.origin) {
            return false;
        }

        // Same page with only a hash change: the browser scrolls, nothing loads.
        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) {
            return false;
        }

        return true;
    };

    document.addEventListener('click', (event) => {
        // Another handler already took over (e.g. an Alpine .prevent), or it is not a plain left click.
        if (event.defaultPrevented || !isPlainClick(event) || !(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('a[href]');

        // HTMLAnchorElement only: an SVG <a> has an SVGAnimatedString href that new URL() cannot parse.
        if (link instanceof HTMLAnchorElement && isTrackableLink(link)) {
            start();
        }
    });

    // Runs after form handlers (bubble phase). The login form's double-submit guard calls
    // preventDefault, so a blocked second submit does not restart the bar.
    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
            return;
        }

        const form = event.target;

        if (form.hasAttribute('data-no-progress') || (form.target && form.target !== '_self') || form.method === 'dialog') {
            return;
        }

        const url = new URL(form.action || window.location.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        start();
    });

    // pageshow fires on normal loads and on back/forward cache restores.
    // A restored page must never show a stuck bar, so reset it when it was persisted.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            reset();
        } else {
            finish();
        }
    });
}
