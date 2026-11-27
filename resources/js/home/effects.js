/**
 * Homepage motion effects (plain JS on GSAP, no React).
 *
 * Three ports of React Bits effects:
 * - splitHeading: SplitText lines and words rise out of a mask.
 * - countUp: mono readouts count up to their real value.
 * - spotlight: a card light follows the pointer (fine pointers only).
 *
 * Section entrances, one per section, all on power3.out / expo.out:
 * - faderHeading: the heading slides in and a fader cap travels under it.
 * - patchRows: a patch line draws, then the rows appear.
 * - meters: service rows get small level bars that rise.
 * - strips: church and Sunday strips rise like faders.
 * - hscroll: desktop only, the gallery track pins and scrubs sideways.
 * - cascade: team cards fade in.
 * - led: the join panel's LEDs power on, and the glow follows the pointer.
 * - signalLine: the gutter line draws down, and nodes light as sections enter.
 *
 * Start states are set from JS only, so without JS every element is visible.
 * Every reveal goes through revealWhen(), which also plays when the page is already past the trigger
 * (direct load at an anchor, or a fast jump), so nothing can stay hidden.
 * The caller (index.js) owns the GSAP context, so everything reverts cleanly.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const finePointer = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;

/**
 * Desktop motion: wide screen with a fine pointer. Only here do we pin the hero and gallery, run smooth scroll
 * (Lenis) and load SplitText. Phones and tablets keep native scroll and skip those downloads.
 */
export const desktopMotion = () => window.matchMedia('(min-width: 1024px) and (pointer: fine)').matches;

/**
 * Runs `play` once, when the trigger's start is reached. If the page is already past it (load at an anchor,
 * or a jump), it runs straight away, so an entrance can never be skipped and leave content hidden.
 */
function revealWhen(trigger, start, play) {
    let done = false;
    const fire = () => {
        if (done) return;
        done = true;
        play();
    };
    const st = ScrollTrigger.create({
        trigger,
        start,
        onUpdate: (self) => {
            if (self.progress > 0) fire();
        },
    });
    if (st.progress > 0) fire();
    return st;
}

/**
 * React Bits SplitText: lines rise out of a mask, word by word. Desktop only; the plugin is a dynamic import so
 * phones never download it. The element is shown again as soon as it is split (the caller hides it first).
 */
export async function splitHeading(element, { delay = 0.15 } = {}) {
    const { SplitText } = await import('gsap/SplitText');
    gsap.registerPlugin(SplitText);
    gsap.set(element, { autoAlpha: 1 });
    return SplitText.create(element, {
        type: 'lines,words',
        mask: 'lines',
        linesClass: 'split-line',
        autoSplit: true,
        onSplit(self) {
            self.masks?.forEach((mask) => mask.classList.add('split-word-mask'));
            element.classList.add('is-split');

            return gsap.from(self.words, {
                yPercent: 110,
                rotate: 3,
                duration: 1.2,
                ease: 'expo.out',
                stagger: 0.08,
                delay,
            });
        },
    });
}

/** React Bits CountUp: the visible number counts up; the sr-only copy keeps the final value. */
export function countUp(readout) {
    const value = readout.querySelector('[data-count-value]');
    const target = Number(readout.dataset.count);
    if (!value || !Number.isFinite(target) || target <= 0) return;

    const counter = { n: 0 };
    value.textContent = '0';

    revealWhen(readout, 'top 88%', () =>
        gsap.to(counter, {
            n: target,
            duration: 1.2,
            ease: 'power3.out',
            onUpdate: () => {
                value.textContent = String(Math.round(counter.n));
            },
        }),
    );
}

/** React Bits SpotlightCard: a radial light follows the pointer (--mx / --my, see .fx-spot in app.css). */
export function spotlight(selector) {
    if (!finePointer()) return;

    document.querySelectorAll(selector).forEach((card) => {
        card.addEventListener(
            'pointermove',
            (event) => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--mx', `${((event.clientX - rect.left) / rect.width) * 100}%`);
                card.style.setProperty('--my', `${((event.clientY - rect.top) / rect.height) * 100}%`);
            },
            { passive: true },
        );
    });
}

/** Heading slides in from a short offset; a thin fader cap travels under it, then fades. */
export function faderHeading(wrapper) {
    const heading = wrapper.querySelector('h2');
    if (!heading) return;

    const holder = heading.parentElement;
    const cap = document.createElement('span');
    cap.className = 'fader-cap';
    cap.setAttribute('aria-hidden', 'true');
    holder.append(cap);

    gsap.set(holder, { position: 'relative' });
    gsap.set(heading, { x: -56, autoAlpha: 0 });
    gsap.set(cap, { autoAlpha: 0 });

    const tl = gsap
        .timeline({ paused: true, defaults: { ease: 'expo.out' } })
        .to(heading, { x: 0, autoAlpha: 1, duration: 0.9 })
        .to(cap, { autoAlpha: 1, duration: 0.2 }, 0.1)
        .to(cap, { x: () => Math.max(0, holder.offsetWidth - cap.offsetWidth), duration: 0.9, ease: 'power3.inOut' }, 0.1)
        .to(cap, { autoAlpha: 0, duration: 0.4 }, '>-0.1');

    revealWhen(wrapper, 'top 86%', () => tl.play());
}

