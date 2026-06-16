# ADR-004: Session-Based vs URL-Prefix Locale Strategy

**Status:** Accepted  
**Date:** 2026-06-16  
**Author:** Engineering (documented from code audit)  
**Related:** `docs/26-locale-system.md`, `app/Http/Middleware/SetLocale.php`, `routes/lang.php`

---

## Context

The platform supports multiple languages (Arabic and English by default, extensible via the admin dashboard). Every part of the application — public marketing pages, client portal, admin dashboard, section rendering, and content translations — depends on knowing the active locale at request time.

### Current Implementation — Confirmed from Code

The system uses **session-based locale storage**. There are no URL prefixes in the route structure.

**Locale Resolution — `SetLocale` middleware (`app/Http/Middleware/SetLocale.php`):**

```
Priority 1: ?change-locale={code} query param (any URL)
   → Validate: code must exist in Language::where('is_active', true)
   → Save: session(['locale' => $code])
   → Redirect: same URL without the query param (clean URL, no locale in path)

Priority 2: session('locale')
   → Validate against active languages list
   → app()->setLocale($locale)

Priority 3: GeneralSetting::first()->default_language → Language->code
   → Admin-configured default from the database

Priority 4: config('app.locale')
   → 'ar' (set in config/app.php: env('APP_LOCALE', 'ar'))
```

**Route structure — `routes/web.php`:**

```php
// All public routes wrapped in setLocale — NO locale prefix:
Route::middleware(['setLocale'])->group(function () {
    Route::get('/', [FrontPageController::class, 'home']);
    Route::get('/portfolio/{slug}', ...);
    Route::get('/templates/{slug}', ...);
    Route::get('/change-locale/{locale}', [LocaleController::class, 'change']);
    // ... all routes are locale-neutral paths
});
```

There is no `Route::prefix('{locale}')` group. There is no `/ar/*` or `/en/*` anywhere in the routing layer.

**Middleware registration — `bootstrap/app.php`:**

```php
$middleware->alias(['setLocale' => SetLocale::class, ...]);
// setLocale is applied as a named alias, not as a global web middleware.
// ServeTenantSite is prepended to the 'web' group (tenant routing).
// SetLocale is applied only to the explicit group in web.php.
```

**Application defaults — `config/app.php`:**

```php
'locale'          => env('APP_LOCALE', 'ar'),          // default: Arabic
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ar'), // fallback: also Arabic
'translation_auto_create' => true,
```

Both `locale` and `fallback_locale` default to `'ar'`, confirming Arabic as the platform's primary language.

### Current Implementation Diagram

```
Browser Request
      │
      ▼
ServeTenantSite Middleware (web group, checks if request is tenant)
      │
      ▼
SetLocale Middleware (named group, applied to all public routes)
      │
      ├─── ?change-locale=ar ──► session(['locale' => 'ar'])
      │                          redirect to same URL (no query param)
      │
      ├─── session('locale') ──► app()->setLocale('ar')
      │
      ├─── GeneralSetting::default_language ──► Language->code
      │
      └─── config('app.locale') ──► 'ar'
                │
                ▼
         app()->setLocale() set
                │
                ▼
         Controller / Blade view
                │
      ┌─────────┴─────────────────────────────┐
      │                                       │
      ▼                                       ▼
  t('key', 'default')              $model->translations
  → translation_values table        → *_translations table
  → cache('translation.ar.key')     → where('locale', 'ar')
      │                                       │
      └─────────────┬─────────────────────────┘
                    ▼
             HTTP Response
       (locale-neutral URL, e.g. /about)
```

---

## Problem

The architectural question is whether locale should be carried in the **session** (current approach) or in the **URL path** (alternative approach). The two options have fundamentally different trade-offs for SEO, sharing, caching, and implementation complexity.

### Option A — URL Prefix Locales

All routes include a locale prefix:

```
/ar/about       /en/about
/ar/templates   /en/templates
/ar/pricing     /en/pricing
```

The locale is determined entirely from the URL. No session state is required to know which language to serve.

### Option B — Session-Based Locale (current)

