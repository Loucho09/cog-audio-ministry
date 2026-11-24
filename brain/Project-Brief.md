# Project Brief: COG Audio Ministry Web App

Status: content gathering (from the Audio Head)
Related: [[00-Index]], [[Roadmap]], [[new-project-brief-adopted]], [[second-brief-merged]]

## Identity
- Header name: **COG Audio Ministry**
- Subheader: **Dasmariñas**
- Logo: use the official logo from the Facebook page, stored in `public/images/logo` (do not redraw)
- Logo filename: `public/images/logo.png` (text placeholder until the file exists)
- Logo received 2026-11-24 from the owner (Facebook blocks automated fetching). See [[logo-and-cover-assets]]

## Tone and Language
- English, warm and simple, Tagalog-friendly copy
- Short sentences. No jargon in public pages.

## Brand (Clean Minimalist Light)
Colors stored as Tailwind theme tokens (no custom hex values needed). Tokens are named: `background`, `surface`, `text`, `primary`, `accent`.

| Role | Tailwind token | Default value |
|------|---|---|
| Page background | `background` | slate-50 |
| Cards / surfaces | `surface` | white |
| Text / headings / dark sections | `text` | slate-900 |
| Primary (buttons, links) | `primary` | sky-600 |
| Accent (highlights, hover) | `accent` | sky-500 |

These tokens are set in the Tailwind config and can be customized per branch if needed in the future.

- ~~Font: Inter (Google Fonts)~~ superseded 2026-11-24
- Font: Montserrat, with Mrs Saint Delafield as a script accent for "Audio" (matches the logo); see [[typography-matches-logo]]
- Responsive from 360px wide (was 375px)

## Contacts and Configuration
- Contacts and links live in `config/ministry.php` (not hardcoded in templates):
  - Facebook: https://www.facebook.com/COGAudioDasma
  - Email: cogdasmaaudio@gmail.com
  - Messenger link: pending
  - Church address: pending (map embed on Contact shows only when address is filled)
- Configuration can be edited in Filament (admin panel) in a future phase.

## Roles (summary)
- `super-admin`: project owner. Creates branches, assigns branch-heads, only role that assigns roles.
- `branch-head`: admin for ONE branch. Manages services, team, duties, announcements, applications.
- `branch-admin` (new, from [[second-brief-merged]]): manages applications and roster for ONE branch. Cannot assign roles.
- `member`: own duties, schedules, events, announcements.

## Branches
- Main: COG Dasmariñas
- Branches: Marilag, Trece, Silang, Jabez
- Branch names use the "COG" prefix: COG Dasmariñas, COG Marilag, COG Trece, COG Silang, COG Jabez. See [[branches-table-in-phase-1]]
- Services are seeded for the main branch (COG Dasmariñas) only. Other branches' service times are still open (see the open question below).
- Each branch has its own branch-head. The super-admin (project owner) creates branches and assigns branch-heads.
- Every branch-scoped record (members, events, applications, duties) has `branch_id`.
- Open question: do all branches follow the same service times?

### `branches` table
- name
- slug
- address (nullable)
- contact fields (nullable)
- is_main (Dasmariñas = true)

## Schedule
Stored in a `services` table, seeded with the main ministry's schedule. Managed in Filament (admin panel).

### `services` table
- name
- day_of_week
- start_time
- branch_id
- is_active

Seeded schedule:
- Tuesday 7:00 PM: Revival Night
- Wednesday 7:00 PM: Salvation Night
- Saturday 5:30 PM: Empowered Night
- Sunday Service: 5:30 AM, 7:30 AM, 11:30 AM, 1:30 PM, 3:30 PM, 5:30 PM, 7:30 PM

Upcoming Services are sorted by next occurrence.

## Homepage Sections (Mobile-first, in order)
Each section is a Blade component.

1. **Navbar:** logo, name + subheader, section links, "Member Login" button, Alpine hamburger on mobile
2. **Hero:** tagline, buttons "Join Us" and "See Service Times"
3. **About Us:** vision/mission (placeholder)
4. **What We Do:** four cards: FOH, Monitors, Livestream, Stage Tech (brief descriptions)
5. **Meet the Team:** (visibility controlled by `config/ministry.php` flag; default OFF) reads `is_featured` members from DB, full-time first, with photos
6. **Upcoming Services:** pulled from `services` table, next 4 weeks, sorted by next occurrence
7. **Gallery:** images from DB, Alpine lightbox. Placeholder images until photos arrive in `public/images/gallery/`
8. **Join Us:** application form CTA + link to dedicated form
9. **FAQ:** from DB, Alpine accordion (common questions about joining and participating)
10. **Contact:** messaging, email, church address, map embed only when address is filled
11. **Footer:** copyright, links

Meet the Team is OFF by default. Do not invent team members.

## Pending from the head
- [ ] Vision and mission statement
- [ ] "Meet the Team" list (full-time members first): name, role, photo
- [ ] Gallery photos (sound booth, serving, services/events, group photo) (still pending as of 2026-11-24)
- [ ] Messenger link and official church address
- [ ] FAQ content
- [ ] Confirm homepage copy (hero sentence, about intro, role descriptions)
- [ ] Confirm placeholder FAQ answers
- [ ] Confirm whether branches follow the same service times
- [ ] Names of first super-admin and branch-heads (owner sets these)
