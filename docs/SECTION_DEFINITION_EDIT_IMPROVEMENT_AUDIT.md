# Section Definition Edit Page — Improvement Audit

**Date:** 2026-06-18  
**Page:** `admin/section-definitions/{id}/edit`  
**Audited files:** 6 files listed below  
**Scope:** UX, UI organization, Field management, Blade scaffold, Validation, Developer safety  

---

## 1. الملفات التي تمت مراجعتها

| الملف | الأسطر | الدور |
|-------|--------|-------|
| `resources/views/dashboard/section_definitions/edit.blade.php` | 975 | الصفحة الرئيسية |
| `resources/views/dashboard/section_definitions/form.blade.php` | 300 | نموذج المعلومات الأساسية |
| `resources/views/dashboard/section_definitions/fields/index.blade.php` | 250+ | صفحة الحقول المنفصلة |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | 420 | الـ Controller الأساسي |
| `app/Models/Sections/SectionDefinition.php` | 122 | الموديل |
| `app/Models/Sections/SectionDefinitionField.php` | 263 | موديل الحقول |

---

## 2. المشاكل الحالية

### أ. مشاكل البنية (Structure Problems)

**M1 — الحقول في صفحة منفصلة تماماً**  
الصفحة الحالية تُظهر "X حقل" كـ link يأخذك بعيداً عن صفحة التعديل. لا يمكن رؤية قائمة الحقول بينما تُعدّل البيانات الأساسية. هذا يكسر السياق: المطور يحتاج معرفة الحقول الموجودة ليقرر template_key أو category المناسب.

**M2 — `editorModeOptions` يحتوي على خيار وحيد فقط**  
`formViewData()` يُعرّف:
```php
'editorModeOptions' => [
    SectionDefinition::EDITOR_MODE_DYNAMIC => t('dashboard.Dynamic', 'ديناميكي'),
],
```
القائمة المنسدلة في الـ view تُظهر خياراً واحداً — هي في الواقع عرض للقيمة وليست تحكماً حقيقياً. هذا يُضلّل المطور ويُعطي إيحاءً بأن هناك خيارات أخرى قادمة.

**M3 — لا توجد منطقة خطر (Danger Zone) في صفحة التعديل**  
`destroy()` موجود في الـ Controller لكن لا يوجد زر حذف في الصفحة. المطور يجب أن يعود للقائمة الرئيسية لحذف تعريف. هذا مقبول للسلامة، لكن يجب أن يوجد على الأقل في أسفل الصفحة بتأكيد صريح.

**M4 — تقسيم التبويبين غير واضح**  
"Info" vs "Blade" — هذا التقسيم ليس واضحاً لمطور جديد. ماذا تعني "Info"؟ أين أُعدّل القالب؟ أين أُضيف حقولاً؟ يفتقر لتوجيه مرئي واضح.

### ب. مشاكل Monaco وتجربة الـ Blade Tab

**M5 — `window.confirm()` للإجراءات الخطرة**  
الـ scaffold overwrite وclear actions تستخدم `window.confirm()` الأصلي للمتصفح. هذا:
- يظهر بالإنجليزية بغض النظر عن لغة المتصفح
- لا يرث تصميم التطبيق
- في RTL pages يبدو غريباً مرئياً
- سهل الإغلاق بالخطأ

**M6 — الـ Field Reference Sidebar لا يُظهر العقد الكاملة**  
الـ sidebar في تبويب Blade يعرض `field_key` + `field_type` فقط. لا يُظهر:
- `validation_rules` الحقل
- `default_value`
- هل الحقل `translatable` أم `shared`
- هل `required`

المطور يجب أن يتذكر هذه التفاصيل من صفحة الحقول أو يفتحها في نافذة أخرى.

**M7 — لا تحذير لحقول `repeater`**  
Phase 5B (repeater schema editor) مُؤجَّل. إذا وجد حقل بنوع `repeater` في القائمة، يظهر بدون أي إشارة تدل على أن editor الـ repeater غير متوفر بعد. scaffold generator يُنتج snippet صحيح لكن المطور لا يعرف أن الـ editor لن يعمل للمستخدم النهائي.

