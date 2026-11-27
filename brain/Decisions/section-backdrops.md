# Decision: section backdrops

Date: 2026-11-24
Approved by: owner (chose "subtle" over "bold")
Status: accepted

Update 2026-11-27: section backdrops are no longer used on the homepage. See [[homepage-console-brand]] and [[homepage-redesign]]. Component file kept for other pages.

## Decision
Homepage sections get a subtle "stage" backdrop from the component `resources/views/components/site/section-backdrop.blade.php`.

Props: `variant`, `fadeFrom`, `mirror`.

Variants:
- `spotlight-left`: about (team section uses `mirror`)
- `rings`: what we do
- `cone`: services
- `vignette`: gallery
- `grid`: ~~FAQ~~ (obsolete: FAQ removed, see [[faq-removed]])
- `floor`: contact
- Join band: no backdrop

Styles are `.backdrop-*` classes in `@layer components` in `resources/css/app.css`.

## Why
- CSS gradients and masks only. No `blur()` filters, because they are slow on mobile.
- No animation. Static backdrops keep the page calm and respect reduced motion.
- "Subtle" was the owner's choice. Dark mode uses slightly stronger alphas so the effect shows on near-black.

## Rules
- Tailwind v3 removes unused classes from `@layer components`. Variant class names must appear as full literal strings in the Blade files. Never build them by string concatenation.
- `fadeFrom` sets the colour the section fades into at its edge. It assumes the default section order (see below).

## Known limitation
- The seam colours assume the default section order. If the team section is hidden (FAQ section removed, see [[faq-removed]]), the seam between two neighbouring sections may be slightly off. Accepted for now. See [[Backlog]].

## See also
- [[stage-palette]]
- [[homepage-sections]]
