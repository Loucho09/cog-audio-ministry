# Decision: primary-dark contrast token

Date: 2026-11-24
Approved by: owner
Status: accepted (accent values below are superseded by [[stage-palette]]: accent is now stage red #E11D48 / `accent-text` #BE123C)

## Decision
Add theme token `primary-dark` #0369A1. Use it for filled backgrounds with white text (buttons, skip link, logo placeholder).
Brand tokens are unchanged: `background` #F8FAFC, `surface` #FFFFFF, `text` #0F172A, `primary` #0284C7, `accent` #0EA5E9.

## Why
- White on `primary` #0284C7 is about 4.1:1.
- White on `accent` #0EA5E9 is about 2.8:1.
- Both are below WCAG AA 4.5:1 for normal text.
- `primary-dark` #0369A1 is about 5.9:1.

## Hover rules
- Filled elements: hover goes darker (`hover:bg-text`), never lighter.
- Text links: hover to `primary-dark`.

## Phase 7 note
- The "DASMARIÑAS" eyebrow on the placeholder page uses `text-primary` on `background`.
- Check small-text contrast in the accessibility pass. See [[Roadmap]].

## See also
- [[public-layout]]