**M8 — لا يتحقق من وجود ملف Blade الفعلي**  
`form.blade.php` يعرض "مسار المرشح" (`front.sections.{category}.{template_key}`) لكن لا يتحقق من أن هذا الملف موجود فعلاً على الـ disk. مطور يُدخل `template_key` خاطئاً لن يرى تحذيراً حتى يحاول عرض القسم في الواجهة الأمامية.

### ج. مشاكل JavaScript

**M9 — AMD isolation معقد وهش**  
المنطق الثلاثي لـ AMD isolation (حفظ قبل Monaco / إخفاء بعد loader / استعادة داخل require callback) يعمل، لكنه في 20 سطر معقد من الـ inline JS. أي تعديل مستقبلي على تحميل مكتبة جديدة قد يكسر هذا التوازن.

**M10 — localStorage key غير مُعرَّف بـ namespace كافٍ**  
```javascript
localStorage.setItem('sectionDef_tab_' + definitionId, tab);
```
المفتاح لا يتضمن environment prefix. في حالة تطابق IDs على staging و production (وهو شائع عند نسخ DB)، يتم استعادة التبويب الخاطئ.

---

## 3. إجابات الأسئلة العشرة

**س١: ما أبرز مشاكل الصفحة الحالية؟**  
راجع M1-M10 أعلاه. الأبرز: M1 (انفصال الحقول)، M2 (select وهمي)، M6 (sidebar منقوص)، M7 (repeater بدون تحذير).

**س٢: أين الازدحام الأكبر في الـ edit.blade.php؟**  
الـ Monaco initialization IIFE (lines 452-972) يحتوي على 520+ سطر JavaScript في ملف Blade. يشمل: توليد Scaffold، Toast system، write logic، field indicators، Ctrl+S، fullscreen، zoom — كلها في كتلة واحدة متداخلة. قابلية الصيانة منخفضة.

**س٣: هل توصيف الحقول واضح للمطور؟**  
لا. الـ Field Reference Sidebar يعطي فكرة سطحية عن الحقول. التفاصيل الحاسمة (validation، default، scope) تحتاج فتح صفحة ثانية.

**س٤: هل ترتيب الحقول واضح؟**  
الترتيب الحالي يعتمد على `sort_order` من صفحة الحقول المنفصلة. لا يُعرض في صفحة التعديل. لا توجد مشكلة وظيفية لكن يفتقر للوضوح.

**س٥: هل أنواع الحقول معروضة بشكل كافٍ؟**  
في الـ Blade tab فقط (كـ text label). لا يوجد icon أو badge مرئي يُميّز `richtext` عن `text` أو `media` عن `url`.

**س٦: هل validation rules الحقول ظاهرة؟**  
لا — غائبة تماماً عن الـ edit page. موجودة في DB لكن لا تُعرض في أي مكان في هذه الصفحة.

**س٧: هل scaffold generator آمن؟**  
نعم. كل الـ snippets تستخدم `$data['key'] ?? ''` حصراً. لا استخدام لـ `$fields`، `$sharedData`، أو `$translatableData`. ✅

**س٨: هل توجد مشاكل في repeater/media/select fields؟**  
- `repeater`: scaffold يُنتج snippet مناسباً، لكن Phase 5B editor غير متوفر — لا تحذير للمطور
- `media`: scaffold يُنتج `$data['key'] ?? ''` وهو المسار النسبي — صحيح
- `select`: scaffold يُنتج string عادي — صحيح

**س٩: هل توجد أزرار خطرة بدون تأكيد كافٍ؟**  
نعم — `window.confirm()` للـ scaffold overwrite وclear في Monaco. مقبول وظيفياً لكن يفتقر لـ UX لائق.

**س١٠: ما التحسينات الممكنة بدون DB migration؟**  
جميع التحسينات المقترحة في هذا التقرير لا تحتاج migration. كلها UI/UX فقط.

---

## 4. Quick Wins — تحسينات سريعة (يوم واحد أو أقل)

