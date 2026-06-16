# Architecture Health Review — June 2026

> **Date:** 2026-06-16  
> **Scope:** Full codebase audit — Technical Debt, Architecture Risks, Database, Section Engine, Security, Documentation  
> **Method:** Read-only analysis of source docs + ADRs + CLAUDE.md session logs  
> **ADR Status at review:** ADR-001 ✅ · ADR-003 ⏳ Stability Window · ADR-004 ✅ · ADR-005 ✅ Waves 1-3 · ADR-006 ✅  
> **Constraint:** Analysis only — no file modifications, no migrations, no new ADRs, no code changes

---

## 1. Technical Debt Audit

### 1A — Money Storage Debt

| ID | Location | Issue | Severity | ADR Coverage |
|----|----------|-------|----------|--------------|
| M-1 | `templates.price`, `templates.discount_price` | Decimal columns in **stability window** — zero active reads remain, Phase 3 drop permitted ≥ 2026-06-23 if G4/G5 pass | Low (resolved, observing) | ADR-003 |
| M-2 | `subscriptions.price decimal(10,2)` | Decimal column with helpers in place; zero active reads; included in Phase 3 drop | Low (observing) | ADR-003 |
| M-3 | `coupons.discount_value decimal(10,2)` | Money stored as float for `fixed` discount type. **No ADR written.** The coupon model exists and has pivot tables but `InvoiceController::calculateTotals()` hard-codes `$discount = 0` — making this moot for now but a future integration liability | Medium | ❌ None |
| M-4 | `domain_tld_prices.cost`, `domain_tld_prices.sale` | Decimal columns never included in any ADR. Domain TLD pricing is admin-configured and read at checkout. Float math risk is real for high-volume pricing | Low | ❌ None |
| M-5 | `OrderActivationService` Path B fallback | When invoice items have no subscription `reference_id`, code treats first item's `reference_id` as a **Template ID** and creates a subscription from it. Same column, different entity — ambiguous and error-prone | Medium | ❌ None |
| M-6 | `resolvedPriceCents()` fallback branches | Template + Subscription model helpers contain decimal fallback branches that become dead code after Phase 3. Must be simplified in Phase 3 PR | Low (planned) | ADR-003 Phase 3 |

**Model helper dead code still present:**
- `app/Http/Controllers/Front/CheckoutController.php:164` — `$plan->discount_price` (plans has no such column → always null → zero billing impact)
- `resources/views/dashboard/management/subscriptions/edit.blade.php:4` — `$plan->price` (same — UX bug: JS price auto-fill always `0`)

---

### 1B — Media Storage Debt

| ID | Location | Issue | Severity | ADR Coverage |
|----|----------|-------|----------|--------------|
| A-1 | `portfolios.images` | Migrated to ID array format (Wave 3) ✅ | Resolved | ADR-005 Wave 3 |
| A-2 | `general_settings` logo/favicon fields | 7 FK columns added (Wave 1) + Eager loading in AppServiceProvider ✅ | Resolved | ADR-005 Wave 1 |
| A-3 | `clients.avatar` | Stores raw path string, not `media.id` FK. `avatar_media_id` column added (Wave 1) but raw path column still active. `resolvedAvatarPath()` helper added | Low (dual-write active) | ADR-005 Wave 1 |
| A-4 | Legacy path columns | Phase 5 (column drop for all Wave 1 legacy path fields) has not been executed. Equivalent to ADR-003's stability window for media | Medium (pending) | ADR-005 Phase 5 |
| A-5 | `page_translations.og_image` | Mixed format: numeric Media ID or raw URL path. No compatibility reader verified. No dedicated ADR sub-task | Medium | ❌ None |
| A-6 | `resolveMediaIdsToPaths()`, `normalizeMediaPath()` | Migration-era utility methods still exist in `PortfolioController` and `HomeController`. They are the pre-ADR-005 conversion layer — will be dead code after Phase 5 | Low | ADR-005 Phase 5 |

---

### 1C — Billing Logic Debt

