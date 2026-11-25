# FAQ removed
Date: 2026-11-25
Status: decided (owner)

Decision: remove the FAQ feature fully. The owner decided on 2026-11-25.

Removed:
- Filament FAQ resource
- `Faq` model, policy, seeder, factory
- Homepage FAQ component (it was already hidden from the homepage, see [[homepage-hooks]])
- FAQ tests

Database:
- New migration `2026_11_25_090000_drop_faqs_table` drops the `faqs` table. Its `down()` recreates it.
- The original create migration is kept (history is not rewritten).

Commit: f0312eb (on `feature/admin-resources-polish`).

Why: the owner does not need FAQs for now. Content can be re-added later as a new feature if wanted.

Related: [[admin-resources-polish]], [[homepage-sections]], [[Roadmap]]