All routes are locale-neutral:

```
/about
/templates
/pricing
```

The locale is stored in the server-side session (PHP session or Laravel session driver) and applied by middleware on each request. Switching languages saves to the session and redirects to the same path.

---

## Options Considered

### Option A — URL Prefix Locales

**Advantages:**

**Full SEO indexability.** Each language version of a page has a distinct URL. Google, Bing, and other crawlers can discover and index both `/ar/about` and `/en/about` independently. `hreflang` tags can reference each URL, enabling geo-targeted search results.

**Shareable locale-aware links.** A user sharing `/ar/about` with another user will always see the Arabic version, regardless of their own session state. Session-based links break when a different user (with a different session locale) opens them.

**Cache-friendly.** CDN and reverse proxy caches can serve locale-specific pages by URL without needing to inspect cookies or session state. `Vary: Cookie` headers make session-based locale very difficult to cache at the CDN layer.

**Predictable behavior for bots and crawlers.** Crawlers do not maintain sessions. A bot accessing `/about` always gets whichever locale the default session dictates — usually not what you want for multi-lingual indexing.

**Stateless.** No session is required to determine locale. Works correctly with any stateless request (API clients, curl, wget).

**Disadvantages:**

**Higher implementation complexity.** Every route must include a `{locale}` segment. Route model binding, named routes, and URL generation (`route()`, `url()`) must all account for the prefix. Redirects after locale changes become more complex. The entire routing layer must be rebuilt.

**Breaking change to existing URLs.** All existing links, bookmarks, and external references to `/about` break when it moves to `/ar/about`. A 301 redirect layer is required to preserve existing link equity.

**Duplicate routes for non-localised resources.** API endpoints, webhook handlers, and asset routes do not need locale prefixes. Two separate route groups must be maintained.

**Slug translation complexity.** The platform already handles per-locale slugs (e.g., `/templates/استضافة-احترافية` in Arabic, `/templates/professional-hosting` in English). Under URL prefixes, this becomes `/ar/templates/استضافة-احترافية` and `/en/templates/professional-hosting`, requiring slug-aware routing for both prefix AND slug resolution.

---

### Option B — Session-Based Locale (current)

**Advantages:**

**Simple routing.** No locale segment in any URL. All routes are defined once. Route generation (`route('frontend.home')`) never needs to know the current locale.

**Locale-aware slug translation already implemented.** `LocaleController::change()` already detects when a user is on a template or portfolio page and redirects to the correct slug in the new locale — without URL prefix changes. This was a deliberate implementation, not a workaround.

**Clean URLs.** Users and administrators see locale-neutral URLs. The language preference is a user setting, not a URL property.

**Minimal session coupling.** The session stores only one key (`locale`). The overhead is negligible for authenticated platforms where sessions already exist.

**No breaking changes.** All existing URLs, bookmarks, and external links remain valid. Locale changes are backward-compatible.

**Disadvantages:**

**Not SEO-indexable per language.** Confirmed in `docs/26-locale-system.md § URL Localization`:
> "This means locale-specific content is not SEO-indexable per language."

All language versions of a page share the same URL. A search engine indexing `/about` gets whichever language the default session produces — typically Arabic (`'locale' => 'ar'` as the default). The English version is invisible to crawlers.

**`hreflang` tags cannot be implemented correctly.** `hreflang` requires distinct URLs for each language variant. A single URL cannot be annotated with `hreflang="ar"` and `hreflang="en"` pointing to different canonical pages — they both resolve to the same URL.

**Session state required.** Requests without session context (bots, API clients, shared links) always receive the default locale. This is acceptable for an authenticated SaaS platform but problematic for a public marketing site with strong multi-language SEO requirements.

**CDN caching is difficult.** A CDN cannot cache locale-specific content at the edge when the locale is determined by a cookie/session. The `Vary: Cookie` requirement effectively bypasses edge caching for all locale-aware pages.

---

## Decision

**The platform adopts Session-Based Locale Management as the canonical localization strategy.**

This decision is confirmed by the code and is not merely inferred. Every element of the localization infrastructure is built for session-based locale:

