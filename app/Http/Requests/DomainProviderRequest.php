<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DomainProvider;

class DomainProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // يمكن إضافة صلاحيات لاحقاً
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:191',
            'type'      => 'required|string|in:' . implode(',', DomainProvider::TYPES) . '|max:50',
            'endpoint'  => 'nullable|url|max:191',
            'username'  => 'nullable|string|max:191',
            'password'  => 'nullable|required_without:api_token|string|max:255',
            'api_token' => 'nullable|required_without:password|string|max:255',
            'is_active' => 'sometimes|boolean',
            'mode'      => 'required|in:' . implode(',', DomainProvider::MODES) . '|max:10',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'      => 'اسم المزود مطلوب.',
            'type.in'            => 'نوع المزود غير مدعوم.',
            'mode.in'            => 'الوضع يجب أن يكون live أو test.',
            'password.required_without'  => 'كلمة المرور مطلوبة إذا لم يتم إدخال التوكن.',
            'api_token.required_without' => 'التوكن مطلوب إذا لم يتم إدخال كلمة مرور.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var($this->input('is_active', false), FILTER_VALIDATE_BOOL),
            'endpoint'  => $this->input('endpoint') ? trim($this->input('endpoint')) : null,
            'type'      => $this->input('type') ? strtolower(trim($this->input('type'))) : null,
            'mode'      => $this->input('mode') ? strtolower(trim($this->input('mode'))) : null,
        ]);
    }
}
