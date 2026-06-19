# BLADE_SOURCE_OF_TRUTH_ADR.md
# قرار معماري: مصدر الحقيقة لملفات Blade في نظام Section Definitions

**النوع:** Architecture Decision Record (ADR)  
**التاريخ:** 2026-06-19  
**آخر تحديث:** 2026-06-19 — توثيق Disk Read-back safety guards + السيناريوهات الأربعة  
**الحالة:** Accepted  
**المرحلة:** Phase 5C+ (بعد File Status Indicator + Sync Hashes)  
**القرار يؤثر على:** BladeGenerator, SectionTemplateFileWriter, Generate & Write flow, Phase 3-5

---

## 1. الخلفية والسياق

### 1.1 ما الذي نحكم عليه؟

نظام Section Definitions يُخزّن محتوى Blade في **مكانَين منفصلَين**:

| المكان | العمود/المسار | الاستخدام الحالي |
|--------|--------------|-----------------|
| **قاعدة البيانات** | `section_definitions.blade_source` | Monaco Editor في لوحة الإدارة |
| **الـ Filesystem** | `resources/views/front/sections/{category}/{key}.blade.php` | Laravel `view()` عند الـ Rendering |

### 1.2 الحالة الراهنة (كما هي)

#### تدفق الكتابة الحالي

```
Admin Panel (Monaco Editor)
    ↓
blade_source (DB) ← saveQuietly()
    ↓
Write to Disk ← SectionTemplateFileWriter::write()
    ↓
Disk File
```

#### تدفق القراءة عند الـ Rendering

```
HTTP Request → SectionRenderer::render()
    ↓
SectionDefinitionFrontendViewDataFactory::build()
    ↓
SectionTemplateRegistry::resolve() → View::exists($view)  ← يفحص disk
    ↓
view($resolvedView, $viewData)  ← Laravel يقرأ من disk حصراً
    ↓
HTML Response
```

**النتيجة الحاسمة:** `blade_source` في DB **لا يُستخدم أبداً** في الـ rendering. Laravel يقرأ من disk فقط عبر `view()`.

#### آلية الـ Disk Read-back (المُطبَّقة حالياً)

في `SectionDefinitionController::edit()` يوجد sync أحادي الاتجاه — **يقرأ من disk إلى Monaco فقط عند فتح صفحة التعديل**:

```php
// Disk Read-back: if blade_source empty + file on disk → pre-populate Monaco
// 'published' = blade_written_at set (admin panel wrote it)
// 'external'  = file exists but blade_written_at is null (external write)
if (empty($sectionDefinition->blade_source) && in_array($bladeFileStatus, ['published', 'external'])) {
    $diskPath = $writer->resolvedPath($sectionDefinition); // security: internal path only
    if ($diskPath !== null && file_exists($diskPath) && is_readable($diskPath)) {
        $diskContent = file_get_contents($diskPath);
        if ($diskContent !== false) {
            $sectionDefinition->blade_source = $diskContent;
            // in-memory only — blade_hash / disk_hash / blade_written_at NOT updated
        }
    }
}
```

**الضمانات الأمنية المطبَّقة:**
- `resolvedPath()` — يُنتج مساراً داخلياً محسوباً من `category + section_key` في DB — لا input من الطلب
- `file_exists()` — تحقق من الوجود الفعلي
- `is_readable()` — تحقق من صلاحيات القراءة قبل أي I/O
- `$diskContent !== false` — حماية من إرجاع `false` في حالة فشل I/O (مثل shared hosting permissions)
- **لا DB writes**: `blade_hash`, `disk_hash`, `blade_written_at` لا تُعدَّل — القراءة ليست "كتابة"

> **ملاحظة تاريخية:** الكود القديم استخدم `['exists', 'external']` (صيغة `SectionTemplateFileWriter::fileStatus()`).  
> بعد إضافة `FileStatusResolver` في Phase 3 أصبح الـ status `'published'` بدلاً من `'exists'`.  
> الكود الحالي يستخدم `['published', 'external']` — وهو ما يعكس هذا التوثيق.

### 1.3 متى يتباعدان؟