| ID | Location | Issue | Severity |
|----|----------|-------|----------|
| B-1 | All payment flows | **`'mock_gateway'` is hardcoded.** Any authenticated client who can reach `/checkout` can pay without providing real payment credentials. This is the single highest-priority pre-launch blocker in the entire codebase | **CRITICAL** |
| B-2 | `InvoiceController::calculateTotals()` | `$discount = 0` hardcoded. Coupon model, `coupon_subscription` pivot, and `Subscription::coupons()` relationship all exist — but nothing reads them. Coupons are completely non-functional as a billing feature | High |
| B-3 | `SubscriptionSyncService::sync()` | cPanel account password generated as `Str::random(14) . '!A9'` if unset, but **never persisted** back to the subscription record. The password is effectively lost after WHM account creation — it exists in WHM but not in the platform | High |
| B-4 | No automatic renewal billing | `next_due_date` is tracked per subscription. No scheduled job queries it and creates renewal invoices. Renewals must be created manually by admins — not scalable | Medium |
| B-5 | `orders.status` → `fraud` state | `fraud` transition is admin-only and manual. No automated fraud detection or scoring exists | Low |

---

### 1D — Schema Integrity Debt

| ID | Column / Table | Issue | Severity |
|----|---------------|-------|----------|
| S-1 | `media.uploader_id` | No FK constraint — references `users` or `clients` but no cascade defined | Low |
| S-2 | `subscriptions.server_id` | Nullable FK with no explicit `constrained()` — can hold a dangling ID if server is deleted | Low |
| S-3 | `portfolios.client` | Free-text string field, not a FK to `clients`. No referential integrity for portfolio-to-client association | Low |
| S-4 | Legacy `sites` table | Exists in DB but is no longer the source of truth (ADR-001 replaced with Page+Section model). Table still present — unclear if safe to drop | Low |
| S-5 | `page_builder_structures` | Archived table (visual builder). Still exists in schema. Not referenced by any active controller | Low |
| S-6 | `section_definitions.custom_editor_key` | Deprecated legacy column. Migration `2026_04_27` normalized all `editor_mode` to `dynamic` but retained the column. Dead storage | Low |

---

### 1E — Section Engine Debt

| ID | Location | Issue | Severity |
|----|----------|-------|----------|
| E-1 | `SectionDefinitionField.validation_rules` | Column stored and normalized, but **no runtime layer reads these rules** when saving section content. Fields marked `required` in the definition do not enforce server-side validation | Medium |
| E-2 | Repeater content editor | Repeater fields can be **defined** and stored, but the dynamic editor UI does not yet **render them for content editing** (Phase 5B deferred). Any section with a `repeater` field cannot be filled via the admin UI | High |
| E-3 | `SectionDefinition` has no SoftDeletes | `destroy()` runs a full cascade including all linked `Section` and `SectionTranslation` rows inside a DB transaction. Hard-delete is permanent and **unrecoverable** | Medium |
| E-4 | `$data` vs `$fields` naming gap | Active runtime variable is `$data` (and `$content` alias). Earlier documentation described `$fields` as the intended contract. Code generated before June 2026 in Monaco may incorrectly reference `$sharedData` or `$translatableData` (undefined at runtime). An ADR to resolve the contract is noted in docs/09 but not yet written | Low |
| E-5 | Old-style sections (`section_definition_id = null`) | With `disable_legacy_fallback: true` active on all public pages, any legacy section without a definition link shows `_missing-template` on the live site — a visible error, not silent degradation | Medium |

---

## 2. Architecture Risk Ranking

Ranked by combined severity × probability × blast radius:

