# Section Definition Blade Tab — Improvement Report

**Date:** 2026-06-18  
**Scope:** Blade tab UX improvements في `section-definitions/{id}/edit`  
**Status:** ✅ Completed (3 تحسينات)

---

## Audit — نتائج الفحص السريع

| السؤال | النتيجة |
|--------|---------|
| أكبر ازدحام في Blade tab؟ | الـ sidebar — بدون scroll، بدون بحث، يطول مع كثرة الحقول |
| Monaco: eager أم lazy؟ | **Eager** — يُحمّل فوراً من CDN بغض النظر عن التبويب المفتوح |
| Field Sidebar مع حقول كثيرة؟ | **غير قابل للاستخدام** — لا max-height، لا scroll، لا بحث |
| أزرار scaffold/write واضحة؟ | نعم — ملائمة |
| window.confirm() موجود؟ | **4 مواضع**: overwrite file / scaffold missing / scaffold replace / clear code |
| إمكانية تحسين بدون refactor؟ | **نعم** — max-height + field search + safety tips كلها CSS/HTML فقط |

---

## 1. الملفات المعدَّلة

| الملف | نوع التعديل |
|-------|-------------|
| `resources/views/dashboard/section_definitions/edit.blade.php` | إضافة 3 تحسينات مستقلة |

**لا ملفات أخرى.** لا Controller، لا Model، لا form.blade.php، لا Migration، لا Seeder.

---

## 2. التحسينات المختارة والمُنفَّذة

### QW5-A: Blade Safety Tips ✅

**الموضع:** sidebar الـ Blade tab، بين أزرار الإجراءات وقائمة الحقول.

**المحتوى:**
```
┌─────────────────────────────────────────────────────┐
│  🛡️ نمط الاستخدام الصحيح                           │
│                                                     │
│  ┌ أخضر ──────────────────────────────────────────┐ │
│  │ $data['key'] ?? ''                             │ │
│  │ كل الحقول في $data — shared + translatable    │ │
│  └────────────────────────────────────────────────┘ │
│                                                     │
│  🚫 لا تستخدم: [$fields] [$sharedData] [$translatableData] │
└─────────────────────────────────────────────────────┘
```

- أخضر خفيف للـ pattern الصحيح
- أحمر خفيف للمحظورات
- `t()` مع fallback مباشر — لا Seeder مطلوب

---

### QW5-B: Runtime Variables Header ✅

**الموضع:** عنوان قائمة الحقول (h6).

**التغيير:**
```
قبل: "الحقول"
بعد: "الحقول  ($data[…])"
```

يذكّر المطور أن كل key في القائمة يُقرأ عبر `$data['key']`.

---

### QW5-C: Field Search + max-height scroll ✅

**الموضع:** داخل `@else` block (عندما توجد حقول) — فوق `#fields-reference-list`.

**الشروط:**
- `@if ($fieldsCount > 3)` — يظهر البحث فقط عند وجود 4+ حقول
- بدون `#field-search-input` لا يُشغَّل JS الفلترة (guard موجود)

**الـ input:**
```html
<input id="field-search-input" class="form-control form-control-sm" 
       placeholder="بحث في الحقول..." style="font-size:11px;">
```

**max-height + scroll:**
```html
<div id="fields-reference-list" style="max-height:380px;overflow-y:auto;">
```

**JS الفلترة** (مضاف في الـ `@push('scripts')` الأول — قبل AMD isolation):
```javascript
// مستقل تماماً، IIFE، لا يلمس Monaco
(function () {
    var searchInput = document.getElementById('field-search-input');
    if (!searchInput) return;

    function filterFields(q) {
        q = q.trim().toLowerCase();
        document.querySelectorAll('#fields-reference-list > div').forEach(function (row) {
            var keyEl = row.querySelector('code');
            var key   = keyEl ? keyEl.textContent.toLowerCase() : '';
            row.style.display = (!q || key.includes(q)) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', function () { filterFields(this.value); });
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { this.value = ''; filterFields(''); this.blur(); }
    });
})();
```

---

## 3. لماذا اخترنا هذه التحسينات؟

| التحسين | السبب |
|---------|-------|
| **Safety Tips** | أكثر ما يحتاجه المطور: contract reminder يمنع `$fields` / `$sharedData` errors |
| **Field Search** | مع 10+ حقول القائمة تصبح عديمة الفائدة بدون فلترة — تحسين UX مباشر |
| **max-height** | يمنع الـ sidebar من التمدد خارج الشاشة — مشكلة واضحة مع كثرة الحقول |
| **$data[…] header** | أقل تغيير ممكن، أعلى قيمة — يذكّر المطور بنمط الـ runtime |

