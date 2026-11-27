/**
 * Hero: title reveal, the 3D desk (boot after first paint), its loader, and the pinned copy story.
 *
 * - Title: SplitText lines rise out of a mask (effects.js splitHeading). The other copy lines fade up.
 * - 3D: a dynamic import after load + idle, so the first paint never waits for Three.js. Gated by canRun3D();
 *   without it the cover photo stays as the poster. Rendering pauses off-screen and in hidden tabs.
 * - Load-in: the camera dollies in from darkness, the faders sweep, and the LEDs light in sequence.
 * - Scroll (desktop only): the section pins for 200% of a viewport. The copy panels crossfade and the
 *   camera moves from a 3/4 view, to top-down over the faders, to a close-up on the first screen.
 *   Mobile: no pin, no scrub, the camera stays on the 3/4 view.
 * - GLB: if the server rendered data-glb (only when public/models/dlive.glb exists), the model replaces the
 *   procedural desk. Only Meshopt is decoded (no Draco or KTX2 decoder files are shipped).
 *
 * Reduced motion never reaches this file (app.js checks first).
 */
import gsap from 'gsap';
import { splitHeading } from './effects';

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

/** Resolves once the page has loaded and the browser is idle, so Three.js never competes with first paint. */
function afterFirstPaint() {
    return new Promise((resolve) => {
        const go = () => {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => resolve(), { timeout: 1500 });
            } else {
                setTimeout(resolve, 200);
            }
        };
        if (document.readyState === 'complete') go();
        else window.addEventListener('load', go, { once: true });
    });
}

function initTitle(hero) {
    const title = hero.querySelector('[data-hero-title]');
    const content = hero.querySelector('[data-hero-content]');
    if (!title) return;

    // The CSS fade-up keeps its end state (fill-mode), which would beat GSAP's inline styles. GSAP takes over.
    content?.classList.remove('motion-safe:animate-fade-up');
    const lines = content ? Array.from(content.children).filter((el) => el !== title) : [];

    splitHeading(title, { delay: 0.15 });
    gsap.from(lines, { y: 28, autoAlpha: 0, duration: 1, stagger: 0.09, delay: 0.35, ease: 'power3.out', clearProps: 'transform' });
}

function initCopyStory(hero, progress) {
    const [main, second, third] = hero.querySelectorAll('[data-hero-panel]');
    if (!main) return;

    const mm = gsap.matchMedia();
    mm.add('(min-width: 1024px)', () => {
        const tl = gsap.timeline({
            scrollTrigger: {
                trigger: hero,
                start: 'top top',
                end: '+=200%',
                scrub: 1,
                pin: true,
                anticipatePin: 1,
                invalidateOnRefresh: true,
            },
        });

        // The scroll drives the scene (0..1). The copy panels crossfade on the same timeline.
        tl.to(progress, { value: 1, ease: 'none', duration: 1 }, 0)
            .to(main, { autoAlpha: 0, yPercent: -6, ease: 'power2.in', duration: 0.3 }, 0.02);
        if (second) tl.fromTo(second, { autoAlpha: 0, yPercent: 6 }, { autoAlpha: 1, yPercent: 0, ease: 'power3.out', duration: 0.3 }, 0.3);
        if (second) tl.to(second, { autoAlpha: 0, yPercent: -6, ease: 'power2.in', duration: 0.25 }, 0.6);
        if (third) tl.fromTo(third, { autoAlpha: 0, yPercent: 6 }, { autoAlpha: 1, yPercent: 0, ease: 'power3.out', duration: 0.3 }, 0.7);
    });
}

async function initStage(hero, progress) {
    const host = hero.querySelector('[data-hero-stage]');
    const canvas = host?.querySelector('canvas');
    const loader = hero.querySelector('[data-hero-loader]');
    if (!canvas || !canRun3D()) return;

    const readout = loader?.querySelector('[data-hero-percent]');
    const bar = loader?.querySelector('[data-hero-meter]');
    const load = { value: 0 };
    const showLoad = (value) => {
        load.value = Math.max(load.value, value);
        if (readout) readout.textContent = String(Math.round(load.value * 100)).padStart(3, '0');
        if (bar) gsap.set(bar, { scaleX: load.value });
    };

    await afterFirstPaint();
    if (loader) loader.hidden = false;
    showLoad(0.1);

    let stage;
    try {
        const { createConsoleScene } = await import('./console-scene');
        showLoad(0.2);
        stage = createConsoleScene(canvas, {
            mobile: window.matchMedia('(max-width: 1023px), (pointer: coarse)').matches,
            glbUrl: host.dataset.glb || null,
            onProgress: (value) => showLoad(value),
        });
        // A failed GLB keeps the procedural desk, so the error is swallowed here on purpose.
        await stage.ready.catch(() => {});
    } catch {
        // No WebGL or the chunk failed: the cover photo stays and the loader is removed.
        loader?.remove();
        return;
    }
    showLoad(0.95);

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
        if (!visible || document.hidden) return;
        stage.state.progress = progress.value;
        stage.render(time);
    });

    // First frame is drawn: crossfade from the photo to the live desk, then power up.
    requestAnimationFrame(() => {
        hero.classList.add('is-3d');
        showLoad(1);
        const tl = gsap.timeline();
        if (loader) tl.to(loader, { autoAlpha: 0, duration: 0.6, ease: 'power2.out', onComplete: () => (loader.hidden = true) }, 0.2);
        tl.to(stage.state, { intro: 1, duration: 2.6, ease: 'power3.out' }, 0)
            .to(stage.state, { power: 1, duration: 2.2, ease: 'power2.inOut' }, 0.3)
            .to(stage.state, { boot: 1, duration: 2.6, ease: 'none' }, 0.8);
    });
}

export function initHero(hero) {
    const progress = { value: 0 };
    initTitle(hero);
    initCopyStory(hero, progress);
    initStage(hero, progress);
}
