# Generate Blade File — Phase 2 Implementation Report

**Date:** 2026-06-19  
**Phase:** Phase 2 — Generate & Write Blade File  
**Status:** Implemented ✅ — Validated

---

## 1. الهدف

إضافة flow جديد يتيح توليد Blade scaffold من تعريفات الحقول وكتابته مباشرة إلى الـ disk بضغطة واحدة، بدون المرور بمحرر Monaco.

**قبل Phase 2 (Preview Only):**
```
Scaffold من الحقول → Preview Modal → إدراج في Monaco → تعديل يدوي → كتابة الملف
```

**بعد Phase 2 (Generate & Write):**
```
Scaffold من الحقول → Preview Modal → توليد وكتابة مباشرة → ملف موجود على disk فوراً
```

---

## 2. القرار المعماري

### البنية الموجودة مسبقاً (لم تُبنَ من الصفر)

| المكوّن | الملف | الحالة |
|---------|-------|--------|
| `BladeGenerator::generate()` | `app/Support/Sections/BladeGenerator.php` | ✅ موجود |
| `SectionTemplateFileWriter::write()` | `app/Support/Sections/SectionTemplateFileWriter.php` | ✅ موجود |
| `SectionTemplateFileWriter::resolvedPath()` | نفس الملف | ✅ موجود |
| `SectionTemplateFileWriter::fileStatus()` | نفس الملف | ✅ موجود |
| `writeBladeFile()` controller | `SectionDefinitionController` | ✅ موجود |
| Route `write-blade` | `routes/dashboard.php` | ✅ موجود |
| Preview Modal | `section_definitions/edit.blade.php` | ✅ موجود |

Phase 2 هو **UI + controller bridge** فقط — يربط مكوّنين موجودَين في عملية واحدة.

### المسار المعتمد للملفات

```
resources/views/front/sections/{category}/{section_key}.blade.php
```

**ممنوع نهائياً:**
```
❌ resources/views/components/sections/
❌ resources/views/sections/
```

---

## 3. الملفات المعدّلة

### `config/sections.php`
**التغيير:** إضافة `template_file_writer.base_path` بدلاً من hardcoded path.

```php
'template_file_writer' => [
    'base_path' => resource_path('views/front/sections'),
],
```

### `app/Support/Sections/SectionTemplateFileWriter.php`
**التغيير:** `__construct()` يقرأ المسار من الـ config بدلاً من hardcoded.

```php
// Before:
$this->baseDir = resource_path('views/front/sections');

// After:
$this->baseDir = config(
    'sections.template_file_writer.base_path',
    resource_path('views/front/sections')
);
```

### `app/Http/Controllers/Admin/SectionDefinitionController.php`
**التغيير:** إضافة method جديدة `generateAndWriteBladeFile()`.

### `routes/dashboard.php`
**التغيير:** إضافة route جديد داخل `section-definitions` group.

### `resources/views/dashboard/section_definitions/edit.blade.php`
**التغييرات:**
1. إضافة `generateWriteUrl` لـ `window.__sdEditorData`
2. إضافة زر "توليد وكتابة مباشرة" في footer الـ Preview Modal
3. إضافة `doGenerateAndWrite(force)` function + event listener

### `database/seeders/DashboardTranslationsSeeder.php`
**التغيير:** إضافة 6 مفاتيح ترجمة جديدة.

---

## 4. الـ Endpoint الجديد

```
POST /admin/section-definitions/{id}/generate-and-write-blade
Route name: dashboard.section_definitions.generate_write_blade
Authorization: update policy على SectionDefinition
```

### Request params

| الحقل | النوع | الوصف |
|-------|-------|--------|
| `_token` | string | CSRF token (مطلوب) |
| `force` | boolean | `1` لتجاوز Overwrite Guard |

### Response — نجاح (200)

