# fullstack Subagent Added

Date: 2026-11-23

## Decision
Added a new `fullstack` subagent alongside frontend-builder, backend-builder, reviewer, and brain-keeper.

## Rationale
Some feature slices genuinely span both frontend and backend (e.g., a form submission that requires a migration, model, controller, validation, routes, Blade template, and Alpine interaction). Routing these back and forth between two agents creates overhead:
- Each hand-off adds latency and context-switching.
- Coordination bugs emerge (agent A builds the backend but agent B forgets to wire the route in `web.php`).
- For early-stage features, it's often unclear upfront whether a task is "front" or "back" until you start.

The fullstack agent is charged with building in explicit slices (backend first: migration → model → controller → routes; then frontend: Blade → Tailwind → Alpine), asking for a go-ahead on large tasks, enforcing satellite_id scoping and authorization Policies, writing a feature test per feature, and asking before schema changes or adding packages.

Model: Sonnet (tracked via alias `sonnet` to auto-upgrade to the latest version).

## Workflow impact
- **Single-layer work:** still use frontend-builder or backend-builder.
- **Multi-layer work or unclear split:** use fullstack.
- Full feature build sequence is still: **build → review → fix → record**.

## See also
[[Subagents]] for the full list and guidance.
