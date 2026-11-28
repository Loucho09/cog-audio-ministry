/**
 * Hero: title reveal, the 3D desk, the five-chapter scroll story, and the Join us button.
 * Spec: brain/Features/hero-console-story.md.
 *
 * - Title: desktop only, SplitText lines rise out of a mask (effects.js splitHeading). Phones fade the block up.
 * - 3D: a dynamic import after load, idle and the first interaction (or 2.5s). Gated by canRun3D() (tier: desktop,
 *   medium for tablets, light for phones) and an FPS check over the first second of drawing (startDesk). Tablets
 *   and phones get the stage box only (no scroll pin yet). A failed check or a missing WebGL2 keeps the poster.
 *   Tiers and settings: brain/Decisions/3d-on-tablets-and-phones.md and console-scene.js.
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
const MIN_FPS = 45; // the live desk must reach this over its first second of rendering, or the poster stays
const MIN_MEMORY_GB = 4; // navigator.deviceMemory, where the browser reports it (Safari and Firefox do not)
const TABLET_QUERY = '(min-width: 768px)';
// Every breakpoint the desk's tier depends on. Crossing one tears the desk down and re-checks the device.
const BREAKPOINTS = ['(min-width: 768px)', '(min-width: 1024px)', '(pointer: fine)'];

/** Dev-only override: ?force3d=1 on a page rendered with APP_ENV=local (hero.blade.php sets data-dev-3d). */
function forced(host) {
    return host.hasAttribute('data-dev-3d') && new URLSearchParams(window.location.search).get('force3d') === '1';
}

/**
 * 'none' (no WebGL2 context), 'software' (a software renderer such as SwiftShader) or 'hardware'.
 * Hidden renderer names count as hardware.
 */
function probeGpu() {
    try {
        const gl = document.createElement('canvas').getContext('webgl2');
        if (!gl) return 'none';
        const info = gl.getExtension('WEBGL_debug_renderer_info');
        const name = String(info ? gl.getParameter(info.UNMASKED_RENDERER_WEBGL) : '');
        gl.getExtension('WEBGL_lose_context')?.loseContext();
        return SOFTWARE_RENDERER.test(name) ? 'software' : 'hardware';
    } catch {
        return 'none';
    }
}

/**
 * The live desk a device gets, or false: the poster stays and three.js is never requested. These checks run
 * before the chunk loads. The FPS gate runs once the desk is drawing (see startDesk).
 * - 'desktop': 1024px and up with a fine pointer. The full desk, as before (needs 5+ cores).
 * - 'medium': tablets (768px and up, or a coarse pointer from 1024px). The full desk at medium quality.
 * - 'light': phones below 768px. The same desk, lighter (pixel ratio 1.25, almost no bloom, no master section).
 * Shared: no reduced motion, no save-data, WebGL2 on a hardware renderer, deviceMemory >= 4 where reported.
 */
function canRun3D(host) {
    const matches = (query) => window.matchMedia(query).matches;
    if (matches('(prefers-reduced-motion: reduce)')) return false;
    if (navigator.connection?.saveData) return false;
    const override = forced(host);
    if (navigator.deviceMemory !== undefined && navigator.deviceMemory < MIN_MEMORY_GB && !override) return false;
    const tier = desktopMotion() ? 'desktop' : matches(TABLET_QUERY) ? 'medium' : 'light';
    if (tier === 'desktop' && (navigator.hardwareConcurrency || 0) < 5 && !override) return false;
    const gpu = probeGpu();
    if (gpu === 'none') return false;
    return gpu === 'hardware' || override ? tier : false;
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
    // Decided at load: a window that starts narrow and is widened later keeps the static hero (accepted trade-off,
    // so phones never download SplitText).
    if (panels.length < 5 || !desktopMotion()) return;

    const { SplitText } = await import('gsap/SplitText');
    gsap.registerPlugin(SplitText);
    // Split only once the brand fonts are in, so the masked lines break where the reader sees them.
    await document.fonts?.ready;

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
                    // While the CTA row is faded out it must not be clickable or focusable. The headline stays in the
                    // accessibility tree (only its opacity changes).
                    onUpdate: (self) => {
                        if (cta) cta.inert = self.progress > 0.04 && self.progress < 0.8;
                    },
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
            tl.to([head, cta], { opacity: 0, y: -24, duration: 0.5, ease: 'power2.in' }, 0.1);
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
            tl.fromTo(cta, { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out', immediateRender: false }, 7.9);
            tl.to({}, { duration: 1.1 }, 8.9); // a short hold before the unpin
        });
    });

    ScrollTrigger.sort();
    ScrollTrigger.refresh();
}

const CHECK_TIMEOUT_MS = 8000; // the desk must pass the FPS check within this much visible time, or the poster stays
const CHECK_GRACE_MS = 250; // after frame 3, shader compile hitches are not counted

