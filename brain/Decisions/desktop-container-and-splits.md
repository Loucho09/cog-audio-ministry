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

## Update 2026-11-24 (polish pass)
- Hero photo: on `lg` it is width-fit and pinned to the bottom (`lg:top-auto lg:h-auto lg:max-h-full`), with a mask fading its top edge. Reason: at 1024-1280px a top-pinned photo put its own "COG Audio" wordmark behind the h1. Below `lg` the photo uses `object-left`, so the wordmark is cropped off and the mixing console shows behind the centred text.
- What We Do panel and services spotlight/upcoming cards stretch to equal height on `lg` (`items-stretch`). Weekly schedule toggle is centred to match the centred section header.
- Contact cards (no-address branch) are one column until `xl`, `max-w-xl` below `lg`, so the email never breaks mid-word. Footer `md` grid is `minmax(0,1.3fr) minmax(0,1fr) auto` so the email fits.
- Church card name wraps with the Main church badge (`flex-wrap`, `break-words`). Navbar brand name is 13px `tracking-tight` on mobile so it fits at 360px.
- Checks: Playwright at 360, 768, 1024, 1280, 1440 and 1920px in light and dark; no horizontal scroll; tests pass; reviewer found nothing blocking.

## Update 2026-11-24 (mobile polish)
- Bug fixed: at 360px the contact cards overflowed the viewport (right edge ~397px on a 345px page, clipped by `overflow-hidden`). Cause: CSS grid with no explicit columns uses an auto track sized to min-content, i.e. the full email width. Fix: `grid-cols-1` (`minmax(0,1fr)`) on the contact grids.
- Rule of thumb: give grids that hold long unbreakable text an explicit `grid-cols-*` so the track can shrink.
- Email in the contact card renders as escaped text with `<wbr>` after "@", so it wraps cleanly ("cogdasmaaudio@" / "gmail.com"). `text-sm sm:text-base` (relative units, follows the user's font size).
- Impact: 2 stats sit side by side on mobile (`grid-cols-2`), tighter padding, xs labels.
- Churches list: two columns on mobile; an odd last card spans both columns.
- Result: page 255px shorter at 360px. Checked light and dark. No horizontal overflow. Tests pass.
