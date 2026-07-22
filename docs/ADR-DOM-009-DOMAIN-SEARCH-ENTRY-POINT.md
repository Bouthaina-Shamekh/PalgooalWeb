# ADR-DOM-009 — Domain Search Interface Consolidation & Legacy Route Resolution

| Field | Value |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-07-22 |
| **Related Plan** | `docs/DOMAIN-SYSTEM-IMPLEMENTATION-PLAN.md` — Phase 0.5, Decision 11 |
| **Related Findings** | R-12 (Risk Log), P-06 (Priority Table) |
| **Decision Scope** | Documentation / architecture only — no code, Route, Blade, or View was created or modified as part of accepting this ADR. |

---

## Context

A follow-up architecture review (Current Architecture Inventory, 2026-07-22) established, with direct code evidence, that the domain search surface of the project is split across **three separate, parallel implementations**, and that one of the routes involved is not simply dead legacy code but an **actively linked, broken production route**:

1. **`resources/views/front/sections/templates/domain_search.blade.php`** — the Page Builder Section previously designated by the Project Owner as the *official* domain search interface (see Phase 0 front-end findings and the "official Section" decision recorded 2026-07-21). Currently static markup with no `t()` usage and no JavaScript/AJAX wiring.
2. **`resources/views/components/template/sections/search-domain.blade.php`** — a fully functional search widget, using `t()` throughout, calling `route('domains.check')` via `fetch()`, and already embedded in `front/pages/checkout.blade.php` and `client/domains/search.blade.php`.
3. **`resources/views/components/template/sections/domains_showcase.blade.php`** — a legacy-but-still-registered Section type (`domains_showcase`, listed in `App\Support\Sections\SectionRenderer::LEGACY_FRONTEND_SECTION_TYPES`), editable from the admin Page Builder via `resources/views/dashboard/pages/sections/partials/blocks/domains-search-fields.blade.php`. Its search form is a plain `<form method="GET">` targeting `route('domains.page', [], false)`.

The route it targets, **`domains.page`** (`GET /domains` → `DomainSearchController::page()` → `view('domains.search')`), points to a view that **does not exist** (`resources/views/domains/search.blade.php` was never created, or was removed without the route being cleaned up). This route is not orphaned: it is linked live from:

- `resources/views/front/layouts/footers/palgoals_marketing.blade.php` (a visible "Domains" footer link).
- `resources/views/front/layouts/partials/footer/menu-links.blade.php` (the default menu fallback used whenever no custom menu is configured).
- The `domains_showcase` search form described above.

Any real visitor using any of these three entry points encounters a fatal, uncaught view-resolution error instead of a search result. This was recorded as **R-12 (Critical / Confirmed)** in the Risk Log and elevated **P-06** from `Low / Needs Confirmation` to `Blocker مؤكَّد` in the Priority Table.

The problem is therefore not "a missing standalone page" but the coexistence of two structurally different entry points into domain search (`domains.page` via plain GET/full-page-reload, and `domains.check` via AJAX) plus a third, redundant Section type (`domains_showcase`), none of which had been reconciled with the Project Owner's earlier decision that the *official* search interface is a Page Builder Section, not a standalone route/page.

## Decision

The following composite policy is adopted, file by file. **This ADR records an architectural decision only; no code, Route, Blade file, or View was created or modified as part of accepting it.** All execution is deferred to Phase 3.

1. **Single official search interface**: `resources/views/front/sections/templates/domain_search.blade.php` remains the one and only official domain search surface, delivered as a Page Builder Section. No standalone page is adopted as an alternative or permanent solution.
2. **`search-domain.blade.php` remains the functional reference**: its existing, working logic (calls to `route('domains.check')` via `fetch`, response handling, `t()` usage) is to be reused or extracted into a shared component when Phase 3 wires up `domain_search.blade.php`, rather than being reimplemented from scratch.
3. **`domains_showcase.blade.php` is decoupled from `domains.page`**: in Phase 3 it must be rewired to call the same unified endpoint, `route('domains.check')`, matching the AJAX pattern already used by `search-domain.blade.php` — or merged into / retired in favor of the official Section, if implementation work in Phase 3 confirms it fully duplicates the official Section's purpose.
4. **Footer and default-menu links are re-pointed, not left as-is**: the live links in `palgoals_marketing.blade.php` and `menu-links.blade.php` that currently resolve `route('domains.page')` must, in Phase 3, either point to a real page that embeds the official Section, or be removed if no such dedicated page is approved.
5. **`domains.page` is classified `Deprecated Pending Removal`**, not deleted immediately and not deleted by this ADR. Removal is gated on all three of the following being completed first, during Phase 3 execution:
   - Every reference to the route (footer link, default-menu link, `domains_showcase` form action) has been removed or redirected.
   - A project-wide search confirms no remaining call site references `domains.page`.
   - The updated navigation and links have been tested end-to-end (manual click-through, not just code review).
