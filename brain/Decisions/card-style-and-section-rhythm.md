# Card style and section rhythm
Status: accepted (2026-11-24)
Related: [[homepage-theme-polish]], [[section-backdrops]], [[stage-palette]], [[00-Index]]

Decision: homepage sections follow two rules. Apply them to all future public sections.

1. Section alternation: sections alternate strictly between `surface` and `background` backgrounds. No two neighbouring sections share a background.
2. Card rule: a card's background is the opposite of its section's background (on a `surface` section, cards use `background`; on a `background` section, cards use `surface`). This keeps cards visible in both themes.

Card style:
- `rounded-3xl`, `border-text/10` (the `text` token flips light in dark mode, so no `dark:border-white` needed; white is only for photo sections), `shadow-sm`.
- Dark mode hover uses a soft glow, not a blur.

Why: the audit found cards blending into their sections and uneven rhythm between sections. Fixed rules avoid per-section guessing.

Consequences: new sections must pick the opposite background from the section before them. Hero and photo sections (white or black text on photos) are exempt.