| السيناريو | النتيجة |
|-----------|--------|
| تعديل الملف مباشرة على disk (editor خارجي / git pull) | disk ≠ blade_source |
| حفظ blade_source في Monaco لكن Write فشل | blade_source ≠ disk |
| git checkout يستعيد نسخة أقدم من الملف | disk ≠ blade_source |
| backup استعادة لـ DB بدون استعادة disk | blade_source ≠ disk |
| Generate & Write يكتب scaffold → ثم تعديل يدوي على disk | disk أحدث من blade_source |

---

## 2. التعريفات

| المصطلح | التعريف |
|---------|--------|
| **Source of Truth (SoT)** | المصدر الذي يُعتمد عليه عند وجود تعارض |
| **Artifact** | نسخة مشتقة من SoT — يمكن إعادة توليدها |
| **blade_source** | محتوى Blade مُخزَّن في جدول DB |
| **Disk File** | الملف الفعلي على filesystem المستخدم في الـ render |
| **blade_written_at** | timestamp يشير إلى آخر مرة كتب النظام الملف |

---

## 3. الخيارات المقترحة

---

### Option A — Database = Source of Truth

**الفكرة الجوهرية:** `blade_source` هو المرجع الأساسي. ملف disk هو مجرد **cache قابل للإعادة** في أي وقت.

```
blade_source (DB) ← الحقيقة الوحيدة
    ↓
Auto-write to disk عند كل change
    ↓
Disk File ← artifact مؤقت / cache
```

#### المزايا

- **كل شيء في DB**: backup، versioning، audit trail، rollback — كلها ممكنة بأدوات DB
- **Admin UI دائماً صحيح**: Monaco يقرأ SoT مباشرةً
- **لا divergence ممكن**: الكتابة على disk تحدث دائماً من blade_source

#### العيوب

- **يُكسر Git Workflow**: الملف على disk متولَّد تلقائياً ← مطور يعدّله → يُستبدل بالنسخة من DB
- **لا مراجعة كودية (Code Review)**: الـ Blade code في DB، لا في git diff
- **CI/CD معقّد**: يجب "deploy DB first → generate files" قبل أي deployment
- **Marketplace غير ممكن**: كود Blade من Marketplace يجب أن يمر عبر DB → مخاطر أمنية

#### المخاطر

- خطأ في Write mechanism يجعل rendering مكسوراً حتى يُصلح
- الـ DB يصبح critical path لكل page render إذا قررنا render من blade_source مباشرةً
- يُصعّب migration لبيئات متعددة (staging, production)

---

### Option B — Filesystem = Source of Truth

**الفكرة الجوهرية:** الملف على disk هو المرجع الأساسي. `blade_source` هو **draft/buffer مؤقت** للإدارة فقط.

```
Disk File ← الحقيقة الوحيدة
    ↓
blade_source (DB) ← draft buffer / working copy للـ Monaco
    ↓
Write = "Deploy" من buffer إلى SoT
```

#### المزايا

- **Git-native**: كل تعديل على blade file يظهر في git diff، قابل للـ code review
- **Laravel-native**: `view()` يقرأ من disk — لا تغيير في rendering pipeline
- **CI/CD طبيعي**: `git pull` + `php artisan cache:clear` = deployment كامل
- **Team-friendly**: مطور يعدّل الملف مباشرةً بـ IDE والنتيجة فورية
- **Marketplace-ready**: template packages = مجرد مجموعة blade files في git repo

#### العيوب

- **Admin UI يحتاج sync**: إذا تغير الملف خارجياً، Monaco يعرض نسخة قديمة
- **blade_source يصبح "stale by design"**: يجب توضيح دوره بوضوح
- **لا DB-side history**: لا يمكن رؤية كيف تغير الـ Blade code عبر الزمن من DB

#### المخاطر

- مطور يعدّل الملف مباشرةً → يفتح Monaco → يضغط "Write" → يُستبدل تعديله
- إذا فُقد disk (server crash بدون backup) → لا يمكن استعادة الـ Blade code من DB (إذا blade_source قديم)

---

### Option C — Hybrid: DB كـ Draft Buffer، Disk كـ SoT للـ Runtime

**الفكرة الجوهرية:** كل مصدر له **دور محدد ومنفصل** — لا تنافس، بل تكامل.