| Rank | Risk | Category | Severity | Likelihood | Blast Radius |
|------|------|----------|----------|------------|--------------|
| 🔴 1 | **Mock gateway in production** — any authenticated user can complete checkout without real payment | Billing | Critical | Certain on public launch | All revenue |
| 🔴 2 | **WHM credentials in plaintext** (`servers.api_token`, `servers.password`, `subscriptions.cpanel_password`) — DB compromise = full server access | Security | High | Low (admin DB access required) | All hosted tenants |
| 🔴 3 | **Coupon system entirely non-functional** — `$discount = 0` hardcoded; feature appears complete to an admin but bills 0 discount | Billing | High | Certain when coupons are used | Revenue integrity |
| 🔴 4 | **Repeater content editor incomplete** — fields can be defined but content cannot be entered via UI. Any section using `repeater` type is stuck | Section Engine | High | Certain on use | Client CX |
| 🟠 5 | **cPanel password not persisted after WHM account creation** — TD-6 in billing doc; admin cannot retrieve it later | Billing | High | Certain on every provision | Hosting clients |
| 🟠 6 | **No admin action audit trail** — Blade writes to disk, resource creates/updates, permission changes all leave no log entry | Security | Medium | Certain on any activity | Accountability |
| 🟠 7 | **ADR-003 Phase 3 stability window** — Phase 3 column drop enabled 2026-06-23 if G4/G5 pass. Premature drop before monitoring is complete = no rollback path | Database | Medium | Low (controlled process) | Pricing integrity |
| 🟠 8 | **`page_translations.og_image` mixed format** — no compatibility reader verified; SEO meta may serve broken images | Database | Medium | Probable on content with old OG images | SEO/Social sharing |
| 🟡 9 | **ADR-005 Phase 5 pending** — legacy path columns not yet dropped; dual-write state adds complexity | Media | Medium | Low (no code path failure) | Code clarity |
| 🟡 10 | **`SectionDefinition` hard-delete risk** — no soft delete; accidental destroy in production = unrecoverable | Section Engine | Medium | Low (admin action) | Content loss |
| 🟡 11 | **`section_definition_fields.validation_rules` not applied** — stored but never enforced at runtime | Section Engine | Medium | Probable (admin assumes it works) | Data quality |
| 🟡 12 | **Livewire component usage unverified** — multiple Livewire component directories exist; active wiring is unconfirmed | Architecture | Medium | Unknown | Unknown |
| 🟡 13 | **Translation values not cached** — `t()` hits `translation_values` table on every string, every request. No query-level cache verified active | Performance | Low | Certain at scale | Request latency |
| 🟢 14 | **`coupons.discount_value` decimal** — no ADR scope, but feature not wired anyway | Database | Low | Blocked by B-2 | None now |
| 🟢 15 | **Legacy `sites` table, `page_builder_structures`** — dead tables occupying DB schema | Database | Low | None | Confusion |

---

## 3. Database Review

### 3A — Money Storage Compliance

| Table | Column | Status |
|-------|--------|--------|
| `invoices` | `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents` | ✅ Integer cents |
| `invoice_items` | `unit_price_cents`, `total_cents` | ✅ Integer cents |
| `order_items` | `price_cents` | ✅ Integer cents (unsignedBigInteger) |
| `plans` | `monthly_price_cents`, `annual_price_cents` | ✅ Integer cents |
| `templates` | `price_cents`, `discount_price_cents` | ✅ Integer cents + dual-write active — stability window |
| `subscriptions` | `price` | ⚠️ `decimal(10,2)` — stability window, zero active reads |
| `coupons` | `discount_value` | ❌ `decimal(10,2)` — no ADR, feature not wired |
| `domain_tld_prices` | `cost`, `sale` | ❌ `decimal(10,2)` — no ADR, admin-set values |

**Net assessment:** All billing-critical paths (invoice creation, order activation, settlement) operate on integer cents. Remaining decimal columns are either in controlled stability windows or relate to features that are not yet wired into billing calculations.

---

### 3B — Referential Integrity

| Relationship | FK defined? | Cascade rule | Risk |
|-------------|-------------|--------------|------|
| `subscriptions.client_id → clients` | ✅ Yes | cascade delete | Low |
| `subscriptions.plan_id → plans` | ✅ Yes | restrictOnDelete | Low |
| `subscriptions.server_id → servers` | ⚠️ Nullable, no constraint | None | Low |
| `subscriptions.domain_id → domains` | ✅ Yes | nullable | Low |
| `order_items.order_id → orders` | ✅ Yes | cascade | Low |
| `invoice_items.invoice_id → invoices` | ✅ Yes | cascade | Low |
| `media.uploader_id → users/clients` | ❌ No FK | None | Low |
| `section_definitions.preview_media_id → media` | ✅ Yes | nullOnDelete | Low |
| `portfolios.client` | ❌ Free text string | None | Low |