6. **No standalone view is created** at `resources/views/domains/search.blade.php`, now or as a permanent fixture — doing so would directly contradict the standing decision that domain search lives inside Page Builder.

## Alternatives Considered

- **(A) Point `domains.page` at a new standalone view immediately.** Rejected as the default/automatic fix: it would resurrect a second, competing search surface parallel to the Page Builder Section, re-introducing the exact duplication this ADR is meant to resolve. Not ruled out completely — a *thin redirect/wrapper page that embeds the official Section* remains an option Phase 3 may choose from the four sub-decisions above, but a fully independent search page is explicitly excluded.
- **(B) Leave `domains_showcase.blade.php` targeting `domains.page` and only fix the missing view.** Rejected: this papers over the underlying problem (two parallel, inconsistent search entry mechanisms — full-page GET vs AJAX) without resolving it, and does nothing to converge the project on a single query endpoint (`domains.check`).
- **(C) Delete `domains.page` and all its references immediately in this same change.** Rejected for this ADR's scope: this document is a documentation-only architectural decision; the plan explicitly requires reference removal, a full search for remaining call sites, and manual link/navigation testing *before* deletion, all of which are implementation work reserved for Phase 3, not something to be asserted as already done here.
- **(D) Treat the three search surfaces as intentionally distinct features (e.g., one for marketing pages, one for checkout, one for a "domains showcase" section) and leave all three permanently.** Rejected: no evidence was found that these serve genuinely distinct product purposes; they are three implementations of the same underlying capability (check domain availability/price) with inconsistent wiring, and the Project Owner's standing decision is that there is exactly one official interface.

## Consequences

- Phase 3 ("البحث عن الدومينات") now has an unambiguous, approved reference to execute against instead of an open question; the `Blocking for Phase 3` gate tied to Decision 11 is cleared.
- `search-domain.blade.php` is confirmed as the reusable logic source, reducing risk of Phase 3 reimplementing AJAX search handling from scratch.
- `domains_showcase.blade.php` will require actual code changes in Phase 3 to swap its form target from a GET route to a `domains.check` AJAX call (or to be merged/retired) — this is now planned, scoped work rather than an undiscovered gap.
- The footer and default-menu navigation will need real edits in Phase 3; until then, the links remain live and broken (R-12 stays `Open` until Phase 3 execution resolves it — accepting this ADR does not close R-12, it only removes the *decision* blocker).
- `domains.page` continues to exist in `routes/web.php` unchanged until Phase 3 completes its removal checklist; no premature deletion risk of breaking an as-yet-unidentified caller.
- No standalone domain search page will appear in the codebase as a side effect of resolving this route, preserving consistency with the Page-Builder-only architecture already documented in Phase 0 and Phase 3 of the implementation plan.

## Migration Plan

*(To be executed in Phase 3 — not part of this ADR's acceptance)*

1. Wire `domain_search.blade.php` to real search behavior, reusing/extracting the logic from `search-domain.blade.php` (fetch to `route('domains.check')`, `t()` for all strings, rate limiting/debounce per Phase 3's existing success criteria in the plan).
2. Update `domains_showcase.blade.php`'s form to call `route('domains.check')` via AJAX instead of submitting `GET` to `route('domains.page')` — or remove/merge the section type if it is found to be fully redundant once the official Section is live.
3. Identify or create the definitive page that should host the official Section (`domain_search.blade.php`) as a real, navigable page.
4. Update `palgoals_marketing.blade.php` and `menu-links.blade.php` to point their "Domains" link at that page instead of `route('domains.page')`, or remove the link if no such page is approved.
5. Run a project-wide search for `domains.page` / `domains.check` call sites to confirm no remaining reference to the deprecated route survives outside of `routes/web.php` and `DomainSearchController::page()` themselves.
6. Manually test the updated navigation end-to-end (footer link, default menu, `domains_showcase` if retained) in a browser, not just via code review.
7. Only after steps 1–6 are verified: remove the `domains.page` route definition and the now-unused `DomainSearchController::page()` method, and close out R-12 as resolved in the Risk Log.

## Rollback Plan

- Because this ADR itself makes no code changes, there is nothing to roll back at the documentation-acceptance stage.
- For the Phase 3 execution that follows from this ADR: each step above is independently revertible via normal version control (git) before the final removal step (step 7). The route removal (step 7) is intentionally sequenced last and gated on explicit verification specifically so that if any unexpected caller of `domains.page` surfaces after steps 1–6, the route can remain in place (still `Deprecated Pending Removal`, not yet deleted) without any user-facing regression, while the newly-discovered caller is addressed.
- If, during Phase 3, `domains_showcase.blade.php`'s migration to `domains.check` reveals it cannot be cleanly adapted, the fallback is to retire (hide from the Section palette) rather than force a broken adaptation — this does not require reverting this ADR, only choosing the "merge/retire" branch of Decision 11's item 3, which this ADR already anticipates.

---

*This ADR was created as part of a documentation-only update. No Route, Blade template, View, Model, Migration, or Service file was created or modified in the process of drafting or accepting it.*
