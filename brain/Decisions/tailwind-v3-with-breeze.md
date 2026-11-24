# Decision: Tailwind v3 with Breeze

Date: 2026-11-24
Status: accepted

## Decision
Keep **Tailwind CSS v3.4** (`tailwind.config.js` + PostCSS). Remove the `@tailwindcss/vite` v4 plugin.

## Why
- Breeze 2.4 (Blade) requires Tailwind v3.
- Laravel 13 ships Tailwind v4 by default.
- Only one version can be used in the build, so v3 was kept to match Breeze.

## Revisit
When Breeze supports Tailwind v4, plan the upgrade as its own task, not during a feature.

## See also
- [[package-breeze]]
- [[public-layout]]
- [[Roadmap]]
