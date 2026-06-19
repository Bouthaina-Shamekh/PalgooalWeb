# Compare Versions — Architecture

**Phase:** 5 of the Section Definition Blade System  
**Status:** Implemented ✅  
**Date:** 2026-06-19  
**Depends on:**
- `docs/OUT_OF_SYNC_DETECTION_ARCHITECTURE.md` (Phase 4 — sync detection)
- `docs/GENERATE_BLADE_FILE_PRE_IMPLEMENTATION_REVIEW.md` (SectionTemplateFileWriter path resolution)

---

## الهدف

Phase 4 يكتشف **أن** Monaco وdisk مختلفان. Phase 5 يُظهر **ماذا** اختلف — سطراً بسطر — عبر Monaco Diff Editor.

---

## قيد الأداء (Phase 5 Security Constraints)

> **ممنوع `file_get_contents()` في `edit()` أو عند تحميل الصفحة.**

كل قراءة للملف من disk تحدث **فقط** عند الضغط على "Compare Versions" — في endpoint منفصل، بطلب AJAX مستقل.

---

## معمارية Lazy Loading

```
صفحة edit() تُحمَّل  → لا disk I/O / لا Monaco Diff
         ↓
المستخدم يضغط "Compare Versions"
         ↓
AJAX → GET /admin/section-definitions/{id}/compare-blade
         ↓
PHP: file_get_contents($resolvedPath)  ← هنا فقط
         ↓
JSON: { draft, disk, sync, view_name, path }
         ↓
JS: Monaco Diff Editor (lazy-loaded إذا لم يكن محملاً)
```

---

## Route

```php
Route::get('/{sectionDefinition}/compare-blade', [SectionDefinitionController::class, 'compareBlade'])
    ->whereNumber('sectionDefinition')
    ->name('compare_blade');
```

**ملاحظة:** GET وليس POST — قراءة فقط، لا side effects.

---

## Controller — `compareBlade()`

```php
public function compareBlade(SectionDefinition $sectionDefinition): JsonResponse
{
    $this->authorize('update', $sectionDefinition);

    $writer       = app(SectionTemplateFileWriter::class);
    $resolver     = app(FileStatusResolver::class);
    $resolvedPath = $writer->resolvedPath($sectionDefinition);

    // Security: فقط resolvedPath() — ممنوع request('path')
    if (!$resolvedPath) {
        return response()->json(['ok' => false, 'message' => 'لا يوجد مسار محسوب لهذا التعريف.'], 422);
    }
    if (!file_exists($resolvedPath)) {
        return response()->json(['ok' => false, 'message' => 'الملف غير موجود على disk.'], 404);
    }

    $diskContent  = file_get_contents($resolvedPath);  // ← يحدث هنا فقط
    $draftContent = $sectionDefinition->blade_source ?? '';
    $fileStatus   = $resolver->resolve($sectionDefinition);

    return response()->json([
        'ok'        => true,
        'draft'     => $draftContent,
        'disk'      => $diskContent,
        'status'    => $fileStatus['status'],
        'sync'      => $fileStatus['sync_status'],
        'view_name' => $fileStatus['view_name'],
        'path'      => $fileStatus['display_path'],
    ]);
}
```

### قواعد Security الصارمة

| القاعدة | التفاصيل |
|---------|----------|
| ✅ `resolvedPath()` فقط | المسار يُحسَب من `section_key` + `template_key` — ليس من input المستخدم |
| ❌ ممنوع `request('path')` | المستخدم لا يتحكم في المسار |
| ❌ ممنوع `file_get_contents()` في `edit()` | يُستدعى فقط عبر endpoint خاص |
| ✅ `$this->authorize('update', ...)` | التحقق من الصلاحيات قبل أي قراءة |

---

## Response Schema

```json
{
  "ok": true,
  "draft": "{{-- Monaco content --}}\n...",
  "disk":  "{{-- Disk file content --}}\n...",
  "status": "published",
  "sync":   "out_of_sync",
  "view_name": "front.sections.hero.hero_main",
  "path": "resources/views/front/sections/hero/hero_main.blade.php"
}
```

**حالات الخطأ:**

| الحالة | HTTP | `message` |
|--------|------|-----------|
| لا مسار محسوب | 422 | لا يوجد مسار محسوب لهذا التعريف. |
| الملف غير موجود | 404 | الملف غير موجود على disk. |
| خطأ في Authorization | 403 | (Laravel standard) |

---

## Monaco Diff Editor — بنية الـ UI

```
┌─ Compare Modal ──────────────────────────────────────────────────────┐
│ Header: [🟡 Git-diff icon] Compare Versions  │ View: front.sections.X │
│─────────────────────────────────────────────────────────────────────│
│ Column labels:                                                        │
│  [✏️ Draft Version (blade_source — Monaco)] │ [📄 Disk Version /path] │
│─────────────────────────────────────────────────────────────────────│
│                                                                       │
│  Monaco Diff Editor (vs-dark theme, readOnly, renderSideBySide)      │
│  LEFT  = Draft (blade_source)   │  RIGHT = Disk File                 │
│  ← الأصل (original)            │  ← المُعدَّل (modified)            │
│                                                                       │
│─────────────────────────────────────────────────────────────────────│
│ Footer: [✅ Publish Draft] [Copy Disk→Draft (disabled)]  [إغلاق]     │
└──────────────────────────────────────────────────────────────────────┘
```