```json
{
  "ok": true,
  "message": "تم توليد وكتابة ملف Blade بنجاح.",
  "path": "resources/views/front/sections/hero/hero_main.blade.php",
  "view": "front.sections.hero.hero_main",
  "written_at": "2026-06-19 14:30:00",
  "stats": {
    "fields": 6,
    "repeaters": 1,
    "components": 2,
    "component_names": ["Intro", "CTA"]
  },
  "scaffold": "@php\n  $title = ...\n@endphp\n<section>...</section>"
}
```

### Response — تحذير Overwrite (409)

```json
{
  "ok": false,
  "requires_confirmation": true,
  "warning": "الملف موجود مسبقاً وتم إنشاؤه خارج النظام. هل تريد الكتابة فوقه؟",
  "path": "resources/views/front/sections/hero/hero_main.blade.php"
}
```

### Response — خطأ Path (422)

```json
{
  "ok": false,
  "error": "مفتاح السكشن أو التصنيف غير صالح — تعذّر تحديد مسار الملف."
}
```

### Response — خطأ كتابة (500)

```json
{
  "ok": false,
  "error": "فشل توليد وكتابة ملف Blade.: [تفاصيل الخطأ]"
}
```

---

## 5. سلوك الـ Overwrite Guard

### القاعدة:

| الحالة | `blade_written_at` | السلوك |
|--------|-------------------|--------|
| الملف غير موجود | أي قيمة | ✅ إنشاء مباشر |
| الملف موجود، كتبه النظام سابقاً | مضبوطة (not null) | ✅ إعادة توليد آمنة (Safe Regenerate) |
| الملف موجود، أُنشئ خارج النظام | null | ⚠ 409 — يطلب تأكيداً |
| `force=1` مُرسَل | أي قيمة | ✅ كتابة فوق الملف |

### المنطق في الـ Controller:

```php
$fileExistsOnDisk   = file_exists($resolvedPath);
$wasSystemGenerated = $sectionDefinition->blade_written_at !== null;
$forceOverwrite     = $request->boolean('force');

if ($fileExistsOnDisk && ! $wasSystemGenerated && ! $forceOverwrite) {
    return response()->json(['requires_confirmation' => true, ...], 409);
}
```

### Flow في الـ UI:

1. زر "توليد وكتابة مباشرة" يُرسل POST
2. إذا جاء 409 → `window.confirm()` يظهر للمستخدم
3. إذا وافق → إعادة الإرسال مع `force=1`
4. إذا رفض → لا شيء يحدث

---

## 6. طريقة الاستخدام من الواجهة

```
1. افتح Section Definition (لوحة الإدارة → Section Definitions → تعديل)
2. انتقل إلى تبويب "Blade"
3. اضغط "⚡ Scaffold من الحقول" في شريط الأدوات
4. تظهر Preview Modal مع الـ scaffold المُولَّد
5. اضغط "توليد وكتابة مباشرة" (الزر الأخضر)
6. إذا كان الملف موجوداً وخارج النظام → نافذة تأكيد تظهر
7. عند النجاح:
   - Toast أخضر يظهر مع المسار واسم الـ view
   - Monaco يُحدَّث بالكود المُولَّد
   - الـ modal يُغلق
8. الـ SectionTemplateRegistry يعثر على الملف تلقائياً عبر Convention view
```

---

## 7. أوامر التشغيل المطلوبة بعد النشر

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

---

## 8. التحقق من صحة التنفيذ

### الـ flows القديمة ما زالت تعمل:

| الميزة | الحالة |
|--------|--------|
| زر "⚡ Scaffold من الحقول" → Preview Modal | ✅ يعمل بدون تغيير |
| زر "إدراج في المحرر" | ✅ يعمل بدون تغيير |
| زر "نسخ الكود" | ✅ يعمل بدون تغيير |
| زر "كتابة الملف" (Monaco → disk) | ✅ يعمل بدون تغيير |
| Ctrl+S في Monaco | ✅ يعمل بدون تغيير |
| SectionRenderer + SectionTemplateRegistry | ✅ لم يُمَس |
| BladeGenerator Preview | ✅ لم يُمَس |

