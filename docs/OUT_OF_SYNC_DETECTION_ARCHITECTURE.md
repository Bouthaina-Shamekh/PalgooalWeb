# Out Of Sync Detection — Architecture

**Phase:** 4 of the Section Definition Blade System  
**Status:** Implemented ✅  
**Date:** 2026-06-19  
**Depends on:**
- `docs/BLADE_SOURCE_OF_TRUTH_ADR.md` (Decision §6 — Option C Hybrid)
- `docs/FILE_STATUS_INDICATOR_ARCHITECTURE.md` (Phase 3)

---

## الهدف

Phase 3 أجاب على سؤال "هل الملف موجود؟" بدقة. Phase 4 يُجيب على سؤال أعمق:

> **"هل ما في Monaco متزامن مع ما على disk؟"**

هذا ضروري لأن:
- قد يُعدّل المطور Monaco ثم ينسى النشر → Disk مختلف عن Draft
- قد يُعدَّل الملف خارجياً (Git pull, SSH) → Disk أحدث من آخر Write

---

## الحالات الأربع

### 1. Unknown (رمادي)

**الشرط:** `blade_hash === null`  
**المعنى:** النظام لم يكتب الملف مطلقاً → لا توجد بصمة محفوظة للمقارنة  
**الإجراء:** اضغط Write لأول مرة لتفعيل التتبع

### 2. In Sync (أخضر)

**الشرط:** `sha256(blade_source) === blade_hash` AND `filemtime($path) <= blade_written_at`  
**المعنى:** Monaco والملف متطابقان تماماً — لا تغييرات معلقة  
**الإجراء:** لا شيء مطلوب

### 3. Out Of Sync (أصفر)

**الشرط:** `sha256(blade_source) !== blade_hash`  
**المعنى:** تم تعديل Monaco بعد آخر Write — التغييرات لم تُنشر بعد  
**الإجراء:** اضغط Write لنشر التغييرات الجديدة

### 4. External Change (برتقالي)

**الشرط:** `sha256(blade_source) === blade_hash` AND `filemtime($path) > blade_written_at->timestamp`  
**المعنى:** Monaco لم يتغير، لكن الملف على disk تغيّر بعد آخر Write (Git pull, SSH edit, إلخ)  
**الإجراء:** راجع التغيير الخارجي — قد تحتاج لتحميله في Monaco أو التراجع عنه

