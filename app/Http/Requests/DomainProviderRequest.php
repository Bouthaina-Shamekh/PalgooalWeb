<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
            'type'      => ['required', 'string', 'in:' . implode(',', DomainProvider::TYPES), 'max:50'],
            // endpoint الآن مطلوب لأنه يُختار صراحة من الـ Select
            'endpoint'  => ['required', 'url', 'max:191'],
            'is_active' => ['sometimes', 'boolean'],
            // mode للعرض/الإحصاء فقط، خليه اختياري
            'mode'      => ['nullable', 'in:' . implode(',', DomainProvider::MODES), 'max:10'],
        ];

        switch ($this->input('type')) {
            case 'enom':
                // في الإنشاء: username مطلوب + (password أو api_token) أحدهما مطلوب
                // في التحديث: username مطلوب،
                //   و password/api_token "اختياريان" (لا نطلبهما إن تُركا فارغين)
                $rules = array_merge($rules, [
                    'username'  => ['required', 'string', 'max:191'],
                    'password'  => [$isCreate ? 'nullable|required_without:api_token' : 'nullable', 'string', 'max:255'],
                    'api_token' => [$isCreate ? 'nullable|required_without:password' : 'nullable', 'string', 'max:255'],
                ]);
                break;

            case 'namecheap':
                // Namecheap يحتاج api_key + client_ip
                // في التحديث، اترك api_key اختيارياً (لو فارغ لا نحدّثه في الـ controller)
                $rules = array_merge($rules, [
                    'username'  => ['required', 'string', 'max:191'],
                    'api_key'   => [$isCreate ? 'required' : 'nullable', 'string', 'max:255'],
                    'client_ip' => ['required', 'ip'],
                ]);
                break;

            case 'cloudflare':
                // Cloudflare يعتمد على api_token فقط (عادة لا يحتاج username/password)
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
            'type.in'            => 'نوع المزود غير مدعوم.',
            'endpoint.required'  => 'يجب اختيار رابط الـ API (Endpoint).',
            'endpoint.url'       => 'رابط الـ API غير صالح.',
            'mode.in'            => 'الوضع يجب أن يكون live أو test.',

            // Enom
            'username.required'            => 'اسم المستخدم مطلوب.',
            'password.required_without'    => 'كلمة المرور مطلوبة إذا لم يتم إدخال الـ API Token (Enom).',
            'api_token.required_without'   => 'الـ API Token مطلوب إذا لم يتم إدخال كلمة المرور (Enom).',

            // Namecheap
            'api_key.required'   => 'مفتاح API مطلوب (لمزود Namecheap).',
            'client_ip.required' => 'عنوان IP مطلوب (لمزود Namecheap).',
            'client_ip.ip'       => 'صيغة عنوان الـ IP غير صحيحة.',

            // Cloudflare
            'api_token.required' => 'الـ API Token مطلوب (لمزود Cloudflare).',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var($this->input('is_active', false), FILTER_VALIDATE_BOOL),
            'endpoint'  => $this->input('endpoint') ? trim($this->input('endpoint')) : null,
            'type'      => $this->input('type') ? strtolower(trim($this->input('type'))) : null,
            // لا تفرض mode = null إذا مفقود
            // 'mode'      => $this->input('mode') ? strtolower(trim($this->input('mode'))) : null,
        ]);

        if ($this->filled('mode')) {
            $this->merge([
                'mode' => strtolower(trim($this->input('mode')))
            ]);
        }
    }
}