```
blade_source (DB)
    ├─ دور: Admin Working Copy / Draft
    ├─ يُستخدم: Monaco Editor فقط
    └─ لا يُستخدم في rendering أبداً

Disk File
    ├─ دور: Production Artifact / Runtime SoT
    ├─ يُستخدم: Laravel view() + SectionTemplateRegistry
    └─ يُعتمد عليه لكل page render

Write Action ("توليد وكتابة" / "كتابة الملف")
    └─ الجسر الوحيد: DB Draft → Disk Artifact
```

#### المزايا

- **يعكس الواقع الحالي** بدقة — لا تغيير في rendering pipeline
- **فصل واضح**: blade_source = مسودة إدارية، disk file = نشر فعلي
- **Git-compatible**: disk files محكومة بـ git، blade_source للتوثيق الإداري
- **لا overhead في rendering**: Laravel يقرأ من disk مباشرةً كما يفعل مع أي view

#### العيوب

- **التعقيد الفكري**: مطور جديد قد يسأل "أيهما أصح؟"
- **يحتاج نظام إشارة واضح**: متى تكون النسختان في sync؟ متى لا؟
- **لا automated sync**: تتباعد النسختان إذا لم ينتبه المطور

#### المخاطر

- يتطلب File Status Indicator (Phase 3) ليصبح عملياً — المستخدم لا يعرف الحالة بدونه
- إذا فُقد disk، يجب إعادة الكتابة من blade_source (موجودة في DB)
- إذا فُقد blade_source، لا تاريخ للتعديلات الإدارية (لكن disk يبقى صالحاً)

---

## 4. مقارنة شاملة

| المعيار | Option A (DB) | Option B (Filesystem) | Option C (Hybrid) |
|---------|--------------|----------------------|------------------|
| **Runtime Safety** | ⚠ يحتاج auto-write | ✅ Laravel-native | ✅ لا تغيير |
| **Git Workflow** | ❌ كسر للـ Git | ✅ Git-native | ✅ Git-compatible |
| **Code Review** | ❌ غير ممكن | ✅ Git diff | ✅ Git diff |
| **CI/CD** | ❌ معقد | ✅ `git pull` | ✅ `git pull` |
| **Admin UI** | ✅ دائماً sync | ⚠ يحتاج read-back | ⚠ يحتاج Status Indicator |
| **Backup/Restore** | ✅ DB-only يكفي | ⚠ يحتاج backup للـ disk | ⚠ يحتاج الاثنين |
| **Marketplace** | ⚠ أمنياً خطير | ✅ packages كـ files | ✅ packages كـ files |
| **Team Development** | ❌ conflict-prone | ✅ standard workflow | ✅ standard workflow |
| **تعقيد التنفيذ** | عالٍ | متوسط | منخفض (الحالة الراهنة) |
| **Migration Risk** | عالٍ | منخفض | منخفض |

---

## 5. التحليل الخاص بالمشروع

### 5.1 ما يكشفه الكود الحالي

فحص `SectionDefinitionController::edit()` يكشف آلية موجودة مسبقاً:

```php
// إذا blade_source فارغ لكن الملف موجود → اقرأ من disk
if (empty($sectionDefinition->blade_source) && in_array($bladeFileStatus, ['exists', 'external'])) {
    $diskPath = $writer->resolvedPath($sectionDefinition);
    if ($diskPath && file_exists($diskPath)) {
        $sectionDefinition->blade_source = file_get_contents($diskPath);
    }
}
```

هذا يعني أن **المطورين الذين كتبوا هذا الكود قرروا ضمنياً**: "إذا الملف موجود على disk ولا يوجد blade_source، الـ disk هو الذي نُظهره للمستخدم." — وهو قرار يتسق مع **Option C/B**.

### 5.2 الـ `blade_written_at` كـ Marker

`blade_written_at` حالياً يؤدي مهمة واحدة: يُمِيّز بين "كتبه النظام" و"كتبه مطور خارجياً". هذا تصميم ذكي يدل على أن مصممي النظام توقعوا وجود نسخ disk مستقلة عن DB — وهو تسق مع Filesystem كـ SoT.

### 5.3 Git + Palgoals CMS

