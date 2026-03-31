## Palgoals Workspace Instructions

Use these instructions for all work in this repository.

### Read-first rule

Before making any non-trivial code change, read these files in this order:

1. `NOTES.md`
2. `docs/developer-guide.md`
3. `docs/architecture.md`
4. `docs/sections-system.md`
5. `docs/editor-system.md`
6. `docs/refactor-plan.md`

Then rely on the targeted `.github/instructions/*.instructions.md` files for the specific area you are editing.

### Project rules

- Follow the naming and routing conventions documented in `NOTES.md`.
- Treat `Section` as structural state and `SectionTranslation` as localized content.
- Assume the project may use either Vite or Laravel Mix depending on the affected screen; verify before changing asset behavior.
- Prefer prepared controller or support-layer data over adding heavy logic in Blade views.
- For section editor work, preserve existing request names, translation payload shape, repeater hooks, and save-time behavior unless the task explicitly requires a contract change.

### Specialized instruction areas

- `resources/views/dashboard/pages/sections/**`: section builder editor UI rules
- `app/Http/Controllers/Admin/SectionController.php`: section builder backend rules
- `app/Support/Sections/**`: sections support-layer rules
- `resources/views/front/pages/**`: frontend section rendering path rules
- `resources/views/components/template/sections/**`: final frontend section partial rules
- `routes/client.php`: client subscription and editor routing rules
- `app/Models/Tenancy/**`: tenancy model rules
- `app/Services/Tenancy/**`: tenancy service rules
- `app/Jobs/**`: provisioning and tenancy job rules

### Safety checks

- Do not assume local `.env` settings match production.
- If a task touches uploads, queues, storage, tenancy, or external providers, verify the related configuration first.
- When changing frontend rendering, identify the active content path before editing: published builder HTML, builder JSON, or legacy sections.