### Security validations:

| الفحص | الطريقة |
|-------|--------|
| Path traversal | `str_starts_with($path, $baseDir)` في `SectionTemplateFileWriter::write()` |
| Invalid category/key | regex `/^[a-z0-9_-]+$/` في `resolvedPath()` |
| Empty scaffold | `empty($definition->blade_source)` في `write()` |
| Wrong base path | `config('sections.template_file_writer.base_path')` |
| External file overwrite | Overwrite Guard في `generateAndWriteBladeFile()` |

---

## 9. القيود الحالية

1. **لا Force Regenerate UI مستقل** — التأكيد يتم عبر `window.confirm()` — كافٍ للمرحلة الحالية.
2. **لا دعم base64 للـ generate-and-write** — الـ scaffold مُولَّد على السيرفر مباشرةً، لا يمر بـ POST body — لا حاجة لـ base64.
3. **Monaco يُحدَّث بالكود المُولَّد** فقط عند النجاح (من `json.scaffold` في الـ response).
4. **`blade_written_at` يُضبط** بعد الكتابة الناجحة — يُحوّل الحالة من `missing/external` إلى `exists`.

---

## 10. المرحلة التالية المقترحة

### Phase 3 — File Status Indicator في الـ UI
- عرض حالة الملف الحالية (`missing` / `exists` / `external`) في تبويب Blade
- تحديث الحالة تلقائياً بعد Generate & Write بدون reload الصفحة

### Phase 4 — Generate & Write من صفحة Index
- زر إنشاء Blade file مباشرةً من قائمة Section Definitions
- مفيد لإعداد section_definitions متعددة دفعة واحدة

### Phase 5 — Section Package Generator
- توليد SectionDefinition + Fields + Blade File في خطوة واحدة من Template Library
- المكوّن `storeFromTemplate()` + `generateAndWriteBladeFile()` في Transaction واحدة

---

## ملخص التغييرات

| الملف | نوع التغيير |
|-------|------------|
| `config/sections.php` | إضافة `template_file_writer.base_path` |
| `app/Support/Sections/SectionTemplateFileWriter.php` | قراءة المسار من config |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | إضافة `generateAndWriteBladeFile()` |
| `routes/dashboard.php` | إضافة route `generate_write_blade` |
| `resources/views/dashboard/section_definitions/edit.blade.php` | زر + JS handler في Preview Modal |
| `database/seeders/DashboardTranslationsSeeder.php` | 6 مفاتيح ترجمة جديدة |
| `docs/GENERATE_BLADE_FILE_PHASE_2_REPORT.md` | هذا الملف |

---

## 11. نتائج التحقق (Validation Results)

**تاريخ التحقق:** 2026-06-19 — Static + Filesystem Analysis

---

### أ. التحقق من الكود (Static Code Validation)

| العنصر | النتيجة |
|--------|--------|
| Route `generate_write_blade` في `routes/dashboard.php` | ✅ موجود |
| `generateAndWriteBladeFile()` في Controller | ✅ موجود ومكتمل |
| `template_file_writer.base_path` في `config/sections.php` | ✅ موجود |
| `config('sections.template_file_writer.base_path')` في `SectionTemplateFileWriter` | ✅ موجود |
| `generateWriteUrl` في `window.__sdEditorData` | ✅ موجود |
| زر `#bsm-generate-write` في Preview Modal | ✅ موجود |
| `doGenerateAndWrite(force)` JS function | ✅ موجود (السطر 1234) |
| 6 مفاتيح `Generate_Write_*` في Seeder | ✅ موجودة |

---

### ب. تحليل المسارات للـ 3 Templates

#### Template 1 — Hero