ملفات `resources/views/front/sections/` غير مستثناة من `.gitignore` — أي أنها **تُتتبع بـ git**. هذا يعني:

- كل تعديل على blade file يظهر في `git diff`
- deployment يعني `git pull` على الـ server
- الـ production server يحصل على الـ blade files من git، لا من DB

### 5.4 Marketplace — الاعتبار الأهم

في سيناريو Marketplace:
- Template Provider يُسلّم **مجموعة ملفات** (blade + assets)
- هذه الملفات يجب أن تخضع لـ **code review** قبل النشر
- تخزينها في DB مباشرةً يعني تنفيذ كود غير مراجَع على السيرفر
- **الـ Filesystem + Git هو الحاجز الأمني الطبيعي**

### 5.5 الـ Technical Debt الموجود

| الدَّيْن | الأثر |
|---------|------|
| لا Status Indicator في UI | مستخدم لا يعرف إذا DB ≠ disk |
| الـ Read-back في `edit()` جزئي | يحدث فقط إذا `blade_source` فارغ |
| لا artisan sync command | تزامن يدوي فقط |
| لا توثيق رسمي لدور `blade_source` | مطور جديد يخمن |

---

## 6. القرار المُوصى به

```
┌─────────────────────────────────────────────────────────────────────┐
│  RECOMMENDED DECISION                                                 │
│                                                                       │
│  Option C — Hybrid: Filesystem = Runtime SoT                         │
│             DB blade_source = Admin Draft Buffer                     │
│                                                                       │
│  مع تعريف رسمي صريح لدور كل مصدر.                                    │
└─────────────────────────────────────────────────────────────────────┘
```

### السبب المفصّل

**أولاً — هذا ما يفعله النظام فعلاً.** Laravel يقرأ من disk. `blade_source` لا يُستخدم في rendering. القرار الحالي هو C بحكم الأمر الواقع — هذا ADR يُضفي شرعية رسمية على ما هو قائم بدلاً من اختراع هندسة جديدة.

**ثانياً — Option B أفضل نظرياً، لكنه يحتاج migration.** لو بنينا من الصفر، لاخترنا Filesystem as SoT بشكل أنقى (بدون blade_source في DB أصلاً). لكن blade_source موجود، مُستخدم في Monaco، ويؤدي وظيفة حقيقية كـ "admin working copy". حذفه الآن يُكسر UX بدون فائدة.

**ثالثاً — Option A مرفوض بشكل قاطع.** يُكسر Git, CI/CD, Marketplace في آنٍ واحد. النظام الحالي لا يتوجه ذلك الاتجاه وليس من الحكمة الذهاب إليه.

**رابعاً — Option C مع تعريف واضح = الجمع بين أفضل ما في الخيارات:**

| الدور | المصدر | السبب |
|-------|--------|--------|
| **Runtime / Production** | Disk File | Laravel-native، Git-tracked، Marketplace-safe |
| **Admin Draft** | `blade_source` | Monaco working copy، استعادة عند فقد الملف |
| **Publish Action** | Write to Disk | الجسر الوحيد من Draft إلى Production |
| **Sync Signal** | `blade_written_at` | يُخبر النظام إذا كان الملف مُزامَناً أم لا |

### التعريف الرسمي المقترح للأدوار

```
blade_source  = "Admin Draft" (مسودة إدارية)
              ← يُستخدم: Monaco Editor فقط
              ← لا يُستخدم: أي render path

Disk File     = "Production Artifact" (أداة الإنتاج)
              ← يُستخدم: Laravel view() runtime
              ← يُتتبع: Git
              ← يُنشر: CI/CD / git pull

Write Action  = "Publish" (نشر من مسودة إلى إنتاج)
              ← يكتب blade_source → disk file
              ← يضبط blade_written_at

blade_written_at = "Sync Marker" (مؤشر التزامن)
              ← null  → الملف موجود لكن لم يكتبه النظام (external)
              ← date  → آخر publish من هذا النظام
```

---

## 7. ما يُغيّر هذا القرار في الممارسة

### 7.1 لا تغييرات برمجية مطلوبة الآن