---

## 4. ما الذي لم يتغير

| العنصر | الحالة |
|--------|--------|
| Monaco initialization (AMD isolation + loader) | ✅ لم يُلمس |
| Scaffold logic (`generateSnippet` / `generateFullScaffold`) | ✅ لم يُلمس |
| Field insert buttons (data-key/type/scope) | ✅ لم تتغير |
| Visual indicators (dot green/amber) | ✅ لم تتغير |
| doWrite() / fetch / base64 | ✅ لم تتغير |
| Toast notification system | ✅ لم يُلمس |
| window.confirm() × 4 | ⏳ يُعالَج في جولة قادمة (Confirm Modal) |
| Monaco lazy-load | ⏳ يُؤجَّل — تغيير أكثر خطورة |
| form.blade.php | ✅ لم يُلمس |
| Controller / Model / Renderer | ✅ لم يُلمس |

---

## 5. هل Monaco logic تأثر؟

**لا.** جميع التغييرات في:
- HTML/Blade فوق موضع AMD isolation (lines 497–505 الأصلية)
- JS مضاف في الـ `@push('scripts')` الأول (قبل AMD scripts) — وهو مجرد IIFE بسيط

Monaco لا يعلم بوجود هذه التغييرات. AMD isolation لم يُلمس. `@verbatim` block لم يُلمس.

---

## 6. هل scaffold output تأثر؟

**لا.** `generateSnippet()` و `generateFullScaffold()` لم تتغير. الـ scaffold buttons و event listeners لم تتغير. `window.sdFieldsData` لم يتغير.

---

## 7. هل runtime contract تأثر؟

**لا.** التحسينات هي:
- HTML display-only (Safety Tips box)
- CSS (max-height/overflow)
- Client-side JS filter (يُخفي rows — لا يُغير data)

لا تغيير في:
- `SectionRenderer`
- `SectionDefinitionRuntimeResolver`
- `SectionDefinitionFrontendViewDataFactory`
- أي front-end Blade view
- المتغيرات المحظورة (`$fields`، `$sharedData`، `$translatableData`) — لم تُستخدم

---

## 8. مفاتيح الترجمة المُستخدَمة (fallback مباشر)

| المفتاح | الـ fallback |
|---------|-------------|
| `dashboard.Blade_Safety_Tips` | `'نمط الاستخدام الصحيح'` |
| `dashboard.Blade_Safety_Contract` | `'كل الحقول في $data — shared + translatable مدمجان'` |
| `dashboard.Blade_Safety_Forbidden` | `'لا تستخدم:'` |
| `dashboard.Search_Fields` | `'بحث في الحقول...'` |

كلها مُضمَّنة كـ fallback — لا Seeder مطلوب.

---

## 9. توصية الجولة التالية

### الأولوية الأعلى: Confirm Modal (QW6)

**المشكلة:** 4 استدعاءات `window.confirm()` تعطل الـ event loop وتبدو قديمة.

**الأهداف:**
- `doWrite(btn)` — line 780: confirmation عند overwrite
- `scaffoldBtn click` — line 958: "إضافة X حقول ناقصة"
- `scaffoldBtn click` — line 963: "استبدال الكود بـ scaffold جديد"
- `clearCodeBtn click` — line 994: "مسح كامل الكود"

**المقترح:** Bootstrap modal خفيف (`#blade-confirm-modal`) مع:
- `data-confirm-title` + `data-confirm-body`
- callback queue يُشغَّل عند تأكيد المستخدم

**الملف الوحيد المتأثر:** `edit.blade.php` فقط.

**الخطورة:** متوسطة — تحتاج تغيير منطق `doWrite()` وفصله عن confirmation.

### الأولوية الثانية: Monaco Lazy-load

**المشكلة:** CDN Monaco (~2MB) يُحمّل حتى لو فتح المستخدم تبويب Info فقط.

**المقترح:** تأجيل `<script src="loader.js">` + AMD setup حتى أول `sdSetTab('blade')`.

**الخطورة:** أعلى — يحتاج إعادة ترتيب AMD isolation. يُوصى به فقط بعد اختبار دقيق.
