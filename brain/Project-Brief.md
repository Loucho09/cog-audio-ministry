# Project Brief: COG Audio Ministry Web App

Status: content gathering (from the Audio Head)
Related: [[00-Index]], [[Roadmap]], [[new-project-brief-adopted]]

## Identity
- Header name: **COG Audio Ministry**
- Subheader: **Dasmariñas**
- Logo: use the official logo from the Facebook page, stored in `public/images/logo` (do not redraw)

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

## Contacts and Configuration
- Contacts and links live in `config/ministry.php` (not hardcoded in templates):
  - Facebook: https://www.facebook.com/COGAudioDasma
  - Email: cogdasmaaudio@gmail.com
  - Messenger link: pending
  - Church address: pending
- Configuration can be edited in Filament (admin panel) in a future phase.

## Branches
- Main: COG Dasmariñas
- Branches: Marilag, Trece, Silang, Jabez
- Each branch has its own branch-head. The super-admin (project owner) creates branches and assigns branch-heads.
- Every branch-scoped record (members, events, applications, duties) has `branch_id`.
- Open question: do all branches follow the same service times?

## Schedule
Stored in a `services` table, seeded with the main ministry's schedule. Managed in Filament (admin panel).
- Tuesday 7:00 PM: Revival Night
- Wednesday 7:00 PM: Salvation Night
- Saturday 5:30 PM: Empowered Night
- Sunday Service: 5:30 AM, 7:30 AM, 11:30 AM, 1:30 PM, 3:30 PM, 5:30 PM, 7:30 PM

## Homepage Sections (Mobile-first, in order)
1. **Hero:** tagline, join CTA
2. **About Us:** vision/mission (placeholder)
3. **What We Do:** FOH, monitors, livestream, stage tech (brief descriptions)
4. **Meet the Team:** (visibility controlled by `config/ministry.php` flag; default OFF) full-time members with photos
5. **Upcoming Services:** pulled from `services` table, next 4 weeks
6. **Gallery:** photos from services, events, team (placeholder; source TBD)
7. **Join Us:** application form CTA + link to dedicated form
8. **FAQ:** common questions about joining and participating
9. **Contact:** messaging, email, church address
10. **Footer:** copyright, links

## Pending from the head
- [ ] Vision and mission statement
- [ ] "Meet the Team" list (full-time members first): name, role, photo
- [ ] Gallery photos (sound booth, serving, services/events, group photo)
- [ ] Messenger link and official church address
- [ ] FAQ content
- [ ] Confirm whether branches follow the same service times
