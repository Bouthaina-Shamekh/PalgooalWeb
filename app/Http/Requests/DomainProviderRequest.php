<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DomainProvider;

class DomainProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أضف صلاحياتك لاحقًا
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post'); // store = POST, update = PUT/PATCH

        // نسمح بالـ endpoint من الـ UI فقط إذا النوع "custom"
        $endpointRule = $this->input('type') === 'custom'
            ? ['required', 'url', 'max:191']
            : ['nullable', 'string', 'max:191']; // سيُعاد اشتقاقه بعد التحقق

        $rules = [
            'name'      => ['bail', 'required', 'string', 'max:191'],
            'type'      => ['bail', 'required', 'string', Rule::in(DomainProvider::TYPES), 'max:50'],
            'endpoint'  => $endpointRule,
            'is_active' => ['sometimes', 'boolean'],
            'mode'      => ['nullable', Rule::in(DomainProvider::MODES), 'max:10'], // live|test
        ];

        // pattern بسيط لاسم المستخدم (يُعدّل حسب مزودك)
        $usernameRules = ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9._-]{2,191}$/'];

        switch ($this->input('type')) {
            case 'enom':
                $rules = array_merge($rules, [
                    'username'  => $usernameRules,
                    // واحد على الأقل من الثلاثة
                    'password'  => ['nullable', 'string', 'max:255', 'required_without_all:api_token,api_key'],
                    'api_token' => ['nullable', 'string', 'max:255', 'required_without_all:password,api_key'],
                    'api_key'   => ['nullable', 'string', 'max:255', 'required_without_all:password,api_token'],
                    // يفضّل تحديد mode لنعرف test/live
                    'mode'      => ['required', Rule::in(DomainProvider::MODES)],
                ]);
                break;

            case 'namecheap':
                $rules = array_merge($rules, [
                    'username'  => $usernameRules,
                    'api_key'   => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
                    'client_ip' => ['required', 'ip'],
                    'mode'      => ['required', Rule::in(DomainProvider::MODES)], // لو عندك ساندبوكس
                ]);
                break;

            case 'cloudflare':
                $rules = array_merge($rules, [
                    'api_token' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
                ]);
                break;

            case 'custom':
                // لا شيء إضافي — يعتمد على endpoint المرسل
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
            'endpoint.required'  => 'يجب إدخال رابط الـ API (للنوع custom).',
            'endpoint.url'       => 'رابط الـ API غير صالح.',
            'mode.in'            => 'الوضع يجب أن يكون live أو test.',
            'mode.required'      => 'يرجى اختيار وضع الاتصال (live/test) لهذا المزود.',

            'username.required'  => 'اسم المستخدم مطلوب.',
            'username.regex'     => 'اسم المستخدم يحتوي على رموز غير مسموح بها.',

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

        // فراغات الحقول السرية
        foreach (['password', 'api_key', 'api_token'] as $secret) {
            if ($this->has($secret)) {
                $val = trim((string) $this->input($secret));
                // لو مفيش قيمة فعلية، حوّلها إلى null كي لا تُخزَّن كسلسلة فارغة
                $this->merge([$secret => ($val === '' ? null : $val)]);
            }
        }
    }

    /**
     * اشتقاق endpoint المسموح به بعد نجاح التحقق
     * (يمنع إدخال endpoints خارج whitelist للأنواع المعروفة)
     */
    protected function resolveEndpoint(string $type, ?string $mode): ?string
    {
        $mode = $mode ?: 'live';

        // فضّل config/ إن توفّرت
        $enom = [
            'live' => config('domains.providers.enom.live', 'https://reseller.enom.com/interface.asp'),
            'test' => config('domains.providers.enom.test', 'https://resellertest.enom.com/interface.asp'),
        ];
        $namecheap = [
            'live' => config('domains.providers.namecheap.live', 'https://api.namecheap.com/xml.response'),
            'test' => config('domains.providers.namecheap.test', 'https://api.sandbox.namecheap.com/xml.response'),
        ];
        $cloudflare = [
            'live' => config('domains.providers.cloudflare.live', 'https://api.cloudflare.com/client/v4'),
            'test' => config('domains.providers.cloudflare.test', 'https://api.cloudflare.com/client/v4'),
        ];

        return match ($type) {
            'enom'       => $enom[$mode]    ?? $enom['live'],
            'namecheap'  => $namecheap[$mode] ?? $namecheap['live'],
            'cloudflare' => $cloudflare[$mode] ?? $cloudflare['live'],
            default      => $this->input('endpoint'), // custom
        };
    }

    public function passedValidation(): void
    {
        $type = (string) $this->input('type');
        $mode = $this->input('mode');

        // الأنواع المعروفة: تجاهل أي endpoint أُرسل من الـ UI، واشتقه من النوع/الوضع
        if (in_array($type, ['enom', 'namecheap', 'cloudflare'], true)) {
            $this->merge([
                'endpoint' => $this->resolveEndpoint($type, $mode),
            ]);
        }
    }
}
