# ADR-006: Feedbacks vs Testimonials Naming Strategy

**Status:** Accepted — Implemented 2026-06-16  
**Date:** 2026-06-16  
**Author:** Engineering (documented from code audit)  
**Related:** `docs/03-database-architecture.md`, `docs/29-content-showcase.md`

---

## Context

The platform manages customer testimonials displayed on public-facing marketing pages. These records are created by the admin panel or submitted by customers via a public form. At some point during development, the database tables were named using the term "feedback" while the PHP application layer — controllers, models (class names), routes, views, policies, and documentation — converged independently on the term "testimonial."

The result is a split that runs entirely along the application/database boundary:

- Everything **above** the database uses "testimonial"
- Everything **at** the database level uses "feedback"

This is not a partial inconsistency. It is a clean horizontal split.

### How the Split Occurred

The migrations were written on `2025-06-25` with "feedback" terminology. The two tables created were `feedbacks` and `feedback_translations`. At that same date or shortly after, the controller was named `TestimonialsController`, the model was named `Testimonial`, the routes used `testimonials`, and the views were placed in `dashboard/testimonials/`. The two naming conventions never converged at the DB layer.

The field name `feedback` inside `feedback_translations` (which stores the testimonial text content) adds a second layer of confusion: the word "feedback" appears both as a table name and as a column name within that table.

---

## Current State

| Layer | Current Name |
|-------|-------------|
| Eloquent Model | `Testimonial` (`app/Models/Testimonial.php`) |
| Model table override | `protected $table = 'feedbacks'` |
| Database table | `feedbacks` |
| Translation Model | `TestimonialTranslation` (`app/Models/TestimonialTranslation.php`) |
| Translation model table override | `protected $table = 'feedback_translations'` |
| Translation database table | `feedback_translations` |
| FK column (translation → parent) | `feedback_id` (in `feedback_translations`) |
| Testimonial text column | `feedback` (in `feedback_translations`) |
| Admin Controller | `TestimonialsController` |
| Front Controller | `TestimonialSubmissionController` |
| Admin route resource | `Route::resource('testimonials', ...)` |
| Admin route names | `dashboard.testimonials.index`, `.create`, `.edit`, `.destroy` |
| Public routes | `/testimonials/submit` (`testimonials.submit`, `testimonials.submit.store`) |
| Admin views directory | `resources/views/dashboard/testimonials/` |
| Public views directory | `resources/views/front/testimonials/` |
| Section Blade component | `resources/views/components/template/sections/testimonials.blade.php` |
| Policy class | `TestimonialPolicy` |
| `SectionQueryResolver` method | `testimonials()`, `testimonialItemPayload()`, `testimonialImageUrl()` |
| Admin UI label | "شهادات" / "Testimonials" |
| Public submission UI label | "Testimonials" |
| Translation key prefix | `dashboard.Testimonials_List`, `dashboard.Add_Testimonial`, etc. |

The **only** places where "feedback" appears:
1. `feedbacks` table (DB)
2. `feedback_translations` table (DB)
3. `feedback_id` FK column (DB)
4. `feedback` text column (DB)
5. `TestimonialTranslation::$fillable = ['feedback_id', 'feedback', ...]`
6. `$testimonial->id` assigned as `'feedback_id'` in `TestimonialsController` and `TestimonialSubmissionController`
7. `$translation?->feedback` read in `SectionQueryResolver::testimonialItemPayload()`

---

## Problem

### 1. Cognitive Load on Every Query

Any developer writing a query involving testimonials must remember the mismatch:

```php
// Working with testimonials requires mentally switching naming systems:
Testimonial::all();                     // ← "testimonial" in PHP
// → SELECT * FROM feedbacks            // ← "feedback" in SQL

TestimonialTranslation::where('feedback_id', $id)->get();
// → SELECT * FROM feedback_translations WHERE feedback_id = ?

// And reading the content:
$text = $translation->feedback;        // ← field also named "feedback"
```

A developer reading `$translation->feedback` sees "feedback" and cannot immediately tell if this refers to the parent record ID (FK) or the text content — because the column name `feedback` in `feedback_translations` is the testimonial text, not a FK reference. The FK to the parent is `feedback_id`. Both the parent table and a data column share the same root word.