/** A patch line draws across the list, then each row is patched in. */
export function patchRows(container) {
    const rows = container.querySelectorAll('[data-patch-row]');
    if (rows.length === 0) return;

    const line = document.createElement('span');
    line.className = 'patch-line';
    line.setAttribute('aria-hidden', 'true');
    container.append(line);

    gsap.set(container, { position: 'relative' });
    gsap.set(line, { scaleX: 0 });
    gsap.set(rows, { autoAlpha: 0, x: -24 });

    const tl = gsap
        .timeline({ paused: true, defaults: { ease: 'expo.out' } })
        .to(line, { scaleX: 1, duration: 1.2, ease: 'power3.inOut' })
        .to(rows, { autoAlpha: 1, x: 0, duration: 0.9, stagger: 0.12 }, '-=0.7');

    revealWhen(container, 'top 82%', () => tl.play());
}

/** Service rows get small level bars beside the day name; they rise as each row enters. */
export function meters(container) {
    const rows = Array.from(container.querySelectorAll(':scope > li'));
    if (rows.length === 0) return;

    rows.forEach((row, rowIndex) => {
        const day = row.querySelector('p');
        if (!day) return;

        const group = document.createElement('span');
        group.className = 'fx-meter';
        group.setAttribute('aria-hidden', 'true');
        for (let i = 0; i < 5; i++) {
            const bar = document.createElement('span');
            bar.className = 'fx-meter-bar';
            bar.style.height = `${40 + ((i * 37 + rowIndex * 23) % 60)}%`;
            group.append(bar);
        }
        day.append(group);

        const bars = row.querySelectorAll('.fx-meter-bar');
        gsap.set(bars, { scaleY: 0 });
        revealWhen(row, 'top 88%', () => gsap.to(bars, { scaleY: 1, duration: 0.6, ease: 'power3.out', stagger: { each: 0.05 } }));
    });
}

/** Channel and Sunday strips rise like faders, one after another. */
export function strips(container) {
    const items = container.querySelectorAll('[data-strip]').length
        ? container.querySelectorAll('[data-strip]')
        : container.querySelectorAll(':scope > li');
    if (items.length === 0) return;

    gsap.set(items, { autoAlpha: 0, y: 48 });
    revealWhen(container, 'top 84%', () =>
        gsap.to(items, { autoAlpha: 1, y: 0, duration: 0.9, ease: 'expo.out', stagger: 0.12 }),
    );
}

/** Desktop only: the section pins and the track slides sideways as you scroll. Mobile keeps the grid. */
export function hscroll(track) {
    const section = track.closest('section');
    if (!section) return;

    gsap.set(section, { overflow: 'hidden' });
    gsap.set(track, { overflow: 'visible' });

    const distance = () => Math.max(0, track.scrollWidth - track.clientWidth);

    gsap.to(track, {
        x: () => -distance(),
        ease: 'none',
        scrollTrigger: {
            trigger: section,
            start: 'top 96px',
            end: () => `+=${distance()}`,
            pin: true,
            scrub: 1,
            invalidateOnRefresh: true,
        },
    });
}

/** Team cards fade in with a small stagger (opacity only). */
export function cascade(list) {
    const items = list.querySelectorAll('[data-cascade-item]');
    if (items.length === 0) return;

    gsap.set(items, { autoAlpha: 0 });
    revealWhen(list, 'top 88%', () => gsap.to(items, { autoAlpha: 1, duration: 0.9, ease: 'power3.out', stagger: 0.12 }));
}

/**
 * The join panel's LEDs power on in sequence; with a fine pointer, a glow follows the cursor.
 * Returns the undo for its window pointermove listener (called by index.js on unload).
 */
export function led(panel) {
    const core = panel.querySelector('.led-core');
    const ticks = panel.querySelectorAll('[data-led-tick]');
    const card = panel.firstElementChild;

    gsap.set(ticks, { autoAlpha: 0 });
    if (core) gsap.set(core, { autoAlpha: 0, scale: 0.4 });

    const tl = gsap.timeline({ paused: true, defaults: { ease: 'power3.out' } });
    if (core) tl.to(core, { autoAlpha: 1, scale: 1, duration: 0.6, ease: 'expo.out' });
    tl.to(ticks, { autoAlpha: 1, duration: 0.3, stagger: 0.06 }, core ? '-=0.2' : 0);

    revealWhen(panel, 'top 85%', () => tl.play());

    if (!card || !finePointer()) return null;

    card.classList.add('led-panel');
    const glow = document.createElement('span');
    glow.className = 'led-glow';
    glow.setAttribute('aria-hidden', 'true');
    card.prepend(glow);

    const onMove = (event) => {
        const rect = card.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        const distance = Math.hypot(x - rect.width / 2, y - rect.height / 2);
        card.style.setProperty('--lx', `${x}px`);
        card.style.setProperty('--ly', `${y}px`);
        card.style.setProperty('--lk', String(Math.max(0, 1 - distance / 420).toFixed(3)));
    };
    window.addEventListener('pointermove', onMove, { passive: true });

    return () => window.removeEventListener('pointermove', onMove);
}

/** The gutter line draws down with the page; each section's node lights when the section enters. */
export function signalLine(line) {
    const wrap = line.parentElement;
    if (!wrap) return;

    gsap.fromTo(
        line,
        { scaleY: 0 },
        {
            scaleY: 1,
            transformOrigin: 'top top',
            ease: 'none',
            scrollTrigger: { trigger: wrap, start: 'top top', end: 'bottom bottom', scrub: true },
        },
    );

    wrap.querySelectorAll(':scope > section').forEach((section) => {
        ScrollTrigger.create({
            trigger: section,
            start: 'top 60%',
            end: 'bottom 60%',
            onToggle: (self) => section.classList.toggle('is-signal', self.isActive),
        });
    });
}
