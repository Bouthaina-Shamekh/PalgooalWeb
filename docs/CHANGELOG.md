# CHANGELOG

> **Scope:** Architectural and functional milestones only. Day-to-day bug fixes,
> styling tweaks, and translation key additions are not recorded here.
>
> **Date policy:** Dates are derived directly from migration timestamps and confirmed
> code. Where the exact date is unknown, this document says **Date Unknown** rather
> than inventing one.
>
> **Order:** Oldest to newest within each section.

---

## 2025 — Initial Platform Foundation

> Migration range: `2024-07-11` → `2025-09-26`
> This period established all core tables and the primary domain model.

### 2024-07-11 — Role / Permission System

Earliest non-Laravel-default migration (`create_role_users_table`). A roles and
permissions system was the first application-level addition on top of the Laravel
scaffold, before any domain tables existed.

### 2025-05 — Billing Foundation (Plans, Categories, Coupons)

- `plan_categories` and `plans` tables created with `decimal(8,2)` price columns
  (monthly and annual pricing). This is the money-storage decision later documented
  as Technical Debt in **ADR-003**.
- `coupons` table created with `decimal` discount amounts.

### 2025-06-02 — Two-Factor Authentication

Laravel Fortify 2FA columns added to the `users` table (`two_factor_secret`,
`two_factor_recovery_codes`).

### 2025-06-05 — Internationalization Infrastructure

`translation_values` table created — the persistence layer for the project-wide `t()`
helper function. Followed immediately by the `languages` table (2025-06-06) linking
active languages to their locale codes.

### 2025-06-11 — Media Library

`media` table created (`disk`, `file_path`, `mime_type`, `size`, `original_name`).
The `Media` model provides a computed `url` attribute via `Storage::disk()->url()`.
This is the canonical object for file storage references throughout the system.

### 2025-06-17 — Site Identity & Global Settings

`general_settings` table created with logo, favicon, and basic branding fields stored
as raw `file_path` strings (not `media.id` foreign keys). This creates a media storage
inconsistency documented in **ADR-005**.

### 2025-06-24 — Services Module

`services` and `service_translations` tables created. Multi-language service catalogue
for the public marketing site.

### 2025-06-25 — Testimonials Module

`feedbacks` and `feedback_translations` tables created (original names). The naming
split between DB and PHP layer was documented in **ADR-006** and resolved in June 2026
— see "2026-06-16 — ADR-006 Implementation" below.

### 2025-06-26 — Page & Section System

`pages`, `page_translations`, `sections`, and `section_translations` tables created.
This is the foundational content engine: pages hold sections, sections hold localized
JSON content. The Page + Section pair becomes the canonical source of truth for all
rendered content, documented in **ADR-001**.

### 2025-06-28 — Portfolio Module

`portfolios` and `portfolio_translations` tables created. Portfolio images stored as
raw path strings (`default_image string`, `images string`), not as `media.id` foreign
keys — another instance of the media storage inconsistency (see **ADR-005**).

### 2025-06-29 — Navigation Header System

`headers`, `header_items`, and `header_item_translations` tables created. Provides
the admin-managed navigation menu system.

### 2025-07-28 — Client Management

`clients` table created. The Client model is the identity record for subscription
holders — distinct from the internal `users` table (admin accounts).

### 2025-07-30 — Template Marketplace

`templates`, `template_translations`, `category_templates`, and
`category_template_translations` tables created. A catalogue of purchasable site
templates linked to plans and categories.

### 2025-08-03 — Domain Registry

`domains` table created. Domains are the addressable endpoint for tenant sites and
can be platform subdomains, existing client-owned domains, or newly purchased domains.

### 2025-08-11 — Template Reviews

`template_reviews` table created. Aggregate rating columns added to `templates`. Public
star-rating system for the template marketplace.

### 2025-08-17 — Subscriptions (Initial Schema)

`subscriptions` table created with `decimal('price', 10, 2)` — a money column using
floating-point decimal storage. This is Technical Debt item TD-1 documented in
**ADR-003** (should be `unsignedInteger` cents).

### 2025-08-18 — Orders, Invoices, and Sites

- `orders` and `order_items` tables created (order_items final date: 2025-09-12).
- `invoices` table created in initial form.
- `sites` table created — the tenant site record linking a subscription to its domain.
- `coupon_subscription` pivot created.

### 2025-08-23 — Invoices Rebuild (Integer Cents)

