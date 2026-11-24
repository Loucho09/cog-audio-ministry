# Homepage hooks tasks
Status: done (owner browser check pending)

Spec: [[homepage-hooks]]
Parent: [[homepage-sections]]

Each slice is about 5 files or fewer. Tests per slice. Commit after each slice.

## Slice 1: hide FAQ on homepage
- [x] Remove `<x-home.faq>` from the welcome view
- [x] Remove the FAQ query from `HomeController`
- [x] Keep `Faq` model, table, seeder, factory and component
- [x] Update tests that expect FAQ on `/`
- [x] Test: `/` does not render FAQ section; FAQ rows still exist in DB

## Slice 2: churches list and impact strip
- [x] Add `churches` list (5 entries, names only) to `config/ministry.php`
- [x] Add impact strip Blade component (churches from config, services per week from `services`, years serving as TODO)
- [x] Place strip right after the hero in the welcome view
- [x] Test: strip shows 5 churches and the active service count; years shows TODO

## Slice 3: "One sound, many churches" section
- [x] Add section Blade component (main church plus 4 of our churches, "behind the sound" story)
- [x] Place section after what we do in the welcome view
- [x] Test: all five church names render; no addresses rendered

## Slice 4: "Stay updated" section
- [x] Add section Blade component (Facebook link from `ministry.facebook_url`, Messenger hidden while `ministry.messenger_url` is empty)
- [x] Place section before contact in the welcome view
- [x] Test: Facebook link renders when set; Messenger hidden when empty

## Review and brain update
- [x] Run `php artisan test` and `./vendor/bin/pint`
- [x] Run `reviewer` on the changes; fixes applied (counter duration, test strength, smaller church logo asset)
- [ ] Check at 360px and desktop, light and dark (owner browser check)
- [x] Update [[homepage-hooks]] status, [[homepage-sections]], [[Roadmap]], [[00-Index]]
