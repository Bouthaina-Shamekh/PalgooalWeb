<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $request->validate([
            'items' => 'required|array|min:1',
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

        // طبّع العناصر الواردة
        $incoming = array_map(function ($it) {
            return [
                'domain'      => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option' => $it['item_option'] ?? $it['option'] ?? null,
                'price_cents' => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'meta'        => $it['meta'] ?? null,
            ];
        }, $raw);

        // حمّل من السيشن وطبّعه أيضًا (لمرات سابقة)
        $existingRaw = session('palgoals_cart_domains', []);
        $existingRaw = is_array($existingRaw) ? $existingRaw : [];
        $existing = array_map(function ($it) {
            return [
                'domain'      => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option' => $it['item_option'] ?? $it['option'] ?? null,
                'price_cents' => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'meta'        => $it['meta'] ?? null,
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

        // تحقق بعد التطبيع
        $v = Validator::make(['items' => array_merge($existingUnique, $incomingUnique)], [
            'items'               => 'required|array|min:1',
            'items.*.domain'      => 'required|string|min:1|distinct',
            'items.*.item_option' => 'required|string|min:1',
            'items.*.price_cents' => 'nullable|integer|min:0',
        ]);
        if ($v->fails()) {
            return response()->json([
                'ok'      => false,
                'message' => 'بيانات السلة غير صالحة.',
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

    /**
     * Process domain-only checkout using session-stored (or body-sent) cart
     */
    public function processDomains(Request $request)
    {
        $raw = $request->input('items', session('palgoals_cart_domains', []));
        if (empty($raw) || !is_array($raw)) {
            return response()->json(['ok' => false, 'message' => 'السلة فارغة.'], 422);
        }

        // فلترة غير الدومينات (أمان إضافي)
        $raw = array_values(array_filter($raw, function ($it) {
            if (isset($it['domain']) && trim((string)$it['domain']) !== '') return true;
            if (isset($it['kind']) && $it['kind'] === 'domain') return true;
            return false;
        }));

        // طبّع العناصر
        $items = array_map(function ($it) {
            return [
                'domain'      => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option' => $it['item_option'] ?? $it['option'] ?? null,
                'price_cents' => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'meta'        => $it['meta'] ?? null,
            ];
        }, $raw);

        // أزل التكرار داخل الطلب نفسه (حتى لو الفرونت كرّر)
        [$uniqueItems, $dups] = $this->dedupeItems($items, []);
        if (empty($uniqueItems)) {
            return response()->json(['ok' => false, 'message' => 'السلة فارغة بعد إزالة العناصر المكررة.'], 422);
        }

        // تحقق سريع
        $v = Validator::make(['items' => $uniqueItems], [
            'items'               => 'required|array|min:1',
            'items.*.domain'      => 'required|string|min:1|distinct',
            'items.*.item_option' => 'required|string|min:1',
            'items.*.price_cents' => 'nullable|integer|min:0',
        ]);
        if ($v->fails()) {
            return response()->json([
                'ok'      => false,
                'message' => 'بيانات السلة غير صالحة.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $total = array_reduce($uniqueItems, fn($carry, $it) => $carry + ((int) ($it['price_cents'] ?? 0)), 0);

        try {
            $order = DB::transaction(function () use ($uniqueItems) {
                // ملاحظة: يُفترض أن order_number يتولَّد تلقائيًا في موديل Order (booted())
                $order = Order::create([
                    'client_id' => auth('client')->check() ? auth('client')->id() : null,
                    'status'    => 'pending',
                    'type'      => 'domain_only',
                    'notes'     => 'الحجز من السلة المؤقتة',
                ]);

                // حمولة البنود
                $payload = array_map(function ($it) {
                    return [
                        'domain'      => $it['domain'],
                        'item_option' => $it['item_option'],
                        'price_cents' => (int) ($it['price_cents'] ?? 0),
                        'meta'        => $it['meta'] ?? null,
                    ];
                }, $uniqueItems);

                // إنشاء البنود
                $order->items()->createMany($payload);

                return $order;
            });
        } catch (\Throwable $e) {
            Log::error('CartController::processDomains transaction failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'تعذر إنشاء الطلب. حاول لاحقًا.'], 500);
        }

        // خزّن مرجعًا في الجلسة لاستهلاكه لاحقًا في /checkout/cart
        session([
            'palgoals_reserved'      => $uniqueItems,
            'palgoals_last_order_id' => $order->id,
            'palgoals_cart_domains'  => $uniqueItems, // تستخدمها CheckoutController@cart لتمرير العناصر للعرض
        ]);

        return response()->json([
            'ok'                  => true,
            'message'             => 'تم حجز الدومينات وإنشاء طلب مؤقت.',
            'order_no'            => $order->order_number,
            'order_id'            => $order->id,
            'total_cents'         => $total,
            'skipped_duplicates'  => $dups, // دومينات كانت مكررة وتم تجاهلها
        ]);
    }
}