| العنصر | القيمة |
|--------|--------|
| `section_key` | `hero` |
| `category` | `hero` |
| مسار الكتابة | `resources/views/front/sections/hero/hero.blade.php` |
| Convention view | `front.sections.hero.hero` |
| Regex validation | ✅ يمر بـ `/^[a-z0-9_-]+$/` |
| الملف موجود على disk؟ | ❌ غير موجود (الملفات الموجودة: `hero_campaign`, `hero_featured`) |
| نتيجة Overwrite Guard | ✅ Case 1 — الملف مفقود → إنشاء مباشر |
| **ملاحظة:** | `'hero'` في `LEGACY_FRONTEND_SECTION_TYPES` لكن `renderDefinitionDriven()` يُجرَّب أولاً — الـ legacy fallback لا يُستدعى إذا وُجد SectionDefinition بـ primary template |

#### Template 2 — Content Showcase

| العنصر | القيمة |
|--------|--------|
| `section_key` | `content_showcase` |
| `category` | `content` |
| مسار الكتابة | `resources/views/front/sections/content/content_showcase.blade.php` |
| Convention view | `front.sections.content.content_showcase` |
| Regex validation | ✅ يمر |
| الملف موجود على disk؟ | ❌ `content/` directory غير موجود أصلاً |
| تعارض مع الموجود؟ | لا — `showcase/content_showcase.blade.php` في directory مختلف (`showcase` ≠ `content`) |
| نتيجة Overwrite Guard | ✅ Case 1 — الملف مفقود → إنشاء مباشر + إنشاء directory |

#### Template 3 — FAQ Accordion

| العنصر | القيمة |
|--------|--------|
| `section_key` | `faq` |
| `category` | `faq` |
| مسار الكتابة | `resources/views/front/sections/faq/faq.blade.php` |
| Convention view | `front.sections.faq.faq` |
| Regex validation | ✅ يمر |
| الملف موجود على disk؟ | ❌ غير موجود (الموجود: `faq/faq_section.blade.php` — key مختلف) |
| نتيجة Overwrite Guard | ✅ Case 1 — الملف مفقود → إنشاء مباشر |

---

### ج. تحقق منطق Overwrite Guard (الـ 3 حالات)

| الحالة | الشروط | السلوك المتوقع | التحقق |
|--------|--------|---------------|--------|
| **Case 1** — الملف غير موجود | `file_exists() = false` | تجاوز الـ Guard، إنشاء مباشر | ✅ منطق الكود صحيح |
| **Case 2** — الملف موجود + أنشأه النظام | `file_exists() = true`, `blade_written_at != null` | `wasSystemGenerated = true` → تجاوز الـ Guard، إعادة توليد | ✅ منطق الكود صحيح |
| **Case 3** — الملف موجود + خارجي | `file_exists() = true`, `blade_written_at = null`, `force = false` | إرجاع 409 مع `requires_confirmation: true` | ✅ منطق الكود صحيح |
| **Case 3b** — بعد التأكيد | `force = true` | تجاوز الـ Guard، كتابة فوق الملف | ✅ `$request->boolean('force')` |

---

### د. سلسلة الـ Rendering (Static Trace)

```
SectionRenderer::render($section)
  └─ renderDefinitionDriven($section)                       ← يُجرَّب أولاً
       └─ SectionDefinitionFrontendViewDataFactory::build()
            ├─ runtimeTablesAvailable()                     ← يتحقق من وجود الجداول
            ├─ resolveLinkedDefinition($section)            ← يجد SectionDefinition
            ├─ hasPrimaryTemplate($definition)              ← يتحقق من وجود template مرتبط
            └─ SectionTemplateRegistry::resolve(templateKey, category)
                 └─ conventionView(key, cat)
                      → "front.sections.{category}.{section_key}"
                      → يجد الملف الذي كتبناه بـ Generate & Write ✅

  إذا فشل renderDefinitionDriven → renderRegisteredSection()  ← legacy fallback
```

