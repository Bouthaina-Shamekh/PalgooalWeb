# Palgoals — Documentation

> **Last Updated:** 2026-06-15 · **Status:** Verified

---

## Welcome

This folder is the authoritative documentation hub for the Palgoals codebase. Every document here has been code-verified and reflects the current state of the system.

**Start here.** Whether you are a new developer joining the project or an AI session beginning a task — read this file first, then follow the links to the documents relevant to your work.

---

## What Is Palgoals?

Palgoals is a **purpose-built Laravel 12 Website Builder and Content Platform** — not a CMS plugin, not WordPress, not a generic drag-and-drop tool. It is a custom application where content structure, field definitions, and rendering templates are managed directly from the admin panel without touching code.

The platform has four surfaces:

- A **public marketing website** — rendered from a managed Page + Section system
- An **admin dashboard** — manages content, clients, subscriptions, plans, templates, and portfolios
- A **client portal** — customers manage their own subscription and tenant site pages
- A **tenant site** — each client subscription gets its own isolated page set

Multi-tenancy is achieved through a single application instance. Tenant isolation is enforced by the `ServeTenantSite` middleware, which resolves the current tenant from the `Host` header.

> For the full picture, read `00-project-overview.md`.

---

## Documentation Philosophy

These principles govern how documentation is written and maintained:

**Code First, Documentation Second** — the code is always the ground truth. When there is a conflict between a document and the actual behaviour of the code, the code wins. Update the document, not a workaround.

**Source of Truth, Not Commentary** — documents describe what the system does and why, not what someone intended it to do. Aspirational notes belong in ADRs, not in architecture docs.

**ADR-Driven Decisions** — any significant architectural decision must produce an ADR (`docs/adr/`). This is the only place where "we considered X and chose Y" belongs.

**No Duplicate Documentation** — every fact is written in exactly one place. Cross-references are links, not copies. If you find the same information in two documents, one of them is wrong.

**Authoritative Documents Override Archives** — the numbered documents in this folder are verified. Documents in `docs/_archive/` are historical context only and may be stale.

---

## Source of Truth Documents

| # | Document | Purpose | Status |
|---|----------|---------|--------|
| 00 | `00-project-overview.md` | What the system is, who uses it, tech stack, user types | Authoritative |
| 01 | `01-system-architecture.md` | Module boundaries, auth guards, middleware layers, data flow | Authoritative |
| 03 | `03-database-architecture.md` | Full schema, relationships, tenant isolation, indexing strategy | Authoritative |
| 07 | `07-section-definitions.md` | Section Definition system: fields, types, Blade view contract, Monaco editor | Authoritative |
| 09 | `09-rendering-flow.md` | How a page request becomes rendered HTML — full pipeline | Authoritative |
| 21 | `21-developer-guide.md` | New developer onboarding, local setup, common workflows, troubleshooting | Authoritative |
| 22 | `22-coding-standards.md` | Naming conventions, t() function rules, UX patterns, PR checklist | Authoritative |
| 24 | `24-security-notes.md` | Auth model, authorization policies, tenant isolation, known risks | Authoritative |
| 25 | `25-billing-system.md` | Orders, invoices, subscriptions, coupons, provisioning, activation lifecycle | Authoritative |
| 26 | `26-locale-system.md` | Languages, translation system, `t()` helper, content translation pattern | Authoritative |

All other `.md` files in this directory that are not listed above are either legacy drafts or superseded by the documents above.

---

## Recommended Reading Order

For a new developer, read in this order:

| Step | Document | Why |
|------|----------|-----|
| 1 | `00-project-overview.md` | Understand what you are building before touching code |
| 2 | `01-system-architecture.md` | Learn the module boundaries, guards, and middleware so nothing surprises you |
| 3 | `03-database-architecture.md` | Understand the data model — multi-tenancy, translations, and subscriptions all live here |
| 4 | `07-section-definitions.md` | The Section Engine is the core of the platform — understand it early |
| 5 | `09-rendering-flow.md` | See how a request travels from URL to rendered HTML |
| 6 | `22-coding-standards.md` | Learn the rules before writing any code |
| 7 | `24-security-notes.md` | Know the auth model, policy system, and known risks before touching auth or tenant code |
| 8 | `21-developer-guide.md` | Set up your local environment and run through the first-day checklist |

---

## Documentation Map

