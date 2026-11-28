# Logo and cover assets
Date: 2026-11-24
Status: accepted (owner approved)

Related: [[homepage-sections]], [[image-path-convention]], [[Project-Brief]], [[00-Index]]

Context: Facebook blocks automated fetching, so the owner supplied the official logo and the Facebook cover banner as files.

Decision:
- `public/images/logo.png`: tighter square crop of the official logo for the 40px navbar circle. Cropping only, no redraw (owner approved).
- `public/images/logo-full.png`: full original at 512px.
- Hero cover: `cover.jpg` (fallback) plus `cover-800`, `cover-1200`, `cover-1600` webp files used with `srcset`.

### Update 2026-11-24 (hero layout, commit 41ac1fb)
- Cover is now a full-bleed background behind the hero (`object-cover`), not a banner below the hero text. Previous banner layout superseded.
- Even black/65 overlay; text centered in white. Owner chose "text centered, full overlay" over "text left, logo visible".
- Cover is decorative: `alt=""`, because the h1 already carries the name.

### Update 2026-11-28 (logo WebP, owner decision)
- Header and footer round badge logo stays the same in both themes.
- Served as WebP at 40, 80 and 120px (1x, 2x, 3x) via `srcset`: `public/images/logo-40.webp`, `logo-80.webp`, `logo-120.webp`.
- `public/images/logo.png` kept as fallback, and as the Open Graph and favicon source.
- Wordmark `public/images/logo-full.png` also has `logo-full.webp` (512px, about 34KB vs 191KB PNG).
- Owner may send a dark-background logo file later. Not yet received.