/**
 * Starts one live desk on the current canvas at the given tier. Returns a handle:
 * - handle.teardown(status) stops the loop, observers and listeners, disposes the scene (GPU objects first, then the
 *   WebGL context), swaps in a fresh canvas (a released context cannot be reused), hides the loader, removes is-3d
 *   and resets the desk3d data attributes. `status` is what the stage reports afterwards ('poster' by default).
 * - handle.dead is true once the desk has ended (teardown, failed check, timeout, context loss, error).
 * - handle.tier is the tier it was started with.
 *
 * FPS gate (every tier), measured on rendered frames only:
 * - Frames 1 and 2 are ignored (shader upload and first-frame warm-up).
 * - From frame 3, a 250ms grace period is skipped (shader compile hitches are not counted).
 * - Then a 1000 ms window: each rendered frame is counted; fps = counted frames / elapsed seconds.
 * - Below MIN_FPS the desk is torn down and the poster stays.
 * - Paused frames (hero off-screen, tab hidden) are not counted, and the check restarts when the desk is back on screen.
 * - Hard timeout: 8s of visible time without a pass tears the desk down to the poster and hides the loader.
 * - The canvas stays hidden while the check runs; the crossfade starts when the check passes.
 * - The phone tier is capped at 30fps only after the check passes (the check itself runs uncapped).
 * Dev override (?force3d=1 on APP_ENV=local): measured and logged but never fails.
 */
function startDesk(hero, host, loader, story, tier) {
    const canvas = host.querySelector('canvas');
    const override = forced(host);
    const handle = { dead: false, tier, teardown: () => {} };

    let stopped = false;
    let scene = null;
    let tick = null;
    let introTl = null;
    let timeout = 0;
    let visible = true;
    let intersection = null;
    let resize = null;
    const listeners = [];
    const listen = (target, type, handler, options) => {
        target.addEventListener(type, handler, options);
        listeners.push(() => target.removeEventListener(type, handler, options));
    };
    const check = { status: 'measuring', seen: 0, count: 0, since: 0, grace: 0, fps: 0 };
    const resetCheck = () => {
        check.seen = 0;
        check.count = 0;
        check.since = 0;
        check.grace = 0;
    };

    // Stops drawing and releases observers, listeners and timers. Used at teardown.
    const stopRendering = () => {
        if (tick) gsap.ticker.remove(tick);
        tick = null;
        clearTimeout(timeout);
        intersection?.disconnect();
        resize?.disconnect();
        intersection = null;
        resize = null;
        listeners.splice(0).forEach((remove) => remove());
    };

    const teardown = (status = 'poster') => {
        if (handle.dead) return;
        handle.dead = true;
        stopped = true;
        introTl?.kill();
        stopRendering();
        scene?.dispose(); // GPU objects, then the WebGL context (console-scene.js)
        scene = null;
        // A released context cannot be reused: the next desk on this stage gets a fresh canvas element.
        const old = host.querySelector('canvas');
        old?.replaceWith(old.cloneNode(false));
        hero.classList.remove('is-3d');
        if (loader) loader.hidden = true;
        delete hero.dataset.desk3dFps;
        delete hero.dataset.desk3dTier;
        hero.dataset.desk3dStatus = status;
    };
    handle.teardown = teardown;

    // Gives up on a check that never passes (slow import, a GPU that never gets to the threshold, a stuck frame loop).
    // Time while the hero is off-screen does not count: the check is paused then, so the timer waits too.
    const onTimeout = () => {
        if (handle.dead || check.status === 'passed') return;
        if (!visible || document.hidden) {
            timeout = setTimeout(onTimeout, 1000);
            return;
        }
        teardown('poster');
    };
    timeout = setTimeout(onTimeout, CHECK_TIMEOUT_MS);

    const start = async () => {
        const readout = loader?.querySelector('[data-hero-percent]');
        const bar = loader?.querySelector('[data-hero-meter]');
        const load = { value: 0 };
        const showLoad = (value) => {
            load.value = Math.max(load.value, value);
            if (readout) readout.textContent = String(Math.round(load.value * 100)).padStart(3, '0');
            if (bar) gsap.set(bar, { scaleX: load.value });
        };

        hero.dataset.desk3dTier = tier;
        hero.dataset.desk3dStatus = 'loading';
        if (loader) loader.hidden = false;
        showLoad(0.1);

        try {
            // three.js and the scene are requested only here: after first paint, idle, and the checks above.
            const { createConsoleScene } = await import('./console-scene');
            if (stopped) return;
            showLoad(0.2);
            scene = createConsoleScene(canvas, { quality: tier, glbUrl: host.dataset.glb || null, onProgress: showLoad });
            // A failed GLB keeps the procedural desk, so the error is swallowed here on purpose.
            await scene.ready.catch(() => {});
        } catch {
            // No WebGL, or the chunk failed to load: the poster stays.
            teardown('error');
            return;
        }
        if (stopped || !scene) return;
        const live = scene;
        showLoad(0.95);

        // Context loss: the poster comes back and nothing is rebuilt (the desk is re-checked on the next breakpoint).
        listen(canvas, 'webglcontextlost', (event) => {
            event.preventDefault();
            teardown('lost');
        });

        intersection = new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            // The loader goes with the desk: hidden while the hero is off-screen, back while it is still loading.
            if (loader && check.status !== 'passed') loader.hidden = !visible;
        });
        intersection.observe(hero);
        resize = new ResizeObserver(() => live.resize());
        resize.observe(canvas);

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

        // Load-in: the desk comes up to standby after the crossfade; the story (scroll) takes it from there.
        const intro = { power: 0 };
        let lastFrame = 0;
        const reveal = () => {
            clearTimeout(timeout);
            hero.classList.add('is-3d');
            showLoad(1);
            const tl = (introTl = gsap.timeline());
            if (loader) tl.to(loader, { autoAlpha: 0, duration: 0.6, ease: 'power2.out', onComplete: () => (loader.hidden = true) }, 0.2);
            tl.to(live.state, { intro: 1, duration: 2.6, ease: 'power3.out' }, 0).to(intro, { power: STANDBY, duration: 2.2, ease: 'power2.inOut' }, 0.3);
        };

        const measure = (now) => {
            check.seen += 1;
            if (check.seen < 3) return; // frames 1 and 2: warm-up, ignored
            if (check.seen === 3) {
                check.grace = now + CHECK_GRACE_MS;
                return;
            }
            if (now < check.grace) return;
            if (check.since === 0) {
                check.since = now; // the 1s window starts at the first frame after the grace period
                return;
            }
            check.count += 1;
            const elapsed = now - check.since;
            if (elapsed < 1000) return;
            check.fps = (check.count * 1000) / elapsed;
            hero.dataset.desk3dFps = check.fps.toFixed(1);
            if (check.fps >= MIN_FPS || override) {
                check.status = 'passed';
                hero.dataset.desk3dStatus = 'live';
                reveal();
            } else {
                check.status = 'failed';
            }
        };

        tick = (time) => {
            if (!visible || document.hidden) {
                // Paused: a check in progress restarts when the desk is back on screen.
                if (check.status === 'measuring') resetCheck();
                return;
            }
            if (check.status === 'measuring') {
                measure(performance.now());
                if (check.status === 'failed') {
                    queueMicrotask(() => teardown('failed')); // not from inside the ticker's own loop
                    return;
                }
            }
            if (tier === 'light' && check.status === 'passed' && time - lastFrame < 1 / 30) return;
            lastFrame = time;
            live.state.power = Math.max(intro.power, story.power);
            live.state.boot = story.boot;
            live.state.shot = story.shot;
            live.state.pastor = story.pastor;
            live.state.mix = story.mix;
            live.render(time);
        };
        gsap.ticker.add(tick);
    };

    start().catch((error) => {
        console.error(error);
        teardown('error');
    });
    return handle;
}

