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

    const reveal = (element) => {
        if (element.hasAttribute('data-revealed')) {
            return;
        }

        // Animate only this once, then hand transitions back to the element's own classes.
        element.classList.add('reveal-animating');
        element.setAttribute('data-revealed', '');
        setTimeout(() => element.classList.remove('reveal-animating'), 600 + (Number(element.dataset.revealDelay) || 0));
    };

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
        // threshold 0 (not a ratio): a section taller than the screen could never reach a ratio on a phone.
        // The bottom margin means cards reveal a moment after they enter, not on the first pixel.
        { threshold: 0, rootMargin: '0px 0px -8% 0px' },
    );

    // Only now start hiding: content is never hidden unless this script is running.
    // Anything already on screen is revealed in the same task (below), so it never flickers.
    document.documentElement.classList.add('reveal-ready');
    items.forEach((element) => observer.observe(element));

    // Safety net: anything already scrolled past (fast scroll, jump to #contact) or never observed is shown.
    const revealPassed = () => {
        items.forEach((element) => {
            if (!element.hasAttribute('data-revealed') && element.getBoundingClientRect().top < window.innerHeight) {
                reveal(element);
                observer.unobserve(element);
            }
        });
    };

    window.addEventListener('scroll', revealPassed, { passive: true });
    window.addEventListener('hashchange', revealPassed);
    window.addEventListener('beforeprint', () => items.forEach(reveal));
    revealPassed();
}
