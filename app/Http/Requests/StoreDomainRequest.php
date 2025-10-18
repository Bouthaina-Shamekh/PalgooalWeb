<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        // فعّل صلاحياتك هنا (Gate/Policy) إن لزم
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'         => ['required', 'exists:clients,id'],
            'domain_name'       => ['required', 'string', 'max:255', 'unique:domains,domain_name'],
            'registrar'         => ['required', 'string', 'max:255'],
            'registration_date' => ['required', 'date'],
            'renewal_date'      => ['required', 'date'],
            'status'            => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'   => 'العميل مطلوب.',
            'client_id.exists'     => 'العميل غير موجود.',
            'domain_name.required' => 'اسم النطاق مطلوب.',
            'domain_name.unique'   => 'هذا النطاق مسجل بالفعل.',
            'registrar.required'   => 'اسم المسجل مطلوب.',
            'registration_date.required' => 'تاريخ التسجيل مطلوب.',
            'renewal_date.required'      => 'تاريخ التجديد مطلوب.',
            'status.required'      => 'الحالة مطلوبة.',
        ];
    }
}
