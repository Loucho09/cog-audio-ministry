/**
 * Hero: title reveal, the 3D desk (boot after first paint), its loader, and the pinned copy story.
 *
 * - Title: desktop only, SplitText lines rise out of a mask (effects.js splitHeading). Phones fade the block up.
 * - 3D: a dynamic import after load, idle and the first interaction (or 2.5s). Gated by canRun3D(); without it the
 *   pre-rendered console poster stays (see the hero component). Rendering pauses off-screen and in hidden tabs.
 * - Context loss: on webglcontextlost the poster comes back and drawing stops. A restored context is not rebuilt.
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
import { desktopMotion, splitHeading } from './effects';

const SOFTWARE_RENDERER = /swiftshader|llvmpipe|software|basic render/i;

/** True unless the browser reports a software WebGL2 renderer. Hidden renderer names count as hardware. */
function gpuIsHardware() {
    try {
        const gl = document.createElement('canvas').getContext('webgl2');
        if (!gl) return false;
        const info = gl.getExtension('WEBGL_debug_renderer_info');
        const name = String(info ? gl.getParameter(info.UNMASKED_RENDERER_WEBGL) : '');
        gl.getExtension('WEBGL_lose_context')?.loseContext();
        return !SOFTWARE_RENDERER.test(name);
    } catch {
        return false;
    }
}

/**
 * Live 3D only on capable desktops: wide, fine pointer, motion allowed, not saving data, a real GPU.
 * Everything else (phones, tablets, software GL, low-memory machines) keeps the pre-rendered poster and never
 * downloads the three.js chunk.
 */
function canRun3D() {
    const matches = (query) => window.matchMedia(query).matches;
    if (!desktopMotion()) return false;
    if (matches('(prefers-reduced-motion: reduce)')) return false;
    if (navigator.connection?.saveData) return false;
    if ((navigator.hardwareConcurrency || 0) <= 4) return false;
    if (navigator.deviceMemory !== undefined && navigator.deviceMemory < 4) return false;
    return gpuIsHardware();
}

/**
 * Resolves after load and browser idle, then at the first interaction or after 2.5s, whichever comes first.
 * Three.js never competes with first paint or with the reader's first scroll or tap.
 */
function afterIdleAndInteraction() {
    const loaded = new Promise((resolve) => {
        if (document.readyState === 'complete') resolve();
        else window.addEventListener('load', () => resolve(), { once: true });
    });
    const idle = () =>
        new Promise((resolve) => {
            if ('requestIdleCallback' in window) requestIdleCallback(() => resolve(), { timeout: 2500 });
            else setTimeout(resolve, 200);
        });
    const interaction = new Promise((resolve) => {
        ['pointerdown', 'keydown', 'touchstart', 'wheel'].forEach((type) =>
            window.addEventListener(type, () => resolve(), { once: true, passive: true }),
        );
    });
    const timeout = new Promise((resolve) => setTimeout(resolve, 2500));
    return loaded.then(idle).then(() => Promise.race([interaction, timeout]));
}

function initTitle(hero) {
    const title = hero.querySelector('[data-hero-title]');
    const content = hero.querySelector('[data-hero-content]');
    if (!title) return;

    // The CSS fade-up keeps its end state (fill-mode), which would beat GSAP's inline styles. GSAP takes over.
    content?.classList.remove('motion-safe:animate-fade-up');
    const all = content ? Array.from(content.children) : [title];
    const lines = all.filter((el) => el !== title);
    const rise = { y: 28, autoAlpha: 0, duration: 1, stagger: 0.09, delay: 0.35, ease: 'power3.out', clearProps: 'transform' };

    if (desktopMotion()) {
        // Desktop: the headline splits into masked lines. It stays hidden until the plugin has loaded (then shown).
        gsap.set(title, { autoAlpha: 0 });
        splitHeading(title, { delay: 0.15 }).catch(() => gsap.set(title, { autoAlpha: 1 }));
        gsap.from(lines, rise);
    } else {
        // Phones and tablets: no SplitText download, the whole block rises together.
        gsap.from(all, rise);
    }
}

