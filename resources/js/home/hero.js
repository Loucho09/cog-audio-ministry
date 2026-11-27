/**
 * Hero: title reveal, the 3D desk, the five-chapter scroll story, and the Join us button.
 * Spec: brain/Features/hero-console-story.md.
 *
 * - Title: desktop only, SplitText lines rise out of a mask (effects.js splitHeading). Phones fade the block up.
 * - 3D: a dynamic import after load, idle and the first interaction (or 2.5s). Gated by canRun3D(): capable
 *   desktops get the full desk; capable phones a lighter one (one bank, no depth of field or SMAA, lower DPR,
 *   30fps). Everything else keeps the pre-rendered poster and never downloads three.js.
 * - Idle (always, subtle): the desk comes up to a low standby, meters play a fake signal, faders drift, the camera
 *   drifts slowly; on fine pointers the desk tilts up to 3.5 degrees toward the cursor.
 * - Story (desktop only): the hero pins for five screens. 0 Silence (headline) -> 1 Power on -> 2 The fader
 *   (PASTOR) -> 3 The mix -> 4 The room (CTA back), then it unpins into About. Each chapter line is split with
 *   SplitText and enters / exits on the same scrubbed timeline as the camera. Without the 3D (software GL, etc.)
 *   the story still runs over the poster.
 * - Context loss: on webglcontextlost the poster comes back and drawing stops. A restored context is not rebuilt.
 * - GLB: if the server rendered data-glb (only when public/models/dlive.glb exists), the model replaces the
 *   procedural desk. Only Meshopt is decoded (no Draco or KTX2 decoder files are shipped).
 *
 * Reduced motion never reaches this file (app.js checks first): the poster and the static page remain.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { desktopMotion, splitHeading } from './effects';
import { initJoinButton } from './join-button';

const SOFTWARE_RENDERER = /swiftshader|llvmpipe|software|basic render/i;
const STANDBY = 0.4; // desk power before chapter 1: dim LEDs and screens, meters still moving

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
 * Live 3D: motion allowed, not saving data, a real GPU. Desktops need 5+ cores; phones and tablets (the lighter
 * desk) need 6+ cores and, where the browser reports it, 4 GB of memory. Returns false, 'desktop' or 'mobile'.
 */
