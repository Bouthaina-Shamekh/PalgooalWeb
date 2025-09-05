<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DomainProvider;

class DomainProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أضف صلاحيات لاحقًا إن لزم
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post'); // store = POST, update = PUT/PATCH

        $rules = [
            'name'      => ['required', 'string', 'max:191'],
            'type'      => ['required', 'string', Rule::in(DomainProvider::TYPES), 'max:50'],
            // endpoint مطلوب (تختاره من الـ UI)
            'endpoint'  => ['required', 'url', 'max:191'],
            'is_active' => ['sometimes', 'boolean'],
            // mode اختياري (للعرض/الإحصاء)
            'mode'      => ['nullable', Rule::in(DomainProvider::MODES), 'max:10'],
        ];

        switch ($this->input('type')) {
            case 'enom':
                // Enom: username مطلوب
                // + واحد على الأقل من (password, api_token, api_key)
                $rules = array_merge($rules, [
                    'username'  => ['required', 'string', 'max:191'],

                    'password'  => [
                        'nullable',
                        'string',
                        'max:255',
                        'required_without_all:api_token,api_key',
                    ],
                    'api_token' => [
                        'nullable',
                        'string',
                        'max:255',
                        'required_without_all:password,api_key',
                    ],
                    'api_key'   => [
                        'nullable',
                        'string',
                        'max:255',
                        'required_without_all:password,api_token',
                    ],
                ]);
                break;

            case 'namecheap':
                // Namecheap: username + api_key + client_ip
                // في التحديث api_key اختياري (وإن كان فارغاً لا نحدّثه في الـ Controller)
                $rules = array_merge($rules, [
                    'username'  => ['required', 'string', 'max:191'],
                    'api_key'   => [$isCreate ? 'required' : 'nullable', 'string', 'max:255'],
                    'client_ip' => ['required', 'ip'],
                ]);
                break;

            case 'cloudflare':
                // Cloudflare: غالبًا api_token فقط
                $rules = array_merge($rules, [
                    'api_token' => [$isCreate ? 'required' : 'nullable', 'string', 'max:255'],
                ]);
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'اسم المزود مطلوب.',
            'type.required'      => 'نوع المزود مطلوب.',
            'type.in'            => 'نوع المزود غير مدعوم.',
            'endpoint.required'  => 'يجب اختيار رابط الـ API (Endpoint).',
            'endpoint.url'       => 'رابط الـ API غير صالح.',
            'mode.in'            => 'الوضع يجب أن يكون live أو test.',

            // مشترك
            'username.required'  => 'اسم المستخدم مطلوب.',

            // Enom: واحد على الأقل من الثلاثة
            'password.required_without_all'  => 'أدخل واحدًا على الأقل من (password, api_token, api_key) لمزوّد Enom.',
            'api_token.required_without_all' => 'أدخل واحدًا على الأقل من (password, api_token, api_key) لمزوّد Enom.',
            'api_key.required_without_all'   => 'أدخل واحدًا على الأقل من (password, api_token, api_key) لمزوّد Enom.',

            // Namecheap
            'api_key.required'   => 'مفتاح API مطلوب (لمزوّد Namecheap).',
            'client_ip.required' => 'عنوان IP مطلوب (لمزوّد Namecheap).',
            'client_ip.ip'       => 'صيغة عنوان الـ IP غير صحيحة.',

            // Cloudflare
            'api_token.required' => 'الـ API Token مطلوب (لمزوّد Cloudflare).',
        ];
    }

    public function prepareForValidation(): void
    {
        // تطبيع سريع قبل التحقق
        $this->merge([
            'is_active' => filter_var($this->input('is_active', false), FILTER_VALIDATE_BOOL),
            'endpoint'  => $this->input('endpoint') ? trim((string) $this->input('endpoint')) : null,
            'type'      => $this->input('type') ? strtolower(trim((string) $this->input('type'))) : null,
        ]);

        if ($this->filled('mode')) {
            $this->merge([
                'mode' => strtolower(trim((string) $this->input('mode'))),
            ]);
        }
    }
}