### 2. Search and Grep Inconsistency

Searching the codebase for "testimonial" finds controllers, routes, views, policies, and tests. Searching for "feedback" finds the DB layer and two specific lines in controllers (`'feedback_id' => $testimonial->id`). A developer fixing a bug in the testimonials system who only searches for "testimonial" will miss all direct DB interactions. A developer searching for "feedback" will not find the controller or the route.

### 3. Documentation Ambiguity

`docs/29-content-showcase.md` already documents this as a **critical finding**:

> "Critical finding: `Testimonial` model uses `protected $table = 'feedbacks'`"

It is documented as surprising enough to deserve a callout. Any future documentation that refers to the testimonials system must include a disclaimer about the table name — permanently increasing documentation maintenance cost.

`docs/03-database-architecture.md` lists the table as `feedbacks` in the schema section but refers to it as part of the "Testimonials" domain in the architecture overview. Any developer using the database architecture document and the code documentation simultaneously will encounter the discrepancy on first read.

### 4. Future Onboarding Friction

A new developer joining the project will encounter:
- `Route::resource('testimonials', ...)` → leads to `TestimonialsController`
- Controller uses `Testimonial::all()` → runs `SELECT * FROM feedbacks`
- `TestimonialTranslation` has `protected $table = 'feedback_translations'` and `$fillable = ['feedback_id', 'feedback']`

At minimum, this requires reading the model file to understand the table mapping. It is not self-documenting. In a codebase where every other model's class name matches its table (e.g., `Portfolio` → `portfolios`, `Plan` → `plans`, `Page` → `pages`), the `Testimonial` → `feedbacks` mapping is a violated expectation.

### 5. Not a Runtime Risk

To be precise about scope: **this is not a functional bug.** The `$table` overrides in both models are correctly declared, and the application works. The problem is exclusively at the cognitive and maintenance layer — naming inconsistency that increases friction without producing errors.

---

## Options Considered

### Option A — Rename Models to `Feedback`

Rename `Testimonial` → `Feedback` and `TestimonialTranslation` → `FeedbackTranslation`. Align the PHP layer to match the DB tables.

**What changes:**
- Class files: `Testimonial.php` → `Feedback.php`, `TestimonialTranslation.php` → `FeedbackTranslation.php`
- All `use App\Models\Testimonial` imports → `use App\Models\Feedback`
- `TestimonialsController` → `FeedbackController` (or `FeedbacksController`)
- Routes: `Route::resource('testimonials', ...)` → `Route::resource('feedbacks', ...)`
- Route names: `dashboard.testimonials.*` → `dashboard.feedbacks.*`
- Views directory: `dashboard/testimonials/` → `dashboard/feedbacks/`
- Policy: `TestimonialPolicy` → `FeedbackPolicy`
- Translation keys: `dashboard.Testimonials_List` → `dashboard.Feedbacks_List`
- Admin UI text: "Testimonials" → "Feedback" / "Feedbacks" (requires re-seeding)

**Advantages:**
- DB tables require no migration (zero data risk)
- The `$table` overrides can be removed from both models

**Disadvantages:**
- "Feedback" is a generic term. In marketing and SaaS contexts, "testimonial" has a more specific meaning: a curated positive review for display. "Feedback" implies raw, unfiltered input. The public UI already uses "Testimonials" — changing it to "Feedback" changes the user-facing terminology.
- Requires renaming controllers, routes, views, policies, translation keys, and all UI labels — a wider surface area than renaming two DB tables.
- Every route name changes (`dashboard.testimonials.*` → `dashboard.feedbacks.*`), breaking any bookmarks, hardcoded route references, or tests.
- The domain language of the entire application must change to match the DB tables — which is backwards from the natural direction of refactoring.

**Verdict:** Rejected. Aligning PHP to DB inverts the natural refactor direction and widens the blast radius.

---

### Option B — Rename Tables to `testimonials` / `testimonial_translations`

Rename the DB tables (and related columns) to match the established application-layer naming.

