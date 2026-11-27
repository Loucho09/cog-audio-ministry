/**
 * Homepage motion effects. Plain JS + GSAP ports of ideas from React Bits (no React):
 * - splitHeadings: section titles rise word by word out of a mask (SplitText).
 * - scrollWords: a paragraph lights up word by word as you scroll (ScrollReveal).
 * - magnet: buttons lean toward the pointer (Magnet).
 * - tiltSpotlight: cards tilt in 3D with a light that follows the pointer (TiltedCard + SpotlightCard).
 * - velocityMarquee: word bands whose speed and skew follow scroll velocity (ScrollVelocity).
 *
 * All of it is decoration: content is fully readable without it, and none of it runs with reduced motion
 * (the caller checks). Pointer effects only run on devices with a fine pointer and hover.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

const finePointer = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;

export function splitHeadings(selector) {
    document.querySelectorAll(selector).forEach((heading) => {
        SplitText.create(heading, {
            type: 'words',
            mask: 'words',
            wordsClass: 'split-word',
            onSplit(self) {
                return gsap.from(self.words, {
                    yPercent: 115,
                    rotate: 4,
                    duration: 1,
                    ease: 'power4.out',
                    stagger: 0.07,
                    scrollTrigger: { trigger: heading, start: 'top 88%', once: true },
                });
            },
        });
    });
}

export function scrollWords(selector) {
    document.querySelectorAll(selector).forEach((paragraph) => {
        SplitText.create(paragraph, {
            type: 'words',
            onSplit(self) {
                return gsap.fromTo(
                    self.words,
                    { opacity: 0.18 },
                    {
                        opacity: 1,
                        ease: 'none',
                        stagger: 0.05,
                        scrollTrigger: { trigger: paragraph, start: 'top 85%', end: 'bottom 55%', scrub: true },
                    },
                );
            },
        });
    });
}

export function magnet(selector, { strength = 0.3, reach = 70 } = {}) {
    if (!finePointer()) return;

    const items = Array.from(document.querySelectorAll(selector)).map((el) => ({
        el,
        x: gsap.quickTo(el, 'x', { duration: 0.7, ease: 'elastic.out(1, 0.45)' }),
        y: gsap.quickTo(el, 'y', { duration: 0.7, ease: 'elastic.out(1, 0.45)' }),
        active: false,
    }));

    if (items.length === 0) return;

    let frame = 0;
    let pointer = null;

    const update = () => {
        frame = 0;
        items.forEach((item) => {
            const rect = item.el.getBoundingClientRect();
            const dx = pointer.clientX - (rect.left + rect.width / 2);
            const dy = pointer.clientY - (rect.top + rect.height / 2);
            const inside = Math.abs(dx) < rect.width / 2 + reach && Math.abs(dy) < rect.height / 2 + reach;

            if (inside) {
                item.active = true;
                item.x(dx * strength);
                item.y(dy * strength);
            } else if (item.active) {
                item.active = false;
                item.x(0);
                item.y(0);
            }
        });
    };

    window.addEventListener(
        'pointermove',
        (event) => {
            pointer = event;
            frame ||= requestAnimationFrame(update);
        },
        { passive: true },
    );
}

export function tiltSpotlight(selector, { max = 9 } = {}) {
    if (!finePointer()) return;

    document.querySelectorAll(selector).forEach((card) => {
        card.classList.add('fx-spot');
        // GSAP owns the transform now; keep the card's own hover transitions for colour and shadow only.
        card.style.transition = 'box-shadow 300ms, border-color 300ms';
        gsap.set(card, { transformPerspective: 900, transformStyle: 'preserve-3d' });

        const rx = gsap.quickTo(card, 'rotationX', { duration: 0.6, ease: 'power3.out' });
        const ry = gsap.quickTo(card, 'rotationY', { duration: 0.6, ease: 'power3.out' });
        const lift = gsap.quickTo(card, 'y', { duration: 0.5, ease: 'power3.out' });

        card.addEventListener('pointerenter', () => lift(-6));
        card.addEventListener('pointermove', (event) => {
            const rect = card.getBoundingClientRect();
            const px = (event.clientX - rect.left) / rect.width;
            const py = (event.clientY - rect.top) / rect.height;
            ry((px - 0.5) * max * 2);
            rx(-(py - 0.5) * max * 2);
            card.style.setProperty('--mx', `${px * 100}%`);
            card.style.setProperty('--my', `${py * 100}%`);
        });
        card.addEventListener('pointerleave', () => {
            rx(0);
            ry(0);
            lift(0);
        });
    });
}

export function velocityMarquee(selector) {
    document.querySelectorAll(selector).forEach((band) => {
        const rows = Array.from(band.querySelectorAll('[data-marquee-row]')).map((row) => ({
            row,
            direction: Number(row.dataset.direction) || 1,
            speed: Number(row.dataset.speed) || 60, // px per second
            x: 0,
            skew: gsap.quickTo(row, 'skewX', { duration: 0.4, ease: 'power3.out' }),
            setX: gsap.quickSetter(row, 'x', 'px'),
        }));

        let velocity = 0;
        let sign = 1;
        let visible = false;

        ScrollTrigger.create({
            trigger: band,
            start: 'top bottom',
            end: 'bottom top',
            onToggle: (self) => (visible = self.isActive),
            onUpdate: (self) => {
                velocity = self.getVelocity();
                if (Math.abs(velocity) > 20) sign = velocity > 0 ? 1 : -1;
            },
        });

        gsap.ticker.add((time, deltaTime) => {
            velocity *= 0.92; // ease back to the base speed once scrolling stops
            if (!visible) return;
            const boost = 1 + Math.min(Math.abs(velocity) / 220, 9);
            rows.forEach((r) => {
                // Each row holds two identical copies, so wrapping at half its width is seamless.
                const half = r.row.scrollWidth / 2;
                r.x -= r.speed * r.direction * sign * boost * (deltaTime / 1000);
                r.x = gsap.utils.wrap(-half, 0, r.x);
                r.setX(r.x);
                r.skew(gsap.utils.clamp(-12, 12, (velocity / -260) * r.direction));
            });
        });
    });
}
