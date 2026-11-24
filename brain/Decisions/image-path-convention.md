# Image path convention
Date: 2026-11-24
Status: accepted

Related: [[homepage-sections]], [[logo-and-cover-assets]], [[00-Index]]

Decision: how image paths stored in the database are resolved.

Rules:
- A DB path starting with `images/` is a bundled file under `public/`. Resolve with `asset()`.
- Any other path is on the `public` storage disk (Filament uploads). Resolve with `Storage::url()`.

Implementation: `App\Support\ImageUrl`, exposed as `GalleryImage->url` and `TeamMember->photo_url`.