**`rebuild_invoices_tables`** migration: the initial invoices schema was dropped and
replaced. The new schema stores all monetary values as `unsignedInteger` (cents):
`subtotal_cents`, `tax_cents`, `discount_cents`, `total_cents`. This is the canonical
money pattern documented in **ADR-003**. It is the only billing table that already
conforms to the ADR.

### 2025-08-22 — Server Management (WHM / cPanel Integration)

`servers` table created. Servers hold WHM API credentials (`hostname`, `api_token`
stored as `text`). The Server model provides `listpkgs`, account provisioning, and
reseller package resolution via WHM API calls.

### 2025-08-29 to 2025-09-22 — Domain & Subscription Augmentation

- Domain fields made nullable in subscriptions (2025-08-29).
- `last_sync_message` added to subscriptions (2025-08-29).
- `domain_providers` table created (2025-08-30).
- `domain_tlds` and `domain_tld_prices` tables created (2025-09-05) — TLD catalogue
  with `decimal` prices (another ADR-003 non-compliant table: TD-4).
- `server_package` column added to both `plans` and `subscriptions` (2025-09-22).

### 2025-09-24 — Featured Plans

`is_featured` and `featured_label` columns added to `plans` and `plan_translations`.
Enables the "Most Popular" badge in the plan selection UI.

### 2025-09-26 — Page Publishing Controls

`published_at` column added to `pages`. Slug column made nullable in
`page_translations` to support draft pages without a slug.

---

## 2025 Q4 — Multi-Tenant Expansion

> Migration range: `2025-10-02` → `2025-12-27`
> This period added DNS management, tenant isolation, and the first iteration of
> a site builder for tenant subscriptions.

### 2025-10 — Contact Info, DNS, and Plan Labels

- Contact info and social link columns added to `general_settings` (2025-10-02).
- DNS columns added to `domains` table (2025-10-18) — supports DNS record propagation
  tracking.
- `featured_label` column added to `plan_translations` (2025-10-08).

### 2025-11-17 — Tenant Architecture

Three simultaneous migrations mark the tenant pivot:

1. **`extend_subscriptions_for_tenants`** — tenant-level columns added to subscriptions
   (`tenant_id`, `domain_type`, `domain_status`, provisioning tracking fields).
2. **`create_notifications_table`** — internal notification system.
3. **`create_subscription_pages_tables`** — `subscription_pages` and
   `subscription_sections` tables created. Each active subscription gets its own
   isolated set of pages and sections (multi-tenant content isolation).
4. **`add_plan_type_to_plans`** — `type` column distinguishes hosting plans from
   content plans.

### 2025-11-30 — Page Builder v2

Three migrations update the page and section schema for an integrated builder
experience:

- `builder_mode` column added to `pages` (values: `visual` | `sections`).
- `sections` table updated for builder v2 (layout, order, display mode columns).
- Unique constraint added on `(section_id, locale)` in `section_translations`.

### 2025-12-06 to 2025-12-27 — Visual Builder Storage

- `style` column added to `sections` (2025-12-06) — section-level CSS overrides.
- `page_builder_structures` table created (2025-12-10) — stores serialized GrapesJS
  visual builder state (component trees, styles, assets). This table is later
  **archived** (see Archived Systems).
- Builder columns and publish fields added to `page_builder_structures` (2025-12-27).

---

## 2026 Q1 — Section Engine Refactor

> Migration range: `2026-01-14` → `2026-03-28`
> This period retired the visual builder, completed the tenant isolation model,
> and laid the groundwork for the dynamic section engine.

### 2026-01-14 — Locale-Aware Builder Structures

`locale` column added to `page_builder_structures` — the visual builder gained
multi-language awareness before the decision to retire it.

### 2026-03-04 to 2026-03-11 — Global Settings Expansion

- `layout_variants`, `header_settings`, and `footer_settings` added to
  `general_settings` (2026-03-04 to 2026-03-07).
- `localized_content` columns added (2026-03-08) — enables multilingual site identity
  content (taglines, descriptions) with fallback resolution through
  `GeneralSetting::resolveLocalizedValue()`.
- Builder JSON columns converted from `text` to `longtext` (2026-03-11) — accommodates
  large GrapesJS state trees.

### 2026-03-15 — Builder Mode on Pages

`builder_mode` formalized on the `pages` table (value `sections` marks the new
engine; `visual` marks GrapesJS legacy pages).

### 2026-03-26 — Tenant Content Scoping

`tenant_id` added to page, section, and section_translation tables. Tenant runtime
metrics table created. From this point, all content queries can be scoped to a specific
tenant subscription.

