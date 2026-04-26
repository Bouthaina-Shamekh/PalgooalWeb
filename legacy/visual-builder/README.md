# Visual Builder Archive

Archived on: 2026-04-25

## Why This Exists

The active page section system is now DB-driven:

- `SectionDefinition` stores reusable section metadata.
- `SectionDefinitionField` stores dynamic field schema.
- `SectionTranslation.content` stores per-instance localized content.
- Frontend rendering resolves through `resources/views/front/sections/{category}/{template_key}.blade.php`.

The older Visual Builder/GrapesJS system is currently unused by the active app. It is archived here so it can be restored, studied, or extracted later without keeping active admin routes and frontend snapshot rendering enabled.

## Original Locations Archived

- `app/Http/Controllers/Admin/PageBuilderController.php`
- `app/Models/PageBuilderStructure.php`
- `app/Services/PageBuilder/**`
- `config/page_builder.php`
- `routes/dashboard.php`
- `app/Http/Controllers/Admin/PageController.php`
- `app/Http/Controllers/Admin/SectionController.php`
- `resources/views/dashboard/pages/builder.blade.php`
- `resources/views/dashboard/pages/index.blade.php`
- `resources/views/dashboard/pages/partials/form.blade.php`
- `resources/views/dashboard/pages/sections/**`
- `resources/views/front/pages/page.blade.php`
- `resources/views/front/pages/_render-builder-sections.blade.php`
- `resources/views/components/template/sections/**`
- `resources/js/dashboard/page-builder.js`
- `resources/js/dashboard/builder/**`
- `public/assets/dashboard/css/builder.css`
- `public/assets/dashboard/css/builder-new.css`
- `public/build/assets/page-builder-sgBlEHHP.js`
- `public/build/assets/grapes.min-BaMFK-eV.js`
- `public/build/assets/grapes-BWp8l4b9.css`
- `public/build/manifest.json`
- `vite.config.mjs`
- builder-related migrations under `database/migrations/**`

## Disabled In Active App

- Dashboard Visual Builder routes were removed from `routes/dashboard.php`.
- Admin page create/edit now accepts and stores only `builder_mode = sections`.
- Admin page index no longer offers a Visual Builder switch or Visual Builder open link.
- Section workspace no longer receives or defaults a Visual Builder URL.
- Frontend page rendering no longer renders `PageBuilderStructure` published HTML/CSS or normalized builder sections.
- Vite no longer builds the Visual Builder JS entrypoints as active app inputs.

## Known Dependencies

- GrapesJS source modules under `resources/js/dashboard/builder/**`
- Builder entrypoint `resources/js/dashboard/page-builder.js`
- Builder CSS under `public/assets/dashboard/css/**`
- Vite/build artifacts under `public/build/assets/**`
- `PageBuilderStructure` model and migrations
- `config/page_builder.php`
- Legacy dynamic component renderers under `resources/views/components/template/sections/**`

## Database Notes

The `page_builder_structures` table and builder-related page columns were not dropped. They are inactive/unused while this archive is in place. Keep the data until a separate migration decision is made.

## Restore Instructions

1. Copy the archived files back to their original paths.
2. Restore the Visual Builder route definitions in `routes/dashboard.php`.
3. Restore `PageController` validation to allow `builder_mode = visual`.
4. Restore admin UI links/buttons for `dashboard.pages.builder`.
5. Restore frontend rendering of `PageBuilderStructure` published snapshots and normalized builder sections in `resources/views/front/pages/page.blade.php`.
6. Rebuild frontend assets if source files under `resources/js/dashboard/builder/**` are changed.
7. Run route and PHP syntax checks before enabling this in a live dashboard.
