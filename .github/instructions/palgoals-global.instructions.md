---
applyTo: "**"
description: "Always-on Palgoals project instructions. Use when working anywhere in this repository so the agent follows project architecture, sections rules, and safe editing constraints."
---

# Palgoals Global Instructions

Apply these rules for every task in this repository:

- Read `NOTES.md` before any non-trivial code change.
- Use `docs/developer-guide.md` and `docs/architecture.md` for system context.
- For sections work, consult `docs/sections-system.md`.
- For admin section editor work, also consult `docs/editor-system.md` and `docs/refactor-plan.md`.
- Treat `Section` as structural state and `SectionTranslation` as localized content.
- Do not assume one frontend asset pipeline; verify whether the affected screen uses Vite or Laravel Mix.
- Prefer controller or support-layer preparation over heavy Blade logic.
- If a task touches uploads, queues, storage, tenancy, or external providers, verify related configuration first.
- Before changing frontend rendering, identify whether the page is using published builder HTML, builder JSON, or legacy sections.