/**
 * Owns the 3D desk for this hero. Waits for load, idle and the first interaction (afterIdleAndInteraction), then
 * starts a desk for the current device (canRun3D). Breakpoint changes (768px, 1024px, pointer) are coalesced into one
 * check per animation frame. A desk that is already running at the same tier is kept; a different tier (or no tier)
 * tears it down first. Returns the stop function (pagehide).
 */
function initStage(hero, story) {
    const host = hero.querySelector('[data-hero-stage]');
    const loader = hero.querySelector('[data-hero-loader]');
    if (!host?.querySelector('canvas')) return () => {};

    let current = null;
    let armed = false;
    let stopped = false;
    let frame = 0;
    const mediaQueries = BREAKPOINTS.map((query) => window.matchMedia(query));
    const alive = () => Boolean(current && !current.dead);

    const reconcile = () => {
        frame = 0;
        if (stopped || !armed) return;
        const tier = canRun3D(host);
        if (alive() && current.tier === tier) return; // same tier: keep the desk running
        current?.teardown('poster');
        current = null;
        if (!tier) {
            hero.dataset.desk3dStatus = 'poster';
            return;
        }
        current = startDesk(hero, host, loader, story, tier);
    };
    const onBreakpoint = () => {
        if (frame || stopped) return;
        frame = requestAnimationFrame(reconcile);
    };

    hero.dataset.desk3dStatus = 'waiting';
    mediaQueries.forEach((mq) => mq.addEventListener('change', onBreakpoint));
    afterIdleAndInteraction().then(() => {
        armed = true;
        reconcile();
    });

    return () => {
        stopped = true;
        cancelAnimationFrame(frame);
        mediaQueries.forEach((mq) => mq.removeEventListener('change', onBreakpoint));
        current?.teardown('poster');
        current = null;
    };
}

/**
 * Returns the teardown for the 3D stage. The title and story live in the caller's gsap context; the Join us
 * button's teardown goes into `disposers` (released on a real unload).
 */
export function initHero(hero, ctx, disposers = []) {
    const story = { power: 0, boot: 0, shot: 0, pastor: 0, mix: 0 };
    initTitle(hero);
    initStory(hero, story, ctx).catch((error) => console.error(error));
    const join = hero.querySelector('[data-join-cta]');
    // The button lives until a real unload (it must keep working after a back-forward cache restore).
    if (join) disposers.push(initJoinButton(join));
    return initStage(hero, story);
}
