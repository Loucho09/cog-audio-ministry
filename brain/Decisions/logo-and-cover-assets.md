# Logo and cover assets
Date: 2026-11-24
Status: accepted (owner approved)

Related: [[homepage-sections]], [[image-path-convention]], [[Project-Brief]], [[00-Index]]

Context: Facebook blocks automated fetching, so the owner supplied the official logo and the Facebook cover banner as files.

Decision:
- `public/images/logo.png`: tighter square crop of the official logo for the 40px navbar circle. Cropping only, no redraw (owner approved).
- `public/images/logo-full.png`: full original at 512px.
- Hero cover: `cover.jpg` (fallback) plus `cover-800`, `cover-1200`, `cover-1600` webp files used with `srcset`.