**Net assessment:** Core billing and tenant models have proper FK constraints. Edge cases (`media.uploader_id`, `subscriptions.server_id`, `portfolios.client`) are low-risk given their access patterns but represent schema quality debt.

---

### 3C — Soft Delete Coverage

| Domain | Soft Deletes Applied? |
|--------|----------------------|
| Billing: `orders`, `order_items`, `invoices`, `invoice_items` | ✅ All (migration 2026_05_04) |
| Tenancy: `subscriptions` | ✅ Yes |
| Content: `section_definitions`, `section_definition_fields` | ❌ Hard delete only |
| Media: `media` | Needs verification |
| Clients, Plans, Portfolios, Testimonials | Needs individual verification |

**Gap:** `SectionDefinition` hard-delete with full cascade is the most dangerous. A misclick deletes the definition + all its fields + all linked section content from all pages — permanently. Adding SoftDeletes here is not blocking for launch but should be prioritized before the section library is used at scale.

---

### 3D — Key Schema Observations

**`subscriptions` table** is the most complex model in the schema — 30+ columns across multiple migration files. Notable:
- `cpanel_password` stored as **plaintext string** — High security risk on DB compromise
- `provisioning_status` + `status` = two separate state machines on one row, both must be tracked
- `domain_verification_status` adds a third status dimension — correct as domain state is independent
- `settings` (JSON) + `theme_settings` (JSON) — proper use of schemaless columns for extensible per-tenant config

**`pages` + `sections` tables** implement a clean owner-by-`context`+`tenant_id` model. Isolation is enforced at the query level in every controller that touches tenant content.

**`section_translations.content`** JSON column is the primary storage for all definition-driven field values. This is architecture-correct but creates a dependency on JSON parsing on every section render — no partial index support and schema changes require content migrations.

---

## 4. Section Engine Review

### 4A — Architecture Assessment

The Section Definition Engine is the most architecturally sophisticated component in the platform. The two-layer design (Definition Layer + Content Layer) is sound:

- **Definition Layer** (`SectionDefinition` + `SectionDefinitionField`) correctly separates *what fields exist* from *what content is stored*
- **Content Layer** (`Section` + `SectionTranslation`) holds instance data independently of the blueprint
- **Render Pipeline** (`SectionRenderer` → `SectionDefinitionFrontendViewDataFactory` → `SectionTemplateRegistry`) is well-factored with clean fallback semantics

The `disable_legacy_fallback: true` flag in `definition-section.blade.php` is the right call — forcing visible failure (`_missing-template`) rather than silent fallback to legacy content ensures admins see and fix broken sections rather than serving stale content.

---

### 4B — Active Gaps

**Gap 1 — Repeater UI (High):**  
`repeater` field type is fully supported in the `SectionDefinitionField` schema layer and persisted correctly. The admin dynamic editor (`DynamicSectionEditorRenderer`) does **not** yet render repeater fields for content input. Any definition using a repeater field displays the other fields normally but silently skips the repeater. This is documented as "Phase 5B deferred" but has no ADR or timeline.

**Gap 2 — `validation_rules` not enforced (Medium):**  
`SectionDefinitionField.validation_rules` stores Laravel rule strings (e.g., `["required", "max:255"]`) and the admin form provides a UI to set them. However, the save path for section content does **not** read or apply these rules. An admin setting a field as `required` in the definition has no runtime effect. This creates a false sense of data integrity.

**Gap 3 — Variable Contract Drift (Low):**  
Docs/09 documents an unresolved naming gap between `$data` (current runtime contract) and `$fields` (intended future API). Monaco scaffold output uses `$data` correctly as of 2026-06. Any Blade views generated before June 2026 that reference `$sharedData` or `$translatableData` will silently render empty. An ADR to formalize the contract is overdue.

