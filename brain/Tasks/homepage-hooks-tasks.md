# Homepage hooks tasks
Status: planned (awaiting owner go-ahead)

Spec: [[homepage-hooks]]
Parent: [[homepage-sections]]

Each slice is about 5 files or fewer. Tests per slice. Commit after each slice.

## Slice 1: hide FAQ on homepage
- [ ] Remove `<x-home.faq>` from the welcome view
- [ ] Remove the FAQ query from `HomeController`
- [ ] Keep `Faq` model, table, seeder, factory and component
- [ ] Update tests that expect FAQ on `/`
- [ ] Test: `/` does not render FAQ section; FAQ rows still exist in DB

## Slice 2: churches list and impact strip
- [ ] Add `churches` list (5 entries, names only) to `config/ministry.php`
- [ ] Add impact strip Blade component (churches from config, services per week from `services`, years serving as TODO)
- [ ] Place strip right after the hero in the welcome view
- [ ] Test: strip shows 5 churches and the active service count; years shows TODO

## Slice 3: "One sound, many churches" section
- [ ] Add section Blade component (main church plus 4 satellites, "behind the sound" story)
- [ ] Place section after the gallery in the welcome view; add section backdrop if it fits [[section-backdrops]]
- [ ] Test: all five church names render; no addresses rendered

## Slice 4: "Stay updated" section
- [ ] Add section Blade component (Facebook link from `ministry.facebook_url`, Messenger hidden while `ministry.messenger_url` is empty)
- [ ] Place section before contact in the welcome view
- [ ] Test: Facebook link renders when set; Messenger hidden when empty

## Review and brain update
- [ ] Run `php artisan test` and `./vendor/bin/pint`
- [ ] Run `reviewer` on the changes; fix issues
- [ ] Check at 360px and desktop, light and dark (owner browser check)
- [ ] Update [[homepage-hooks]] status, [[homepage-sections]], [[Roadmap]], [[00-Index]]