الكود الحالي يتوافق مع هذا القرار. القيمة هنا ليست في الكود — بل في الوضوح المعماري للفريق.

### 7.2 Phase 3 (File Status Indicator) أصبح ضرورة، لا رفاهية

بناءً على هذا القرار، لا بد أن يعرف المستخدم:
- `missing` → لم يُنشر بعد
- `in-sync` → `blade_written_at` حديث + disk = blade_source (إذا كان متاحاً للمقارنة)
- `published` → الملف موجود ومن هذا النظام (`blade_written_at != null`)
- `external` → الملف موجود لكن من خارج النظام (`blade_written_at = null`)

### 7.3 قاعدة جديدة للمطورين

```
❌ لا تعدّل الـ disk file مباشرةً إذا كنت تعمل عبر Admin Panel.
✅ عدّل في Monaco → اضغط "Write to Disk" → Git يلتقط التغيير.

❌ لا تفترض أن blade_source = ما هو على disk.
✅ استخدم blade_written_at + File Status لمعرفة حالة التزامن.
```

### 7.4 عند وجود Marketplace مستقبلاً

- Template packages = مجلد `front/sections/{category}/` يُستخدم عبر git submodule أو package installer
- `blade_source` يُملأ تلقائياً بعد installation (disk → DB read-back)
- لا يُسمح بتنفيذ `blade_source` من Marketplace مباشرةً على disk بدون review

---

## 8. إجابات على الأسئلة المطلوبة

### س1: ما هو النموذج الأفضل للنظام الحالي؟

**Option C (Hybrid) كما هو قائم.** `blade_source` كـ Admin Draft، disk كـ Runtime SoT. الكود الحالي يعكس هذا بالفعل — نحتاج فقط توثيقه رسمياً وإضافة File Status Indicator لجعله واضحاً للمستخدم.

### س2: ما هو النموذج الأفضل عند وجود Marketplace؟

**Option B (Filesystem = Full SoT) تدريجياً.** مع توسع المنصة، الاتجاه الطبيعي هو تقليص دور `blade_source` وزيادة اعتماد Git + package management. `blade_source` يُصبح "display cache" فقط.

الانتقال من C إلى B يحدث تدريجياً:
- المرحلة الحالية: C (Hybrid)
- مع Marketplace: B+ (Filesystem Primary + read-only DB cache)

### س3: هل يوجد Technical Debt حالياً؟

نعم، ثلاثة عناصر:

1. **لا Status Indicator** → Phase 3 يحلها
2. **Read-back في `edit()` جزئي** (يحدث فقط إذا `blade_source` فارغ، لا عند اختلاف المحتوى) → يحتاج تحسيناً مستقبلاً
3. **لا وثيقة رسمية لدور كل مصدر** → هذا ADR يحلها

### س4: هل نحتاج أي تغييرات قبل Phase 3؟

**لا — لا تغييرات برمجية مطلوبة.** هذا ADR هو التحضير المطلوب. Phase 3 (File Status Indicator) تبني مباشرةً على هذا القرار بعرض حالة التزامن للمستخدم.

---

## 9. مسار الانتقال المستقبلي (للمرجعية)

```
المرحلة الحالية (C - Hybrid)
    blade_source = Admin Draft
    Disk = Runtime SoT
    Write = Manual Publish

↓ Phase 3 (File Status Indicator)

المرحلة المتوسطة (C Enhanced)
    + Status Indicator يُظهر: missing / published / external
    + Read-back محسّن عند التعارض

↓ عند Marketplace (Phase N)

المرحلة المتقدمة (B+ / Filesystem Primary)
    + Template Packages via Git
    + blade_source = read-only display cache
    + DB لا تُولَّد من blade_source أبداً
```

---

## 10. الخلاصة

| العنصر | القرار |
|--------|--------|
| **SoT للـ Runtime** | Disk File |
| **SoT للـ Admin** | blade_source (Draft Buffer) |
| **الجسر** | Write Action = "Publish" |
| **Signal التزامن** | blade_written_at |
| **مطلوب الآن** | توثيق رسمي (هذا الملف) + Phase 3 |
| **مطلوب للـ Marketplace** | تعزيز Filesystem كـ Full SoT |

---

---

## 11. Disk Read-back — السيناريوهات الأربعة

