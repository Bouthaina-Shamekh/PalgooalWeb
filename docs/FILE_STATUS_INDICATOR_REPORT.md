# File Status Indicator — Implementation Report

**Phase:** 3  
**Status:** Implemented ✅  
**Date:** 2026-06-19

---

## الملفات المُنشأة

### `app/Support/Sections/FileStatusResolver.php`

Service جديدة مسؤولة عن تحديد حالة ملف Blade لـ SectionDefinition.

**المدخل:** `SectionDefinition $definition`  
**المخرج:**
```php
[
    'status'       => 'missing'|'published'|'external'|'invalid',
    'label'        => 'Missing'|'Published'|'External'|'Invalid',
    'color'        => 'gray'|'green'|'orange'|'red',
    'icon'         => 'ti-circle-dashed'|'ti-circle-check-filled'|'ti-alert-triangle-filled'|'ti-ban',
    'view_name'    => 'front.sections.{category}.{key}'|null,
    'display_path' => 'resources/views/front/sections/{category}/{key}.blade.php',
]
```

**منطق التحديد (مرتّب بالأولوية):**
1. `resolvedPath() === null` → `invalid`
2. `! file_exists($path)` → `missing`
3. `blade_written_at !== null` → `published`
4. otherwise → `external`

**الفرق الجوهري عن `SectionTemplateFileWriter::fileStatus()`:**  
الـ Resolver يستخدم `blade_written_at` (الـ Sync Marker حسب ADR) وليس `blade_source === null`.

---

## الملفات المُعدَّلة

### `app/Http/Controllers/Admin/SectionDefinitionController.php`

**التغييرات في `edit()`:**

```php
// قبل
$writer          = app(SectionTemplateFileWriter::class);
$bladeFileStatus = $writer->fileStatus($sectionDefinition);  // 'exists'|...
// ...
if (empty($sectionDefinition->blade_source) && in_array($bladeFileStatus, ['exists', 'external'])) {
// ...
compact('bladeFileStatus', 'bladeExpectedPath')

// بعد
$writer            = app(SectionTemplateFileWriter::class);
$resolver          = app(FileStatusResolver::class);
$fileStatus        = $resolver->resolve($sectionDefinition);   // array كامل
$bladeFileStatus   = $fileStatus['status'];                    // 'published'|...
// ...
if (empty($sectionDefinition->blade_source) && in_array($bladeFileStatus, ['published', 'external'])) {
// ...
compact('fileStatus', 'bladeFileStatus', 'bladeExpectedPath')
```

**المتغيرات المُمرَّرة للـ View:**
- `$fileStatus` ← array كامل (جديد)
- `$bladeFileStatus` ← string مختصر (محافظ على التوافق)
- `$bladeExpectedPath` ← string المسار (بدون تغيير)

### `resources/views/dashboard/section_definitions/edit.blade.php`

**أ. تحديث 4 مراجع `=== 'exists'` → `=== 'published'`:**

| السطر (تقريبي) | المكان | التغيير |
|--------------|--------|---------|
| Header badge | `page-header` status pill | `'exists'` → `'published'` |
| Tab nav button | Badge الدائرة الخضراء | `'exists'` → `'published'` |
| Write button (main) | `data-confirm` attribute | `'exists'` → `'published'` |
| Write button (sidebar) | `data-confirm` attribute | `'exists'` → `'published'` |

**ب. استبدال Path Card بـ File Status Card:**

قبل — بطاقة بسيطة تعرض المسار فقط:
```html
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="flex flex-wrap items-center gap-4">
            <i class="ti ti-file-code ..."></i>
            <code>resources/views/...</code>
            [آخر كتابة: ...]
            [نسخ]
        </div>
    </div>
</div>
```

بعد — File Status Card بصفّين:

```
┌─────────────────────────────────────────────────────────┐
│ [🟢 Published]  تم نشر الملف بواسطة النظام             │
│─────────────────────────────────────────────────────────│
│ View: front.sections.content.content_showcase  │  path  │  آخر نشر: منذ 3 ساعات  │ [نسخ] │
└─────────────────────────────────────────────────────────┘
```

### `database/seeders/DashboardTranslationsSeeder.php`

