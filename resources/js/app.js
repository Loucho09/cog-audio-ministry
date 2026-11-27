

import Alpine from 'alpinejs';
import { initSubmitGuard } from './submit-guard';
import { initPageProgress } from './page-progress';
import { initReveal } from './reveal';
import { initNextService } from './home/next-service';

window.Alpine = Alpine;

Alpine.start();

// Each init is isolated so one failure never breaks the others (e.g. leaves buttons or content stuck).
// Order matters: the submit guard's double-submit block must run before the progress bar's submit listener.
[initReveal, initSubmitGuard, initPageProgress, initNextService].forEach((init) => {
    try {
        init();
    } catch (error) {
        console.error(error);
    }
});

// Homepage motion layer (GSAP, Lenis, Three.js): its own chunk, fetched only on pages that opt in,
// and never with reduced motion. If it fails, the server-rendered page is already complete.
if (document.querySelector('[data-home-fx]') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const startHome = () =>
        import('./home/index')
            .then(({ initHome }) => initHome())
            .catch((error) => console.error(error));

    // Desktop starts at once. Phones wait for the page to load and the browser to go idle, so the first paint
    // (the hero photo) is not competing with the motion code for the main thread.
    if (window.matchMedia('(min-width: 1024px) and (pointer: fine)').matches) {
        startHome();
    } else {
        const whenIdle = () => ('requestIdleCallback' in window ? requestIdleCallback(startHome, { timeout: 1500 }) : setTimeout(startHome, 300));
        if (document.readyState === 'complete') whenIdle();
        else window.addEventListener('load', whenIdle, { once: true });
    }
}
