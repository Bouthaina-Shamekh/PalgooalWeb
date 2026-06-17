# Section Definition Edit — QW1 Implementation Report

**Date:** 2026-06-18  
**Scope:** استبدال `editor_mode` select بـ badge ثابت  
**Status:** ✅ Completed  

---

## 1. الملف المعدَّل

| الملف | نوع التعديل |
|-------|-------------|
| `resources/views/dashboard/section_definitions/form.blade.php` | استبدال block واحد (lines 167–189) |

لا Controller. لا Model. لا Migration. لا `edit.blade.php`. لا Monaco.

---

## 2. ما الذي تغيّر

**قبل:**
```blade
<label for="editor_mode" class="form-label">وضع المحرر</label>
<select id="editor_mode" name="editor_mode" class="form-control" required>
    @foreach ($editorModeOptions as $editorModeValue => $editorModeLabel)
        <option value="{{ $editorModeValue }}" @selected(...)>{{ $editorModeLabel }}</option>
    @endforeach
</select>
<div class="mt-1 text-xs text-slate-500">Dynamic يستخدم مفتاح القالب...</div>
<div class="mt-3 ...">مسار Dynamic / ...</div>
@error('editor_mode') ... @enderror
```

**بعد:**
```blade
<label class="form-label">وضع المحرر</label>

<input type="hidden" name="editor_mode"
       value="{{ \App\Models\Sections\SectionDefinition::EDITOR_MODE_DYNAMIC }}">

<div class="rounded border border-blue-200 bg-blue-50 px-3 py-2.5">
    <span class="inline-flex items-center gap-1.5 font-semibold text-blue-700 text-sm">
        <i class="ti ti-bolt"></i> ديناميكي
    </span>
    <p class="mt-1 mb-0 text-xs text-blue-600">
        هذا التعريف يستخدم المحرر الديناميكي فقط. لا توجد أوضاع تحرير أخرى حالياً.
    </p>
</div>

<div class="mt-3 rounded border border-slate-200 bg-slate-50 ...">
    مسار Dynamic / ...
</div>
```

**الفرق المهم:**
- ❌ حُذف: `<select>` + `@foreach ($editorModeOptions...)` + `required` attribute + `@error('editor_mode')`
- ✅ أُضيف: `<input type="hidden" name="editor_mode" value="dynamic">`
- ✅ أُضيف: badge ثابت بلون أزرق خفيف + أيقونة `ti-bolt`
- ✅ محافظ: بلوك "مسار Dynamic" الإرشادي الموجود مسبقاً

---

## 3. هل قيمة `editor_mode` ما زالت تُرسل؟

**نعم.** الـ hidden input يُرسل `editor_mode=dynamic` مع كل submit للـ form. القيمة ثابتة ومأخوذة من constant الموديل `EDITOR_MODE_DYNAMIC = 'dynamic'` مباشرة — لا hardcoded string.

الـ Controller (`update()`) يقرأ `editor_mode` من الـ Request عبر `$this->persistableAttributes($validated)` الذي يُمرر `EDITOR_MODE_DYNAMIC` بشكل ثابت أصلاً:
```php
'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
```
لذا حتى لو لم يُرسل الـ hidden input، القيمة ستكون صحيحة. الـ hidden input يوفر حمايتين: validation pass + وضوح المقصد.

---

## 4. هل تغير runtime contract؟

**لا.** الـ runtime contract هو ما يستقبله Blade view — `$data['key']`. لم يتغير شيء في:
- `SectionRenderer`
- `SectionDefinitionRuntimeResolver`
- `SectionDefinitionFrontendViewDataFactory`
- أي Blade view في `front/`

---

## 5. هل يمكن متابعة QW4 لاحقاً؟

**نعم، بدون أي تعارض.**

**QW4** — إضافة قائمة حقول خفيفة read-only في Info tab — يُضيف HTML جديد في `form.blade.php` أو الـ sidebar في `edit.blade.php`. التعديل الحالي كان في نطاق الـ `editor_mode` block فقط (lines 167–189). QW4 سيكون في نطاق مختلف تماماً.
