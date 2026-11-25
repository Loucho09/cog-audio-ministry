# Gallery thumbnails with PHP GD
Status: accepted (2026-11-25)

Related: [[00-Index]], [[Roadmap]], [[homepage-theme-polish]], [[homepage-sections]], [[Backlog]]

## Context
Gallery photos are uploaded at full size (source up to 1600px). The homepage grid and lightbox need smaller WebP files and a `srcset`.

## Decision
- Generate thumbnails with PHP's built-in GD extension in `app/Support/GalleryThumbnail.php`.
- Tiers: 800px and 1600px wide, capped at the photo width. Format: WebP.
- Stored on the public disk as `thumbs/gallery/<sha1(path)>-<width>.webp`.
- Made on create and path change (`GalleryImage` model events). Deleted when no row uses the photo.
- `HomeController` lists the thumbs directory once per request and passes the `srcset`.
- Backfill with `php artisan gallery:thumbnails`. Run once on production after deploy.

## Why GD, not a package such as Intervention Image
- No new Composer dependency. `CLAUDE.md` requires asking before adding packages.
- Trade-off: GD is lower level, so the safety checks are written in the project.
- Server requirement: the GD extension must be enabled on production (`php -m`).

## Why the width is in the filename, not a DB column
- No schema change, so no migration touches existing data. `CLAUDE.md` requires asking before such changes.
- Trade-off: the homepage lists the thumbs directory. This is fine at gallery size.

## Safety checks
- 24 MP pixel cap, read from the image header before decoding (memory guard).
- JPEG EXIF orientation parsed without the `exif` extension.
- Paths with `..`, backslashes or absolute paths are rejected.
