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
import {
    cascade,
    countUp,
    desktopMotion,
    faderHeading,
    hscroll,
    led,
    meters,
    patchRows,
    signalLine,
    spotlight,
    strips,
    velocityMarquee,
} from './effects';
import { initHero } from './hero';

gsap.registerPlugin(ScrollTrigger);

// Section top lands 80px below the viewport top (fixed 64px navbar plus a gap). Measured, not Lenis offset, because
// Lenis resolves element offsets against its cached layout. Phones use the same gap as CSS scroll-margin-top.
const NAV_OFFSET = -80;
const targetY = (el) => window.scrollY + el.getBoundingClientRect().top + NAV_OFFSET;

/** Decodes a URL fragment. A malformed one (e.g. "/#%") returns null instead of throwing. */
function safeDecode(value) {
    try {
        return decodeURIComponent(value);
    } catch {
        return null;
    }
}

/** The on-page section a click points at, or null when the browser should handle the click itself. */
function samePageTarget(event) {
    // Only plain left clicks. Modified clicks (new tab, download) and defaultPrevented clicks keep browser behaviour.
    if (event.button !== 0 || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return null;

    const link = event.target.closest('a[href*="#"]');
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return null;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || !url.hash || url.hash === '#main') return null;

    const id = safeDecode(url.hash.slice(1));
    return id ? document.getElementById(id) : null;
}

function focusSection(target) {
    if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
}

/**
 * Smooth scroll is desktop only. Lenis (and its CSS) is a dynamic import, so phones and tablets never download it
 * and keep native scrolling; their anchors use scroll-margin-top (app.css) instead.
 */
async function initSmoothScroll() {
    const [{ default: Lenis }] = await Promise.all([import('lenis'), import('lenis/dist/lenis.css')]);
    const lenis = new Lenis({ lerp: 0.09, smoothWheel: true, autoRaf: false });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);

    // Same-page anchors (navbar, hero buttons, scroll cue) glide instead of jumping.
    // The skip link is left alone so keyboard focus moves exactly as the browser does it.
    document.addEventListener('click', (event) => {
        const target = samePageTarget(event);
        if (!target) return;

        event.preventDefault();
        history.pushState(null, '', '#' + target.id);
        lenis.scrollTo(targetY(target), {
            duration: 1.4,
            onComplete: () => focusSection(target),
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
    ['[data-fx="marquee"]', velocityMarquee],
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
function initSections(root, ctx, disposers) {
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
        ctx.add(() => {
            // Some setups (led) add window listeners; keep their undo functions for teardown.
            const dispose = setup(el);
            if (typeof dispose === 'function') disposers.push(dispose);
        });
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
    // One context owns every tween, pin and trigger on the page, the hero copy story included.
    const ctx = gsap.context(() => {}, root);
    // Undo functions for window listeners (see initSections). Released on a real unload, see pagehide below.
    const disposers = [];

    // The hero pin goes first: its spacer changes every position below it, so later triggers must measure after it.
    let stopHeroStage = () => {};
    if (hero) {
        ctx.add(() => {
            stopHeroStage = initHero(hero, ctx, disposers);
        });
    }

    initSections(root, ctx, disposers);

    // Set once Lenis has loaded (desktop only). Phones keep native scrolling, so this stays null there.
    let lenis = null;

    // A direct load at an anchor (e.g. /#contact) must land on that section. Lenis starts from the scroll position it
    // reads at construction, so without this the page snaps back to the top. Phones: the browser's own hash jump
    // already respects scroll-margin-top, so only the re-landing after layout settles is needed there.
    const landOnHash = () => {
        const id = safeDecode(window.location.hash.slice(1));
        const target = id && id !== 'main' ? document.getElementById(id) : null;
        if (!target) return;
        if (lenis) lenis.scrollTo(targetY(target), { immediate: true });
        else target.scrollIntoView({ block: 'start', behavior: 'instant' });
    };
    // The page is still growing while images, fonts and pin spacers settle: re-measure, then land again.
    const settle = () => {
        lenis?.resize(); // Lenis caches the scroll limit; re-read it once the page has its final height
        ScrollTrigger.refresh();
        landOnHash();
    };

    if (desktopMotion()) {
        initSmoothScroll().then((instance) => {
            lenis = instance;
            settle();
        });
    } else {
        // Phones: native scrolling. The page's CSS smooth scroll can stop short on long anchor jumps, so the jump is
        // instant here (scroll-pt-20 on <html> keeps the section below the navbar).
        document.addEventListener('click', (event) => {
            const target = samePageTarget(event);
            if (!target) return;

            event.preventDefault();
            history.pushState(null, '', '#' + target.id);
            target.scrollIntoView({ block: 'start', behavior: 'instant' });
            focusSection(target);
        });
    }

    if (document.readyState === 'complete') settle();
    else window.addEventListener('load', settle, { once: true });
    document.fonts?.ready.then(settle);

    // Leaving the page: the hero's WebGL loop and listeners stop (the poster returns if the page is restored from the
    // back-forward cache). Window listeners are released only on a real unload, so a restored page keeps its effects.
    window.addEventListener('pagehide', (event) => {
        stopHeroStage();
        if (!event.persisted) disposers.splice(0).forEach((dispose) => dispose());
    });
}