**What changes in the DB:**
- `feedbacks` → `testimonials`
- `feedback_translations` → `testimonial_translations`
- `feedback_translations.feedback_id` → `testimonial_translations.testimonial_id`
- `feedback_translations.feedback` (text column) → `testimonial_translations.text` *(optional — see Rationale)*

**What changes in PHP:**
- `Testimonial::$table` override removed (convention now matches)
- `TestimonialTranslation::$table` override removed
- `TestimonialTranslation::$fillable`: `'feedback_id'` → `'testimonial_id'`, `'feedback'` → `'text'`
- `Testimonial::translations()` FK argument: `'feedback_id'` → `'testimonial_id'`
- `TestimonialsController`: `'feedback_id' => $testimonial->id` → `'testimonial_id' => $testimonial->id`
- `TestimonialSubmissionController`: same
- `SectionQueryResolver::testimonialItemPayload()`: `$translation?->feedback` → `$translation?->text`

**No changes required:**
- Controller class names, route names, view directories, policies, UI labels, translation keys — all already use "testimonial"

**Advantages:**
- Aligns DB naming with the entire application layer
- Removes `$table` overrides from both models (code becomes self-documenting)
- Narrow blast radius: only the DB layer and the 5–6 PHP lines that reference the FK/field names
- The `feedback` text column rename to `text` also resolves the second-level ambiguity (same word for table and content field)

**Disadvantages:**
- Requires an actual DB migration (table rename, column rename, FK drop/recreate)
- Data migration must be coordinated — no data is lost, but the operation has a brief lock on the tables
- All existing DB snapshots, backups, or staging environments must be updated

**Verdict:** Recommended.

---

### Option C — Keep As-Is (Document the Mismatch)

Accept the `feedbacks`/`testimonials` split permanently. Add a comment at the top of `Testimonial.php` explaining the table mapping. Update documentation to always note the mismatch.

**Advantages:**
- Zero migration risk
- No code changes required

**Disadvantages:**
- The mismatch is already documented in `docs/29-content-showcase.md` as a "critical finding" — formalizing it as permanent does not make it less confusing, it just stops the clock.
- Every future developer must learn the exception and remember it. In a codebase that otherwise follows Laravel conventions (model name → table name), this is a permanent cognitive exception.
- The `feedback` column naming ambiguity (table named `feedbacks`, column named `feedback` storing text content) remains a source of confusion regardless of documentation.

**Verdict:** Rejected. The cost of the mismatch compounds over time; the cost of the migration is one-time.

---

## Decision

**Rename the database tables and columns to match the established application-layer terminology.**

```
feedbacks                    →   testimonials
feedback_translations        →   testimonial_translations
feedback_translations.feedback_id  →   testimonial_translations.testimonial_id
feedback_translations.feedback     →   testimonial_translations.text
```

The `Testimonial` model and `TestimonialTranslation` model class names, the `TestimonialsController`, all routes, views, policies, and translation keys remain unchanged. The PHP application layer is already correct. Only the database layer moves.

---

## Rationale

### The Application Layer Has Already Voted

Every single naming decision made at the PHP layer — controllers, routes, views, policies, helper methods, translation keys, the model class name itself — uses "testimonial." The database tables are the sole outlier. This is not an evenly split inconsistency; it is 95% of the codebase on one side and two table names on the other.

### "Testimonial" Is the More Precise Domain Term

In the context of a website builder and hosting platform, a "testimonial" is a curated, approved customer review displayed on the marketing site. "Feedback" is a broader term that includes bug reports, feature requests, and unfiltered customer input. The admin UI labels this section "Testimonials" in Arabic (`شهادات`) and English. The public submission form is `/testimonials/submit`. The Blade section component is `testimonials.blade.php`. The domain language is settled.

### The `feedback` Text Column Creates a Second Ambiguity

Within `feedback_translations`, the column storing the testimonial text is named `feedback`. A developer reading this table definition sees:

```
feedback_translations
├── feedback_id    ← FK to feedbacks.id (the parent record)
└── feedback       ← the testimonial text content
```

The word "feedback" appears twice with different meanings in the same table definition. After renaming to `testimonial_translations.text`, this ambiguity is eliminated:

