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

// Entrances that are set up lazily: each one builds its tweens and ScrollTriggers only when its element nears the
// viewport (see initSections). Listed in page order.
const LAZY_SETUPS = [
    ['[data-fx="fader-heading"]', faderHeading],
    ['[data-fx="patch-rows"]', patchRows],
    ['[data-fx="meters"]', meters],
    ['[data-fx="strips"]', strips],
    ['[data-fx="cascade"]', cascade],
    ['[data-count]', countUp],
    ['[data-fx="led"]', led],
];

/** Desktop: the gallery pins and slides sideways. Mobile keeps the two-column grid (nothing to set up). */
function pinGallery(track) {
    gsap.matchMedia().add({ desktop: '(min-width: 1024px)' }, ({ conditions }) => {
        if (conditions.desktop) hscroll(track);
    });
}

/**
 * Section entrances and readouts. Each element is set up once, when it is within half a screen of the viewport,
 * so the page does not build dozens of ScrollTriggers before the reader gets there. Everything runs inside the
 * one gsap context (ctx.add), so a single revert still cleans it all up. Pins below the reader are added below
 * the fold, so nothing above them moves.
 */
function initSections(root, ctx) {
    const setups = new Map();
    LAZY_SETUPS.forEach(([selector, setup]) => root.querySelectorAll(selector).forEach((el) => setups.set(el, setup)));
    root.querySelectorAll('[data-fx="hscroll"]').forEach((el) => setups.set(el, pinGallery));

    // Immediate, cheap parts: spotlight pointer listeners and the gutter line (one scrub plus a trigger per section).
    ctx.add(() => {
        spotlight('.fx-spot');
        document.querySelectorAll('[data-fx="signal-line"]').forEach(signalLine);
    });

    const runSetup = (el) => {
        const setup = setups.get(el);
        setups.delete(el);
        ctx.add(() => setup(el));
    };

    // Set-up creates pins and triggers below the reader, so measured positions are refreshed once per batch.
    let refreshTimer = 0;
    const scheduleRefresh = () => {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => ScrollTrigger.refresh(), 150);
    };

    if (!('IntersectionObserver' in window)) {
        setups.forEach((_, el) => runSetup(el));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            const near = entries
                .filter((entry) => entry.isIntersecting)
                .map((entry) => entry.target)
                // Top to bottom, so ScrollTrigger refresh order matches the page.
                .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
            near.forEach((el) => {
                observer.unobserve(el);
                runSetup(el);
            });
            if (near.length > 0) scheduleRefresh();
            if (setups.size === 0) observer.disconnect();
        },
        { rootMargin: '0px 0px 50% 0px' },
    );
    setups.forEach((_, el) => observer.observe(el));
}

export function initHome() {
    const hero = document.querySelector('[data-hero]');
    const root = document.getElementById('main') ?? document.body;

    const lenis = initLenis();

    // The hero pin goes first: its spacer changes every position below it, so later triggers must measure after it.
    if (hero) initHero(hero);

    initSections(root, gsap.context(() => {}, root));

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