### QW1 — استبدال `<select>` بـ badge مرئي
**المشكلة:** M2  
**الحل:** في `form.blade.php`، احذف الـ `<select>` واستبدله بـ:
```blade
<input type="hidden" name="editor_mode" value="dynamic">
<div class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-sm">
    <span class="inline-flex items-center gap-1.5 font-semibold text-blue-700">
        <i class="ti ti-bolt"></i>
        {{ t('dashboard.Dynamic', 'ديناميكي') }}
    </span>
    <p class="mt-1 mb-0 text-xs text-blue-600">{{ t('dashboard.Def_Editor_Mode_Hint', '...') }}</p>
</div>
```
لا migration، تغيير بسيط يزيل إيهام الخيار.

### QW2 — إضافة تفاصيل الحقل للـ Field Reference Sidebar
**المشكلة:** M6  
**الحل:** في `edit.blade.php`، عند بناء قائمة الحقول في الـ Blade tab، أضف:
```blade
@if($field->validation_rules)
    <span class="text-xs text-slate-400 font-mono">{{ $field->validation_rules }}</span>
@endif
@if($field->isTranslatable())
    <span class="text-xs text-indigo-500">translatable</span>
@else
    <span class="text-xs text-amber-500">shared</span>
@endif
```
البيانات موجودة في `$sectionDefinition->fields` المُحمَّل مسبقاً — صفر queries إضافية.

### QW3 — تحذير لحقول `repeater`
**المشكلة:** M7  
**الحل:** في الـ Field Reference Sidebar، بعد عرض كل حقل:
```blade
@if($field->isRepeater())
    <div class="mt-1 rounded bg-amber-50 border border-amber-200 px-2 py-1 text-xs text-amber-700">
        <i class="ti ti-alert-triangle me-1"></i>
        {{ t('dashboard.Repeater_Editor_Phase5B', 'محرر الـ Repeater قيد التطوير (Phase 5B)') }}
    </div>
@endif
```

### QW4 — إضافة قائمة حقول خفيفة في Info tab
**المشكلة:** M1  
**الحل:** في `form.blade.php` أو الـ sidebar، أضف قسماً يعرض الحقول كـ read-only chips:
```blade
@if($sectionDefinition->exists && $sectionDefinition->fields->isNotEmpty())
<div class="mt-4 rounded border border-slate-200 bg-slate-50 px-3 py-3">
    <h6 class="mb-2 text-xs font-semibold text-slate-700">
        {{ t('dashboard.Fields', 'الحقول') }}
        <span class="text-slate-400">({{ $sectionDefinition->fields->count() }})</span>
    </h6>
    <div class="flex flex-wrap gap-1">
        @foreach($sectionDefinition->fields as $field)
            <span class="inline-flex items-center rounded-full bg-white border px-2 py-0.5 text-xs font-mono text-slate-600">
                {{ $field->field_key }}
                <span class="ml-1 text-slate-400">{{ $field->field_type }}</span>
            </span>
        @endforeach
    </div>
    <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
       class="mt-2 block text-xs text-indigo-600 hover:underline">
        {{ t('dashboard.Manage_Fields', 'إدارة الحقول') }} →
    </a>
</div>
@endif
```

### QW5 — Danger Zone بـ modal تأكيد
**المشكلة:** M3  
**الحل:** في الـ sidebar (أسفل أزرار الحفظ)، أضف:
```blade
<hr class="my-4 border-red-100">
<div class="rounded border border-red-200 bg-red-50 px-3 py-3">
    <h6 class="mb-1 text-sm font-semibold text-red-700">{{ t('dashboard.Danger_Zone', 'منطقة الخطر') }}</h6>
    <p class="mb-2 text-xs text-red-600">{{ t('dashboard.Def_Delete_Warning', 'يحذف التعريف وجميع حقوله والأقسام المرتبطة.') }}</p>
    <button type="button" class="btn btn-sm w-full"
            style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;"
            data-bs-toggle="modal" data-bs-target="#deleteDefinitionModal">
        <i class="ti ti-trash me-1"></i>{{ t('dashboard.Delete_Definition', 'حذف التعريف') }}
    </button>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteDefinitionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-red-700">{{ t('dashboard.Confirm_Delete_Definition', 'تأكيد الحذف') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ t('dashboard.Def_Delete_Confirm_Text', 'سيُحذف هذا التعريف مع جميع حقوله وملف Blade المرتبط والأقسام التي تستخدمه.') }}</p>
                <p class="font-mono text-sm bg-slate-100 rounded px-2 py-1">{{ $sectionDefinition->section_key }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ t('dashboard.Cancel', 'إلغاء') }}</button>
                <form method="POST" action="{{ route('dashboard.section_definitions.destroy', $sectionDefinition) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ t('dashboard.Delete_Permanently', 'حذف نهائي') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
```

