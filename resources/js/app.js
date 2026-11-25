

import Alpine from 'alpinejs';
import { initSubmitGuard } from './submit-guard';
import { initPageProgress } from './page-progress';
import { initReveal } from './reveal';

window.Alpine = Alpine;

Alpine.start();

// Each init is isolated so one failure never breaks the others (e.g. leaves buttons or content stuck).
// Order matters: the submit guard's double-submit block must run before the progress bar's submit listener.
[initReveal, initSubmitGuard, initPageProgress].forEach((init) => {
    try {
        init();
    } catch (error) {
        console.error(error);
    }
});