```
testimonial_translations
├── testimonial_id    ← FK to testimonials.id (clear)
└── text              ← the testimonial text content (clear)
```

### Laravel Convention Alignment

In a standard Laravel application, `Testimonial` maps to `testimonials`. The `$table` property override is a deliberate escape hatch for legacy table names or non-standard conventions. Removing it makes the models self-documenting and eliminates the surprise for any developer who expects convention to hold.

---

## Migration Strategy

The migration is a rename-only operation. No data is lost, no rows are transformed, no values change. The migration risk is exclusively around the FK constraint, which must be dropped before renaming the column and recreated after.

### Phase 1 — Rename Tables and Columns

```php
// Migration: rename_feedbacks_to_testimonials

Schema::rename('feedbacks', 'testimonials');
Schema::rename('feedback_translations', 'testimonial_translations');

Schema::table('testimonial_translations', function (Blueprint $table) {
    // Drop the FK before renaming the column
    $table->dropForeign(['feedback_id']);
});

Schema::table('testimonial_translations', function (Blueprint $table) {
    $table->renameColumn('feedback_id', 'testimonial_id');
    $table->renameColumn('feedback', 'text');
});

Schema::table('testimonial_translations', function (Blueprint $table) {
    // Recreate the FK with the new column name
    $table->foreign('testimonial_id')
        ->references('id')
        ->on('testimonials')
        ->onDelete('cascade');
});
```

> **Coordination note:** This migration renames tables. Any other migration running concurrently that references `feedbacks` or `feedback_translations` will fail. Run this migration during a maintenance window or ensure no pending migrations reference these tables.

### Phase 2 — Update Eloquent Models

```php
// Testimonial.php — remove $table override (convention now matches):
// REMOVE: protected $table = 'feedbacks';

// TestimonialTranslation.php — remove $table override:
// REMOVE: protected $table = 'feedback_translations';

// TestimonialTranslation::$fillable update:
protected $fillable = ['testimonial_id', 'locale', 'text', 'name', 'major'];
// WAS: ['feedback_id', 'locale', 'feedback', 'name', 'major']

// Testimonial::translations() relationship FK update:
public function translations()
{
    return $this->hasMany(TestimonialTranslation::class, 'testimonial_id', 'id');
}
// WAS: hasMany(TestimonialTranslation::class, 'feedback_id', 'id')
```

### Phase 3 — Update FK References in Controllers

```php
// TestimonialsController (store and update):
'testimonial_id' => $testimonial->id,
// WAS: 'feedback_id' => $testimonial->id

// TestimonialSubmissionController (store):
'testimonial_id' => $testimonial->id,
// WAS: 'feedback_id' => $testimonial->id
```

### Phase 4 — Update Field Access in Resolvers

```php
// SectionQueryResolver::testimonialItemPayload():
$text = trim((string) ($translation?->text ?? ''));
// WAS: $translation?->feedback

// TestimonialTranslation form fields in views:
<input name="translations[ar][text]" ...>
// WAS: translations[ar][feedback]
```

### Phase 5 — Update Documentation

- `docs/03-database-architecture.md` — update table name from `feedbacks` to `testimonials`
- `docs/29-content-showcase.md` — remove "critical finding" note, update table references
- `CLAUDE.md` session log — note the rename in change history
- Any Blade views that reference `$translation->feedback` as a field value

---

## Consequences

### Positive

**Models become self-documenting.** `Testimonial` maps to `testimonials` by Laravel convention. The `$table` override on both models is removed. Any developer who knows Laravel conventions now correctly understands the table mapping without reading the model file.

**The `feedback` column ambiguity is eliminated.** Renaming `feedback` → `text` removes the dual meaning within `feedback_translations`. `testimonial_translations.text` is unambiguous: it is the testimonial's textual content.

**Search/grep is consistent.** Searching for "testimonial" in the codebase now finds all layers — PHP models, controllers, routes, views, and DB table names.

**Future developers have no exception to learn.** In a codebase of thousands of files, the `feedbacks` exception required a mental note. After migration, there is nothing special about the testimonials table — it follows the standard pattern.