### 2026-03-27 — Retirement of Legacy Subscription Content Tables

**`drop_legacy_subscription_content_tables`** — `subscription_pages` and
`subscription_sections` (created 2025-11-17) are dropped. Tenant content migrated
to the shared `pages` / `sections` system with tenant scoping. The
`page_builder_structures` table remains but is no longer the primary content store.
Visual Builder (GrapesJS) integration is archived (see Archived Systems).

### 2026-03-28 — Domain Verification

`domain_verification_status`, `domain_verified_at`, and related DNS verification
columns added to `subscriptions`. Supports the domain verification workflow
(platform, custom, SSL pending, DNS pending, failed states).

---

## 2026 Q2 — Dynamic Section Engine

> Migration range: `2026-04-03` → `2026-05-20`
> This period introduced the Section Definitions system: a developer-facing registry
> of reusable section blueprints that drives dynamic rendering without code deploys.

### 2026-04-03 — Section Type Normalisation

`replace_hero_minimal_with_hero_default_in_sections_table` — an existing section type
value renamed as part of the definition migration groundwork.

### 2026-04-11 — Section Definitions System (Launch)

Six simultaneous migrations introduce the full Section Definitions stack:

1. `create_section_definitions_table` — `SectionDefinition` records: `section_key`,
   `label`, `category`, `blade_source`, `editor_mode`, `is_active`, `is_visible`.
2. `create_section_definition_fields_table` — `SectionDefinitionField` records:
   field schema per definition (key, type, scope, default, options, repeater schema).
3. `create_section_templates_table` — `SectionTemplate` records linking definitions
   to renderable templates.
4. `create_section_definition_template_table` — pivot between definitions and templates.
5. `add_builder_columns_to_section_definition_fields_table` — builder display metadata
   on fields.
6. `add_section_definition_id_to_sections_table` — `Section` records can now reference
   a `SectionDefinition`, enabling definition-driven content resolution.

The rendering pipeline for definition-driven sections: `SectionRenderer::render()` →
`renderDefinitionDriven()` → `SectionDefinitionRuntimeResolver` →
`SectionDefinitionFrontendViewDataFactory` → Blade view.

### 2026-04-12 — Custom Preset Backfills

Two backfill migrations activate the `custom_preset` editor mode for the
`hosting_hero` and `wordpress_ai_promo` section definitions. The `custom_preset` mode
is later retired (see Archived Systems).

### 2026-04-18 — Preview Media & Definition Mode Reset

- `preview_media_id` foreign key added to `section_definitions` referencing `media`
  (nullable, `nullOnDelete`). First correct Pattern A media reference on the
  section definitions table.
- `reset_hosting_hero_definition_to_dynamic` — `hosting_hero` converted from
  `custom_preset` to `dynamic` editor mode.

### 2026-04-25 to 2026-04-27 — Dynamic Mode Consolidation

- `ensure_hero_campaign_definition_uses_dynamic_renderer` (2026-04-25).
- `remove_hero_default_section_type` (2026-04-25) — the `hero_default` section type
  value removed from the `sections` table.
- `normalize_section_definition_editor_mode_to_dynamic` (2026-04-27) — all remaining
  `section_definitions` rows with `editor_mode = 'custom_preset'` set to `dynamic`.
  The `custom_preset` mode is now archived (see Archived Systems).

### 2026-05 — Soft Deletes and Data Integrity Pass

A systematic soft-delete pass across six tables:

| Date | Table |
|------|-------|
| 2026-05-04 | `invoices` |
| 2026-05-04 | `orders` |
| 2026-05-05 | `subscriptions` |
| 2026-05-05 | `feedbacks` (now `testimonials`) |
| 2026-05-08 | `headers`, `header_items` |
| 2026-05-15 | `template_reviews` |

Additional integrity fixes in May 2026:

- `fix_general_settings_language_fk` (2026-05-08) — `default_language` foreign key
  corrected.
- `add_unique_to_languages_code` (2026-05-08) — `languages.code` uniqueness enforced
  at DB level.
- `update_language_flags_to_cdn` (2026-05-10) — flag image references moved to CDN
  URLs.
- `update_portfolios_table` (2026-05-05) — schema corrections on the portfolios table.
- `add_theme_settings_to_subscriptions_table` (2026-05-02) — tenant theme/appearance
  preferences stored per subscription.

### 2026-05-20 — Client Password Reset

`client_password_reset_tokens` table created — client portal password reset
independent from the admin `password_reset_tokens` table.