**الشرط الأساسي للعمل:** يجب أن يكون للـ SectionDefinition primary template مرتبط (`hasPrimaryTemplate() = true`).  
`storeFromTemplate()` يُنشئ هذا الربط عبر `syncTemplateSelection()`.

---

### هـ. الحالة الراهنة على الـ Disk

```
resources/views/front/sections/
├── hero/
│   ├── hero_campaign.blade.php      ← موجود (خارجي)
│   ├── hero_featured.blade.php      ← موجود (خارجي)
│   └── [hero.blade.php]             ← غير موجود → سيُنشأ بعد Generate & Write
├── [content/]                       ← directory غير موجود → سيُنشأ
│   └── [content_showcase.blade.php] ← غير موجود
├── faq/
│   ├── faq_section.blade.php        ← موجود (key مختلف، لا تعارض)
│   └── [faq.blade.php]             ← غير موجود → سيُنشأ
├── showcase/
│   └── content_showcase.blade.php   ← موجود في category مختلفة (showcase ≠ content)
└── ...
```

---

### نقطة انتباه: `hero` و LEGACY_FRONTEND_SECTION_TYPES

`SectionRenderer` يحتوي على قائمة `LEGACY_FRONTEND_SECTION_TYPES` تشمل `'hero'`. هذا **لا يمنع** عمل Generate & Write لأن:

1. `renderDefinitionDriven()` يُجرَّب **أولاً** في كل الحالات.
2. إذا وُجدت `SectionDefinition` بـ `section_key = 'hero'` وعليها primary template → يُستخدم الـ convention view مباشرةً.
3. الـ legacy fallback يُستدعى فقط إذا فشل `resolveRenderableDefinition()` (أي لم تُجد SectionDefinition أو لا primary template).

**الخلاصة:** ملف `hero/hero.blade.php` الذي يُنشأ بـ Generate & Write **سيُستخدم** فعلاً عند الـ render — بشرط أن يكون للـ SectionDefinition primary template.

---

### و. تقييم الاستقرار الإنتاجي

| البُعد | التقييم |
|--------|--------|
| **عدم التأثير على flows قائمة** | ✅ Route جديد منفصل، لا تعديل على SectionRenderer أو Registry |
| **Path Security** | ✅ Path traversal مُعالج في SectionTemplateFileWriter |
| **External File Protection** | ✅ Overwrite Guard يحمي الملفات الخارجية |
| **Rollback سهل** | ✅ حذف Route + Method يُعيد الوضع لـ Phase 1 |
| **الـ Convention views صحيحة** | ✅ مُتحقق منها لـ hero, content_showcase, faq |
| **لا DB migrations مطلوبة** | ✅ `blade_written_at` موجود مسبقاً |

**الحكم: Phase 2 مستقرة وآمنة للنشر.**

---

### ز. الـ Tech Debt المُحدَّد

1. **`'hero'` في `LEGACY_FRONTEND_SECTION_TYPES`** — لا يُشكّل خطراً حالياً، لكنه مُربك. يُنصح بمراجعة القائمة وإزالة section_keys التي أصبحت definition-driven.
2. **`window.confirm()` للـ Overwrite Guard** — بسيط وكافٍ حالياً، لكن يمكن استبداله بـ confirmation modal لاحقاً.
3. **لا File Status Indicator في UI** — المستخدم لا يعلم حالة الملف قبل الضغط. مُقترح لـ Phase 3.

---

### ح. توصية المرحلة التالية

| الأولوية | المقترح |
|----------|--------|
| **1 (عالي)** | Phase 3 — File Status Indicator: عرض `exists / missing / external` في تبويب Blade قبل أي action |
| **2 (متوسط)** | تنظيف `LEGACY_FRONTEND_SECTION_TYPES` من keys أصبحت definition-driven |
| **3 (متأخر)** | استبدال `window.confirm()` بـ Modal احترافي للـ Overwrite Guard |