> **⚠️ ملاحظة معمارية — Phase 4 vs Phase 5:**
>
> اكتشاف External Change في Phase 4 منفَّذ فعلياً، لكنه يعتمد على **filemtime heuristic** وليس على `disk_hash`.  
> آلية الفحص الحالية: `filemtime($resolvedPath) > $blade_written_at->timestamp`  
>
> `disk_hash` موجود كحقل في DB لكنه **لا يُستخدم** في `resolveSyncStatus()` ولا يتم تحديثه تلقائياً بعد أي تعديل خارجي.  
> هو محجوز حصراً لـ Phase 5 حين يتم فحص المحتوى الفعلي للملف.  
>
> راجع جدول المقارنة في قسم [استراتيجية الأداء](#استراتيجية-الأداء) لمعرفة حدود هذا الأسلوب.

---

## معمارية التخزين

### الحقول المضافة لـ `section_definitions`

```sql
blade_hash  VARCHAR(64) NULL   -- sha256(blade_source) عند آخر Write
disk_hash   VARCHAR(64) NULL   -- sha256(disk_file) عند آخر Write (للمستقبل)
```

**Migration:** `database/migrations/2026_06_19_000001_add_hash_columns_to_section_definitions.php`

### لماذا هما حقلان وليس حقل واحد؟

عند كل Write:
```
blade_source → disk           (Write action)
sha256(blade_source) = hash   (computed in-memory)
blade_hash = hash             (stored in DB)
disk_hash  = hash             (stored in DB — identical to blade_hash at Write time)
```

بعد مرور وقت، قد يتغير أحدهما:
- إذا عدّل المطور Monaco → `sha256(blade_source) ≠ blade_hash` → **Out of Sync**
- إذا عُدِّل الملف خارجياً → `filemtime > blade_written_at` (لكن blade_hash لا يزال يطابق sha256(blade_source)) → **External Change**
- `disk_hash` محفوظ للمستقبل عند تنفيذ Phase 5 (قراءة وتحديث hash الـ disk بشكل دوري)

### حالة `disk_hash` في Phase 4 — توضيح صريح

| الجانب | الحالة الحالية |
|--------|--------------|
| يُحفظ عند Write | ✅ نعم — بنفس قيمة `blade_hash` |
| يُحدَّث بعد تعديل خارجي | ❌ لا — يبقى على قيمة آخر Write |
| يُستخدم في `resolveSyncStatus()` | ❌ لا — الكود لا يقرأه |
| الغرض الحالي | حجز مكان في schema للاستخدام في Phase 5 |

**خلاصة:** `disk_hash` هو حقل "محجوز" (reserved). كتابته الآن تُجنّب migration مستقبلياً، لكنه غير فعّال في منطق الـ detection.

---

## استراتيجية الأداء

### المشكلة

`sync_status` يتغير في كل مرة يُعدَّل Monaco أو يُعدَّل الملف على disk. لا يمكن تخزينه بشكل موثوق.

### الحل

يُحسَب `sync_status` **ديناميكياً** عند كل `resolve()` بدون قراءة الملف:

| العملية | النوع | التكلفة |
|---------|-------|---------|
| `hash('sha256', $blade_source)` | In-memory string hash | ~0.1ms |
| `filemtime($resolvedPath)` | Single `stat()` syscall | ~0.01ms |
| **المجموع** | | **< 0.15ms** |

### القيد الصارم: لا `file_get_contents()`

قراءة محتوى الملف في كل طلب `edit()` مقبولة، لكن حساب `sha256()` على محتواه لا:

```php
// ❌ ممنوع — يقرأ الملف كاملاً في كل طلب
$diskContent = file_get_contents($resolvedPath);
$diskHash = hash('sha256', $diskContent);

// ✅ مسموح — يقرأ blade_source من DB (موجود أصلاً في الذاكرة)
$currentBladeHash = hash('sha256', $definition->blade_source ?? '');

// ✅ مسموح — syscall واحدة للـ mtime
$mtime = @filemtime($resolvedPath);
```

### مقارنة آلية External Change: Phase 4 vs Phase 5

| الجانب | Phase 4 — الحالي | Phase 5 — Roadmap |
|--------|-------------------|-------------------|
| **الآلية** | `filemtime($path) > blade_written_at` | `sha256(disk_file) !== disk_hash` |
| **التكلفة** | `stat()` واحدة — ~0.01ms | `file_get_contents()` + `sha256()` — يتناسب مع حجم الملف |
| **الدقة** | جيدة — تعتمد على وقت التعديل | مطلقة — تعتمد على المحتوى الفعلي |
| **القيود** | لا يكتشف: `touch -t` / تعديل بنفس الثانية / clock drift على NFS | لا قيود على مستوى المحتوى |
| **`disk_hash` مستخدم؟** | ❌ لا | ✅ نعم — المقارنة الأساسية |
| **الحالة** | ✅ منفَّذ | 🔜 Roadmap |

**الخلاصة العملية:** في بيئة التطوير المعتادة (local filesystem, SSH)، filemtime كافٍ تماماً. Phase 5 يُضيف ضماناً على مستوى المحتوى لسيناريوهات النشر الإنتاجي.

---

## تسلسل الأولويات في `resolveSyncStatus()`

```php
// Priority 1: No fingerprint at all
if ($definition->blade_hash === null) → 'unknown'

// Priority 2: Monaco was edited (in-memory comparison — no disk I/O)
if (hash('sha256', $blade_source) !== $blade_hash) → 'out_of_sync'

// Priority 3: Disk was edited externally (single stat() syscall)
if (filemtime($path) > $blade_written_at->timestamp) → 'external_change'

// Priority 4: Everything matches
→ 'in_sync'
```

ترتيب الأولويات مهم: `out_of_sync` يُفحص قبل `external_change` لأنه إذا كان Monaco مختلفاً، فإن أي تغيير خارجي يصبح ثانوياً.

---

## كيفية حفظ الـ Hash عند Write

في `SectionTemplateFileWriter::write()`:

```php
// بعد file_put_contents() الناجح:
$contentHash = hash('sha256', $definition->blade_source);

$definition->blade_hash       = $contentHash;
$definition->disk_hash        = $contentHash;
$definition->blade_written_at = now();
$definition->saveQuietly();
```

نقطتان مهمتان:
1. `sha256(blade_source)` لا `sha256(file_get_contents($path))` — البيانات في الذاكرة
2. كلا الـ hashes متساويان عند Write لأن `blade_source` هو **بالضبط** ما كُتب على disk

---

## العلاقة مع ADR

```
blade_source    = Admin Draft Buffer   (Monaco — قد يختلف عن disk)
Disk File       = Runtime SoT          (Laravel يقرأ منه)
blade_written_at = Sync Marker         (متى كتب النظام الملف)
blade_hash      = Draft Fingerprint    (sha256(blade_source) عند آخر Write)
disk_hash       = Disk Fingerprint     (sha256(disk) عند آخر Write — محفوظ للمستقبل)
```

Phase 4 لا يكسر ADR — الـ Disk File يبقى Runtime SoT. Phase 4 فقط يكشف متى يكون Draft (Monaco) مختلفاً عن آخر Publish.

---

## Compare Versions Button (مستقبلاً — Phase 5)

الزر موجود في الـ UI حالياً لكنه `disabled`:

```blade
<button type="button" disabled class="... cursor-not-allowed"
        title="{{ t('dashboard.Compare_Versions_Soon', 'Compare Versions — قريباً') }}">
    <i class="ti ti-git-diff"></i>
    {{ t('dashboard.Compare_Versions', 'Compare Versions') }}
</button>
```

تنفيذ Phase 5 سيتطلب:
1. `GET /admin/section-definitions/{id}/diff-blade` — endpoint يُعيد `{draft, disk}`
2. Monaco Diff Editor (`editor.createDiffEditor()`) — يُقارن Draft vs Disk جنباً لجنب
3. زر "Use Disk Content" لاستيراد محتوى الملف الخارجي إلى Monaco

---

## Roadmap

| Phase | الوصف | الحالة |
|-------|-------|--------|
| Phase 3 | File Status Indicator (Missing/Published/External/Invalid) | ✅ مكتمل |
| Phase 4 | Out Of Sync Detection (Unknown/In Sync/Out Of Sync/External Change) | ✅ مكتمل |
| Phase 5 | Compare Versions (Diff Editor Monaco) | 🔜 مستقبلاً |
| Phase 6 | Auto-detect External Changes on interval (polling) | 🔜 مستقبلاً |

---

## بنية الملفات

```
app/Support/Sections/
└── FileStatusResolver.php     ← يحسب sync_status ديناميكياً (Phase 3 + 4)
    └── SectionTemplateFileWriter.php  ← يحفظ blade_hash + disk_hash عند Write

database/migrations/
└── 2026_06_19_000001_add_hash_columns_to_section_definitions.php

resources/views/dashboard/section_definitions/
└── edit.blade.php             ← Status Card: File Status Row + Sync Status Row
```