السيناريوهات التالية تُوثّق السلوك الكامل لـ `edit()` عند فتح صفحة التعديل:

### السيناريو أ — ملف موجود + `blade_source` فارغ (الحالة التي تم إصلاحها)

```
SectionDefinition #18 (content_showcase)
  blade_source = null
  blade_written_at = "2026-06-15 10:30:00"  → status = 'published'
  blade_hash = null                           → sync_status = 'unknown'
  disk: resources/views/front/sections/showcase/content_showcase.blade.php ✅

في edit():
  1. bladeFileStatus = 'published'
  2. empty(null) = true → enters read-back block
  3. resolvedPath() = ".../showcase/content_showcase.blade.php"
  4. file_exists() = true ✅
  5. is_readable() = true ✅
  6. file_get_contents() = "<section>...</section>" (non-false) ✅
  7. blade_source ← disk content (in-memory only)

Monaco: يعرض محتوى الملف ✅
blade_written_at: لم تُعدَّل ✅
blade_hash / disk_hash: لم تُعدَّلا ✅
```

### السيناريو ب — لا ملف + `blade_source` فارغ

```
SectionDefinition (جديد)
  blade_source = null
  blade_written_at = null  → status = 'missing'
  disk: لا يوجد ملف

في edit():
  1. bladeFileStatus = 'missing'
  2. in_array('missing', ['published', 'external']) = false → لا يدخل الـ block

Monaco: فارغ (سلوك صحيح — لا يوجد محتوى لعرضه) ✅
```

### السيناريو ج — `blade_source` غير فارغ (الحالة الطبيعية)

```
SectionDefinition (محرَّر عبر Monaco سابقاً)
  blade_source = "<section>...</section>"
  blade_written_at = "2026-06-15"  → status = 'published' / 'out_of_sync'

في edit():
  1. empty("<section>...</section>") = false → لا يدخل الـ block

Monaco: يعرض blade_source من DB مباشرةً ✅
```

### السيناريو د — ملف خارجي + `blade_source` فارغ (External)

```
SectionDefinition
  blade_source = null
  blade_written_at = null  → status = 'external' (ملف موجود من git pull أو مطور)
  disk: resources/views/front/sections/{cat}/{key}.blade.php ✅

في edit():
  1. bladeFileStatus = 'external'
  2. empty(null) = true → enters read-back block
  3. resolvedPath() = valid path ✅
  4. file_exists() = true ✅
  5. is_readable() = true ✅
  6. file_get_contents() = disk content ✅
  7. blade_source ← disk content (in-memory only)

Monaco: يعرض محتوى الملف كـ Draft (للمشاهدة والتعديل) ✅
ملاحظة: هذا لا يُعني أن الـ blade_source أصبح "مُزامَناً" مع disk —
المطور يجب أن يضغط "Write to Disk" بعد أي تعديل لتحديث الملف الفعلي.
```

---

### قائمة الـ Technical Debt المُحدَّثة (ما بعد الإصلاح)

| الدَّيْن | الحالة |
|---------|-------|
| لا Status Indicator في UI | ✅ **تمّ**: Phase 3 أضافت File Status Card |
| Read-back في `edit()` يفتقر لـ `is_readable()` وفحص `false` | ✅ **تمّ في هذه الجلسة** |
| لا توثيق رسمي لدور `blade_source` | ✅ **تمّ**: هذا ADR |
| الـ Read-back يحدث فقط إذا `blade_source` فارغ (لا عند divergence) | ⚠ **مفتوح** — Compare Versions (Phase 5C) يغطي هذا عبر UI |

---

*مُراجَع مع: SectionTemplateFileWriter, SectionDefinitionController (edit + writeBladeFile + generateAndWriteBladeFile), SectionRenderer, SectionTemplateRegistry, SectionDefinitionFrontendViewDataFactory, FileStatusResolver*  
*الكود المرجعي الحاسم: `edit()` (read-back block)، `SectionRenderer::renderDefinitionDriven()` (view() من disk)، `SectionTemplateRegistry::resolve()` (`View::exists()` على disk)*  
*آخر تحديث: 2026-06-19 — إصلاح Disk Read-back safety guards*
