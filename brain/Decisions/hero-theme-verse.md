# Decision: hero theme verse

Date: 2026-11-24
Requested by: owner (to match the Facebook page)
Status: accepted

Update 2026-11-27: the redesigned hero ([[homepage-redesign]], brand in [[homepage-console-brand]]) still shows the verse in the first copy panel (under the tagline, reference as a mono caption), hidden when empty. Config keys are unchanged.

## Decision
Show a theme verse in the hero, under the tagline. Psalm 66:8 (NIV), a short quotation with its reference.

- Stored in `config/ministry.php` under `verse.text` and `verse.reference`.
- Set from env: `MINISTRY_VERSE_TEXT` and `MINISTRY_VERSE_REFERENCE`.
- Hidden when `verse.text` is empty.
- No verse text lives in Blade views.

## Placement
- Owner chose the hero over a separate band, the join section, or the footer.

## Why
- Owner request, to match the verse on the Facebook page.
- Config keeps the text editable without a code change, as with other ministry copy.

## Open check
- Readability on mobile over the cover photo. See [[Backlog]].

## See also
- [[homepage-sections]]
- [[logo-and-cover-assets]]
- [[Project-Brief]]