function initCopyStory(hero, progress) {
    const [main, second, third] = hero.querySelectorAll('[data-hero-panel]');
    if (!main) return;

    // Called inside the initHome gsap context, so the matchMedia and its pin are reverted with the page's other effects.
    gsap.matchMedia().add('(min-width: 1024px)', () => {
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

/**
 * Starts the live desk. Returns a teardown that stops the loop and observers, removes the listeners and disposes the
 * stage. Safe to call before the stage exists (e.g. during the idle wait): the start checks `stopped` after each await.
 */
function initStage(hero, progress) {
    const host = hero.querySelector('[data-hero-stage]');
    const canvas = host?.querySelector('canvas');
    const loader = hero.querySelector('[data-hero-loader]');
    if (!canvas || !canRun3D()) return () => {};

    let stopped = false;
    let lost = false;
    let scene = null;
    let tick = null;
    const observers = [];
    const listeners = [];
    const listen = (target, type, handler, options) => {
        target.addEventListener(type, handler, options);
        listeners.push(() => target.removeEventListener(type, handler, options));
    };

    // Stops drawing and releases observers and listeners. The poster stays. Used on context loss and at teardown.
    const stopRendering = () => {
        if (tick) gsap.ticker.remove(tick);
        tick = null;
        observers.splice(0).forEach((observer) => observer.disconnect());
        listeners.splice(0).forEach((remove) => remove());
    };

    const onContextLost = (event) => {
        // preventDefault asks the browser to allow a restore; we keep the poster either way (no rebuild).
        event.preventDefault();
        lost = true;
        stopRendering();
        hero.classList.remove('is-3d');
        if (loader) loader.hidden = true;
    };

    const teardown = () => {
        if (stopped) return;
        stopped = true;
        stopRendering();
        scene?.dispose();
        scene = null;
        hero.classList.remove('is-3d');
    };

    const start = async () => {
        await afterIdleAndInteraction();
        if (stopped) return;

        const readout = loader?.querySelector('[data-hero-percent]');
        const bar = loader?.querySelector('[data-hero-meter]');
        const load = { value: 0 };
        const showLoad = (value) => {
            load.value = Math.max(load.value, value);
            if (readout) readout.textContent = String(Math.round(load.value * 100)).padStart(3, '0');
            if (bar) gsap.set(bar, { scaleX: load.value });
        };

        if (loader) loader.hidden = false;
        showLoad(0.1);

        try {
            const { createConsoleScene } = await import('./console-scene');
            if (stopped) return;
            showLoad(0.2);
            scene = createConsoleScene(canvas, {
                mobile: window.matchMedia('(max-width: 1023px), (pointer: coarse)').matches,
                glbUrl: host.dataset.glb || null,
                onProgress: (value) => showLoad(value),
            });
            // A failed GLB keeps the procedural desk, so the error is swallowed here on purpose.
            await scene.ready.catch(() => {});
        } catch {
            // No WebGL or the chunk failed: the poster stays and the loader is removed.
            loader?.remove();
            return;
        }
        if (stopped || !scene) return;
        const live = scene;
        showLoad(0.95);

        listen(canvas, 'webglcontextlost', onContextLost);

        let visible = true;
        const intersection = new IntersectionObserver(([entry]) => (visible = entry.isIntersecting));
        intersection.observe(hero);
        const resize = new ResizeObserver(() => live.resize());
        resize.observe(canvas);
        observers.push(intersection, resize);

        if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            listen(
                window,
                'pointermove',
                (event) => {
                    live.state.pointerX = (event.clientX / window.innerWidth - 0.5) * 2;
                    live.state.pointerY = -(event.clientY / window.innerHeight - 0.5) * 2;
                },
                { passive: true },
            );
        }

        tick = (time) => {
            if (!visible || document.hidden) return;
            live.state.progress = progress.value;
            live.render(time);
        };
        gsap.ticker.add(tick);

        // First frame is drawn: crossfade from the photo to the live desk, then power up.
        requestAnimationFrame(() => {
            if (stopped || lost) return;
            hero.classList.add('is-3d');
            showLoad(1);
            const tl = gsap.timeline();
            if (loader) tl.to(loader, { autoAlpha: 0, duration: 0.6, ease: 'power2.out', onComplete: () => (loader.hidden = true) }, 0.2);
            tl.to(live.state, { intro: 1, duration: 2.6, ease: 'power3.out' }, 0)
                .to(live.state, { power: 1, duration: 2.2, ease: 'power2.inOut' }, 0.3)
                .to(live.state, { boot: 1, duration: 2.6, ease: 'none' }, 0.8);
        });
    };

    start();
    return teardown;
}

/** Returns the teardown for the 3D stage (the title and copy story live for the page's lifetime). */
export function initHero(hero) {
    const progress = { value: 0 };
    initTitle(hero);
    initCopyStory(hero, progress);
    return initStage(hero, progress);
}
