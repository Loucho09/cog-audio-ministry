# Desktop container and two-column splits
Date: 2026-11-24
Status: accepted
Branch: `feature/desktop-layout`, commit 806ce7d (Phase 1 homepage polish)

Related: [[00-Index]], [[homepage-theme-polish]], [[public-layout]], [[homepage-sections]], [[Backlog]]

## Why
- Owner said the desktop sides looked off.
- At 1440px, sections used `max-w-6xl`, then shrank to `max-w-3xl`, `max-w-4xl` or `max-w-2xl`.
- Result: a narrow strip with mismatched left edges.

## Decision
- One container for the navbar, every homepage section and the footer: `max-w-7xl` with `px-5 sm:px-6 lg:px-8`.
- From `lg` (1024px), sections use column splits on a 12-column grid:
  - Impact: heading left, stats right. The 3 stats use a smaller number size and padding on `lg`.
  - What We Do: vertical tablist `col-span-4` (up/down arrow keys, `aria-orientation` bound via `matchMedia`), panel `col-span-8`.
  - Services: spotlight `col-span-7`, upcoming list `col-span-5`. Each spans 12 when the other is missing.
  - Contact: heading `col-span-4`, cards and map `col-span-8`. Map still shows only when the address is filled.
  - Stay updated: text left, buttons right.
- `section-header` gained `align="split"`: centred below `lg`, left at `lg`.

## Hero
- Cover photo is 1600x609 (very wide).
- With `object-cover` at 1440x765 the left side was cropped, and the COG Audio wordmark in the photo slid behind the hero text.
- Fix: on `lg` the image is width-fit (`h-auto`, `max-h-full`) with a bottom mask fade into the black section.
- Hero min-height on `lg`: `clamp(44rem,85svh,52rem)`. Title uses `2xl:text-8xl`.

## Checks
- Playwright at 360, 1440, 1920 and 2560px: no horizontal scroll.
- Tests: 145 passed, 1 skipped. Pint clean.
- Reviewer run; must-fix items addressed.

## Open items
- See [[Backlog]] (larger cover photo, services column heights, contact map width, contact indent).
