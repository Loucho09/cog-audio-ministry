/**
 * Scroll reveal for elements with data-reveal (see the rule in resources/css/app.css).
 *
 * Each element is revealed once, the first time at least a little of it is on screen:
 * the script sets data-revealed, and the CSS fades and raises it. data-reveal-delay="80" adds a
 * stagger (ms). Reduced motion, or no IntersectionObserver: everything is revealed straight away.
 * Written by hand (no package) on purpose.
 */

const SELECTOR = '[data-reveal]';

export function initReveal() {
    const items = Array.from(document.querySelectorAll(SELECTOR));

    if (items.length === 0) {
        return;
    }

    const reveal = (element) => element.setAttribute('data-revealed', '');

    items.forEach((element) => {
        const delay = Number(element.dataset.revealDelay);

        if (delay > 0) {
            element.style.setProperty('--reveal-delay', `${delay}ms`);
        }
    });

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (reducedMotion.matches || !('IntersectionObserver' in window)) {
        items.forEach(reveal);
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        // A small bottom margin means cards reveal a moment after they enter, not on the first pixel.
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
    );

    items.forEach((element) => observer.observe(element));
}
