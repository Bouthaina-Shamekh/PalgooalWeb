# Out Of Sync Detection — Implementation Report

**Phase:** 4  
**Status:** Implemented ✅  
**Date:** 2026-06-19

---

## الملفات المُنشأة

### `database/migrations/2026_06_19_000001_add_hash_columns_to_section_definitions.php`

إضافة حقلي بصمة المحتوى:

```sql
ALTER TABLE section_definitions
    ADD COLUMN blade_hash VARCHAR(64) NULL AFTER blade_written_at,
    ADD COLUMN disk_hash  VARCHAR(64) NULL AFTER blade_hash;
```

---

## الملفات المُعدَّلة

### `app/Models/Sections/SectionDefinition.php`

إضافة `blade_hash` و `disk_hash` للـ `$fillable`:

```php
// Added:
'blade_hash',
'disk_hash',
```

إضافة cast:

```php
'blade_hash' => 'string',
'disk_hash'  => 'string',
```

### `app/Support/Sections/SectionTemplateFileWriter.php`

تحديث `write()` لحفظ الـ hashes بعد كل Write ناجح:

```php
// After file_put_contents():
$contentHash = hash('sha256', $definition->blade_source);
$definition->blade_hash      = $contentHash;
$definition->disk_hash       = $contentHash;
$definition->blade_written_at = now();
$definition->saveQuietly();
```

### `app/Support/Sections/FileStatusResolver.php`

إعادة كتابة كاملة لإضافة sync detection:

**المخرج الجديد لـ `resolve()`:**
```php
[
    'status'       => 'missing'|'published'|'external'|'invalid',
    'label'        => string,
    'color'        => 'gray'|'green'|'orange'|'red',
    'icon'         => string,
    'view_name'    => string|null,
    'display_path' => string,
    // --- جديد في Phase 4 ---
    'sync_status'  => 'unknown'|'in_sync'|'out_of_sync'|'external_change',
    'sync_color'   => 'gray'|'green'|'yellow'|'orange',
    'sync_icon'    => string,
]
```

**التسلسل المنطقي للـ sync detection:**

| الأولوية | الشرط | النتيجة |
|----------|-------|---------|
| 1 | `blade_hash === null` | `unknown` |
| 2 | `sha256(blade_source) !== blade_hash` | `out_of_sync` |
| 3 | `filemtime($path) > blade_written_at->timestamp` | `external_change` |
| 4 | all pass | `in_sync` |

### `resources/views/dashboard/section_definitions/edit.blade.php`

إضافة **Row 2** للـ File Status Card (تظهر فقط لـ `published` و `external`):

```
┌─ File Status Card ────────────────────────────────────────────┐
│ Row 1: [🟢 Published] تم نشر الملف بواسطة النظام             │
│───────────────────────────────────────────────────────────────│
│ Row 2: Sync: [🟢 In Sync] Monaco و disk متطابقان            │
│───────────────────────────────────────────────────────────────│
│ Row 3: View: front.sections.hero.hero_main │ path │ آخر نشر │ نسخ │
└───────────────────────────────────────────────────────────────┘
```

**حالات Row 2:**

| sync_status | Badge | اللون | الرسالة |
|-------------|-------|-------|---------|
| `in_sync` | 🟢 In Sync | أخضر | Monaco و disk متطابقان |
| `out_of_sync` | 🟡 Out Of Sync | أصفر | Monaco يحتوي تغييرات لم تُنشر + زر Compare Versions (معطل) |
| `external_change` | 🟠 External Change | برتقالي | تم تعديل الملف على disk منذ آخر Publish |
| `unknown` | ⚪ Unknown | رمادي | لا يوجد بصمة — اضغط Write لتفعيل التتبع |

### `database/seeders/DashboardTranslationsSeeder.php`

إضافة **14 ترجمة جديدة** في قسم `// Phase 4: Out Of Sync Detection`:

| المفتاح | القيمة |
|--------|--------|
| `dashboard.Sync_Status` | `'Sync:'` |
| `dashboard.Sync_In_Sync` | `'In Sync'` |
| `dashboard.Sync_In_Sync_Msg` | `'Monaco و disk متطابقان'` |
| `dashboard.Sync_Out_Of_Sync` | `'Out Of Sync'` |
| `dashboard.Sync_Out_Of_Sync_Msg` | `'Monaco يحتوي تغييرات لم تُنشر بعد'` |
| `dashboard.Sync_External_Change` | `'External Change'` |
| `dashboard.Sync_External_Change_Msg` | `'تم تعديل الملف على disk منذ آخر Publish'` |
| `dashboard.Sync_Unknown` | `'Unknown'` |
| `dashboard.Sync_Unknown_Msg` | `'لا يوجد بصمة محفوظة — اضغط Write لتفعيل التتبع'` |
| `dashboard.Compare_Versions` | `'Compare Versions'` |
| `dashboard.Compare_Versions_Soon` | `'Compare Versions — قريباً'` |

---

## التحقق من الحالات الثلاث

### التحقق 1 — In Sync

1. افتح أي SectionDefinition مع ملف منشور
2. اضغط Write (أو Generate & Write)
3. النتيجة المتوقعة: `sync_status = in_sync` (🟢 In Sync)

**السبب:** `blade_hash = hash('sha256', blade_source)` يُحفظ. عند `resolve()`: `sha256(blade_source) === blade_hash` ✓ و `filemtime <= blade_written_at` ✓

### التحقق 2 — Out Of Sync

1. افتح SectionDefinition منشور (In Sync)
2. عدّل Monaco بإضافة أي نص (لا تضغط Write)
3. احفظ نموذج التعريف فقط (Ctrl+S أو Save Definition)
4. أعد فتح الصفحة
5. النتيجة المتوقعة: `sync_status = out_of_sync` (🟡 Out Of Sync)

**السبب:** `blade_source` تغيّر → `hash('sha256', blade_source) !== blade_hash` (blade_hash ما زال القيمة القديمة)

### التحقق 3 — External Change

1. افتح SectionDefinition منشور (In Sync)  
2. عدّل الملف مباشرة على السيرفر:
   ```bash
   echo "<!-- external edit -->" >> resources/views/front/sections/category/key.blade.php
   ```
3. أعد فتح الصفحة
4. النتيجة المتوقعة: `sync_status = external_change` (🟠 External Change)

**السبب:** `blade_source` لم يتغير → `sha256` يطابق `blade_hash` ✓، لكن `filemtime($path) > blade_written_at->timestamp` ✗

---

## أوامر النشر

```bash
# 1. تشغيل Migration
php artisan migrate

# 2. تحديث الترجمات
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

> **ملاحظة:** `blade_hash` و `disk_hash` سيكونان NULL لجميع السجلات الموجودة حتى أول Write.  
> هذا طبيعي تماماً — ستظهر Sync Status = **Unknown** حتى يُنشر كل تعريف مجدداً.

---

## Success Criteria — تم تحقيقها ✅

- ✅ المطور يعرف **بدون أي إجراء** إذا كانت Monaco تحتوي تغييرات غير منشورة
- ✅ لا `file_get_contents()` في كل Request — فقط in-memory hash + single `stat()` syscall
- ✅ `sync_status` مُحسَّب ديناميكياً — لا يمكن أن يصبح stale في الـ DB
- ✅ `Compare Versions` placeholder موجود للـ `out_of_sync` state (تنفيذ Phase 5)
- ✅ Sync Row مرئي فقط عند وجود ملف (published/external) — مخفي عند missing/invalid

---

## الفرق الجوهري: File Status vs Sync Status

| الجانب | File Status (Phase 3) | Sync Status (Phase 4) |
|--------|----------------------|----------------------|
| **السؤال** | "هل الملف موجود؟" | "هل Draft = Disk؟" |
| **المصدر** | `file_exists()` + `blade_written_at` | `sha256(blade_source)` vs `blade_hash` + `filemtime` |
| **يتغير عند** | Write / Delete | Edit Monaco / Write / External disk change |
| **يُخزَّن في DB** | `blade_written_at` (مؤشر) | `blade_hash`, `disk_hash` (بصمات) |
| **مرئي لـ** | دائماً | فقط عند وجود ملف (published/external) |