- `SetLocale` middleware reads and writes `session('locale')` — no URL parsing
- All routes in `routes/web.php` are defined without locale prefix
- `routes/lang.php` defines `/change-locale/{locale}` as a **standalone route**, not as part of a prefixed group
- `LocaleController::change()` saves to session and redirects to the **same path** (not a prefixed path)
- `config/app.php` configures `locale` and `fallback_locale` as application defaults, not as URL conventions
- The `page_slug()` helper resolves slugs by content translation, not by URL prefix
- The slug-translation behavior in `LocaleController::change()` demonstrates an explicit design choice to keep URLs locale-neutral while translating content

The strategy is **intentionally chosen** for a platform where:
1. The primary user is an authenticated client managing their site (session already exists)
2. The admin dashboard is always authenticated (session always exists)
3. The platform's focus is on the SaaS tooling experience, not on public-facing multi-language SEO

---

## Rationale

### Route Structure Evidence

No locale prefix exists anywhere in the routing layer. `routes/web.php` has a single group:

```php
Route::middleware(['setLocale'])->group(function () { ... });
// Not: Route::prefix('{locale}')->middleware(['setLocale'])->group(...)
```

This is not an oversight. Adding URL prefix localization to Laravel requires rewriting the entire route group definition and all `route()` and `redirect()` calls. The absence of this structure is a confirmed architectural decision.

### Translation Architecture Evidence

The `translation_values` table uses `locale` as a string column (not a URL segment). The `t()` helper calls `app()->getLocale()` — which is set by the session, not by parsing the URL. This is structurally session-dependent.

### Middleware Evidence

`SetLocale` contains no URL-parsing logic for locale extraction. Its entire flow is: read query param → save to session; or read session → apply to `app()`. There is no `$request->segment(1)` or `$request->route('locale')` call.

### Database Default Evidence

`config/app.php` sets `'locale' => env('APP_LOCALE', 'ar')`. The primary language is Arabic. The platform is **RTL-first**, which informs the choice: Arabic content typically comes first, and the session default ensures Arabic is always the initial locale without requiring URL prefixes.

---

## SEO Considerations

This section documents the SEO implications of the session-based approach and when URL prefixes become necessary.

### Current SEO Limitations

**Single canonical URL per page.** When Google crawls `/about`, it receives one language version (the default locale — Arabic). The English translation is unreachable to crawlers because they do not maintain sessions.

**No `hreflang` tags can be implemented correctly.** The HTML standard `hreflang` attribute requires distinct URLs per language:

```html
<!-- This is NOT possible with session-based locales: -->
<link rel="alternate" hreflang="ar" href="https://example.com/about" />
<link rel="alternate" hreflang="en" href="https://example.com/about" />
<!-- Both point to the same URL — Google ignores duplicate hreflang annotations -->
```

**Google indexes only the default locale.** The session-based approach effectively means the Arabic version of all content is indexed (since `config/app.php` defaults to `'ar'`), and the English version is dark to search engines.

### When URL Prefixes Become Necessary

URL prefix localization becomes a **hard requirement** when any of the following is true:

1. **Organic search in multiple languages is a primary growth channel.** If the business depends on Google indexing both Arabic and English pages, session-based locale is insufficient.

2. **`hreflang` annotations are required for geo-targeting.** Multi-country deployments (different price/content per country+language combination) require distinct URLs.

3. **CDN edge caching for locale-specific content is required.** High-traffic multilingual pages that must be served from edge nodes cannot rely on session cookies for locale resolution.

4. **Shareable locale-anchored links are a feature.** If a user must be able to share a URL that opens in a specific language regardless of the viewer's session state, the URL must encode the locale.

### Does the Current Platform Require URL Prefixes?

Based on the platform architecture:

**The SaaS admin dashboard does not require URL prefixes.** It is always authenticated, sessions always exist, and it is not crawled by search engines.

**The client portal does not require URL prefixes.** Clients log into their own site management portal — authenticated, session-based, not publicly indexed.

