# Copy Disk To Draft — Architecture

**Phase:** 6 of the Section Definition Blade System  
**Status:** Implemented ✅  
**Date:** 2026-06-19  
**Depends on:**
- `docs/COMPARE_VERSIONS_ARCHITECTURE.md` (Phase 5 — Monaco Diff Editor + Compare Modal)
- `docs/OUT_OF_SYNC_DETECTION_ARCHITECTURE.md` (Phase 4 — sync detection)

---

## الهدف

تمكين المطور من استيراد محتوى الملف الموجود على disk مباشرةً إلى Monaco Editor، بشكل آمن وواضح، دون أي حفظ أو نشر تلقائي.

---

## الفرق الجوهري: Copy vs Publish

| الإجراء | ماذا يفعل؟ | هل يُعدَّل قاعدة البيانات؟ | هل يُكتَب الـ disk؟ |
|---------|-----------|--------------------------|---------------------|
| **Copy Disk To Draft** | Disk → Monaco فقط | ❌ لا | ❌ لا |
| **Publish Draft** | Monaco → Disk + DB | ✅ نعم (`blade_source`, `blade_hash`, `blade_written_at`) | ✅ نعم |
| **Save Definition** | Monaco → DB فقط | ✅ نعم (`blade_source`) | ❌ لا |

**القاعدة الصارمة:** Copy Disk To Draft = استبدال محتوى Monaco فقط. لا side effects.

---

## Workflow

```
المطور يفتح Compare Modal
         ↓
يرى الفرق: Draft (يسار) vs Disk (يمين)
         ↓
يضغط "Copy Disk To Draft"
         ↓
Confirmation Dialog:
  "Copy Disk Content To Draft?
   سيتم استبدال محتوى Monaco الحالي.
   لن يتم حفظ أي شيء أو نشره تلقائياً."
         ↓ (تأكيد)
setCode(_cachedDiskContent)          ← استبدال محتوى Monaco
updateFieldIndicators()              ← تحديث مؤشرات الحقول
updateStats()                        ← تحديث الإحصائيات
closeModal()                         ← إغلاق Compare Modal
showWriteToast('success', ...)       ← إشعار النجاح
```

---

## Safety Model

```
✅ مسموح:
  - setCode(diskContent)       ← Monaco API فقط
  - updateFieldIndicators()
  - updateStats()
  - closeModal()
  - showWriteToast()

❌ ممنوع تماماً:
  - أي fetch/AJAX request
  - أي DB write (saveQuietly, update, etc.)
  - استدعاء doWrite()
  - استدعاء doGenerateAndWrite()
  - تحديث blade_hash / disk_hash / blade_written_at
  - أي نشر تلقائي
```

---

## مصدر diskContent — بدون طلب جديد

`diskContent` مُخزَّن في `_cachedDiskContent` داخل closure الـ IIFE منذ استجابة `compareBlade()` في Phase 5:

```javascript
// عند fetch ناجح في openCompareVersions():
var diskContent = json.disk || '';
_cachedDiskContent = diskContent;  // ← closure variable

// عند الضغط على Copy:
setCode(_cachedDiskContent);  // ← بدون أي request جديد
```

**لماذا هذا مهم؟** لا يوجد `file_get_contents()` إضافي — المحتوى مُحمَّل مسبقاً عند فتح الـ modal.

---

## ظهور الزر — متى يُعرض؟

| syncStatus | هل يظهر؟ | هل مُفعَّل؟ | السبب |
|-----------|----------|------------|-------|
| `out_of_sync` | ✅ | ✅ | Monaco مختلف عن disk — يمكن الاستيراد |
| `external_change` | ✅ | ✅ | disk تغيَّر خارجياً — الاستيراد مفيد |
| `in_sync` | ❌ | — | لا فرق — لا داعي للاستيراد |
| `unknown` | ❌ | — | لا ملف — لا شيء للاستيراد |

```javascript
if (copyDiskBtn && (syncStatus === 'out_of_sync' || syncStatus === 'external_change')) {
    copyDiskBtn.style.display = '';
    copyDiskBtn.disabled      = false;
}
```

---

## Confirmation Dialog

```
Copy Disk Content To Draft?

سيتم استبدال محتوى Monaco الحالي.
لن يتم حفظ أي شيء أو نشره تلقائياً.

[إلغاء]  [موافق]
```

Native `window.confirm()` — لا dependency إضافية.

---

## Success Toast

بعد النسخ الناجح:
```
✓  تم نسخ Disk إلى Draft
   تذكر الحفظ أو النشر إذا أردت الاحتفاظ بالتغييرات.
```

يستخدم `showWriteToast('success', ...)` الموجودة — نفس toast نظام Write.

---

## حالات الاستخدام

### Scenario 1 — External Change
المطور فعل `git pull` أو `ssh edit` على الملف مباشرة.  
النتيجة: `sync_status = external_change`

**الخطوات:**
1. يرى badge "🟠 External Change"
2. يفتح Compare Versions
3. يرى التغييرات الخارجية في اليمين (Disk)
4. يضغط "Copy Disk To Draft"
5. Monaco يصبح = Disk
6. يضغط "Save Definition" إذا أراد حفظ blade_source في DB

### Scenario 2 — Out Of Sync (تراجع عن التعديلات)
المطور عدَّل Monaco لكن أدرك أن التعديلات خاطئة.  
النتيجة: `sync_status = out_of_sync`

**الخطوات:**
1. يرى badge "🟡 Out Of Sync"
2. يفتح Compare Versions
3. يرى الفرق
4. يقرر الرجوع لنسخة Disk
5. يضغط "Copy Disk To Draft"
6. Monaco يعود لمحتوى الـ disk

---

## ما بعد Copy — حالة Sync

بعد `setCode(diskContent)`:
- `monacoInstance.getValue()` يُعيد `diskContent`
- `sha256(blade_source_in_db) !== sha256(diskContent)` إذا كان blade_source لم يُحدَّث
- `sync_status` قد يبقى `out_of_sync` حتى يُعيد المطور تحميل الصفحة

**لماذا لا نحدّث blade_hash تلقائياً؟**  
لأن Monaco تغيَّر لكن DB لم يتغير. الـ sync_status الصحيح هو "out_of_sync" (Monaco ≠ آخر write) — لكن الآن المحتوى = disk.  
تحديث الـ DB يتطلب: إما "Save" أو "Publish Draft".

---

## بنية الملفات

```
resources/views/dashboard/section_definitions/edit.blade.php
  ├── #cvm-copy-disk-btn  (HTML: btn-warning, disabled, display:none)
  └── JS IIFE:
      ├── _cachedDiskContent = null      (closure variable)
      ├── resetModal(): disabled + hidden
      ├── fetch success: enable + show (للحالات المناسبة)
      └── copyDiskBtn.click:
            confirm → setCode → updateIndicators → updateStats → close → toast

database/seeders/DashboardTranslationsSeeder.php
  └── 5 Phase 6 translation keys
```

---

## Roadmap

| Phase | الوصف | الحالة |
|-------|-------|--------|
| Phase 3 | File Status Indicator | ✅ |
| Phase 4 | Out Of Sync Detection | ✅ |
| Phase 5 | Compare Versions (Diff Editor) | ✅ |
| Phase 6 | Copy Disk To Draft | ✅ |
| Phase 7 | Auto-detect External Changes (polling/event) | 🔜 |
| Phase 8 | Merge / Three-way Diff | 🔜 |
