# Homepage sections
Status: in progress
Phase: 1

Related: [[00-Index]], [[Roadmap]], [[Project-Brief]], [[public-layout]], [[homepage-hooks]] (new planned slices: impact strip, satellites story, stay updated, FAQ hidden)

Goal: build the homepage from Blade components, in the order set in [[Project-Brief]]: hero, about us, what we do, meet the team (behind a config flag, OFF by default), upcoming services, gallery (Alpine lightbox), join us CTA, ~~FAQ (Alpine accordion)~~ (obsolete: FAQ removed, see [[faq-removed]]), contact (map only when the address is set), footer.

Users: visitor (public pages), member (same pages when logged in, no extra content yet)

Screens / routes:
- `/` named `home`, served by `HomeController`
- Homepage section components under `resources/views/components/` (one component per section)

Data changes (tables, columns):
- `branches`: id, name, slug (unique), address (nullable), contact fields (nullable), is_main (boolean)
- `services`: id, branch_id (FK), name, day_of_week, start_time, is_active; unique (branch_id, day_of_week, start_time)
- `gallery_images`: id, branch_id (nullable, see Backlog), image path, alt text, sort order, is_active
- ~~`faqs`: id, question, answer, sort order, is_active~~ (obsolete: FAQ removed, see [[faq-removed]])
- `team_members`: id, branch_id (nullable, see Backlog), name, role, employment type (full-time first), photo path, is_featured, sort order
- Models and seeders for all five tables. Content seeders use `firstOrCreate` ([[seeders-first-or-create]]).
- Image path rule: [[image-path-convention]]
- Timezone for service times: [[timezone-asia-manila]]
- Branch table decision: [[branches-table-in-phase-1]]
- Logo and cover assets: [[logo-and-cover-assets]]

Permissions:
- Public read only. Homepage and its data are readable by visitors and members.
- No write access from the public site. Editing content is in Filament (later slice or Phase 2 admin rules).
- Meet the Team visibility is controlled by `config/ministry.php` (default OFF).

Slices:
1. Data (done): tables, models, seeders, `App\Actions\UpcomingServices`. See [[homepage-sections-tasks]].
2. Hero, about us, what we do (done).
   - Hero (updated 2026-11-24, commit 41ac1fb): cover photo is a full-bleed background (`object-cover`) with an even black/65 overlay and centered white text. Decorative image (`alt=""`). See [[logo-and-cover-assets]].
   - ~~Hero cover shown as a banner below the hero text~~ (superseded 2026-11-24 by the full-bleed background above).
3. Upcoming services and gallery with Alpine lightbox, `HomeController` (~~in progress~~ done, see [[homepage-sections-tasks]]).
4. Join us CTA, FAQ accordion, contact with map only when address is set, meet the team behind the config flag (~~planned~~ done, commit 1c8d658; browser check pending).
   - Join Us CTA links to `#contact` until the Phase 3 application form exists (owner choice, 2026-11-24). Superseded 2026-11-24: the button now opens the Google Join Form in a new tab (`ministry.join_form_url`, env `MINISTRY_JOIN_FORM_URL`); falls back to `#contact` if the value is empty. See [[join-form-link]]. Phase 3 must replace this link.
   - Hero "Join Us" button now points to `#join`.
   - ~~FAQ section is hidden when there are no published FAQs.~~ (obsolete: FAQ removed, see [[faq-removed]])
   - Contact items are hidden when their config value is null.
   - Map is a Google Maps iframe embed (no API key), shown only when `MINISTRY_ADDRESS` is set.
   - Meet the Team is behind `ministry.show_team` (default OFF).
   - Owner-confirm copy added (pending owner sign-off): Join Us heading and body, including the Tagalog line "Halina at sumama sa amin!"; Contact intro "We would love to hear from you."
   - Status stays "in progress" until the mobile (360px) and dark-mode browser check is done. Blocked on MySQL being connected.
5. Polish pass, stage backdrops and theme verse (done 2026-11-24, commits 0e95da6, 6abf5bb, 93afab1, ce75b8a, 7cd3941, 21e7723; browser check pending).
   - Shared section header, glow, eq-bars and wave-divider components. Transparent navbar over the hero. Lightbox teleported above the navbar. AA contrast on small labels (`text-text/70`). Reduced-motion and forced-colors fallbacks.
   - Contact fallback: "Contact details are coming soon." when all contact fields are empty.
   - Subtle stage backdrops per section: [[section-backdrops]], colours in [[stage-palette]].
   - Hero theme verse (Psalm 66:8, NIV) from config: [[hero-theme-verse]].
   - Status stays "in progress" until the owner's browser check (360px and desktop, light and dark) is done.

6. Homepage hooks (done 2026-11-24, see [[homepage-hooks]]; browser check pending).
   - FAQ section hidden from the homepage. `Faq` model, table, seeder and component kept. (obsolete: FAQ removed 2026-11-25, see [[faq-removed]])
   - Section order (updated 2026-11-25): hero, impact, about, churches, team (flag), services, gallery, join, stay-updated, contact. What We Do removed at owner request, see [[what-we-do-removed]].
7. Gallery with real photos (done 2026-11-24, branch `feature/gallery-photos`, commit 4cc3e4d; merged to main in b3415eb; browser check pending).
   - 6 real photos in `public/images/gallery/01-team-on-stage.webp` to `06-behind-the-scenes.webp`. WebP, 1600px long edge, q80, about 800KB total. Converted with PHP GD.
   - `GallerySeeder` has a `CAPTIONS` map (filename to title and alt text) with sort order. `04-outreach-day` (landscape) is last so it gets the wide 16:9 tile on mobile. When real photos are created, placeholder rows get `is_published = false`; the seeder only does this when new real photos were created, so Filament re-publishes survive a re-seed. Rows are never deleted.
   - Bento layout: with 5+ photos the first is featured (2x2 on md and up, full width on mobile). Mobile and md+ tile spans are computed separately. Desktop grid stays 3 columns. Last tile stretches to avoid orphans.
   - Captions: always visible below md. On md and up only the featured caption is visible; others show on hover or focus. Empty title falls back to alt text.
   - Reviewer run. Fixed: mobile grid gap with 5, 7 or 11 photos; seeder re-publish guard; empty-title fallback.
   - Remaining before live: owner confirms consent from everyone pictured, including parents of children. Re-run `php artisan db:seed --class=GallerySeeder` on MySQL (the local preview server used a separate SQLite DB).
   - Gallery status: done (code). Owner consent and MySQL re-seed still open.

Owner-confirm copy (placeholders until the owner confirms):
- Hero sentence
- About intro (vision and mission still pending)
- What We Do role descriptions (FOH, Monitors, Livestream, Stage Tech)
- ~~Placeholder FAQ answers~~ (obsolete: FAQ removed, see [[faq-removed]])

Out of scope:
- Filament resources for services, gallery, FAQs, team members, branches (moved to Phase 2, see [[filament-resources-phase-2]] and [[Roadmap]])
- The join application form itself (Phase 3)
- Team list, address, Messenger link (pending from the owner). Gallery photos are done (slice 7).
- SEO meta, Open Graph, sitemap (Phase 7)

Tasks: [[homepage-sections-tasks]]
Notes / decisions: [[branches-table-in-phase-1]], [[timezone-asia-manila]], [[image-path-convention]], [[logo-and-cover-assets]], [[seeders-first-or-create]]