**The public marketing site (`palgoals.wpgoals.com`) has a marginal SEO concern.** If organic search in English is a growth requirement for the platform, URL prefixes would be beneficial. However, given that the platform's primary market is Arabic-speaking (confirmed by RTL-first design, Arabic as default locale, `faker_locale = 'ar'`), the SEO cost of not indexing the English version of the marketing pages is low.

**Conclusion:** The current platform does not require URL prefix localization. The limitation should be acknowledged and revisited if English-language SEO becomes a business priority.

---

## Consequences

### Positive

**Zero breaking changes.** All existing URLs, bookmarks, sitemaps, and external links continue to work without modification.

**Routing remains simple.** All routes are defined once. No locale-aware `route()` generation is required anywhere in the codebase.

**Slug-based translation already works.** The smart redirect in `LocaleController::change()` (template slug translation, portfolio slug translation) provides a good UX for language switching without URL prefix complexity.

**Admin and client portal work correctly.** Both portals are session-driven by nature. The locale strategy is consistent with how all other user state (authentication, preferences) is managed.

**RTL/LTR direction is applied correctly.** `current_dir()` reads the session-driven `app()->getLocale()` and sets the `dir` attribute on `<html>`. This works identically whether locale comes from session or URL prefix.

### Negative

**English (and other non-default language) content is not indexed by search engines.** This is the direct consequence of session-based locale. All search engine crawlers see only the default locale (Arabic).

**No `hreflang` implementation is possible.** Proper multi-language SEO annotation requires distinct URLs per language.

**CDN caching is constrained.** Edge caches must pass locale-specific requests to the origin (or require `Vary: Cookie` which effectively disables shared caching). This is acceptable at current traffic levels but becomes a scaling concern at high traffic.

**Shared links are locale-ambiguous.** A URL shared between users will render in each user's own session locale, not in the sharer's locale. This is usually the desired behavior for a platform but is occasionally confusing.

---

## Alternatives Rejected

### URL Prefix Localization — Why Not Now

URL prefix localization was evaluated and rejected for the following reasons, confirmed by the code:

**Rewrites all route definitions.** Every `Route::get(...)` in `web.php` would need to move inside a `Route::prefix('{locale}')` group. All `route('name', ...)` calls would need a `locale` parameter. This is a pervasive change across every controller and Blade template.

**Breaks existing content URLs.** The `page_translations` table stores slugs like `about`, `pricing`, `templates`. Under URL prefixes, these become `/ar/about` and `/en/about`. All existing canonical links and any sitemap entries would need 301 redirects.

**Slug translation is already implemented differently.** The current `LocaleController::change()` handles template and portfolio slug translation (from Arabic slug to English slug) via a redirect. Under URL prefixes, this redirect must also prepend the locale — adding complexity to an already-nuanced feature.

**The business case is not established.** There is no evidence in the codebase that English-language SEO is a current business priority. The platform defaults to Arabic, is RTL-first, and targets Arabic-speaking markets. Adding URL prefix complexity without a clear SEO business requirement would be premature optimization.

---

## Migration Path (If Needed Later)

If the platform decides to add URL prefix localization in the future, the following architectural steps are required. This section describes the approach without implementing it.

### Step 1 — Route Structure Redesign

Replace the existing flat route group with a prefixed group:

```php
// New structure:
Route::prefix('{locale}')
    ->where(['locale' => '[a-z]{2}'])
    ->middleware(['setLocale'])
    ->group(function () {
        Route::get('/about', ...);
        Route::get('/templates/{slug}', ...);
        // ...
    });

// Default redirect (no prefix → detect browser language → redirect to /{locale}/):
Route::get('/', function () {
    $locale = session('locale', config('app.locale'));
    return redirect("/{$locale}");
});
```

### Step 2 — `SetLocale` Middleware Redesign

The middleware must extract locale from the URL instead of (or in addition to) the session:

```php
// New priority:
// 1. $request->route('locale') from URL prefix
// 2. Validate against active languages
// 3. app()->setLocale($locale)
// 4. Optionally also save to session (for consistency)
```

### Step 3 — URL Generation

