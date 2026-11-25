# Gallery upload fix

Status: done on branch `fix/gallery-upload` (not merged yet, 2026-11-25)
Commits: 7ad8709 (return to list), 933adfb (harden return URL, delete returns to list)
Tests: 435 pass, 1 skip.

Related: [[admin-resources-polish]], [[Backlog]]

## Root causes
1. **Upload size limit (local PHP).** Local `upload_max_filesize` was 2M, but the form allows 4MB. Uploads over 2MB failed with a generic error.
   - Fix: owner's `php.ini` (`C:\Users\Loucho\Downloads\php-8.6.0-Win32-vs18-x64\php.ini`) set to `upload_max_filesize` 8M, `post_max_size` 10M, `memory_limit` 256M. Backup: `php.ini.bak-2026-11-25`.
   - Restart `php artisan serve` after any ini change.
2. **Missing storage symlink.** `public/storage` did not exist, so uploaded images did not show.
   - Fix: `php artisan storage:link`. The link is gitignored, so run it on every machine and in production (see [[Backlog]]).
3. **No return to list after save.** Create, edit and delete left the user on the wrong page and lost the page, tab and filters.

## Fix: return to the list
- Trait `app/Filament/Resources/GalleryImages/Pages/Concerns/ReturnsToGalleryList.php`.
- On `mount`, it stores `url()->previous()` in a `#[Locked]` property, but only if it is the gallery index URL on the same scheme, host and port. Max 2000 characters, printable ASCII only.
- After Create, Edit save and Delete, it redirects to that URL, keeping page, tab, filters and search.
- If the stored URL is missing or fails the check, it goes to the plain index.
- "Create another" is unchanged.
- Reviewer checked open-redirect bypasses: none found.

## Production
- Add to the production checklist (see [[Backlog]]): `upload_max_filesize` at least 8M, `post_max_size` at least 10M, and run `storage:link`.
