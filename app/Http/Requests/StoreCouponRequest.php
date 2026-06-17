<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Coupon::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9_\-]+$/i',
                Rule::unique('coupons', 'code'),
            ],
            'discount_type'          => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value'         => ['required', 'numeric', 'min:0.01'],
            'expires_at'             => ['nullable', 'date', 'after:today'],
            'max_uses'               => ['nullable', 'integer', 'min:1'],
            'minimum_amount_cents'   => ['nullable', 'integer', 'min:0'],
            'is_active'              => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'          => t('dashboard.Coupon_Code', 'Coupon Code') . ' مطلوب.',
            'code.unique'            => 'هذا الكود مستخدم بالفعل.',
            'code.regex'             => 'الكود يجب أن يحتوي على حروف وأرقام وشرطة أو شرطة سفلية فقط.',
            'discount_type.required' => t('dashboard.Coupon_Discount_Type', 'Discount Type') . ' مطلوب.',
            'discount_type.in'       => 'نوع الخصم يجب أن يكون fixed أو percent.',
            'discount_value.required'=> t('dashboard.Coupon_Discount_Value', 'Discount Value') . ' مطلوب.',
            'discount_value.min'     => 'قيمة الخصم يجب أن تكون أكبر من صفر.',
            'expires_at.after'       => 'تاريخ الانتهاء يجب أن يكون في المستقبل.',
            'max_uses.min'           => 'الحد الأقصى للاستخدام يجب أن يكون 1 على الأقل.',
            'minimum_amount_cents.min' => 'الحد الأدنى للطلب يجب أن يكون 0 أو أكثر.',
        ];
    }

    /**
     * Prepare the data for validation — normalize the code to uppercase.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }
}