Every `route()` call in controllers and Blade templates must include `locale`:

```php
route('frontend.about', ['locale' => app()->getLocale()])
// or: use a helper that automatically prepends the current locale
```

A `locale_route()` helper would reduce the verbosity:

```php
function locale_route(string $name, array $params = []): string {
    return route($name, array_merge(['locale' => app()->getLocale()], $params));
}
```

### Step 4 — Canonical URLs and Redirects

Add 301 redirects from all existing no-prefix URLs to the correct locale-prefixed URLs:

```php
Route::get('/about', fn() => redirect(route('frontend.about', ['locale' => app()->getLocale()]), 301));
```

Or use a catch-all redirect middleware that moves non-prefixed public requests to their prefixed equivalent.

### Step 5 — `hreflang` Tags

In `resources/views/layouts/front.blade.php` (or equivalent), add:

```blade
@foreach (available_locales() as $lang)
    <link rel="alternate" hreflang="{{ $lang->code }}" href="{{ locale_route(Route::currentRouteName(), ['locale' => $lang->code]) }}" />
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ locale_route(Route::currentRouteName(), ['locale' => config('app.locale')]) }}" />
```

### Step 6 — Sitemap Update

Generate one sitemap entry per locale per page:

```
/ar/about
/en/about
/ar/templates
/en/templates
```

### Step 7 — Content Slug Reconciliation

Audit all `page_translations` slugs. For pages where both locales share the same slug (e.g., `about`), the URL prefix alone distinguishes them (`/ar/about` vs `/en/about`). For pages with translated slugs, route model binding must resolve the slug within the locale prefix.

---

## Impacted Systems

### Locale System (`docs/26-locale-system.md`)

The session-based locale is the backbone of the entire locale system. `SetLocale` middleware, `t()` helper, `current_dir()`, and `available_locales()` all depend on `app()->getLocale()` being set by the session. No changes required under the current decision.

### Translation Values (`translation_values` table)

UI string translations are keyed by `locale` string (e.g., `'ar'`, `'en'`). This is independent of URL structure — no impact regardless of locale strategy.

### Pages (`page_translations`)

Pages are currently resolved by slug within the active locale:

```php
Page::whereHas('translations', fn($q) =>
    $q->where('slug', 'about')->where('locale', 'ar')
)->first()
```

Under URL prefixes, the locale would come from the URL segment, not the session — but the DB query logic would be identical.

### Sections (`section_translations`)

Section content is resolved by `(section_id, locale)`. Fully independent of URL structure.

### Menu URLs (`header_item_translations`)

Navigation links are stored as raw URLs (e.g., `/about`, `/templates`). Under URL prefixes, these stored URLs would need to include the locale prefix (or be generated dynamically). This is a content migration concern, not just a code concern.

### SEO (`page_translations.meta_*`)

Meta title, meta description, and Open Graph data per locale exist in `page_translations`. These are not exposed to search engines under the current session-based approach. Under URL prefixes, these would become indexable.

---

## Technical Debt Closed

No technical debt is closed by this ADR.

This ADR documents and accepts the current session-based locale strategy as intentional. The SEO limitation (non-indexable language versions) is acknowledged in `docs/26-locale-system.md § Future Improvements` but is not classified as technical debt — it is a deliberate trade-off.

---

## References

- `app/Http/Middleware/SetLocale.php` — confirmed session-based locale resolution
- `routes/web.php` — confirmed absence of locale prefix groups
- `routes/lang.php` — locale switch routes (locale-neutral paths)
- `app/Http/Controllers/Admin/LocaleController.php` — smart redirect + security validation
- `config/app.php` — `locale = 'ar'`, `fallback_locale = 'ar'`, RTL-first defaults
- `bootstrap/app.php` — middleware registration (setLocale as alias, not global)
- `docs/26-locale-system.md` — full locale system documentation including TD-1 through TD-5
- `docs/26-locale-system.md § URL Localization` — explicit statement: "The system does not use locale prefixes in URLs"
- `docs/26-locale-system.md § Future Improvements` — SEO limitation acknowledged
