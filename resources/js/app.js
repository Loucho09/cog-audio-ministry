

import Alpine from 'alpinejs';
import { initSubmitGuard } from './submit-guard';
import { initPageProgress } from './page-progress';

window.Alpine = Alpine;

Alpine.start();

// Order matters: the submit guard's double-submit block must run before the progress bar's submit listener.
initSubmitGuard();
initPageProgress();