---

## 5. Medium Improvements — تحسينات متوسطة (يوم واحد إلى يومين)

### MI1 — "Runtime Variables" table في الـ Blade tab
جدول يُظهر للمطور جميع متغيرات `$data` التي سيستقبلها الـ Blade view:

```
| $data['key']     | Field Type | Scope       | Validation            |
|------------------|------------|-------------|----------------------|
| $data['title']   | text       | translatable| required|max:200     |
| $data['image']   | media      | shared      | nullable|string      |
| $data['items']   | repeater   | shared      | ⚠️ Phase 5B          |
```

بدون migration — يُبنى من `$sectionDefinition->fields` الموجود.

### MI2 — استبدال `window.confirm()` بـ Modal مُصمَّم
في `doWrite()` و scaffold generation، استبدل:
```javascript
if (!window.confirm('هل تريد الكتابة؟')) return;
```
بـ:
```javascript
showConfirmModal({
    title: 'كتابة ملف Blade',
    message: 'سيُستبدل الكود الموجود على الـ disk. هل تريد المتابعة؟',
    confirmText: 'نعم، اكتب الملف',
    onConfirm: () => doWrite()
});
```
Modal بسيط مُضمَّن في الـ Blade.

### MI3 — Blade file existence check في `form.blade.php`
```blade
@php
    $resolvedViewPath = str_replace('.', '/', 'front.sections.' . $selectedCategory . '.' . $selectedTemplateKey);
    $bladeExists = file_exists(resource_path("views/{$resolvedViewPath}.blade.php"));
@endphp
@if($selectedTemplateKey && !$bladeExists && $bladeFileStatus !== 'exists')
    <div class="mt-2 rounded border border-amber-200 bg-amber-50 px-2 py-1 text-xs text-amber-700">
        <i class="ti ti-alert-triangle me-1"></i>
        {{ t('dashboard.Blade_File_Not_Found', 'ملف Blade غير موجود على الـ disk بعد. استخدم تبويب Blade لإنشائه.') }}
    </div>
@endif
```

### MI4 — تحسين بنية تبويبات الصفحة
إضافة تبويب ثالث "الحقول" inline (read-only مع رابط للإدارة الكاملة):
```
[✦ المعلومات الأساسية] [⊞ الحقول (5)] [</> محرر Blade]
```
التبويب الأوسط يعرض قائمة الحقول كجدول مبسط بدون CRUD. رابط "إدارة الحقول →" في أسفله.

---

## 6. Risky Improvements — تحسينات تحتاج دراسة أعمق

### RI1 — Inline Field CRUD في صفحة التعديل
ممكن من حيث الـ UX لكن يُعقّد الصفحة بشكل كبير. يحتاج AJAX + إعادة رسم القائمة + validation state منفصل. **غير موصى به حالياً** — الصفحة المنفصلة تعمل بشكل جيد.

### RI2 — Live Preview للقسم
عرض الـ Section rendered داخل `<iframe>` في صفحة التعديل. يحتاج: route للـ preview، تمرير بيانات وهمية، عزل CSS. تعقيد عالٍ وفائدة محدودة للمطور. **لا يُوصى به**.

### RI3 — استخراج Monaco JS إلى ملف منفصل
نقل 520+ سطر JavaScript من `edit.blade.php` إلى `public/js/section-definition-editor.js`. يُحسّن قابلية الصيانة لكن يتطلب تمرير متغيرات PHP عبر `data-*` attributes أو `<script>` block منفصل للـ config. مُستحسَن على المدى البعيد.

