# Homepage sections
Status: in progress
Phase: 1

Related: [[00-Index]], [[Roadmap]], [[Project-Brief]], [[public-layout]]

Goal: build the homepage from Blade components, in the order set in [[Project-Brief]]: hero, about us, what we do, meet the team (behind a config flag, OFF by default), upcoming services, gallery (Alpine lightbox), join us CTA, FAQ (Alpine accordion), contact (map only when the address is set), footer.

Users: visitor (public pages), member (same pages when logged in, no extra content yet)

Screens / routes:
- `/` named `home`, served by `HomeController`
- Homepage section components under `resources/views/components/` (one component per section)

Data changes (tables, columns):
- `branches`: id, name, slug (unique), address (nullable), contact fields (nullable), is_main (boolean)
- `services`: id, branch_id (FK), name, day_of_week, start_time, is_active; unique (branch_id, day_of_week, start_time)
- `gallery_images`: id, branch_id (nullable, see Backlog), image path, alt text, sort order, is_active
- `faqs`: id, question, answer, sort order, is_active
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
   - Join Us CTA links to `#contact` until the Phase 3 application form exists (owner choice, 2026-11-24).
   - Hero "Join Us" button now points to `#join`.
   - FAQ section is hidden when there are no published FAQs.
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

Owner-confirm copy (placeholders until the owner confirms):
- Hero sentence
- About intro (vision and mission still pending)
- What We Do role descriptions (FOH, Monitors, Livestream, Stage Tech)
- Placeholder FAQ answers

Out of scope:
- Filament resources for services, gallery, FAQs, team members, branches (later phase, see [[Roadmap]])
- The join application form itself (Phase 3)
- Real gallery photos, team list, address, Messenger link (pending from the owner)
- SEO meta, Open Graph, sitemap (Phase 7)

Tasks: [[homepage-sections-tasks]]
Notes / decisions: [[branches-table-in-phase-1]], [[timezone-asia-manila]], [[image-path-convention]], [[logo-and-cover-assets]], [[seeders-first-or-create]]
