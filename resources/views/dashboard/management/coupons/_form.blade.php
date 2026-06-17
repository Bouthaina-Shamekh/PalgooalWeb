{{--
    Coupon _form partial
    Variables expected:
      $coupon  — Coupon model instance (new or existing)
      $isEdit  — bool
--}}

<div class="grid grid-cols-12 gap-6">

    {{-- ── Main form column ───────────────────────────────────────────────── --}}
    <div class="col-span-12 xl:col-span-8">

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 list-disc ps-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── Section 1: Basic Info ──────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-xs font-bold shrink-0">١</span>
                <h5 class="mb-0">{{ t('dashboard.Coupon_Basic_Info', 'معلومات الكوبون') }}</h5>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Code --}}
                    <div class="md:col-span-2">
                        <label for="code" class="form-label">
                            {{ t('dashboard.Coupon_Code', 'كود الكوبون') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               value="{{ old('code', $coupon->code ?? '') }}"
                               class="form-control font-mono @error('code') is-invalid @enderror"
                               dir="ltr"
                               placeholder="{{ t('dashboard.Coupon_Code_Placeholder', 'مثال: SUMMER20') }}"
                               required
                               oninput="this.value = this.value.toUpperCase()" />
                        <small class="text-muted">{{ t('dashboard.Coupon_Code_Hint', 'حروف كبيرة وأرقام فقط. يُحوَّل إلى أحرف كبيرة تلقائياً.') }}</small>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Discount Type --}}
                    <div>
                        <label for="discount_type" class="form-label">
                            {{ t('dashboard.Coupon_Discount_Type', 'نوع الخصم') }}
                            <span class="text-danger">*</span>
                        </label>
                        <select id="discount_type" name="discount_type"
                                class="form-control @error('discount_type') is-invalid @enderror"
                                onchange="updateValueHint()">
                            <option value="fixed"
                                {{ old('discount_type', $coupon->discount_type ?? 'fixed') === 'fixed' ? 'selected' : '' }}>
                                {{ t('dashboard.Coupon_Type_Fixed', 'مبلغ ثابت (دولار)') }}
                            </option>
                            <option value="percent"
                                {{ old('discount_type', $coupon->discount_type ?? '') === 'percent' ? 'selected' : '' }}>
                                {{ t('dashboard.Coupon_Type_Percent', 'نسبة مئوية (%)') }}
                            </option>
                        </select>
                        @error('discount_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Discount Value --}}
                    <div>
                        <label for="discount_value" class="form-label">
                            {{ t('dashboard.Coupon_Discount_Value', 'قيمة الخصم') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               id="discount_value"
                               name="discount_value"
                               value="{{ old('discount_value', $coupon->discount_value ?? '') }}"
                               class="form-control font-mono @error('discount_value') is-invalid @enderror"
                               dir="ltr"
                               min="0.01"
                               step="0.01"
                               placeholder="0.00"
                               required />
                        <small id="value-hint" class="text-muted"></small>
                        @error('discount_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                {{-- Fixed-type notice --}}
                <div id="fixed-notice" class="alert alert-info mt-3 text-sm hidden">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ t('dashboard.Coupon_Value_Fixed_Hint', 'المبلغ بالعملة الأساسية (مثال: 10 = خصم ١٠ دولار). يُخزَّن بالسنتات داخلياً عبر Coupon::computeDiscountCents().') }}
                </div>
            </div>
        </div>

        {{-- ── Section 2: Restrictions ─────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-xs font-bold shrink-0">٢</span>
                <h5 class="mb-0">{{ t('dashboard.Coupon_Restrictions', 'القيود والشروط') }}</h5>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Max Uses --}}
                    <div>
                        <label for="max_uses" class="form-label">
                            {{ t('dashboard.Coupon_Max_Uses', 'الحد الأقصى للاستخدام') }}
                        </label>
                        <input type="number"
                               id="max_uses"
                               name="max_uses"
                               value="{{ old('max_uses', $coupon->max_uses ?? '') }}"
                               class="form-control font-mono @error('max_uses') is-invalid @enderror"
                               dir="ltr"
                               min="1"
                               placeholder="{{ t('dashboard.Coupon_Unlimited', 'بلا حدود') }}" />
                        <small class="text-muted">{{ t('dashboard.Coupon_Max_Uses_Hint', 'اتركه فارغاً للسماح باستخدام غير محدود.') }}</small>
                        @error('max_uses')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Minimum Amount --}}
                    <div>
                        <label for="minimum_amount" class="form-label">
                            {{ t('dashboard.Coupon_Min_Amount', 'الحد الأدنى للطلب ($)') }}
                        </label>
                        <input type="number"
                               id="minimum_amount"
                               name="minimum_amount"
                               value="{{ old('minimum_amount', isset($coupon->minimum_amount_cents) && $coupon->minimum_amount_cents !== null ? number_format($coupon->minimum_amount_cents / 100, 2) : '') }}"
                               class="form-control font-mono @error('minimum_amount_cents') is-invalid @enderror"
                               dir="ltr"
                               min="0"
                               step="0.01"
                               placeholder="0.00" />
                        <small class="text-muted">{{ t('dashboard.Coupon_Min_Amount_Hint', 'اتركه فارغاً إذا لم يكن هناك حد أدنى.') }}</small>
                        @error('minimum_amount_cents')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Expires At --}}
                    <div>
                        <label for="expires_at" class="form-label">
                            {{ t('dashboard.Coupon_Expires_At', 'تاريخ الانتهاء') }}
                        </label>
                        <input type="date"
                               id="expires_at"
                               name="expires_at"
                               value="{{ old('expires_at', isset($coupon->expires_at) && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}"
                               class="form-control font-mono @error('expires_at') is-invalid @enderror"
                               dir="ltr" />
                        <small class="text-muted">{{ t('dashboard.Coupon_Expires_Hint', 'اتركه فارغاً إذا لم يكن للكوبون تاريخ انتهاء.') }}</small>
                        @error('expires_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Is Active --}}
                    <div>
                        <label class="form-label d-block">{{ t('dashboard.Coupon_Is_Active', 'حالة الكوبون') }}</label>
                        <div class="d-flex flex-column gap-2 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_1"
                                       value="1"
                                       {{ old('is_active', ($coupon->is_active ?? true) ? '1' : '0') === '1' ? 'checked' : '' }} />
                                <label class="form-check-label cursor-pointer" for="is_active_1">
                                    {{ t('dashboard.Coupon_Active_Label', 'نشط — يمكن استخدامه في الدفع') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_0"
                                       value="0"
                                       {{ old('is_active', ($coupon->is_active ?? true) ? '1' : '0') === '0' ? 'checked' : '' }} />
                                <label class="form-check-label cursor-pointer" for="is_active_0">
                                    {{ t('dashboard.Coupon_Inactive_Label', 'معطّل — لا يُقبل في الدفع') }}
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Used Count (edit mode only, read-only info) --}}
                @if($isEdit)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm text-muted mb-0">
                            <i class="ti ti-info-circle me-1"></i>
                            <strong>{{ t('dashboard.Coupon_Used_Count', 'مرات الاستخدام الحالية') }}:</strong>
                            <span class="font-mono font-semibold text-gray-700">{{ $coupon->used_count }}</span>
                            — {{ t('dashboard.Coupon_Used_Count_Hint', 'هذه القيمة تُحدَّث تلقائياً عند كل عملية دفع ناجحة.') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- end main column --}}

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <div class="col-span-12 xl:col-span-4">
        <div class="card sticky top-6">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-device-floppy me-2 text-primary"></i>
                    @if($isEdit)
                        {{ t('dashboard.Update_Coupon', 'حفظ التعديلات') }}
                    @else
                        {{ t('dashboard.Create_Coupon', 'إنشاء الكوبون') }}
                    @endif
                </h5>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-check me-1"></i>
                    @if($isEdit)
                        {{ t('dashboard.Update_Coupon', 'حفظ التعديلات') }}
                    @else
                        {{ t('dashboard.Create_Coupon', 'إنشاء الكوبون') }}
                    @endif
                </button>
                <a href="{{ route('dashboard.coupons.index') }}" class="btn btn-light w-100">
                    {{ t('dashboard.Cancel', 'إلغاء') }}
                </a>
            </div>

            {{-- Help --}}
            <div class="card-footer bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 mb-2">
                    <i class="ti ti-bulb me-1 text-warning"></i>
                    {{ t('dashboard.Coupon_Help_Title', 'كيف تعمل الكوبونات؟') }}
                </p>
                <ul class="text-xs text-muted list-disc ps-4 space-y-1 mb-0">
                    <li>{{ t('dashboard.Coupon_Help_1', 'الكوبون يُطبَّق على إجمالي الطلب.') }}</li>
                    <li>{{ t('dashboard.Coupon_Help_2', 'الخصم الثابت بالعملة الأساسية (دولار).') }}</li>
                    <li>{{ t('dashboard.Coupon_Help_3', 'الخصم المئوي يُحسَّب بعد الخصومات الأخرى.') }}</li>
                    <li>{{ t('dashboard.Coupon_Help_4', 'يُسجَّل الاستخدام فقط بعد إتمام الدفع.') }}</li>
                </ul>
                @if($isEdit)
                    <div class="alert alert-warning mt-3 text-xs mb-0">
                        <i class="ti ti-alert-triangle me-1"></i>
                        {{ t('dashboard.Coupon_Delete_Warning', 'لا يمكن حذف كوبون مرتبط بفاتورة مدفوعة. عطّله بدلاً من ذلك.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>{{-- end grid --}}

@push('scripts')
<script>
(function () {
    const typeSelect   = document.getElementById('discount_type');
    const hint         = document.getElementById('value-hint');
    const fixedNotice  = document.getElementById('fixed-notice');
    const valueInput   = document.getElementById('discount_value');

    const fixedHint   = @json(t('dashboard.Coupon_Value_Fixed_Hint', 'المبلغ بالعملة الأساسية (مثال: 10 = خصم ١٠ دولار).'));
    const percentHint = @json(t('dashboard.Coupon_Value_Percent_Hint', 'نسبة من 0 إلى 100 (مثال: 20 = خصم ٢٠٪).'));

    function updateValueHint() {
        const type = typeSelect.value;
        if (type === 'fixed') {
            hint.textContent = fixedHint;
            fixedNotice.classList.remove('hidden');
            valueInput.min = '0.01';
            valueInput.step = '0.01';
            valueInput.max = '';
        } else {
            hint.textContent = percentHint;
            fixedNotice.classList.add('hidden');
            valueInput.min = '0.01';
            valueInput.step = '0.01';
            valueInput.max = '100';
        }
    }

    typeSelect.addEventListener('change', updateValueHint);
    window.updateValueHint = updateValueHint; // called from onchange in select too
    updateValueHint(); // init on page load
})();
</script>
@endpush