---

## 2026 Q2 — Blade Editor Introduction

> Migration: `2026-06-13`
> The section definitions system gains an in-browser code editor so developers can
> write and deploy Blade templates without server file system access.

### 2026-06-13 — Blade Source Column

`blade_source` column (`longtext`, nullable) added to `section_definitions`. This
column is the authoritative in-database copy of the Blade template code for each
definition.

The Blade Editor feature (Monaco Editor embedded in the admin panel) allows a developer
to write Blade code in the browser, which is:

1. Stored in `section_definitions.blade_source` (persisted in DB).
2. Written to disk at `resources/views/front/sections/{category}/{key}.blade.php`
   via `SectionTemplateFileWriter`.

The two layers are kept in sync on demand. The DB is the source of truth; the disk file
is the deployable artefact. If the disk file is missing, the section falls back to
`_missing-template.blade.php` silently.

**Apache redirect fix:** On the production server the document root is
`public_html/` (not `public_html/public/`). All POST requests from the editor's
"Write File" button must target the `/public/` prefixed URL to avoid a 301 redirect
that would silently downgrade POST to GET and produce a 405 response. The
`doWrite()` JavaScript function handles this with a URL normalisation guard.

**AMD isolation fix:** Monaco Loader sets `window.define.amd = true`, which causes
UMD libraries (SweetAlert2, Feather, Sortable) to register as AMD modules instead of
window globals. Fixed by backing up and removing `window.define` before loader
injection, then restoring after Monaco's own `require.config()` completes.

---

## 2026 Q2 — Documentation Initiative

> Date: 2026-06-15
> All documentation work recorded here was performed in a single session.

### 2026-06-15 — Documentation Cleanup

22 legacy and superseded documents moved to `docs/_archive/`. The `docs/` root
replaced with 11 authoritative numbered documents, each code-verified against the
current codebase.

**Authoritative document set:**

| File | Subject |
|------|---------|
| `README.md` | Gateway document — reading order |
| `00-project-overview.md` | Product context, surfaces, multi-tenancy |
| `01-system-architecture.md` | Module map, guards, middleware chain |
| `03-database-architecture.md` | Full schema, relationships, table index |
| `07-section-definitions.md` | Section engine, field types, Blade contract |
| `09-rendering-flow.md` | Full render pipeline from route to Blade |
| `21-developer-guide.md` | Onboarding, setup, common workflows |
| `22-coding-standards.md` | `t()`, naming conventions, UX patterns |
| `24-security-notes.md` | Auth guards, policies, tenant isolation |
| `25-billing-system.md` | Orders, invoices, subscriptions, provisioning |
| `26-locale-system.md` | Languages, translation system, `t()` internals |
| `27-media-library.md` | Media Library, Media Picker, Media Storage Patterns |
| `28-site-identity.md` | General Settings, Appearance, Navigation System |
| `29-content-showcase.md` | Portfolio, Testimonials, Reviews, Public Content |

### 2026-06-15 — ADR Program Launch

Architecture Decision Records introduced. All approved ADRs live in `docs/adr/`.

### 2026-06-16 — ADR-005 Fully Implemented (Media Storage Format Unification)

All three waves of ADR-005 completed in a single session. See `docs/ADR_005_CLOSEOUT_REPORT.md`.

**Wave 1 — Simple FK Columns:** `clients.avatar_media_id`, `portfolios.default_image_media_id`, and seven `general_settings` logo/favicon `*_media_id` FK columns added. `MediaPathNormalizer` shared service created. `Section::image()` ghost relation removed. Dual-write and `resolved*Path()` model helpers in place across `Client`, `Portfolio`, and `GeneralSetting` models.

**Wave 2 — Template Image:** `templates.image_media_id` FK column added. `TemplateController` direct-upload path now creates a `Media` record at write time, closing the pre-existing orphan gap that had blocked Wave 2.

**Wave 3 — JSON Media Fields:** `portfolios.images` migrated from path-string arrays to integer ID arrays. `logo_override` and `payment_logos` JSON sub-keys in `general_settings` migrated to dual-write objects (`{"id", "path"}` and `{"ids", "paths"}` formats) enabling zero render overhead on every page request. Compatibility readers added to all consuming Blade views.

**`services.icon` permanently excluded:** stores static SVG template asset paths, not user-uploaded media. Remains Pattern B indefinitely by architectural decision.

---

