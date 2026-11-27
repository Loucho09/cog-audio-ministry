/**
 * Homepage motion layer. Loaded on demand by app.js only when the page has [data-home-fx],
 * so other pages never download GSAP, Lenis or Three.
 *
 * - One Lenis instance, driven by the GSAP ticker so ScrollTrigger stays in sync.
 * - Section entrances live in ./effects.js (one signature entrance per section), set up in one gsap.context().
 * - The hero (title, 3D desk, pinned copy story) lives in ./hero.js.
 *
 * Reduced motion: nothing here runs; the page stays exactly as the server rendered it.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import {
    cascade,
    countUp,
    faderHeading,
    hscroll,
    led,
    meters,
    patchRows,
    signalLine,
    spotlight,
    strips,
} from './effects';
import { initHero } from './hero';

gsap.registerPlugin(ScrollTrigger, SplitText);

// Section top lands 80px below the viewport top (fixed 64px navbar plus a gap). Measured, not Lenis offset, because
// Lenis resolves element offsets against its cached layout.
const NAV_OFFSET = -80;
const targetY = (el) => window.scrollY + el.getBoundingClientRect().top + NAV_OFFSET;

function initLenis() {
    const lenis = new Lenis({ lerp: 0.09, smoothWheel: true, autoRaf: false });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);

    // Same-page anchors (navbar, hero buttons, scroll cue) glide instead of jumping.
    // The skip link is left alone so keyboard focus moves exactly as the browser does it.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href*="#"]');
        if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || !url.hash || url.hash === '#main') return;

        const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
        if (!target) return;

        event.preventDefault();
        history.pushState(null, '', url.hash);
        lenis.scrollTo(targetY(target), {
            duration: 1.4,
            onComplete: () => {
                if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
                target.focus({ preventScroll: true });
            },
        });
    });

    // The gallery lightbox locks the page with overflow-hidden on <body>: pause smooth scroll while it is open.
    new MutationObserver(() => {
        document.body.classList.contains('overflow-hidden') ? lenis.stop() : lenis.start();
    }).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    return lenis;
}

/** Section entrances and readouts. Created inside one context, so a single revert cleans everything. */
function initSections(root) {
    // Created top to bottom, so ScrollTrigger refresh order matches the page.
    root.querySelectorAll('[data-fx="fader-heading"]').forEach(faderHeading);
    root.querySelectorAll('[data-fx="patch-rows"]').forEach(patchRows);
    root.querySelectorAll('[data-fx="meters"]').forEach(meters);
    root.querySelectorAll('[data-fx="strips"]').forEach(strips);
    root.querySelectorAll('[data-fx="cascade"]').forEach(cascade);
    root.querySelectorAll('[data-count]').forEach(countUp);
    root.querySelectorAll('[data-fx="led"]').forEach(led);
    document.querySelectorAll('[data-fx="signal-line"]').forEach(signalLine);

    spotlight('.fx-spot');

    // Desktop: the gallery pins and slides sideways. Mobile keeps the two-column grid (nothing to set up).
    const mm = gsap.matchMedia();
    mm.add({ desktop: '(min-width: 1024px)', mobile: '(max-width: 1023px)' }, ({ conditions }) => {
        if (conditions.desktop) root.querySelectorAll('[data-fx="hscroll"]').forEach(hscroll);
    });
}

export function initHome() {
    const hero = document.querySelector('[data-hero]');
    const root = document.getElementById('main') ?? document.body;

    const lenis = initLenis();

    // The hero pin goes first: its spacer changes every position below it, so later triggers must measure after it.
    if (hero) initHero(hero);

    gsap.context(() => initSections(root), root);

    // A direct load at an anchor (e.g. /#contact) must land on that section. Lenis starts from the scroll position it
    // reads at construction, so without this the page snaps back to the top.
    const landOnHash = () => {
        const id = decodeURIComponent(window.location.hash.slice(1));
        const target = id && id !== 'main' ? document.getElementById(id) : null;
        if (target) lenis.scrollTo(targetY(target), { immediate: true });
    };
    // The page is still growing while images, fonts and pin spacers settle: re-measure, then land again.
    const settle = () => {
        lenis.resize(); // Lenis caches the scroll limit; re-read it once the page has its final height
        ScrollTrigger.refresh();
        landOnHash();
    };
    landOnHash();
    if (document.readyState === 'complete') settle();
    else window.addEventListener('load', settle, { once: true });
    document.fonts?.ready.then(settle);
}
