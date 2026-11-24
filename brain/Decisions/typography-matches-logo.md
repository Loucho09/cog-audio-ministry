# Typography matches the logo
Date: 2026-11-24
Status: accepted (owner chose "Montserrat + script accent")

Related: [[Project-Brief]], [[second-brief-merged]], [[Roadmap]], [[Backlog]], [[00-Index]]

Context: Inter did not match the logo. "COG" is a heavy geometric sans. "Audio" is a thin signature script.
Options considered: Montserrat only; Montserrat headings with Inter body; Montserrat + script accent (chosen).

Decision:
- Montserrat for all site text. Headings bold/extrabold (ExtraBold is the closest free match to "COG"). Body regular.
- Mrs Saint Delafield as the script accent (closest free match to "Audio"). Monsieur La Doulaise was compared and rejected as too ornate.
- Tailwind `font-script` is a decorative accent only. Today it is only the word "Audio" in the hero h1. Never use it for body text.
- Fonts load from fonts.bunny.net. No new packages.
- Breeze auth pages are unchanged.

Open points:
- The exact logo fonts are unknown and may be commercial. If the owner learns their names, revisit this decision.
- CLAUDE.md still says "Font: Inter". The owner should update that line (not edited here).
- Phase 7: self-host both fonts (see [[Roadmap]], [[Backlog]]).
