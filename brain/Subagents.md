# Subagents

Workflow: standard loop for every feature is **build → review → fix → record in brain**.

## Five agents

- **frontend-builder:** Blade templates, Tailwind CSS, Alpine.js UI. Never touches migrations, models, or auth.
- **backend-builder:** migrations, models, auth, roles, schedules, notifications, tests.
- **fullstack:** full features end-to-end when a task touches both frontend and backend, or when it's unclear which agent to use. Builds in slices (backend first, then frontend), asks for a go-ahead on large tasks, scopes queries/Policies by branch_id, writes feature tests, asks before schema changes or adding packages. Added 2026-11-23; see [[fullstack-subagent-added]].
- **reviewer:** read-only review of recent changes (bugs, security, Laravel conventions).
- **brain-keeper:** edits only `brain/` to record decisions, specs, status, tasks.

## Guidance
- Prefer frontend-builder or backend-builder for single-layer work.
- Use fullstack when a slice genuinely spans both layers or the split is unclear.
