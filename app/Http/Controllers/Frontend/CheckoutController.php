<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index($template_id)
    {
        $template = \App\Models\Template::find($template_id);
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();

        return view('tamplate.checkout', compact('template_id', 'template', 'translation'));
    }

    /**
     * Render checkout for domain-only cart (no template).
     * Reads items from session if available. Renders same view with null template.
     */
    public function cart(Request $request)
    {
        $items = session('palgoals_cart_domains', []);
        // دعم تمرير قالب عبر الاستعلام لعرضه داخل المراجعة الموحدة
        $template_id = $request->query('template_id') ?? $request->query('tid');
        $template = null;
        $translation = null;
        if (!empty($template_id)) {
            $template = \App\Models\Template::find($template_id);
            $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        }

        return view('tamplate.checkout', compact('template_id', 'template', 'translation', 'items'));
    }

    /**
     * Checkout handler for both template checkout and domain-only checkout (when $template_id is null).
     */
    public function process(Request $request, $template_id)
    {
        // إنشاء حساب للعميل في حال عدم التسجيل
        if (!auth('client')->check()) {
            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'email'      => 'required|email|max:255|unique:clients,email',
                'phone'      => 'required|string|max:30',
                'password'   => 'required|string|min:6|confirmed',
            ]);

            $client = new \App\Models\Client();
            $client->first_name  = $request->first_name;
            $client->last_name   = $request->last_name;
            $client->email       = $request->email;
            $client->phone       = $request->phone;
            $client->company_name = $request->company_name ?? '-';
            $client->password    = bcrypt($request->password);
            $client->save();

            auth('client')->login($client);
        }

        $isDomainOnly = empty($template_id);

        // لو في عناصر قادمة من الطلب استخدمها؛ وإلا خذها من السيشن (لسيناريو الدومين فقط)
        $rawItems = $request->input('items', session('palgoals_cart_domains', []));
        $items = array_map(function ($it) {
            return [
                'domain'      => isset($it['domain']) ? strtolower(trim($it['domain'])) : null,
                'item_option' => $it['item_option'] ?? $it['option'] ?? null, // تطبيع
                'price_cents' => isset($it['price_cents']) ? (int) $it['price_cents'] : 0,
                'meta'        => $it['meta'] ?? null,
            ];
        }, is_array($rawItems) ? $rawItems : []);

        // معلومات القالب (إن وجد)
        $template    = $isDomainOnly ? null : \App\Models\Template::find($template_id);
        $translation = $template?->translations()->where('locale', app()->getLocale())->first();
        $template_name = $translation?->name ?? $template?->name ?? '';

        $basePrice = (float) ($template->price ?? 0);
        $discRaw   = $template->discount_price ?? null;
        $discPrice = is_null($discRaw) ? null : (float) $discRaw;
        $hasDiscount  = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
        $showDiscount = $hasDiscount && (!$template?->discount_ends_at || now()->lt($template->discount_ends_at));

        // تطبيع اختيار الدومين القادم من الواجهة (للاشتراك)
        $rawOption = $request->input('domain_option'); // قد لا يكون موجودًا
        $optionMap = [
            'register'  => 'new',
            'subdomain' => 'subdomain',
            'own'       => 'existing',
            'transfer'  => 'existing',
        ];
        $normalizedOption = $rawOption ? ($optionMap[$rawOption] ?? $rawOption) : null;

        // إجمالي سلة الدومينات (في حالة الدومين فقط)
        $domainsTotalCents = array_reduce($items, fn($c, $it) => $c + ((int) ($it['price_cents'] ?? 0)), 0);

        // تحقق خاص بتدفّق القالب: يجب وجود دومين أساسي
        if (!$isDomainOnly) {
            $request->validate([
                'domain'        => 'required|string|min:1',
                'domain_option' => 'required|string|min:1',
            ]);
        }

        try {
            $result = DB::transaction(function () use (
                $isDomainOnly,
                $items,
                $template,
                $template_id,
                $template_name,
                $basePrice,
                $discPrice,
                $showDiscount,
                $normalizedOption,
                $request
            ) {
                // 1) إنشاء الطلب
                $order = \App\Models\Order::create([
                    'client_id' => auth('client')->check() ? auth('client')->id() : null,
                    'status'    => 'pending',
                    'type'      => $isDomainOnly ? 'domains' : 'subscription',
                    'notes'     => $isDomainOnly ? 'حجز دومينات من سلة الدومينات' : 'طلب عبر صفحة checkout',
                ]);

                // 2) إنشاء بنود الطلب
                if ($isDomainOnly) {
                    if (empty($items)) {
                        abort(422, 'السلة فارغة.');
                    }
                    $payload = array_map(function ($it) {
                        return [
                            'domain'      => $it['domain'],
                            'item_option' => $it['item_option'],
                            'price_cents' => (int) ($it['price_cents'] ?? 0),
                            'meta'        => $it['meta'],
                        ];
                    }, $items);
                    $order->items()->createMany($payload);
                } else {
                    // لو وصل دومين مع شراء القالب، خزّنه كبند واحد (اختياري لكنه مفيد للمراجعة/التتبع)
                    $domainFromRequest = $request->input('domain');
                    $optionFromRequest = $request->input('domain_option');
                    if (!empty($domainFromRequest) || !empty($optionFromRequest)) {
                        $order->items()->create([
                            'domain'      => $domainFromRequest ? strtolower(trim($domainFromRequest)) : null,
                            'item_option' => $optionFromRequest ?? null,
                            'price_cents' => 0,
                            'meta'        => null,
                        ]);
                    }
                }

                // 3) إنشاء الفاتورة
                if ($isDomainOnly) {
                    $subtotalCents = (int) array_reduce($items, fn($c, $it) => $c + ((int) ($it['price_cents'] ?? 0)), 0);
                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => $subtotalCents,
                        'discount_cents' => 0,
                        'tax_cents'      => 0,
                        'total_cents'    => $subtotalCents,
                        'currency'       => 'USD',
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                    ]);
                    // بإمكانك لاحقًا إضافة invoice_items لكل دومين إن رغبت
                } else {
                    $unitCents = $showDiscount ? (int) ($discPrice * 100) : (int) ($basePrice * 100);
                    $discountCents = $showDiscount ? (int) (($basePrice - $discPrice) * 100) : 0;

                    $invoice = \App\Models\Invoice::create([
                        'client_id'      => $order->client_id,
                        'number'         => 'INV-' . $order->order_number,
                        'status'         => 'draft',
                        'subtotal_cents' => (int) ($basePrice * 100),
                        'discount_cents' => $discountCents,
                        'tax_cents'      => 0,
                        'total_cents'    => $unitCents,
                        'currency'       => 'USD',
                        'due_date'       => now()->addDays(3),
                        'order_id'       => $order->id,
                    ]);

                    \App\Models\InvoiceItem::create([
                        'invoice_id'       => $invoice->id,
                        'item_type'        => 'subscription',
                        'reference_id'     => $template_id,
                        'description'      => $template_name,
                        'qty'              => 1,
                        'unit_price_cents' => $unitCents,
                        'total_cents'      => $unitCents,
                    ]);

                    // 4) إنشاء اشتراك Pending (اختياريًا؛ حسب منطقك)
                    $plan = $template?->plan;
                    if ($plan) {
                        // استخرج دومين إن وُجد بند به دومين
                        $firstDomainItem = $order->items()
                            ->whereNotNull('domain')
                            ->where('domain', '<>', '')
                            ->orderBy('id')
                            ->first();

                        \App\Models\Subscription::create([
                            'client_id'     => $order->client_id,
                            'plan_id'       => $plan->id,
                            'status'        => 'pending',
                            'billing_cycle' => $plan->billing_cycle ?? 'annually',
                            'price'         => $unitCents / 100,
                            'server_id'     => $plan->server_id ?? null,
                            'domain_option' => $normalizedOption ?? 'subdomain',
                            'domain_name'   => $firstDomainItem->domain ?? $request->input('domain'),
                            'starts_at'     => now(),
                            'next_due_date' => now()->addMonth(),
                        ]);
                    }
                }

                return $order;
            });

            // احفظ مرجعًا في الجلسة
            session([
                'palgoals_reserved'      => $items,
                'palgoals_last_order_id' => $result->id,
            ]);

            // البيانات المرجعة للواجهة
            $client_name   = auth('client')->user()->first_name ?? '';
            $domainPicked  = $items[0]['domain'] ?? $request->input('domain');
            $totalCents    = $isDomainOnly
                ? $domainsTotalCents
                : ($showDiscount ? (int) ($discPrice * 100) : (int) ($basePrice * 100));

            $responseData = [
                'success'      => true,
                'order_no'     => $result->order_number,
                'order_id'     => $result->id,
                'domain'       => $domainPicked,
                'total_cents'  => $totalCents,
                'client_name'  => $client_name,
                'template_name' => $template_name,
                'redirect'     => route('checkout.domains.success'),
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($responseData);
            }

            return redirect()->route('checkout', array_merge([
                'template_id'        => $template_id,
                'success'            => 1,
                'order_no'           => $result->order_number,
                'domain'             => $domainPicked,
                'total'              => $totalCents / 100,
                'client_name'        => $client_name,
                'template_name'      => $template_name,
            ]));
        } catch (\Throwable $e) {
            Log::error('CheckoutController::process failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الدفع الآن.'], 500);
        }
    }

    /**
     * POST entry for cart checkout (domain-only). Validates items then forwards to process().
     */
    public function processCart(Request $request)
    {
        $data = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.domain'      => 'required|string',
            'items.*.option'      => 'required|string', // سنطبّعها داخل process إلى item_option
            'items.*.price_cents' => 'nullable|integer|min:0',
        ]);

        session(['palgoals_cart_domains' => $data['items']]);

        // مرّر العناصر إلى process() مع template_id = null
        return $this->process($request->merge(['items' => $data['items']]), null);
    }
}