**Gap 4 — SoftDeletes missing (Medium):**  
`SectionDefinition::destroy()` executes a full cascade: `SectionTranslation` → `Section` → `SectionDefinitionField` → pivot → definition. This runs inside a DB transaction correctly, but there is no recovery path. Accidental deletion of a definition that powers live marketing sections would require a DB restore.

---

### 4C — Render Path Correctness

| Scenario | Behavior | Correct? |
|----------|----------|----------|
| Section has `section_definition_id` set, definition active, Blade file exists | Renders correctly via factory | ✅ |
| Section has no `section_definition_id` | Shows `_missing-template` with debug info | ✅ (intentional design) |
| Definition `is_active = false` | Shows `_missing-template` | ✅ |
| Definition has no `template_key` | Shows `_missing-template` | ✅ |
| Blade file written via Monaco, then definition key renamed | `fileStatus() = external` (file exists but DB `blade_source` null-or-stale) | ⚠️ Manageable but confusing |
| `SectionQueryResolver` type match (e.g. `testimonials`) | Live DB data injected into `$data` | ✅ |
| Legacy section type (`hero`, `reviews_showcase`) without definition link | Shows `_missing-template` on public pages | ✅ (expected post-migration) |

---

### 4D — Template Registry

Only **one** section type (`portfolio_slider`) is explicitly registered in `config/sections.php`. All other types use the convention-based path `front.sections.{category}.{template_key}`. This is clean and extensible but means:
- A typo in `category` at definition creation creates a path that never resolves
- No central manifest of "what sections exist" for ops/deployment checklists

---

## 5. Security Review

### 5A — Authentication & Authorization: Solid

| Area | Status |
|------|--------|
| Dual guard separation (`web`/`client`) | ✅ Clean — separate session stores, no cross-guard access |
| Admin `$this->authorize()` per action | ✅ Enforced via `ModelPolicy` + `role_user` table |
| Client resource ownership (`abort_unless`) | ✅ Consistent pattern with `(int)` cast before comparison |
| `can_login` middleware enforcement | ✅ `EnsureClientCanLogin` runs on every client request |
| CSRF protection | ✅ Default `VerifyCsrfToken` — all `web` group routes |
| Session regeneration on login/logout/impersonation | ✅ Confirmed |
| Rate limiting on auth routes | ✅ `throttle:5,1` on client login, register, password reset |
| Password reset anti-enumeration | ✅ Always returns success regardless of email existence |

**One exception** in DomainController:
```php
// Uses != (loose) instead of !== (strict)
if ($request->client_id != $client->id) {
```
Functionally safe (both are integers from `findOrFail()`), but does not match the project's strict comparison standard.

---

### 5B — Known Open Risks

| Risk | Severity | Notes |
|------|----------|-------|
| `servers.api_token`, `servers.password` in plaintext DB | **High** | No at-rest encryption. DB access = server compromise. Recommend Laravel encrypted casts or Vault integration |
| `subscriptions.cpanel_password` in plaintext | **High** | Same risk. Password generated on provision but stored unencrypted. Not shown to client after creation but accessible to anyone with DB read |
| No centralized admin audit trail | **Medium** | Admin login, permission changes, resource creates/updates, Blade writes — none are logged to a queryable audit store. `blade_written_at` timestamp is the only write-side audit marker |
| `clients.avatar` accepts arbitrary path string | **Medium** | Validation changed from `nullable|image|max:2048` to `nullable|string|max:500` when media picker was introduced. Admin-only field, but a crafted request could store any path |
| `{!! t('...') !!}` XSS risk | **Medium** | Mitigated (admin-controlled translations only). Unescaped translation rendering is an XSS vector if admin account is compromised |
| Livewire component authorization scope | **Low** | Multiple Livewire component directories exist. Whether their `authorize()` contracts match the Blade form POST standards is unverified |

---

### 5C — File Write Security (Blade Editor)

The Monaco Blade editor is the highest-risk feature. The current controls:

