# Compare Versions — Implementation Report

**Phase:** 5  
**Status:** Implemented ✅  
**Date:** 2026-06-19

---

## الملفات المُعدَّلة

### `routes/dashboard.php`

```php
Route::get('/{sectionDefinition}/compare-blade', [SectionDefinitionController::class, 'compareBlade'])
    ->whereNumber('sectionDefinition')
    ->name('compare_blade');
```

---

### `app/Http/Controllers/Admin/SectionDefinitionController.php`

إضافة `compareBlade()`:

- يُجيب بـ JSON: `{ ok, draft, disk, status, sync, view_name, path }`
- يُفعَّل فقط عند الطلب — لا disk I/O في `edit()`
- يستخدم `SectionTemplateFileWriter::resolvedPath()` حصراً
- `$this->authorize('update', ...)` مُطبَّق

---

### `resources/views/dashboard/section_definitions/edit.blade.php`

**التغييرات في Task #185:**
- إضافة `compareUrl` لـ `window.__sdEditorData`

**التغييرات في Task #186 — Compare Versions Modal:**

HTML مُضاف بعد `#blade-scaffold-modal`:
- `#compare-modal` — modal overlay
- `#cvm-loader` — spinner أثناء AJAX
- `#cvm-error` + `#cvm-error-msg` — حالة الخطأ
- `#cvm-labels` — column headers (Draft / Disk)
- `#cvm-disk-path` — مسار الملف على disk
- `#cvm-diff-container` + `#cvm-diff-editor` — حاوية Monaco Diff
- `#cvm-publish-btn` — Publish Draft (يظهر فقط عند `out_of_sync`)
- `#cvm-copy-disk-btn` — Copy Disk To Draft (disabled — Phase 6 placeholder)
- `#cvm-close` + `#cvm-close-x` — أزرار الإغلاق

JS مُضاف (IIFE منفصل):
- `openCompareVersions()` — fetch + parse response + populate modal
- `buildDiffEditor()` — lazy-load Monaco إذا لم يكن محملاً + AMD isolation
- `createDiff()` — `monaco.editor.createDiffEditor()` + `setModel()`
- `closeModal()` — cleanup + overflow restore
- `publishBtn.click` → delegates to `#blade-write-btn`
- Escape + backdrop close

---

### `database/seeders/DashboardTranslationsSeeder.php`

إضافة **14 ترجمة جديدة** (Phase 5):

| المفتاح | القيمة |
|--------|--------|
| `dashboard.Compare_Modal_Title` | `'Compare Versions'` |
| `dashboard.Compare_Modal_Subtitle` | `'مقارنة Draft (Monaco) مع الملف على disk'` |
| `dashboard.Compare_Draft_Label` | `'Draft Version'` |
| `dashboard.Compare_Draft_Hint` | `'(blade_source — Monaco)'` |
| `dashboard.Compare_Disk_Label` | `'Disk Version'` |
| `dashboard.Compare_Loading` | `'جاري تحميل المقارنة…'` |
| `dashboard.Compare_Error` | `'تعذّر تحميل المقارنة'` |
| `dashboard.Compare_Publish_Draft` | `'Publish Draft'` |
| `dashboard.Compare_Copy_Disk` | `'Copy Disk To Draft'` |
| `dashboard.Compare_Copy_Disk_Soon` | `'Copy Disk → Draft — قريباً'` |

---

### `docs/COMPARE_VERSIONS_ARCHITECTURE.md`

ملف معمارة كامل — إنشاء جديد.

---

## أوامر النشر

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

لا migration جديد في Phase 5.

---

## Success Criteria — تم تحقيقها ✅

- ✅ لا `file_get_contents()` في `edit()` — Lazy Loading عبر endpoint مستقل
- ✅ المسار يُحسَب عبر `resolvedPath()` فقط — ممنوع `request('path')`
- ✅ Monaco Diff Editor يُحمَّل on-demand — لا تأثير على page load time
- ✅ Left = Draft (blade_source) / Right = Disk File
- ✅ "Publish Draft" يُفوَّض لـ `doWrite()` الموجود — لا منطق مكرر
- ✅ "Copy Disk To Draft" موجود كـ placeholder (disabled) لـ Phase 6
- ✅ AMD conflict مُعالَج بـ 3 مراحل (pre/post loader/require callback)
- ✅ لا Polling / Auto Sync / Background Jobs

---

## التكامل مع الـ Phases السابقة

| المصدر | الاستخدام في Phase 5 |
|--------|---------------------|
| Phase 3 — `FileStatusResolver` | `resolve()` يُعيد `sync_status` لـ JSON response |
| Phase 3 — `SectionTemplateFileWriter::resolvedPath()` | مسار آمن + لا input من المستخدم |
| Phase 4 — `blade_hash` / `disk_hash` | `sync` field في JSON response |
| Phase 4 — Out Of Sync button | نفس `#compare-versions-btn` أصبح active |
| Phase 6 (السابق) — Monaco AMD isolation | نفس النمط مُعاد استخدامه في lazy load |
