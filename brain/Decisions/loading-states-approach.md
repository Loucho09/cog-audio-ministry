# Loading states approach
Date: 2026-11-25
Status: accepted
Related: [[loading-states]], [[filament-widget-styling]], [[dark-mode]], [[Roadmap]]

Decision:
- Hand-built loading UI: no NProgress or other packages. Progress bar, skeletons, submit guard, reveal and live region are small JS and Blade files in the repo.
- Cross-document View Transitions (`@view-transition`) for page fades, not SPA navigation. No Livewire navigate, Turbo or htmx.
- Scroll reveal must never hide content unless its script is running. Hiding starts only after `reveal.js` sets `reveal-ready` on `<html>`. Safety net: scroll, hash and beforeprint reveal everything.
- Submit buttons are disabled on the next tick, not immediately. The HTML spec leaves a disabled submitter out of form data, so disabling synchronously would drop the clicked button's name and value.
- Forms that stay on the page (target `_blank`, dialog, `data-no-progress`) skip the progress bar and submit guard.
- Submit guard unlocks after 30s so a stuck request cannot leave a button locked forever.
- Filament placeholders use inline styles and Filament CSS variables, per [[filament-widget-styling]]. No custom theme.

Why:
- No new packages needed (CLAUDE.md asks owner approval for packages).
- View Transitions are a native feature; browsers without support just navigate.
- Hidden content with a failed script would leave a blank page. Fallbacks keep the site readable.
- Respect `prefers-reduced-motion` on all animation.

Consequences:
- The JS behaviour has no automated browser tests yet. See [[Backlog]].