| Control | Status |
|---------|--------|
| `sectiondefinitions.update` ability required | ✅ (`super_admin` in practice) |
| `SectionTemplateFileWriter` path prefix check | ✅ (`resources/views/front/sections/` prefix enforced) |
| `template_key` and `category` validated against `[a-z0-9_-]+` | ✅ (path traversal blocked) |
| base64 encoding to bypass ModSecurity WAF | ✅ (documented workaround) |
| Apache `/public/` prefix in AJAX URL | ✅ (POST → GET redirect prevented) |
| `redirect: 'manual'` on fetch to detect silent redirects | ✅ |
| Write audit: `blade_written_at` timestamp | ✅ |
| **What is NOT logged:** who wrote what code, previous content | ⚠️ Gap |

The path traversal and authorization controls are correct. The gap is in audit trail richness — `blade_written_at` tells you *that* a write happened and *when*, but not who performed it or what the previous content was.

---

### 5D — Validation Quality

The FormRequest pattern is consistently applied. Positive observations:
- Whitelist regex (`[a-z0-9_-]+`) on keys and slugs — no blacklist approach
- `in:` used consistently for enum fields (status, sort direction, billing cycle)
- `max:` constraints on all string inputs
- `prepareForValidation()` normalizes input before rules run (prevents edge-case bypass)
- `StoreSectionDefinitionRequest` validates `template_key` against `SectionTemplateRegistry::isValidTemplateKey()` — key-length attack prevented

---

## 6. Documentation Coverage

### 6A — Coverage Map

| System | Documented? | Doc file(s) | Staleness Risk |
|--------|------------|-------------|----------------|
| System Architecture | ✅ Comprehensive | `01-system-architecture.md` | Low (2026-06-15) |
| Database Schema | ✅ Comprehensive | `03-database-architecture.md` | Low (2026-06-15) |
| Section Definitions | ✅ Comprehensive | `07-section-definitions.md` | Low (2026-06-15) |
| Rendering Flow | ✅ Comprehensive | `09-rendering-flow.md` | Low (2026-06-15) |
| Security | ✅ Comprehensive | `24-security-notes.md` | Low (2026-06-15) |
| Billing System | ✅ Comprehensive | `25-billing-system.md` | Low (2026-06-15) |
| Locale & Translation | ✅ Comprehensive | `26-locale-system.md` | Low (2026-06-15) |
| ADR-001 (Page+Section) | ✅ | `adr/001-*.md` | Low |
| ADR-003 (Integer Cents) | ✅ + Stability Plan | `adr/003-*.md` + stability plan | Low (active) |
| ADR-004 (Session Locale) | ✅ | `adr/004-*.md` | Low |
| ADR-005 (Media Storage) | ✅ + Wave reports | `adr/005-*.md` + wave docs | Low |
| ADR-006 (Testimonials rename) | ✅ | `adr/006-*.md` | Low |
| Coding Standards | ✅ | `22-coding-standards.md` | Assumed current |
| Payment Gateway | ❌ Not documented | None | N/A (not built) |
| Coupon application | ❌ Not documented | Mentioned in `25-billing.md` as TD-3 | N/A (not built) |
| Automatic renewal billing | ❌ Not documented | Mentioned in `25-billing.md` | N/A (not built) |
| Livewire component contracts | ⚠️ Incomplete | Noted in `01-system-architecture.md` as "needs verification" | Unknown |
| Tenant theme system | ⚠️ Incomplete | Noted in `01-system-architecture.md` as "needs verification" | Unknown |
| Domain renewal client flow | ⚠️ Incomplete | Noted in `01-system-architecture.md` as "needs verification" | Unknown |

**Coverage estimate: ~75% of active architecture is documented.** Gaps are primarily in unbuilt features (payment, coupons, auto-renewal) and two unverified live states (Livewire wiring, tenant themes).

---

### 6B — CLAUDE.md Session Log Coverage

The CLAUDE.md session log is the most exhaustive change record in the project — 25+ sessions covering every view refactored, every controller fixed, every seeder updated, every bug resolved. This is a strong substitute for a PR/commit audit trail.

Notable: CLAUDE.md captures *why* changes were made (bug fixes, pattern enforcement, architectural decisions), not just *what* changed. This is above-average for project documentation.