### RI4 — Repeater Schema Visual Editor (Phase 5B)
خارج نطاق هذا الـ audit تماماً. مُؤجَّل بقرار مقصود.

---

## 7. الخطة المقترحة للتنفيذ

### المرحلة الأولى — Quick Wins (يوم واحد)
1. QW1: استبدال `<select>` الوهمي ببadge
2. QW2: إضافة validation rules + scope للـ Field Reference Sidebar  
3. QW3: تحذير للـ repeater fields
4. QW4: قائمة الحقول الخفيفة في Info tab
5. QW5: Danger Zone بـ modal

**الملفات المتأثرة:** `edit.blade.php`، `form.blade.php` فقط.

### المرحلة الثانية — Medium Improvements (يوم إلى يومين)
1. MI1: Runtime Variables table في Blade tab
2. MI2: استبدال `window.confirm()` بـ Modal مُصمَّم
3. MI3: Blade file existence check

**الملفات المتأثرة:** `edit.blade.php`، `form.blade.php` فقط.

### المرحلة الثالثة — Structural (اختياري، أسبوع)
- MI4: تبويب ثالث للحقول (read-only)
- RI3: استخراج Monaco JS لملف منفصل

---

## 8. ما لا يجب تغييره

| العنصر | السبب |
|--------|-------|
| Runtime contract (`$data['key']`) | صحيح ومطبَّق كاملاً. لا تغيير |
| scaffold generator logic | آمن وصحيح. يستخدم `$data['key'] ?? ''` حصراً |
| AMD isolation three-phase pattern | يعمل على shared hosting بـ ModSecurity. لا تبسيط |
| `writeBladeFile()` base64 pattern | ضروري لتجاوز ModSecurity. لا تغيير |
| `SectionTemplateFileWriter` | يعمل بشكل صحيح. لا تعديل |
| Renderer / SectionRenderer | خارج نطاق هذا الـ audit |
| Section frontend output | خارج نطاق هذا الـ audit |
| SectionDefinitionField schema | لا تغيير في DB أو model API |

---

## 9. ملخص نهائي

### أهم 5 مشاكل في الصفحة

1. **M1** — الحقول في صفحة منفصلة تماماً — المطور يفقد السياق عند الانتقال
2. **M2** — `editor_mode` select يعرض خياراً وحيداً — يُضلّل ويُوهم بخيارات وهمية
3. **M6** — الـ Field Reference Sidebar لا يُظهر validation/scope/default — المطور يكتب Blade بدون معلومات كافية
4. **M7** — حقول `repeater` بدون تحذير Phase 5B — توقعات خاطئة
5. **M3** — لا Danger Zone في صفحة التعديل — المطور يضطر للعودة للقائمة للحذف

### أفضل 5 تحسينات سريعة

1. **QW1** — استبدال select الوهمي ببadge ثابت (30 دقيقة)
2. **QW2** — إضافة validation + scope للـ sidebar (1 ساعة)
3. **QW3** — تحذير repeater Phase 5B (15 دقيقة)
4. **QW4** — قائمة حقول خفيفة في Info tab (2 ساعة)
5. **QW5** — Danger Zone بـ Bootstrap modal (2 ساعة)

### هل نحتاج migration؟
**لا.** جميع التحسينات في هذا التقرير UI/UX فقط. البيانات اللازمة موجودة كلها في `$sectionDefinition` و `$sectionDefinition->fields` المُحمَّلين مسبقاً.

### هل يمكن البدء بتحسين UI فقط؟
**نعم، بالكامل.** جميع Quick Wins وجميع Medium Improvements لا تلمس:
- قاعدة البيانات
- الـ runtime contract
- الـ renderer
- الـ scaffold logic
- الـ AMD isolation

### أول خطوة تنفيذية مقترحة
**QW2 + QW3 أولاً** — تعديل الـ Field Reference Sidebar ليعرض validation/scope وإضافة تحذير repeater. هذان التعديلان في `edit.blade.php` فقط، في منطقة واضحة (البلوك الذي يبني قائمة الحقول في الـ Blade tab sidebar)، وفائدتهما فورية لأي مطور يستخدم Monaco لكتابة Blade views.
