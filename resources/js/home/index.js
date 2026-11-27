/**
 * Homepage motion layer. Loaded on demand by app.js only when the page has [data-home-fx],
 * so other pages never download GSAP, Lenis or Three.
 *
 * - Lenis smooth scroll, driven by the GSAP ticker so ScrollTrigger stays in sync.
 * - Hero: split-word title, a hand-written reveal for "Audio", magnetic buttons, and a 3D mixing console
 *   (Three.js) that boots up, plays meters, and flies the camera in as you scroll (pinned on desktop).
 * - Sections: masked heading reveals, scroll-lit paragraphs, tilt/spotlight cards, velocity marquee.
 *
 * Reduced motion: nothing here runs; the page stays exactly as the server rendered it.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { magnet, scrollWords, splitHeadings, tiltSpotlight, velocityMarquee } from './effects';

gsap.registerPlugin(ScrollTrigger, SplitText);

const NAV_OFFSET = -80; // matches scroll-pt-20 on <html>

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
        lenis.scrollTo(target, {
            offset: NAV_OFFSET,
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

function canRun3D() {
    if (navigator.connection?.saveData) return false;
    if ((navigator.hardwareConcurrency || 4) <= 2) return false;
    try {
        const probe = document.createElement('canvas');
        return Boolean(probe.getContext('webgl2'));
    } catch {
        return false;
    }
}

function initHeroTitle(hero) {
    const title = hero.querySelector('[data-hero-title]');
    if (!title) return;

    // The content's CSS fade-up keeps its end state (fill-mode), which would beat GSAP's inline styles and
    // freeze the scroll fade. GSAP takes over: drop the class and stagger the other lines in itself.
    const content = hero.querySelector('[data-hero-content]');
    content?.classList.remove('motion-safe:animate-fade-up');
    const lines = content ? Array.from(content.children).filter((el) => el !== title) : [];

    const script = title.querySelector('[data-hero-script]');
    const split = SplitText.create(title, { type: 'words', ignore: script || undefined, wordsClass: 'hero-word' });
    title.classList.add('is-split');
    gsap.set(title, { perspective: 900 });

    const tl = gsap.timeline({ defaults: { ease: 'power4.out' }, delay: 0.15 });
    tl.from(split.words, { yPercent: 120, rotationX: -85, opacity: 0, transformOrigin: '50% 100%', duration: 1.2, stagger: 0.14 });
    tl.from(lines, { y: 28, opacity: 0, duration: 1, stagger: 0.09, ease: 'power3.out', clearProps: 'transform' }, 0.35);
    if (script) {
        // "Audio" writes itself in, left to right, like a pen stroke.
        tl.fromTo(
            script,
            { clipPath: 'inset(-40% 100% -40% -10%)' },
            { clipPath: 'inset(-40% -10% -40% -10%)', duration: 1.6, ease: 'power2.inOut' },
            0.3,
        );
    }
}

async function initHeroStage(hero, heroProgress) {
    const host = hero.querySelector('[data-hero-stage]');
    const canvas = host?.querySelector('canvas');
    if (!canvas || !canRun3D()) return;

    let stage;
    try {
        const { createConsoleScene } = await import('./console-scene');
        stage = createConsoleScene(canvas, {
            mobile: window.matchMedia('(max-width: 767px), (pointer: coarse)').matches,
            wallImage: host.dataset.wallImage || null,
        });
    } catch (error) {
        console.error(error); // No WebGL or the chunk failed: the cover photo simply stays.
        return;
    }

    let visible = true;
    new IntersectionObserver(([entry]) => (visible = entry.isIntersecting)).observe(hero);
    new ResizeObserver(() => stage.resize()).observe(canvas);

    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        window.addEventListener(
            'pointermove',
            (event) => {
                stage.state.pointerX = (event.clientX / window.innerWidth - 0.5) * 2;
                stage.state.pointerY = -(event.clientY / window.innerHeight - 0.5) * 2;
            },
            { passive: true },
        );
    }

    gsap.ticker.add((time) => {
        if (!visible) return;
        stage.state.progress = heroProgress.value;
        stage.render(time);
    });

    // First frame is drawn: crossfade from the photo to the live stage, then power up.
    requestAnimationFrame(() => {
        hero.classList.add('is-3d');
        gsap.timeline()
            .to(stage.state, { power: 1, duration: 2.4, ease: 'power2.inOut' })
            .to(stage.state, { boot: 1, duration: 2.6, ease: 'none' }, 0.5);
    });
}

function initHeroScroll(hero, heroProgress) {
    const content = hero.querySelector('[data-hero-content]');
    const overlays = hero.querySelectorAll('[data-hero-overlay]');
    const cue = hero.querySelector('[data-hero-cue]');
    const mm = gsap.matchMedia();

    mm.add({ desktop: '(min-width: 1024px)', mobile: '(max-width: 1023px)' }, (context) => {
        const { desktop } = context.conditions;
        const tl = gsap.timeline({
            scrollTrigger: {
                trigger: hero,
                start: 'top top',
                end: desktop ? '+=120%' : 'bottom top',
                scrub: desktop ? 1 : 0.5,
                pin: desktop,
                anticipatePin: 1,
            },
        });

        tl.to(heroProgress, { value: 1, ease: 'none', duration: 1 }, 0)
            .to(content, { yPercent: desktop ? -22 : -10, opacity: 0, ease: 'power2.in', duration: 0.5 }, 0)
            .to(overlays, { opacity: 0.25, ease: 'none', duration: 0.6 }, 0)
            .to(cue, { opacity: 0, duration: 0.15 }, 0);
    });
}

export function initHome() {
    const hero = document.querySelector('[data-hero]');
    const heroProgress = { value: 0 };

    initLenis();

    if (hero) {
        initHeroTitle(hero);
        initHeroScroll(hero, heroProgress);
        initHeroStage(hero, heroProgress);
    }

    // Created top to bottom, after the hero pin, so trigger positions account for the pin spacer.
    velocityMarquee('[data-marquee]');
    splitHeadings('#main section:not([data-hero]) h2');
    scrollWords('[data-scroll-words], #about-title ~ p');
    tiltSpotlight('[data-tilt]');
    magnet('[data-magnet]');

    // Fonts change line heights and widths: re-measure once they are in.
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
}