**Gap:** CLAUDE.md does not capture which Blade views are still using legacy patterns (e.g. `__()`, `session('success')`, hardcoded strings) that have not yet been refactored. A codebase grep audit would be needed to identify remaining stale views outside the sessions already covered.

---

## 7. Next ADR Recommendations

Ranked by urgency:

### ADR-007 (CRITICAL) — Payment Gateway Integration

**Problem:** `'mock_gateway'` is hardcoded everywhere in the billing flow. The entire `InvoiceSettlementService::markPaid()` API accepts a payment method string and the UI calls it with `'mock_gateway'` unconditionally.

**What an ADR should decide:**
- Which gateway provider(s) to integrate (Stripe, PayPal, MyFatoorah, etc.)
- Webhook signature verification approach
- Idempotency key strategy for double-payment prevention
- How `order_items.price_cents` maps to gateway line items
- Whether checkout is synchronous (redirect) or asynchronous (webhook-driven)

**This ADR blocks commercial launch.**

---

### ADR-008 (High) — Coupon Application Architecture

**Problem:** Coupons are modeled correctly (`coupons` table, `coupon_subscription` pivot, `discount_type` enum, `discount_value`) but `InvoiceController::calculateTotals()` hard-codes `$discount = 0`. The billing calculator never reads the coupon model.

**What an ADR should decide:**
- When coupons are applied: at checkout (checkout flow) or at invoice creation (admin flow)
- How `percent` vs `fixed` discount types interact with `total_cents`
- Whether `discount_value decimal(10,2)` is migrated to `discount_value_cents integer` (M-3 above) before or as part of this ADR
- Expiry enforcement: `expires_at` exists on `coupons` — does validation happen at checkout only, or also at invoice generation?
- Maximum discount cap rules

---

### ADR-009 (Medium) — Repeater Content Editor (Section Engine Phase 5B)

**Problem:** `repeater` fields can be *defined* and *stored* in `SectionDefinitionField.schema`, and the frontend renders them from `$data['field_key']` correctly, but the admin section editor (`DynamicSectionEditorRenderer`) does not yet render repeater fields for content input.

**What an ADR should decide:**
- UI pattern for repeater item management (add/remove/reorder in the section editor)
- Whether sub-fields support all existing field types or a restricted subset (current V1 restriction excludes `richtext` and `number` from sub-fields)
- Storage format: current format is `[{"key": "value", ...}, {...}]` per item — confirm and document
- Client Portal editor: does the client get repeater editing capability in their site editor, or admin only?

---

### ADR-010 (Medium) — `SectionDefinition` Soft Deletes

**Problem:** Hard deletion with no recovery path for a record type that owns live site content.

**What an ADR should decide:**
- Add `deleted_at` (SoftDeletes) to `section_definitions` and `section_definition_fields`
- How soft-deleted definitions interact with `SectionDefinitionRuntimeResolver` (should treat as inactive)
- Whether sections pointing to a soft-deleted definition show `_missing-template` or retain last-known HTML
- `forceDelete()` implementation: must explicitly cascade to associated `Section` and `SectionTranslation` rows

---

### ADR-011 (Medium) — Blade Variable Contract (`$data` vs `$fields`)

**Problem:** docs/09 documents an unresolved gap between the current runtime variable `$data` and the aspirational API `$fields`. This creates documentation confusion and puts newly-generated Monaco scaffold at risk of drifting.

**What an ADR should decide:**
- Formalize `$data` as the permanent contract (simplest), or
- Introduce `$fields` as a typed collection wrapping `$data` for IDE autocomplete support, or  
- Add `$fields` as a second alias alongside `$data`

**Low urgency**, but formalizing the contract prevents future scaffold drift.

---

### ADR-012 (Low) — At-Rest Encryption for Server Credentials

**Problem:** `servers.api_token`, `servers.password`, `subscriptions.cpanel_password` are stored as plaintext strings. A DB read-only credential compromise exposes WHM access to all hosted accounts.

**What an ADR should decide:**
- Laravel `encrypted` cast vs application-level AES-256 via `Crypt::encrypt()` vs external Vault
- Key rotation strategy
- Whether `cpanel_password` should even be stored (consider ephemeral generation only)
- Migration path from plaintext to encrypted columns