## ADR Timeline

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [ADR-001](adr/001-page-section-as-source-of-truth.md) | Page + Section as Source of Truth | Accepted | Date Unknown |
| [ADR-003](adr/003-integer-cents-as-canonical-money-storage.md) | Integer Cents as Canonical Money Storage | Proposed | 2026-06-15 |
| [ADR-004](adr/004-session-based-vs-url-prefix-locale-strategy.md) | Session-Based vs URL-Prefix Locale Strategy | Accepted | 2026-06-15 |
| [ADR-005](adr/005-media-storage-format-unification.md) | Media Storage Format Unification | **Accepted — Implemented** | 2026-06-16 |
| [ADR-006](adr/006-feedbacks-vs-testimonials-naming-strategy.md) | Feedbacks vs Testimonials Naming Strategy | **Accepted — Implemented** | 2026-06-16 |

> ADR-002 is intentionally absent — the number was reserved and not used.

---

## Documentation Milestones

| Date | Event |
|------|-------|
| Date Unknown | First architecture documents written (now in `docs/_archive/legacy-docs/`) |
| Date Unknown | ADR-001 (Page + Section as Source of Truth) written and accepted |
| 2026-06-15 | Documentation cleanup: 22 legacy files archived, 14 authoritative docs published |
| 2026-06-15 | ADR program launched; ADR-003, ADR-004, ADR-005, ADR-006 written |
| 2026-06-15 | `docs/section-definitions.md` extended (507-line developer reference) |
| 2026-06-15 | `docs/CHANGELOG.md` created (this file) |
| 2026-06-16 | **ADR-006 implemented** — `feedbacks` → `testimonials`, `feedback_translations` → `testimonial_translations`, `feedback_id` → `testimonial_id`, `feedback` column → `text`; 3 Livewire orphan files deleted; TD-1 and TD-2 resolved |
| 2026-06-16 | **ADR-005 implemented** — Wave 1 (clients + portfolios + general_settings ×7 FK columns), Wave 2 (templates.image_media_id), Wave 3 (portfolios.images → ID arrays; logo_override + payment_logos → dual-write objects). `services.icon` permanently excluded. Closeout: `docs/ADR_005_CLOSEOUT_REPORT.md` |

---

## Archived Systems

The following sub-systems were introduced and later retired. Their migrations remain
in the codebase as the historical record of what existed.

---

### Visual Builder (GrapesJS)

**Active:** 2025-12-10 → 2026-03-27

GrapesJS was integrated as a drag-and-drop page builder. It stored its component
trees, styles, and assets in `page_builder_structures`. Pages using this mode had
`builder_mode = 'visual'`.

**Retired because:** The Section Definitions system provides the same capability
through a structured field schema and a dynamic render pipeline, without exposing
raw component-tree serialization to the database. GrapesJS state is brittle across
schema migrations and not directly editable without the full GrapesJS runtime.

**Retirement migration:** `2026-03-27_drop_legacy_subscription_content_tables`
(the `page_builder_structures` table itself was not dropped — it remains as a
historical table but is no longer written to by any active controller).

---

### `custom_preset` Editor Mode

**Active:** 2026-04-12 → 2026-04-27 (15 days)

A transitional `editor_mode` value on `section_definitions`. Two definitions
(`hosting_hero`, `wordpress_ai_promo`) were briefly activated as `custom_preset`,
meaning they used a hardcoded PHP "preset" function rather than a dynamic field
schema and Blade file.

**Retired because:** `custom_preset` required custom PHP code per definition, defeating
the purpose of the registry approach. All definitions normalized to `dynamic` by
`2026-04-27_normalize_section_definition_editor_mode_to_dynamic`.

**No live `custom_preset` rows exist** in `section_definitions`. The code path may
still be present in `SectionDefinitionRuntimeResolver` but is not reachable.

---

### Subscription Pages (Tenant Content Isolation v1)

**Active:** 2025-11-17 → 2026-03-27

`subscription_pages` and `subscription_sections` provided per-subscription copies of
pages and sections. Each active subscription had its own isolated row set, entirely
separate from the shared `pages` / `sections` tables.

**Retired because:** The approach duplicated the entire page/section schema and made
cross-subscription content management impossible. Replaced by `tenant_id` scoping on
the shared `pages` and `sections` tables (added 2026-03-26), which achieves the same
isolation with a single unified schema.

**Retirement migration:** `2026-03-27_drop_legacy_subscription_content_tables`.

---

### Legacy Section Registry (Section Types as Strings)

**Active:** Initial platform launch → 2026-04-27 (progressive replacement)

