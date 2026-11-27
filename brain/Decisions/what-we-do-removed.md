# What We Do section removed from the homepage
Date: 2026-11-25
Status: superseded 2026-11-27. The What We Do section is restored on `/` per owner, as CH 02 in [[homepage-redesign]] (tokens in [[homepage-console-brand]]). The removal above stays as history.
Related: [[homepage-sections]], [[homepage-theme-polish]], [[Roadmap]], [[00-Index]]

Decision: the "What We Do" section (eyebrow "How we serve", interactive role picker) is removed from `/`. Owner asked for the removal. Reason not stated.

What changed (branch `feature/remove-what-we-do`, commit 8867a58):
- `<x-home.what-we-do />` removed from `resources/views/welcome.blade.php`.
- "What We Do" links removed from the navbar and footer.
- Tests updated: they assert the section and `#what-we-do` links are absent.
- Result: 434 tests, 433 pass, 1 skipped.

Kept on purpose:
- `resources/views/components/home/what-we-do.blade.php` is unused but not deleted. Owner can decide later whether to delete it.

Notes:
- Slices D and E of [[homepage-theme-polish]] are superseded by this removal.
- Homepage section order is in [[homepage-sections]].