**`docs/29-content-showcase.md` critical finding is closed.** The documentation no longer needs a callout for the naming mismatch.

### Negative

**One migration run required.** The table rename and FK manipulation must be coordinated. On a live production system, `RENAME TABLE` is nearly instantaneous in MySQL (metadata operation, not a data copy), but the FK drop/recreate requires brief table-level locks.

**Any raw SQL, DB views, or stored procedures referencing `feedbacks`** will break. The codebase has no identified stored procedures or DB views, but must be audited before running Phase 1.

**Staging and development databases must be migrated separately.** Developers with local `feedbacks` tables must run the migration locally. Seeded staging environments need the same migration applied.

**The migration file itself becomes permanent history.** Future developers reading the migration log will see `rename_feedbacks_to_testimonials` and understand the history, but the rename does add one more migration to the timeline.

---

## Impacted Systems

### Database

Two tables renamed. One column renamed (FK). One column renamed (text content). One FK dropped and recreated. No data transformation, no data loss.

### Models

`Testimonial.php` — remove `$table` override.  
`TestimonialTranslation.php` — remove `$table` override, update `$fillable`, update relationship FK argument.  
`Testimonial::translations()` — update FK column name from `'feedback_id'` to `'testimonial_id'`.

### Controllers

`TestimonialsController` — two lines: `'feedback_id' => $testimonial->id` → `'testimonial_id' => $testimonial->id`.  
`TestimonialSubmissionController` — one line: same change.

### Documentation

`docs/03-database-architecture.md` — table reference update.  
`docs/29-content-showcase.md` — critical finding note removed, table references updated.

### Admin UI

No changes. All admin labels, translation keys (`dashboard.Testimonials_List`, etc.), routes (`dashboard.testimonials.*`), and view paths already use "testimonial."

### Public Submission Flow

No changes to routes, view paths, or labels. One line in `TestimonialSubmissionController::store()` changes: `'feedback_id'` → `'testimonial_id'`.

### Support / Resolvers

`SectionQueryResolver::testimonialItemPayload()` — one line: `$translation?->feedback` → `$translation?->text`.  
`SectionMediaPreviewBuilder` — no change (does not reference the text field).

### Livewire / Legacy Views

`resources/views/livewire/admin/testimonials.blade.php` and `livewire/admin/sections/testimonials-section.blade.php` — check for any `->feedback` field reads and update to `->text`.

---

## Technical Debt Closed

This ADR directly addresses:

**TD-1 (Feedback/Testimonial Naming Mismatch)** — documented in `docs/29-content-showcase.md § Critical Findings`:

> "Critical finding: `Testimonial` model uses `protected $table = 'feedbacks'`"

And in `docs/03-database-architecture.md` where the `feedbacks` table appears in the schema but is described under "Testimonials" in the domain overview.

Closed by: completing Phase 1 (table rename migration) and Phase 2 (model updates). All documentation references to the naming mismatch can be removed after Phase 4.

---

## References

- `app/Models/Testimonial.php` — `protected $table = 'feedbacks'` (override to be removed)
- `app/Models/TestimonialTranslation.php` — `protected $table = 'feedback_translations'`, `$fillable = ['feedback_id', 'feedback', ...]`
- `app/Http/Controllers/Admin/TestimonialsController.php` — `'feedback_id' => $testimonial->id`
- `app/Http/Controllers/Front/TestimonialSubmissionController.php` — `'feedback_id' => $testimonial->id`
- `app/Policies/TestimonialPolicy.php` — already uses "Testimonial" naming
- `app/Support/Sections/SectionQueryResolver.php` — `$translation?->feedback` (field read to update)
- `database/migrations/2025_06_25_111513_create_feedbacks_table.php` — origin of the naming
- `database/migrations/2025_06_25_111554_create_feedback_translations_table.php` — `feedback_id` FK + `feedback` text column
- `routes/dashboard.php` — `Route::resource('testimonials', ...)` (already correct)
- `routes/web.php` — `testimonials.submit` routes (already correct)
- `docs/29-content-showcase.md` — critical finding § Testimonial System
- `docs/03-database-architecture.md` — `feedbacks` table schema entry