أُضيفت **13 ترجمة جديدة** في قسم `// Phase 3: File Status Indicator`:

---

## مفاتيح الترجمة المُضافة

| المفتاح | القيمة |
|--------|--------|
| `dashboard.File_Status_Missing` | `'Missing'` |
| `dashboard.File_Status_Published` | `'Published'` |
| `dashboard.File_Status_External` | `'External'` |
| `dashboard.File_Status_Invalid` | `'Invalid'` |
| `dashboard.File_Missing_Msg` | `'لم يتم إنشاء الملف بعد — اضغط Generate & Write للنشر'` |
| `dashboard.File_Published_Msg` | `'تم نشر الملف بواسطة النظام'` |
| `dashboard.File_External_Msg` | `'الملف موجود لكنه كُتب خارج النظام — blade_written_at غير مضبوط'` |
| `dashboard.File_Invalid_Msg` | `'المفتاح أو الفئة غير صالح — لا يمكن تحديد المسار'` |
| `dashboard.Blade_View_Name` | `'View:'` |
| `dashboard.Last_Published` | `'آخر نشر:'` |

> **ملاحظة:** Labels الحالات (Missing/Published/External/Invalid) بالإنجليزية عمداً — هي مصطلحات تقنية يجب أن تبقى ثابتة عبر اللغات، مثل "Git", "Commit", "Draft".

---

## وصف الواجهة — الحالات الثلاث

### حالة Missing
```
┌─ File Status ─────────────────────────────────────────────┐
│ ⚪ Missing    لم يتم إنشاء الملف بعد — اضغط Generate...  │
│───────────────────────────────────────────────────────────│
│ View: front.sections.hero.hero  │  resources/views/.../hero/hero.blade.php  │ [نسخ] │
└───────────────────────────────────────────────────────────┘
```

### حالة Published
```
┌─ File Status ─────────────────────────────────────────────┐
│ 🟢 Published   تم نشر الملف بواسطة النظام                │
│───────────────────────────────────────────────────────────│
│ View: front.sections.hero.hero  │  resources/views/.../hero/hero.blade.php  │  آخر نشر: منذ 3 ساعات  │ [نسخ] │
└───────────────────────────────────────────────────────────┘
```

### حالة External
```
┌─ File Status ─────────────────────────────────────────────┐
│ 🟠 External   الملف موجود لكنه كُتب خارج النظام...       │
│───────────────────────────────────────────────────────────│
│ View: front.sections.faq.faq  │  resources/views/.../faq/faq.blade.php  │ [نسخ] │
└───────────────────────────────────────────────────────────┘
```

*(لا يُعرض "آخر نشر" لأن `blade_written_at === null`)*

---

## اختبار الحالات الثلاث

| الحالة | الشرط | كيفية الاختبار |
|-------|--------|----------------|
| **Missing** | قاعدة بيانات فقط، لا ملف على disk | أي SectionDefinition لم يُنشر بعد |
| **Published** | ملف على disk + `blade_written_at` مضبوط | اضغط Generate & Write → ستتحول من Missing/External إلى Published |
| **External** | ملف على disk + `blade_written_at === null` | قم بـ `touch resources/views/front/sections/hero/hero.blade.php` مع إبقاء `blade_written_at = null` في DB |

---

## Success Criteria — تم تحقيقها ✅

عند فتح أي Section Definition، يستطيع المطور الآن معرفة:

- ✅ هل الملف موجود؟ ← Badge `Missing` vs `Published`/`External`
- ✅ هل تم نشره بواسطة النظام؟ ← Badge `Published` يعني `blade_written_at !== null`
- ✅ هل هو خارجي؟ ← Badge `External`
- ✅ أين يوجد؟ ← Disk Path في صف الـ metadata
- ✅ ما اسم الـ View؟ ← View Name `front.sections.{category}.{key}`
- ✅ متى آخر Publish؟ ← "آخر نشر: منذ X" (يظهر فقط إذا `blade_written_at` مضبوط)

**دون الحاجة للضغط على أي زر أو تنفيذ أي إجراء إضافي.**

---

## أوامر يجب تشغيلها بعد النشر

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```