function canRun3D() {
    const matches = (query) => window.matchMedia(query).matches;
    if (matches('(prefers-reduced-motion: reduce)')) return false;
    if (navigator.connection?.saveData) return false;
    if (navigator.deviceMemory !== undefined && navigator.deviceMemory < 4) return false;
    const cores = navigator.hardwareConcurrency || 0;
    const tier = desktopMotion() ? 'desktop' : 'mobile';
    if (cores < (tier === 'desktop' ? 5 : 6)) return false;
    return gpuIsHardware() ? tier : false;
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
    const cta = hero.querySelector('[data-hero-cta]');
    if (!title) return;

    // The CSS fade-up keeps its end state (fill-mode), which would beat GSAP's inline styles. GSAP takes over.
    content?.classList.remove('motion-safe:animate-fade-up');
    const all = [...(content ? Array.from(content.children) : [title]), cta].filter(Boolean);
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

/**
 * The pinned chapter story (desktop). SplitText is loaded first, so the timeline is built a moment after the rest
 * of the page; refreshPriority keeps the hero pin measured before every trigger below it.
 */
async function initStory(hero, story, ctx) {
    const panels = Array.from(hero.querySelectorAll('[data-hero-panel]'));
    const cta = hero.querySelector('[data-hero-cta]');
    if (panels.length < 5 || !desktopMotion()) return;

    const { SplitText } = await import('gsap/SplitText');
    gsap.registerPlugin(SplitText);

    ctx.add(() => {
        gsap.matchMedia().add('(min-width: 1024px)', () => {
            const [head, ...chapters] = panels;
            const parts = new Map(
                chapters.map((panel) => {
                    const line = panel.querySelector('[data-hero-line]');
                    const split = SplitText.create(line, { type: 'lines,words', mask: 'lines', linesClass: 'split-line' });
                    split.masks?.forEach((mask) => mask.classList.add('split-word-mask'));
                    const extras = Array.from(panel.children).filter((el) => el !== line);
                    return [panel, { words: split.words, extras }];
                }),
            );

            const tl = gsap.timeline({
                defaults: { ease: 'none' },
                scrollTrigger: {
                    trigger: hero,
                    start: 'top top',
                    end: '+=500%',
                    scrub: 1,
                    pin: true,
                    anticipatePin: 1,
                    invalidateOnRefresh: true,
                    refreshPriority: 1,
                },
            });

            const enter = (panel, at) => {
                const { words, extras } = parts.get(panel);
                tl.set(panel, { autoAlpha: 1 }, at);
                tl.fromTo(extras, { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.35, ease: 'power2.out', immediateRender: false }, at);
                tl.fromTo(words, { yPercent: 110 }, { yPercent: 0, duration: 0.5, stagger: 0.04, ease: 'power3.out', immediateRender: false }, at + 0.05);
            };
            const exit = (panel, at) => {
                const { words, extras } = parts.get(panel);
                tl.to(words, { yPercent: -110, duration: 0.35, stagger: 0.025, ease: 'power2.in' }, at);
                tl.to(extras, { autoAlpha: 0, duration: 0.3 }, at);
                tl.set(panel, { autoAlpha: 0 }, at + 0.6);
            };
            const move = (prop, from, to, at, duration) =>
                tl.fromTo(story, { [prop]: from }, { [prop]: to, duration, immediateRender: false }, at);

            const [power, fader, mix, room] = chapters;

            // 0 -> 1 Silence to Power on: the headline and CTA leave as the desk boots and the camera pushes in.
            tl.to([head, cta], { autoAlpha: 0, y: -24, duration: 0.5, ease: 'power2.in' }, 0.1);
            move('power', STANDBY, 1, 0.3, 1.6);
            move('boot', 0, 1, 0.5, 1.8);
            move('shot', 0, 1, 0, 2.2);
            enter(power, 0.7);
            exit(power, 2.1);

            // 2 The fader: close on PASTOR, its fader rises, the waveform grows into speech.
            move('shot', 1, 2, 2.3, 1.3);
            move('pastor', 0, 1, 2.6, 1.7);
            enter(fader, 2.8);
            exit(fader, 4.3);

            // 3 The mix: the other channels join one by one; the camera glides along the bay, then pulls wide.
            move('shot', 2, 3, 4.4, 0.8);
            move('shot', 3, 4, 5.2, 1.4);
            move('mix', 0, 1, 4.6, 2.0);
            enter(mix, 4.8);
            exit(mix, 6.5);

            // 4 The room: back to the full desk, the last line and the CTA return, then the hero unpins.
            move('shot', 4, 5, 6.8, 1.4);
            enter(room, 7.4);
            tl.fromTo(cta, { autoAlpha: 0, y: 16 }, { autoAlpha: 1, y: 0, duration: 0.5, ease: 'power2.out', immediateRender: false }, 7.9);
            tl.to({}, { duration: 1.1 }, 8.9); // a short hold before the unpin
        });
    });

    ScrollTrigger.sort();
    ScrollTrigger.refresh();
}

/**
 * Starts the live desk. Returns a teardown that stops the loop and observers, removes the listeners and disposes the
 * stage. Safe to call before the stage exists (e.g. during the idle wait): the start checks `stopped` after each await.
 */
function initStage(hero, story) {
    const host = hero.querySelector('[data-hero-stage]');
    const canvas = host?.querySelector('canvas');
    const loader = hero.querySelector('[data-hero-loader]');
    const tier = canvas ? canRun3D() : false;
    if (!tier) return () => {};
    const mobile = tier === 'mobile';

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
                mobile,
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

        // Load-in brings the desk to standby; the story (scroll) takes it from there.
        const intro = { power: 0 };
        let lastFrame = 0;
        tick = (time) => {
            if (!visible || document.hidden) return;
            if (mobile && time - lastFrame < 1 / 30) return; // phones: 30fps is plenty for an idle desk
            lastFrame = time;
            live.state.power = Math.max(intro.power, story.power);
            live.state.boot = story.boot;
            live.state.shot = story.shot;
            live.state.pastor = story.pastor;
            live.state.mix = story.mix;
            live.render(time);
        };
        gsap.ticker.add(tick);

        // First frame is drawn: crossfade from the poster to the live desk, then come up to standby.
        requestAnimationFrame(() => {
            if (stopped || lost) return;
            hero.classList.add('is-3d');
            showLoad(1);
            const tl = gsap.timeline();
            if (loader) tl.to(loader, { autoAlpha: 0, duration: 0.6, ease: 'power2.out', onComplete: () => (loader.hidden = true) }, 0.2);
            tl.to(live.state, { intro: 1, duration: 2.6, ease: 'power3.out' }, 0).to(intro, { power: STANDBY, duration: 2.2, ease: 'power2.inOut' }, 0.3);
        });
    };

    start();
    return teardown;
}

/**
 * Returns the teardown for the 3D stage. The title, story and Join us button live for the page's lifetime
 * (inside the caller's gsap context).
 */
export function initHero(hero, ctx) {
    const story = { power: 0, boot: 0, shot: 0, pastor: 0, mix: 0 };
    initTitle(hero);
    initStory(hero, story, ctx).catch((error) => console.error(error));
    const join = hero.querySelector('[data-join-cta]');
    if (join) initJoinButton(join);
    return initStage(hero, story);
}
