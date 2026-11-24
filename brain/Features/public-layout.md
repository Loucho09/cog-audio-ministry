# Public layout
Status: done
Phase: 1
Goal: shared public layout (navbar, footer, theme tokens, Inter font) for every public page. Contacts and flags come from config, not views.
Users: visitor, member
Screens / routes:
- `home` (GET /): placeholder welcome page using `<x-public-layout>`. Homepage sections come in Phase 1 Task 3.
Data changes (tables, columns): none. Config only: `config/ministry.php` (name, subheader, tagline, facebook_url, email, messenger_url, address, show_team). URLs accepted only if https; email validated.
Components:
- `<x-public-layout>`: page shell.
- `<x-site.navbar>`: full menu from lg (1024px), hamburger below. Text logo placeholder until `public/images/logo.png` exists.
- `<x-site.footer>`: footer with tagline from config.
Permissions: public pages only. No login and no role checks in this feature.
Out of scope:
- Homepage sections (Phase 1 Task 3)
- SEO meta, Open Graph, sitemap (Phase 7)
- Self-hosted Inter font (see [[Backlog]])
- Real logo file (owner to provide)
- Breeze login and roles (Phase 2)
Tasks: [[Roadmap]] (Phase 1, tasks 1-2)
Notes / decisions: [[tailwind-v3-with-breeze]], [[primary-dark-contrast-token]], [[menu-breakpoint-lg]], [[footer-tagline]]
Result: 41 tests (40 pass, 1 skipped until the logo exists). Checked at 360px, 1024px and 1280px. Reviewer run, issues fixed.