### Architecture
- `00-project-overview.md` — product definition, tech stack, user types
- `01-system-architecture.md` — module map, guards, middleware, service layer
- `03-database-architecture.md` — schema, relationships, indexing, tenant isolation

### Section Engine
- `07-section-definitions.md` — Section Definitions, field types, Blade View Contract, Monaco scaffold
- `09-rendering-flow.md` — full render pipeline from `SectionRenderer` to Blade output

### Development
- `21-developer-guide.md` — local setup, common workflows, 10 common mistakes, troubleshooting
- `22-coding-standards.md` — `t()` function, naming conventions, UX patterns, PR rules

### Security
- `24-security-notes.md` — authentication, authorization policies, tenant isolation, WHM risks, security checklist

### Billing
- `25-billing-system.md` — Orders, Invoices, Subscriptions, Coupons, provisioning (WHM/multi-tenant), activation lifecycle, status machines, technical debt

### Locale & Translation
- `26-locale-system.md` — Language management, `t()` helper, UI string translations, content translation tables, locale switching, admin translation CRUD

### Architectural Decisions
- `adr/001-page-section-as-source-of-truth.md` — Page + Section as the canonical content model

---

## By Task

Find the right document for the work you are doing:

| I want to… | Read |
|-----------|------|
| Understand how the system works at a high level | `00-project-overview.md` |
| Learn the module boundaries and service layer | `01-system-architecture.md` |
| Understand the database schema | `03-database-architecture.md` |
| Add a new Section Definition (new content block type) | `07-section-definitions.md` |
| Write a Blade template for a section | `07-section-definitions.md § Blade View Contract` |
| Debug a rendering or "section not showing" issue | `09-rendering-flow.md` |
| Add a new translation key | `26-locale-system.md` + `22-coding-standards.md § t() Function` |
| Understand the `t()` function | `26-locale-system.md § t() Helper` |
| Add a new language | `26-locale-system.md § Common Workflows` |
| Debug a missing translation | `26-locale-system.md § Missing Translation Handling` |
| Understand which auth guard to use | `01-system-architecture.md § Authentication` |
| Review or add a Policy | `24-security-notes.md § Authorization` |
| Add a tenant-scoped feature | `01-system-architecture.md § Multi-Tenancy` + `03-database-architecture.md § Tenant Isolation` |
| Fix a permission error (403) | `24-security-notes.md § ModelPolicy` |
| Set up a local development environment | `21-developer-guide.md § Local Development Setup` |
| Onboard as a new developer | `21-developer-guide.md § First Day Checklist` |
| Understand common mistakes to avoid | `21-developer-guide.md § 10 Common Mistakes` |
| Troubleshoot a production issue | `21-developer-guide.md § Troubleshooting` |
| Understand billing / invoices / subscriptions | `25-billing-system.md` |
| Write or review an ADR | `adr/` directory + this section below |

---

## Architectural Decisions (ADRs)

An **ADR (Architectural Decision Record)** documents a significant technical decision: the context, the options considered, the decision made, and the consequences.

**When to write an ADR:**
- You are changing how a core abstraction works (rendering, auth, tenancy, billing)
- You are choosing between two viable implementation approaches
- You are deprecating a pattern that currently exists in the codebase
- A future developer reading the code will ask "why was this done this way?"

**Where ADRs live:** `docs/adr/`

**Format:** Follow the pattern in `adr/001-page-section-as-source-of-truth.md` — Status, Context, Decision, Consequences.

**Current ADRs:**

| ADR | Decision | Status |
|-----|----------|--------|
| [001](adr/001-page-section-as-source-of-truth.md) | Page + Section as the canonical content model (not hardcoded types) | Accepted |

---

## Archived Documents

Legacy and superseded documents have been moved to `docs/_archive/`. They are preserved for historical reference only. **Do not use them as reference** — they may contain stale or contradictory information.

```
docs/_archive/
├── legacy-docs/          ← Pre-numbered system documentation
│   ├── architecture.md
│   ├── developer-guide.md
│   ├── editor-architecture.md
│   ├── editor-system.md
│   ├── section-definitions.md
│   ├── section-definitions-system.md
│   ├── sections-system.md
│   ├── pages-system.md
│   ├── pages-sections-system.md
│   ├── invoice-system.md
│   ├── order-system.md
│   ├── subscription-system.md
│   ├── locale-system.md
│   ├── menu-system.md
│   ├── general-settings-system.md
│   ├── portfolio-system.md
│   ├── testimonial-system.md
│   ├── appearance-system.md
│   └── section-blade-editor.md
├── legacy-plans/         ← Superseded planning documents
│   ├── refactor-plan.md
│   ├── I18N_REFACTOR_PLAN.md
│   └── VIEW_CONTRACT_REVIEW.md
└── legacy-sections/      ← Section preset drafts (pre-Section Definition system)
    └── sections/
```

