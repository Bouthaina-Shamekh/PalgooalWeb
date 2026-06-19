# File Status Indicator — Architecture

**Phase:** 3 of the Section Definition Blade System  
**Status:** Implemented ✅  
**Date:** 2026-06-19  
**Depends on:** `docs/BLADE_SOURCE_OF_TRUTH_ADR.md` (Decision §6 — Option C Hybrid)

---

## الهدف

قبل Phase 3، كان المطور لا يعرف حالة ملف Blade الخاص بـ SectionDefinition إلا بعد الضغط على زر ما. هذا يعني:

- قد يضغط على **Write** فيحصل على خطأ "ملف موجود خارجياً"
- قد يتوقع أن الكود في Monaco هو ما سيُنفَّذ، لكن الملف على disk مختلف
- لا يعرف متى آخر مرة تم نشر الملف
- لا يعرف ما هو اسم الـ View الذي يستخدمه Laravel

Phase 3 يحل هذه المشاكل بإظهار **لوحة حالة واضحة** أعلى Monaco مباشرةً.

---

## الحالات الثلاث

### 1. Missing (رمادي)

```
[⚪ Missing]  لم يتم إنشاء الملف بعد — اضغط Generate & Write للنشر
```

**الشرط:** `! file_exists($resolvedPath)`  
**المعنى:** لم يُنشأ أي ملف Blade على disk بعد  
**الإجراء الموصى به:** اضغط Generate & Write (أو اكتب كوداً في Monaco ثم اضغط Write)

### 2. Published (أخضر)

```
[🟢 Published]  تم نشر الملف بواسطة النظام
```

**الشرط:** `file_exists($resolvedPath) && blade_written_at !== null`  
**المعنى:** الملف موجود وتم نشره عبر لوحة الإدارة — `blade_written_at` مضبوط  
**الإجراء الموصى به:** لا شيء. الملف منشور ومتزامن مع ما كتبه النظام.

### 3. External (برتقالي)

```
[🟠 External]  الملف موجود لكنه كُتب خارج النظام — blade_written_at غير مضبوط
```

**الشرط:** `file_exists($resolvedPath) && blade_written_at === null`  
**المعنى:** ملف موجود على disk لكن لم تكتبه لوحة الإدارة — كُتب عبر Git أو يدوياً أو قبل وجود هذا النظام  
**الإجراء الموصى به:** راجع الملف (سيُحمَّل تلقائياً في Monaco إذا كان `blade_source` فارغاً)

### Invalid (أحمر) — حالة نادرة

```
[🔴 Invalid]  المفتاح أو الفئة غير صالح — لا يمكن تحديد المسار
```

**الشرط:** `category` أو `section_key` تحتوي أحرفاً غير مسموح بها (`/^[a-z0-9_-]+$/`)  
**المعنى:** خلل في بيانات التعريف نفسه — يمنع أي عملية كتابة أو قراءة

---

## الفرق الجوهري بين FileStatusResolver و SectionTemplateFileWriter::fileStatus()

| الجانب | `SectionTemplateFileWriter::fileStatus()` | `FileStatusResolver::resolve()` |
|--------|------------------------------------------|--------------------------------|
| **Discriminator** | `blade_source === null` | `blade_written_at === null` |
| **قيمة "موجود ومكتوب"** | `'exists'` | `'published'` |
| **المستند إليه** | منطق داخلي قديم | ADR `BLADE_SOURCE_OF_TRUTH_ADR.md` |
| **الاستخدام** | `write()` + قرارات داخلية | UI, Controller, Status Display |

`blade_written_at` هو الـ Sync Marker الرسمي حسب ADR §6. يُضبط فقط عند `write()` وهو الشاهد الوحيد الموثوق على أن النظام هو من كتب الملف.

---

## معلومات إضافية في الـ Status Card

كل حالة تعرض في الـ Card:

| المعلومة | المصدر | مثال |
|---------|--------|-------|
| **View Name** | `FileStatusResolver::conventionViewName()` | `front.sections.content.content_showcase` |
| **Disk Path** | `SectionTemplateFileWriter::displayPath()` | `resources/views/front/sections/content/content_showcase.blade.php` |
| **Last Published** | `$sectionDefinition->blade_written_at` | "منذ 3 أيام" (diffForHumans) |

الـ View Name مهم لأن المطور قد يحتاجه لـ:
- استدعاء `@include('front.sections.content.content_showcase')`
- التحقق عبر `View::exists('front.sections.content.content_showcase')`
- معرفة مكان الملف في بنية الـ views

---

## العلاقة مع ADR

`docs/BLADE_SOURCE_OF_TRUTH_ADR.md` يحدد:

```
blade_source    = Admin Draft Buffer   (Monaco فقط — لا يُستخدم في الـ rendering)
Disk File       = Runtime SoT          (Laravel يقرأ منه دائماً عبر view())
Write Action    = "Publish"            (نقل من Draft → Artifact)
blade_written_at = Sync Marker         (الشاهد الرسمي أن النظام كتب الملف)
```

Phase 3 يُجسّد هذا القرار في الـ UI:
- **Missing** → Draft Buffer موجود، لكن لم يُنشر بعد  
- **Published** → blade_written_at مضبوط → Sync Marker صحيح  
- **External** → Disk File موجود لكن Sync Marker غائب → النظام لا يعرف تاريخه

---

## العلاقة مع Generate & Write (Phase 2)

Phase 2 أضاف `generateAndWriteBladeFile()` في الـ Controller. بعد نجاحه:
- يُضبط `blade_written_at` = now()
- يصبح Status → **Published** (لأن `blade_written_at !== null`)

Phase 3 يجعل هذا التحول مرئياً للمطور فور إعادة فتح الصفحة.

---

## Roadmap — الحالات المستقبلية

الحالات التالية **لم تُنفَّذ في Phase 3** وتبقى للمستقبل:

| الحالة | الشرط | التحدي |
|-------|--------|--------|
| **Out of Sync** | Disk File يختلف عن `blade_source` | يحتاج hash مقارنة — تكلفة I/O عند كل `edit()` |
| **Modified** | `blade_source` تغيّر بعد آخر Write | يحتاج hash snapshot عند آخر Write |
| **Conflict** | File على disk وبه تغييرات يدوية بعد آخر Published | يحتاج Git integration أو file hash |

هذه الحالات ستكون جزء من **Phase 4 — Sync Status (Out of Sync Detection)** مستقبلاً.

---

## ملف الـ Service

```
app/Support/Sections/FileStatusResolver.php
```

**مسؤوليات الكلاس:**
1. استقبال `SectionDefinition`
2. تحديد المسار عبر `SectionTemplateFileWriter::resolvedPath()`
3. التحقق من `file_exists()`
4. مقارنة `blade_written_at`
5. إرجاع array كامل: `status`, `label`, `color`, `icon`, `view_name`, `display_path`

**يعتمد على:**
- `SectionTemplateFileWriter` — لتحديد المسار
- `SectionTemplateRegistry::normalizeCategory()` — لبناء view_name
- `SectionTemplateRegistry::TEMPLATE_KEY_REGEX` — للتحقق من صلاحية الـ key
