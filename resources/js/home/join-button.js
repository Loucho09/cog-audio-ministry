/**
 * The hero's Join us button (spec: brain/Features/hero-console-story.md). Markup: components/home/join-cta.blade.php.
 *
 * - Meter bars: five bars bounce like a live signal (random heights every ~160ms, ~90ms on hover).
 * - Sonar: every 4s two rings ripple out, 350ms apart. The running border light is CSS (app.css .join-border).
 * - Off-screen (IntersectionObserver): sonar, meters and the border light pause.
 * - Hover (fine pointer): the label scrambles and settles on "Join Us", the arrow slides 5px, the button scales to 1.04.
 * - Magnet (fine pointer): within ~160px the button leans toward the cursor (max 15px), elastic return on leave.
 * - Click: press 0.95 -> 1.04, three sound-wave rings burst, the label scrambles to "Let's go", then the form opens
 *   after ~400ms. Modified clicks (new tab, etc.) are left to the browser.
 *
 * Reduced motion never reaches this file (app.js), so the bars stay static there, as specified.
 */
import gsap from 'gsap';
import { ScrambleTextPlugin } from 'gsap/ScrambleTextPlugin';

gsap.registerPlugin(ScrambleTextPlugin);

const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
const MAGNET_RANGE = 160;
const MAGNET_MAX = 15;

/** Distance from a point to a rectangle (0 inside it). */
function distanceToRect(x, y, rect) {
    const dx = Math.max(rect.left - x, 0, x - rect.right);
    const dy = Math.max(rect.top - y, 0, y - rect.bottom);
    return Math.hypot(dx, dy);
}

export function initJoinButton(wrap) {
    const button = wrap.querySelector('[data-join-button]');
    const label = wrap.querySelector('[data-join-label]');
    const arrow = wrap.querySelector('[data-join-arrow]');
    if (!button || !label) return () => {};

    const bars = Array.from(wrap.querySelectorAll('[data-join-meter] i'));
    const rings = Array.from(wrap.querySelectorAll('[data-join-ring]'));
    const bursts = Array.from(wrap.querySelectorAll('[data-join-burst]'));
    const text = label.textContent.trim();
    const fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const undo = [];
    const listen = (target, type, handler, options) => {
        target.addEventListener(type, handler, options);
        undo.push(() => target.removeEventListener(type, handler, options));
    };

    // Meter bars ------------------------------------------------------------------------------------------
    let hover = false;
    let meterTimer = 0;
    const bounce = () => {
        bars.forEach((bar) =>
            gsap.to(bar, { scaleY: gsap.utils.random(0.18, 1), duration: hover ? 0.08 : 0.14, ease: 'power1.out', overwrite: true }),
        );
        meterTimer = window.setTimeout(bounce, hover ? 90 : 160);
    };
    const stopMeters = () => {
        clearTimeout(meterTimer);
        meterTimer = 0;
    };

    // Sonar: two rings, 350ms apart, once every 4 seconds -----------------------------------------------------
    const sonar = gsap.timeline({ repeat: -1, repeatDelay: 4 - 1.75, paused: true });
    rings.forEach((ring, i) =>
        sonar.fromTo(ring, { scaleX: 1, scaleY: 1, opacity: 0.7 }, { scaleX: 1.14, scaleY: 1.7, opacity: 0, duration: 1.4, ease: 'power2.out' }, i * 0.35),
    );

    // Pause everything while the button is off-screen.
    const observer = new IntersectionObserver(([entry]) => {
        const visible = entry.isIntersecting;
        wrap.classList.toggle('is-offscreen', !visible);
        if (visible) {
            sonar.resume();
            if (!meterTimer) bounce();
        } else {
            sonar.pause();
            stopMeters();
        }
    });
    observer.observe(wrap);

    // Hover and magnet (fine pointers only) ---------------------------------------------------------------
    const scramble = (value, duration = 0.6) =>
        gsap.to(label, { duration, scrambleText: { text: value, chars: CHARS, speed: 0.6 }, overwrite: true });

    if (fine) {
        listen(button, 'mouseenter', () => {
            hover = true;
            scramble(text);
            gsap.to(arrow, { x: 5, duration: 0.3, ease: 'power3.out' });
            gsap.to(button, { scale: 1.04, duration: 0.3, ease: 'power3.out' });
        });
        listen(button, 'mouseleave', () => {
            hover = false;
            gsap.to(arrow, { x: 0, duration: 0.3, ease: 'power3.out' });
            gsap.to(button, { scale: 1, duration: 0.3, ease: 'power3.out' });
        });

        const xTo = gsap.quickTo(wrap, 'x', { duration: 0.4, ease: 'power3.out' });
        const yTo = gsap.quickTo(wrap, 'y', { duration: 0.4, ease: 'power3.out' });
        let engaged = false;
        let returning = null;
        listen(
            window,
            'pointermove',
            (event) => {
                // Off-screen or faded out by the hero story (inert): no magnet, and no layout reads.
                if (wrap.classList.contains('is-offscreen') || wrap.closest('[inert]')) return;
                const rect = button.getBoundingClientRect();
                const near = distanceToRect(event.clientX, event.clientY, rect) < MAGNET_RANGE;
                if (near) {
                    returning?.kill();
                    returning = null;
                    engaged = true;
                    const dx = event.clientX - (rect.left + rect.width / 2);
                    const dy = event.clientY - (rect.top + rect.height / 2);
                    xTo(gsap.utils.clamp(-MAGNET_MAX, MAGNET_MAX, dx * 0.12));
                    yTo(gsap.utils.clamp(-MAGNET_MAX, MAGNET_MAX, dy * 0.2));
                } else if (engaged) {
                    engaged = false;
                    returning = gsap.to(wrap, { x: 0, y: 0, duration: 1.1, ease: 'elastic.out(1, 0.35)', overwrite: true });
                }
            },
            { passive: true },
        );
    }

    // Click: press, burst, "Let's go", then open the form -----------------------------------------------------
    let leaving = false;
    let navigateTimer = 0;
    listen(button, 'click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (leaving) return;
        leaving = true;

        gsap.timeline()
            .to(button, { scale: 0.95, duration: 0.08, ease: 'power2.out' })
            .to(button, { scale: 1.04, duration: 0.25, ease: 'back.out(3)' });
        bursts.forEach((burst, i) =>
            gsap.fromTo(burst, { scale: 0.5, opacity: 0.85 }, { scale: 3.2 + i * 1.1, opacity: 0, duration: 0.7, delay: i * 0.08, ease: 'power2.out' }),
        );
        scramble("Let's go", 0.35);
        navigateTimer = window.setTimeout(() => window.location.assign(button.href), 400);
    });

    // A page restored from the back-forward cache must be clickable again.
    listen(window, 'pageshow', (event) => {
        if (!event.persisted) return;
        clearTimeout(navigateTimer);
        leaving = false;
        gsap.set(button, { scale: 1 });
        label.textContent = text;
    });

    return () => {
        stopMeters();
        clearTimeout(navigateTimer);
        sonar.kill();
        observer.disconnect();
        undo.splice(0).forEach((remove) => remove());
    };
}