**Rule:** If you find a link to any of the above files in code or documentation, replace it with the appropriate authoritative numbered document.

---

## Documentation Rules

These rules apply to all contributors:

1. **Do not copy content between documents.** Write each fact once. Use cross-references (`see 07-section-definitions.md`) instead of repeating text.

2. **Do not write aspirational documentation.** Document what the system does, not what you plan to make it do. Plans and proposals belong in ADRs or task tracking.

3. **Update documentation when you change the system.** A migration that adds a column must update `03-database-architecture.md`. A new section type must update `07-section-definitions.md`. A new middleware must update `01-system-architecture.md`.

4. **The code is the final authority.** When a document contradicts the code, fix the document. Do not add a comment in the code to match a wrong document.

5. **Authoritative documents override archives.** Never reference a legacy file when an authoritative numbered document covers the same topic.

6. **Every significant architectural change requires an ADR.** No exceptions. "We didn't have time" creates confusion for the next developer and the next AI session.

---

## Keeping Documentation Updated

Update the relevant document when you make any of these changes:

| Change Type | Document to Update |
|------------|-------------------|
| New database migration (column, table, index) | `03-database-architecture.md` |
| New section type or field type | `07-section-definitions.md` |
| Changes to rendering pipeline | `09-rendering-flow.md` |
| New middleware or auth guard change | `01-system-architecture.md` + `24-security-notes.md` |
| New Policy or permission rule | `24-security-notes.md` |
| New translation convention or t() behaviour | `22-coding-standards.md` |
| Billing or subscription logic change | `25-billing-system.md` + `03-database-architecture.md § Billing` |
| New module or service class | `01-system-architecture.md` |
| New tenant isolation rule | `01-system-architecture.md` + `03-database-architecture.md` |
| Any decision with architectural consequences | New ADR in `docs/adr/` |

---

## For AI Assistants

If you are an AI session starting work on this codebase, follow this protocol:

**Mandatory reading before any task:**

1. `docs/README.md` ← you are here
2. `docs/00-project-overview.md` — product context
3. `docs/01-system-architecture.md` — module map and guard model
4. `docs/22-coding-standards.md` — `t()` rules, naming conventions, UX patterns

**Then read the task-specific document:**

| Task type | Also read |
|----------|-----------|
| Section / rendering work | `07-section-definitions.md`, `09-rendering-flow.md` |
| Database / migration work | `03-database-architecture.md` |
| Auth / permissions work | `24-security-notes.md` |
| Onboarding / setup | `21-developer-guide.md` |

**Rules:**

- **Never rely on archived documents** (the non-numbered `.md` files in `docs/`). If there is a conflict between an archived document and a numbered document, the numbered document is correct.
- **`t()` is the only translation function.** `__()` is prohibited everywhere.
- **Flash key standard:** `session('ok')` for success, `session('error')` for errors. Never `session('success')`.
- **Check `CLAUDE.md`** at the project root — it contains the running log of session changes and project-specific conventions that complement these documents.

---

## Quick Links

| Need | Document |
|------|---------|
| What is this system? | [00-project-overview.md](00-project-overview.md) |
| Module map and service layer | [01-system-architecture.md](01-system-architecture.md) |
| Database schema | [03-database-architecture.md](03-database-architecture.md) |
| Section Definitions + Blade contract | [07-section-definitions.md](07-section-definitions.md) |
| Render pipeline | [09-rendering-flow.md](09-rendering-flow.md) |
| New developer onboarding | [21-developer-guide.md](21-developer-guide.md) |
| Coding standards and t() rules | [22-coding-standards.md](22-coding-standards.md) |
| Security model and known risks | [24-security-notes.md](24-security-notes.md) |
| Billing, invoices, subscriptions, provisioning | [25-billing-system.md](25-billing-system.md) |
| Translation system, languages, `t()` helper | [26-locale-system.md](26-locale-system.md) |
| ADR 001 — Page+Section as source of truth | [adr/001-page-section-as-source-of-truth.md](adr/001-page-section-as-source-of-truth.md) |
| Project session log | [../CLAUDE.md](../CLAUDE.md) |