### Left vs Right

| الجهة | المحتوى | لماذا؟ |
|-------|---------|--------|
| **LEFT (original)** | Draft — `blade_source` | ما يراه المطور في Monaco |
| **RIGHT (modified)** | Disk File | السوت الحالي على disk (Runtime SoT) |

يتوافق هذا مع اتجاه Monaco Diff حيث الـ `original` هو الـ baseline والـ `modified` هو الإصدار الأحدث. من الناحية المعمارية، disk هو الـ Runtime SoT لكن للمطور، "الأصل" هو ما عدّله في Monaco.

---

## Lazy Loading — Monaco Diff Editor

### التسلسل

```javascript
// عند الضغط:
1. fetch(compareUrl) → { draft, disk, sync, ... }
2. buildDiffEditor(draftContent, diskContent)
   a. إذا Monaco محمّل مسبقاً (موجود في الـ tab) → استخدمه مباشرة
   b. إذا لم يُحمَّل → script CDN lazy load → callback → createDiff()
3. createDiff(monaco, draft, disk)
   → monaco.editor.createDiffEditor(container, options)
   → setModel({ original: draftModel, modified: diskModel })
```

### AMD Isolation

Monaco يُعيّن `window.define.amd = true` عند تحميله، مما يُخفق UMD libraries. عند Lazy Load:

```javascript
script.onload = function () {
    window.__monacoRequire = window.require;
    try { window.define.amd = false; } catch (e) {}  // أخفِ AMD من UMD libs
    window.__monacoRequire.config({ paths: { 'vs': '...' } });
    try { window.define.amd = {}; } catch (e) {}    // أعد تفعيله لـ Monaco modules
    window.__monacoRequire(['vs/editor/editor.main'], function (m) {
        createDiff(m || window.monaco, ...);
    });
};
```

---

## Publish Draft — التكامل مع Write Flow

"Publish Draft" لا ينفّذ Write منفرداً — يُفوّض لـ `doWrite()` الموجود:

```javascript
publishBtn.addEventListener('click', function () {
    closeModal();  // أغلق الـ modal أولاً
    var writeBtn = document.getElementById('blade-write-btn');
    if (writeBtn) writeBtn.click();  // شغّل write flow الحالي
});
```

**لماذا هذا التفويض؟**
- `doWrite()` يحتوي: CSRF, URL normalization (Apache /public/ fix), toast, confirm for force-write
- لا داعي لتكرار هذا المنطق في الـ Compare modal

---

## Copy Disk To Draft — Phase 6 (Placeholder)

الزر موجود لكن `disabled` في Phase 5:

```html
<button id="cvm-copy-disk-btn" type="button" class="btn btn-light btn-sm" disabled
        title="Copy Disk → Draft — قريباً">
    <i class="ti ti-arrow-bar-to-right me-1"></i>Copy Disk To Draft
</button>
```

**Phase 6** سيُفعّله: `setCode(diskContent)` + `updateFieldIndicators()` + `updateStats()` + `closeModal()`.

---

## زر Trigger — حالتان

### Out Of Sync (indigo)
```html
<button id="compare-versions-btn" class="btn btn-sm" style="background:#4f46e5;color:#fff;">
    <i class="ti ti-git-diff"></i> Compare Versions
</button>
```
الـ footer يظهر: **Publish Draft** + Copy Disk (disabled)

### External Change (orange)
```html
<button id="compare-versions-btn" class="btn btn-sm" style="background:#ea580c;color:#fff;">
    <i class="ti ti-git-diff"></i> Compare Versions
</button>
```
الـ footer يظهر: **Copy Disk (disabled فقط)** — بدون Publish Draft (Draft = disk عند External Change)

---

## Roadmap

| Phase | الوصف | الحالة |
|-------|-------|--------|
| Phase 3 | File Status Indicator | ✅ |
| Phase 4 | Out Of Sync Detection | ✅ |
| Phase 5 | Compare Versions (Diff Editor) | ✅ |
| Phase 6 | Copy Disk To Draft (في Diff Modal) | 🔜 |
| Phase 7 | Auto-detect External Changes (polling/event) | 🔜 |

---

## بنية الملفات

```
routes/dashboard.php
  └── GET /{sectionDefinition}/compare-blade → compare_blade

app/Http/Controllers/Admin/SectionDefinitionController.php
  └── compareBlade(SectionDefinition $def): JsonResponse

resources/views/dashboard/section_definitions/edit.blade.php
  ├── #compare-versions-btn (out_of_sync row)
  ├── #compare-versions-btn (external_change row)
  ├── #compare-modal (HTML structure)
  └── JS: openCompareVersions / buildDiffEditor / createDiff / closeModal

database/seeders/DashboardTranslationsSeeder.php
  └── Phase 5 translation keys (14 keys)
```
