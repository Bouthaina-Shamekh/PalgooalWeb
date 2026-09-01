<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Domains\DomainPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    /**
     * Normalize domain: strip protocol/path, drop leading www., lowercase, IDN -> ASCII if possible.
     */
    private function normalizeDomain(?string $raw): ?string
    {
        if (!is_string($raw)) return null;
        $d = trim($raw);

        // لو أرسل رابط كامل، استخرج الـ host
        $host = parse_url($d, PHP_URL_HOST);
        if (!$host) {
            // جرب باعتبار أن القيمة بدون بروتوكول
            $host = parse_url('http://' . $d, PHP_URL_HOST) ?: $d;
        }

        $host = strtolower($host);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        // دعم IDN لو متاح
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) $host = strtolower($ascii);
        }

        // إزالة النقطة النهائية إن وُجدت
        $host = rtrim($host, '.');

        return $host ?: null;
    }

    /**
     * فحص شكلي سريع على الدومين (اختياري لكنه مفيد).
     */
    private function isLikelyDomain(string $d): bool
    {
        if (substr_count($d, '.') < 1) return false;
        if (strlen($d) > 253) return false;
        return true;
    }

    /**
     * Deduplicate items by normalized domain.
     * Returns array: [$uniqueItems, $duplicatesDomains]
     */
    private function dedupeItems(array $items, array $existingDomains = []): array
    {
        $seen = array_fill_keys($existingDomains, true);
        $unique = [];
        $dups = [];

        foreach ($items as $it) {
            $norm = $this->normalizeDomain($it['domain'] ?? null);
            if (!$norm || !$this->isLikelyDomain($norm)) {
                // تجاهل مدخل غير صالح
                continue;
            }

            // خزّن النسخة المطبّعة داخل العنصر
            $it['domain'] = $norm;

            if (isset($seen[$norm])) {
                $dups[] = $norm;
                continue;
            }
            $seen[$norm] = true;
            $unique[] = $it;
        }

        return [$unique, array_values(array_unique($dups))];
    }

    /**
     * Store client-side cart into server session (merge + dedupe against existing)
     */
    public function store(Request $request)
    {
        $unsupportedActionMessage = t(
            'site.Domain_Item_Option_Unsupported',
            'نوع عملية الدومين غير مدعوم في هذا المسار.'
        );

        $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.item_option' => ['required', 'string', Rule::in(['register'])],
        ], [
            'items.*.item_option.required' => $unsupportedActionMessage,
            'items.*.item_option.string'   => $unsupportedActionMessage,
            'items.*.item_option.in'       => $unsupportedActionMessage,
        ]);

        // عناصر مرسلة من الواجهة
        $raw = $request->input('items', []);

        // اسمح فقط بعناصر الدومين (في حال أرسلت الواجهة عناصر قوالب أو أي نوع آخر)
        $raw = array_values(array_filter($raw, function ($it) {
            // نوع قديم بدون kind لكنه يحتوي domain
            if (isset($it['domain']) && trim((string)$it['domain']) !== '') return true;
            // نوع موحّد: نمرِّر فقط kind=domain
            if (isset($it['kind']) && $it['kind'] === 'domain') return true;
            return false;
        }));

        if (empty($raw)) {
            return response()->json([
                'ok'      => false,
                'message' => 'لا توجد عناصر دومين صالحة في الطلب.',
            ], 422);
        }

        // ADR — تسعير موثوق: price_cents القادم من المتصفح لا يُعتمَد كمصدر مالي إطلاقاً.
        // كل دومين يُعاد تسعيره هنا من DomainPricingService (domain_tld_prices)؛ فشل تسعير أي عنصر يوقف الدفعة كاملة (لا سلة جزئية).
        $pricing = app(DomainPricingService::class);

        $incoming = [];
        foreach ($raw as $it) {
            $domain = isset($it['domain']) ? strtolower(trim((string) $it['domain'])) : null;
            $clientPriceCents = isset($it['price_cents']) ? (int) $it['price_cents'] : null;

            if (!$domain) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'تعذر تحديد سعر هذا الدومين حالياً.',
                ], 422);
            }

            $quote = $pricing->registrationQuoteForDomain($domain);

            if ($quote === null) {
                Log::warning('Cart store: no trusted registration price for domain', [
                    'domain'                       => $domain,
                    'client_submitted_price_cents' => $clientPriceCents,
                ]);

                return response()->json([
                    'ok'      => false,
                    'message' => 'تعذر تحديد سعر هذا الدومين حالياً.',
                ], 422);
            }

            // تسجيل تشخيصي فقط عند اختلاف ما أرسله المتصفح عن السعر الموثوق — لا يُستخدم مالياً بأي شكل
            if ($clientPriceCents !== null && $clientPriceCents !== $quote['price_cents']) {
                Log::info('Cart store: client-submitted price_cents ignored (differs from trusted catalog price)', [
                    'domain'                       => $domain,
                    'client_submitted_price_cents' => $clientPriceCents,
                    'trusted_price_cents'          => $quote['price_cents'],
                ]);
            }

            $incoming[] = [
                'domain'        => $domain,
                'item_option'   => 'register',
                'price_cents'   => $quote['price_cents'],
                'price'         => $quote['price'],
                'currency'      => $quote['currency'],
                'provider_id'   => $quote['provider_id'],
                'provider_type' => $quote['provider_type'],
                'provider_mode' => $quote['provider_mode'],
                'domain_tld_id' => $quote['domain_tld_id'],
                'meta'          => array_merge(is_array($it['meta'] ?? null) ? $it['meta'] : [], [
                    'provider_id' => $quote['provider_id'],
                    'provider_type' => $quote['provider_type'],
                    'provider_mode' => $quote['provider_mode'],
                    'domain_tld_id' => $quote['domain_tld_id'],
                    'currency' => $quote['currency'],
                    'years' => 1,
                ]),
            ];
        }

        // حمّل من السيشن وطبّعه أيضًا (لمرات سابقة — عناصر موثوقة بالفعل من نفس هذا المسار)
        $existingRaw = session('palgoals_cart_domains', []);
        $existingRaw = is_array($existingRaw) ? $existingRaw : [];
        $existing = array_map(function ($it) {
            return [
                'domain'        => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option'   => $it['item_option'] ?? $it['option'] ?? null,
                'price_cents'   => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'price'         => $it['price'] ?? null,
                'currency'      => $it['currency'] ?? null,
                'provider_id'   => $it['provider_id'] ?? null,
                'provider_type' => $it['provider_type'] ?? null,
                'provider_mode' => $it['provider_mode'] ?? null,
                'domain_tld_id' => $it['domain_tld_id'] ?? null,
                'meta'          => $it['meta'] ?? null,
            ];
        }, $existingRaw);

        // أزل التكرار داخل الموجود أصلًا
        [$existingUnique,] = $this->dedupeItems($existing, []);

        // ابني مجموعة الدومينات الموجودة
        $existingDomains = array_values(array_unique(array_map(
            fn($it) => $this->normalizeDomain($it['domain'] ?? null),
            $existingUnique
        )));
        $existingDomains = array_filter($existingDomains);

        // أزل تكرار العناصر الواردة مع مراعاة الموجود سابقًا
        [$incomingUnique, $dups] = $this->dedupeItems($incoming, $existingDomains);

        // تحقق بعد التطبيع — price_cents الآن مطلوب وموجب دائماً (مصدره الخدمة الموثوقة فقط)
        $v = Validator::make(['items' => array_merge($existingUnique, $incomingUnique)], [
            'items'               => 'required|array|min:1',
            'items.*.domain'      => 'required|string|min:1|distinct',
            'items.*.item_option' => ['required', 'string', Rule::in(['register'])],
            'items.*.price_cents' => 'required|integer|min:1',
        ], [
            'items.*.item_option.required' => $unsupportedActionMessage,
            'items.*.item_option.string'   => $unsupportedActionMessage,
            'items.*.item_option.in'       => $unsupportedActionMessage,
        ]);
        if ($v->fails()) {
            return response()->json([
                'ok'      => false,
                'message' => $v->errors()->first('items.*.item_option')
                    ?: 'بيانات السلة غير صالحة.',
                'errors'  => $v->errors(),
            ], 422);
        }

        // ادمج و خزّن
        $final = array_values(array_merge($existingUnique, $incomingUnique));
        session(['palgoals_cart_domains' => $final]);

        return response()->json([
            'ok'                 => true,
            'message'            => 'تم حفظ السلة في الجلسة.',
            'items_count'        => count($final),
            'skipped_duplicates' => $dups, // تم تجاهل هذه الدومينات لأنها مكررة
        ]);
    }

    /**
     * Clear domain-only items from server session cart.
     */
    public function clear(Request $request)
    {
        // Remove only domain cart key; keep other session data intact
        $request->session()->forget('palgoals_cart_domains');
        return response()->json(['ok' => true]);
    }

}