Before the Section Definitions system, section types were resolved by matching the
`sections.type` string value (`'hero'`, `'hero_default'`, `'services'`, etc.) against
hardcoded view paths. There was no registry, no field schema, and no admin UI for
managing section blueprints.

**Retired because:** The Section Definitions system provides a database-backed registry
where each definition has an explicit `section_key`, a field schema, and a linked
Blade template. Type-string routing was ad hoc and required code changes for every
new section type.

**Retirement migrations:** `2026-04-03_replace_hero_minimal_with_hero_default`,
`2026-04-25_remove_hero_default_section_type`, and the normalization pass through
April–May 2026 that assigned `section_definition_id` to existing sections.

---

## Current State

As of **2026-06-16** the platform consists of:

**Active sub-systems:**

- Public marketing website (Page + Section engine, dynamic rendering)
- Admin dashboard (full CRUD for all domain entities)
- Client portal (subscription management, tenant site pages)
- Tenant site (per-subscription domain, scoped by `tenant_id`)
- Section Definitions engine (registry + field schema + dynamic Blade rendering)
- Blade Editor (Monaco in admin panel → `blade_source` DB column → disk write)
- Media Library (Pattern A — `media.id` FK — is now the canonical and fully implemented
  pattern across all tables. **ADR-005 closed 2026-06-16.** Legacy path columns
  (`clients.avatar`, `portfolios.default_image/images`, `templates.image`,
  `general_settings` logos ×7) remain dual-written during stability window; column
  drops deferred per ADR-005 closeout plan. `services.icon` is a permanent Pattern B
  exception by architectural decision.)
- Billing (orders, invoices in integer cents; subscriptions and plans still in decimal
  — see **ADR-003**)
- WHM/cPanel provisioning (server API, `listpkgs`, account create/suspend/terminate)
- Multi-language support (`t()` helper, `session('locale')` — documented in **ADR-004**)

**Open technical debt (from ADRs):**

| ID | Debt | ADR |
|----|------|-----|
| TD-1 | `subscriptions.price` — `decimal(10,2)` | ADR-003 |
| TD-2 | `plans.monthly_price` / `annual_price` — `decimal(8,2)` | ADR-003 |
| TD-3 | `coupons.discount_amount` — `decimal(8,2)` | ADR-003 |
| TD-4 | `domain_tld_prices.*_price` — `decimal` | ADR-003 |
| ~~TD-5~~ ✅ | `general_settings` logos/favicon — **RESOLVED by ADR-005 Wave 1 (2026-06-16)** | ADR-005 |
| ~~TD-6~~ ✅ | `portfolios.default_image` / `images` — **RESOLVED by ADR-005 Waves 1 & 3 (2026-06-16)** | ADR-005 |
| ~~TD-7~~ ✅ | `Testimonial` / `TestimonialTranslation` — `$table = 'feedbacks'` — **RESOLVED by ADR-006 (2026-06-16)** | ADR-006 |
| TD-8 | `clients.avatar`, `portfolios.default_image/images`, `templates.image`, `general_settings` ×7 logo/favicon — legacy path columns still present (dual-write period). Drop after stability window per `docs/ADR_005_CLOSEOUT_REPORT.md §6.1` | ADR-005 Phase 5 |

---

## Documentation Maturity

**Current level: High**

| Metric | Count |
|--------|-------|
| Authoritative documents | 14 |
| Accepted ADRs | 4 |
| Accepted + Implemented ADRs | 2 (ADR-005, ADR-006) |
| Proposed ADRs | 1 (ADR-003) |

The platform's core architecture, billing, localisation, media, site identity,
content showcase, rendering engine, and security model are now documented through
code-verified source-of-truth documents. Open ADRs (ADR-003, ADR-005) record
the remaining technical debt as formal decisions pending implementation. ADR-006 was accepted and implemented 2026-06-16.

**Documentation coverage:**

All major systems are now covered by authoritative documents. The three documents added
after the initial cleanup round closed the remaining gaps:

- `27-media-library.md` — covers the Media model, Media Picker component, and the three
  media storage patterns (A: `media.id` FK, B: raw path string, C: hybrid controller
  conversion).
- `28-site-identity.md` — covers `GeneralSetting`, appearance/theme settings,
  `resolveLocalizedValue()`, layout variants, and the Navigation / Header system.
- `29-content-showcase.md` — covers Portfolio management, Testimonials, Template Reviews,
  and the public content systems.

Legacy docs for these systems in `docs/_archive/legacy-docs/` are now superseded.