---

## 8. Final Verdict

### Commercial Launch Readiness: ❌ NOT READY

**Two absolute blockers before commercial launch:**

**Blocker 1 — Payment Gateway (Risk #1):**  
The platform currently processes all payments via `'mock_gateway'` with zero real payment validation. Any authenticated client can check out and receive an activated subscription without providing payment. This is not a feature gap — it is a fundamental commercial integrity failure. No revenue is collectible, and any subscriptions activated after public launch would be legitimately disputed.

**Blocker 2 — Coupon System Non-Functional (Risk #3):**  
`$discount = 0` is hardcoded in all invoice calculation. If coupons are advertised or expected by clients, every invoice will ignore the discount. This is a billing integrity issue that will generate support disputes on first use.

---

### Architecture Health Score: **7.2 / 10**

| Area | Score | Rationale |
|------|-------|-----------|
| Core Architecture | 9/10 | ADR-001 through ADR-006 implemented cleanly. Two-layer section model, guard separation, tenant isolation — all solid. |
| Security | 6/10 | Auth, CSRF, ownership validation — strong. Credentials in plaintext, no audit log — material gaps. |
| Database | 7/10 | ADR-003 in controlled stability window. Most constraints correct. A handful of FK gaps and two decimal-column outliers. |
| Section Engine | 7/10 | Definition + render pipeline is well-designed. Repeater editor incomplete and `validation_rules` unenforced are real functional gaps. |
| Billing | 4/10 | Core settlement machinery (`InvoiceSettlementService`, `OrderActivationService`) is well-designed. Missing: real payment gateway, coupon wiring, auto-renewal, cPanel password persistence. |
| Documentation | 8/10 | Unusually thorough. ADRs, session logs, verified code-first docs. Gap: unbuilt features and two "needs verification" states. |
| Code Quality | 8/10 | `t()` mandate enforced, `session('ok')` consistent, strict comparison standard applied, FormRequest pattern consistent. Some legacy dead code (D4/D5). |

---

### What to do in the next 30 days

| Priority | Item | Why it matters |
|----------|------|----------------|
| 🔴 Now | Draft ADR-007 (Payment Gateway) and select a provider | Nothing else matters commercially until this is decided |
| 🔴 Now | Prioritize ADR-003 Phase 3 (2026-06-23 earliest) | G4/G5 monitoring — run SQL queries daily, proceed on schedule |
| 🟠 Week 2 | Draft ADR-008 (Coupon Application) + fix `discount_value decimal` | Complete the billing loop |
| 🟠 Week 2 | Investigate and document Livewire component active usage | Unverified state in a production system is a maintenance liability |
| 🟠 Week 2 | Verify `TenantThemeCssGenerator` completion state | Listed as in-progress in docs/01 — confirm or close |
| 🟡 Week 3 | ADR-009 (Repeater editor) design decision | Clients who see repeater fields in their editor get a broken UI |
| 🟡 Week 3 | Add audit log for admin actions | Admin Blade writes with no log entry is a compliance gap |
| 🟡 Week 4 | ADR-010 (SectionDefinition SoftDeletes) | Low risk now, high-consequence on accidental deletion at scale |
| 🟡 Week 4 | Plan ADR-012 (at-rest encryption for server credentials) | Not launch-blocking but a real risk to document and schedule |

---

### Summary

Palgoals has a **well-designed core architecture** that handles multi-tenancy, content management, multi-language, and section definition with notable quality. The ADR governance process (ADR-001 through ADR-006) has produced a traceable, well-documented evolution. The section engine, auth system, and database ownership model are production-grade.

The platform is **not ready for commercial launch** because the billing layer is incomplete — no real payment gateway and no coupon application. These are not technical architecture problems; they are feature completion gaps that require implementation decisions (ADR-007, ADR-008).

The remaining items — repeater editor, credential encryption, audit logging, soft deletes on definitions — are quality improvements that should be scheduled into the 30-day roadmap but do not block a controlled (limited) launch.

---

*This review reflects the state of the codebase as of 2026-06-16. No files were modified during this analysis.*
