<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DomainTldController extends Controller
{
    /**
     * قائمة TLDs الافتراضية للإقلاع من الصفر (cold start)، مشتركة بين كل المزوّدين.
     * TLD-1: توحيد سلوك cold-start بين Namecheap وEnom بدل fallback منفصل لكل مزوّد.
     */
    private const DEFAULT_BOOTSTRAP_TLDS = [
        'com',
        'net',
        'org',
        'shop',
        'xyz',
        'live',
        'news',
        'rocks',
        'ninja',
    ];

    /**
     * TLD-2B: حد أقصى لعدد TLDs الصريحة القادمة من حقل "tlds" في نموذج المزامنة،
     * لمنع طلب واحد من إطلاق عدد غير محدود من نداءات HTTP الخارجية للمزوّد.
     * يُطبَّق فقط على القائمة الصريحة؛ لا يمس أولوية catalog/bootstrap في resolveTldsToSync().
     */
    private const MAX_EXPLICIT_TLDS = 50;

    /**
     * نفس normalization المستخدمة أصلاً في sync() لحقل "tlds" القادم من الطلب:
     * lowercase + trim + إزالة أي نقطة بادئة + استبعاد الفارغ + إزالة التكرار.
     *
     * @param  array<int, string>  $tlds
     * @return array<int, string>
     */
    private function normalizeTlds(array $tlds): array
    {
        return collect($tlds)
            ->map(fn ($s) => strtolower(trim(ltrim((string) $s, '.'))))
            ->filter()->unique()->values()->all();
    }

    /**
     * ترتيب اختيار TLDs للمزامنة (نفس المنطق لكل من Namecheap وEnom):
     * 1) قائمة صريحة من الطلب (إن وُجدت) — تُستخدم كما هي فقط.
     * 2) وإلا: TLDs المعلّمة حاليًا في الكتالوج لهذا المزوّد (in_catalog = true).
     * 3) وإلا: القائمة البذرية المشتركة DEFAULT_BOOTSTRAP_TLDS (بعد نفس الـ normalization).
     *
     * @param  array<int, string>  $explicitTlds
     * @return array<int, string>
     */
    private function resolveTldsToSync(int $providerId, array $explicitTlds): array
    {
        if (!empty($explicitTlds)) {
            return $explicitTlds;
        }

        $catalogTlds = \App\Models\DomainTld::where('provider_id', $providerId)
            ->where('in_catalog', true)
            ->pluck('tld')->all();

        if (!empty($catalogTlds)) {
            return $catalogTlds;
        }

        return $this->normalizeTlds(self::DEFAULT_BOOTSTRAP_TLDS);
    }

    /**
     * TLD-2B: يصنّف نتيجة sync() الواحدة إلى success/warning/error بناءً فقط على
     * ما تم إثباته فعليًا (عدد صفوف الأسعار المضافة/المحدثة + وجود issues مُهيكلة).
     * لا success أخضر غير مشروط عند وجود issues.
     */
    private function classifySyncStatus(int $priceRowsAdded, int $priceRowsUpdated, array $issues): string
    {
        if (empty($issues)) {
            return 'success';
        }
        if ($priceRowsAdded > 0 || $priceRowsUpdated > 0) {
            return 'warning';
        }
        return 'error';
    }

    public function index(Request $req)
    {
        $this->authorize('viewAny', DomainTld::class);
        $providerId = (int) $req->query('provider_id', 0);
        $providers  = DomainProvider::active()->whereIn('type', ['namecheap', 'enom'])->get();

        // TLD-2B: فلاتر إضافية additive فقط — بحث TLD + enabled + in_catalog. لا تمس pagination
        // ولا أي عقد حالي؛ withQueryString() الموجودة أصلاً في الـBlade تحملها عبر صفحات الترقيم.
        $tldSearch = trim((string) $req->query('q', ''));
        $enabledFilter = (string) $req->query('enabled', 'all');
        if (!in_array($enabledFilter, ['all', '1', '0'], true)) $enabledFilter = 'all';
        $catalogFilter = (string) $req->query('in_catalog', 'all');
        if (!in_array($catalogFilter, ['all', '1', '0'], true)) $catalogFilter = 'all';

        $q = DomainTld::query()->with(['prices' => function ($q) {
            $q->whereIn('action', ['register', 'renew', 'transfer'])->where('years', 1);
        }])->orderBy('tld');

        if ($providerId) $q->where('provider_id', $providerId);
        if ($tldSearch !== '') $q->where('tld', 'like', '%' . $tldSearch . '%');
        if ($enabledFilter !== 'all') $q->where('enabled', $enabledFilter === '1');
        if ($catalogFilter !== 'all') $q->where('in_catalog', $catalogFilter === '1');

        $rows = $q->paginate(50);

        return view('dashboard.management.domain_tlds.index', compact(
            'rows', 'providers', 'providerId', 'tldSearch', 'enabledFilter', 'catalogFilter'
        ));
    }

    public function sync(Request $req)
    {
        $this->authorize('create', DomainTld::class);

        // TLD-2B: عدم استخدام firstOrFail() هنا لتفادي 404 خام — provider غير موجود/غير نشط
        // يُعالَج الآن كـ sync_summary بحالة error مفهومة للأدمن بدل صفحة خطأ خام.
        $provider = DomainProvider::active()
            ->where('id', (int) $req->input('provider_id'))
            ->whereIn('type', ['namecheap', 'enom'])
            ->first();

        if (!$provider) {
            return redirect()
                ->route('dashboard.domain_tlds.index')
                ->with('sync_summary', [
                    'status'             => 'error',
                    'provider'           => null,
                    'requested_tlds'     => 0,
                    'price_rows_added'   => 0,
                    'price_rows_updated' => 0,
                    'issues_count'       => 1,
                    'issues'             => [[
                        'tld' => null,
                        'action' => null,
                        'reason' => 'المزوّد المحدد غير موجود أو غير نشط.',
                    ]],
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ]);
        }

        $explicitTlds = $this->normalizeTlds(explode(',', (string) $req->input('tlds', '')));

        // TLD-2B: حد أقصى للقائمة الصريحة فقط (Sync input hardening) — لا يمس أولوية
        // catalog/bootstrap داخل resolveTldsToSync() لأنها تُستدعى فقط عندما تكون القائمة الصريحة فارغة.
        $requestedExplicitCount = count($explicitTlds);
        $explicitTlds = array_slice($explicitTlds, 0, self::MAX_EXPLICIT_TLDS);
        $trimmedExplicitCount = $requestedExplicitCount - count($explicitTlds);

        $report = ($provider->type === 'namecheap')
            ? $this->syncFromNamecheap($provider, $explicitTlds)
            : $this->syncFromEnom($provider, $explicitTlds);

        $issues = $report['issues'] ?? [];
        if ($trimmedExplicitCount > 0) {
            array_unshift($issues, [
                'tld' => null,
                'action' => null,
                'reason' => "تم تجاهل {$trimmedExplicitCount} TLD إضافية بسبب الحد الأقصى (" . self::MAX_EXPLICIT_TLDS . ') لكل طلب مزامنة.',
            ]);
        }

        $status = $this->classifySyncStatus((int) $report['added'], (int) $report['updated'], $issues);

        $summary = [
            'status'             => $status,
            'provider'           => [
                'id'   => $provider->id,
                'name' => $provider->name,
                'type' => $provider->type,
            ],
            'requested_tlds'     => (int) ($report['requested'] ?? 0),
            'price_rows_added'   => (int) $report['added'],
            'price_rows_updated' => (int) $report['updated'],
            'issues_count'       => count($issues),
            'issues'             => $issues,
            'timestamp'          => now()->format('Y-m-d H:i:s'),
        ];

        return redirect()
            ->route('dashboard.domain_tlds.index', ['provider_id' => $provider->id])
            ->with('sync_summary', $summary);
    }

    public function updateSale(Request $req)
    {
        $this->authorize('update', DomainTld::class);
        $data = $req->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:domain_tld_prices,id'],
            'items.*.sale' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $row) {
                DomainTldPrice::where('id', $row['id'])->update(['sale' => $row['sale']]);
            }
        });

        return back()->with('ok', 'تم حفظ أسعار البيع.');
    }

    protected function syncFromNamecheap(\App\Models\DomainProvider $p, array $onlyTlds = []): array
    {
        $headers = [
            'Accept'          => 'application/xml',
            'Accept-Encoding' => 'gzip',
            'User-Agent'      => 'PalgoalsBot/1.0',
        ];
        $options = [
            'curl'        => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4, \CURLOPT_ENCODING => ''],
            'http_errors' => false,
        ];
        $endpoint = $p->endpoint ?: ($p->mode === 'test'
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response');

        $base = [
            'ApiUser'     => trim((string)$p->username),
            'ApiKey'      => trim((string)$p->api_key),
            'UserName'    => trim((string)$p->username),
            'ClientIp'    => trim((string)$p->client_ip),
            'ProductType' => 'DOMAIN',
        ];

        $actions     = ['REGISTER', 'RENEW', 'TRANSFER'];
        $added = 0;
        $updated = 0;
        $tldsTouched = 0;
        $message = '';
        $issues = []; // TLD-2B: structured issues [tld, action, reason] — بديل عن رسالة نصية غير مُهيكلة فقط

        // مغلّف XPath آمن: يضمن تسجيل namespace 'nc' على نفس السياق قبل كل استعلام
        $NS = 'http://api.namecheap.com/xml.response';
        $xp = function (\SimpleXMLElement $ctx, string $expr) use ($NS): array {
            $ctx->registerXPathNamespace('nc', $NS);
            $res = @$ctx->xpath($expr);        // @ لمنع التحذيرات
            return is_array($res) ? $res : [];
        };

        $fetchXml = function (array $params, int $timeout = 45) use ($endpoint, $headers, $options) {
            $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withOptions($options)->connectTimeout(10)->timeout($timeout)->retry(2, 400)
                ->get($endpoint, $params);

            if (!$resp->ok() || stripos((string)$resp->header('Content-Type'), 'xml') === false) {
                return [null, "HTTP {$resp->status()} أو غير XML"];
            }
            $xml = @simplexml_load_string((string)$resp->body(), 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOERROR | \LIBXML_NOWARNING);
            return $xml ? [$xml, null] : [null, 'XML parse error'];
        };

        // ترتيب الاختيار الموحّد: صريح من الطلب → in_catalog → bootstrap مشترك (TLD-1)
        $onlyTlds = $this->resolveTldsToSync($p->id, $onlyTlds);
        $requested = count($onlyTlds); // TLD-2B: يُعاد في التقرير لتمييزه عن عدّادات صفوف الأسعار

        // أداة استخراج (years, price, currency) من أي عقدة تسعير
        $extractPricing = function (\SimpleXMLElement $node): array {
            $a = $node->attributes();

            // Years
            $durationRaw = (string)($a['Duration'] ?? $a['duration'] ?? $node->Duration ?? $node->duration ?? '');
            if ($durationRaw === '') $durationRaw = (string)$node; // fallback
            $years = (int)preg_replace('/\D+/', '', $durationRaw);
            if ($years <= 0) $years = 1;

            // Price: YourPrice > Price (Attribute أو Child)
            $priceStr = (string)($a['YourPrice'] ?? $a['Price'] ?? '');
            if ($priceStr === '') $priceStr = (string)($node->YourPrice ?? $node->Price ?? '');
            $price = ($priceStr !== '' ? (float)$priceStr : null);

            // Currency (اختياري)
            $curr = (string)($a['Currency'] ?? $node->Currency ?? '');

            return [$years, $price, $curr ?: null];
        };

        foreach ($actions as $action) {
            foreach ($onlyTlds as $tldWanted) {
                [$xml, $err] = $fetchXml($base + [
                    'Command'     => 'namecheap.users.getPricing',
                    'ActionName'  => $action,
                    'ProductName' => strtoupper(ltrim($tldWanted, '.')), // COM, NET, ...
                ], 30);

                if (!$xml) {
                    $message .= " [{$tldWanted} {$action}: $err]";
                    // TLD-2B (D): كانت تمر بصمت — الآن مُسجَّلة كـ issue مُهيكل، بدون تغيير endpoint/auth/parsing.
                    $issues[] = ['tld' => $tldWanted, 'action' => strtolower($action), 'reason' => "transport_error: {$err}"];
                    continue;
                }
                // جهّز الـnamespace على الجذر قبل أي XPath
                $xp($xml, '.');
                $statusOk = strcasecmp((string)($xml['Status'] ?? ''), 'OK') === 0;
                if (!$statusOk) {
                    $message .= " [{$tldWanted} {$action}: provider error]";
                    $issues[] = ['tld' => $tldWanted, 'action' => strtolower($action), 'reason' => 'provider_error'];
                    continue;
                }

                // منتجات = TLDs
                $products = $xp($xml, '//nc:Product');
                if (empty($products)) {
                    // fallback لو رد بدون namespace (نادر)
                    $products = $xp($xml, '//Product');
                }
                if (empty($products)) {
                    // TLD-2B (D): "empty products" كانت تمر بصمت تمامًا — الآن مُسجَّلة كـ issue مُهيكل.
                    $issues[] = ['tld' => $tldWanted, 'action' => strtolower($action), 'reason' => 'no_products_returned'];
                    continue;
                }

                DB::transaction(function () use ($products, $action, $p, &$added, &$updated, &$tldsTouched, &$issues, $xp, $extractPricing) {
                    foreach ($products as $prod) {
                        // سجل namespace على النود نفسها قبل أي xpath عليها
                        $xp($prod, '.');

                        $nameAttr = $prod['Name'] ?? $prod['name'] ?? null;
                        $tld = ltrim(strtolower((string)$nameAttr), '.');
                        if ($tld === '') continue;

                        $tldRow = \App\Models\DomainTld::firstOrCreate(
                            ['provider_id' => $p->id, 'tld' => $tld],
                            ['provider' => $p->type, 'currency' => 'USD', 'enabled' => true, 'supports_premium' => true, 'in_catalog' => true]
                        );
                        $tldsTouched++;

                        // اجمع كل عقد التسعير المحتملة تحت المنتج
                        $nodes = $xp($prod, './/nc:DurationRange|.//nc:Price');
                        if (empty($nodes)) {
                            // fallback بدون namespace
                            $nodes = $xp($prod, './/DurationRange|.//Price');
                        }

                        $tldCurrency = null;
                        $savedAnyPrice = false; // TLD-2B (D): لتمييز "منتج بلا أي سعر صالح" كـ issue

                        foreach ($nodes as $n) {
                            [$years, $cost, $curr] = $extractPricing($n);
                            if ($years < 1 || $years > 10 || $cost === null) continue;

                            $pr = \App\Models\DomainTldPrice::firstOrNew([
                                'domain_tld_id' => $tldRow->id,
                                'action'        => strtolower($action),
                                'years'         => $years,
                            ]);
                            $ex = $pr->exists;
                            $pr->cost = $cost; // نحدّث التكلفة فقط
                            $pr->save();
                            $ex ? $updated++ : $added++;
                            $savedAnyPrice = true;

                            if (!$tldCurrency && $curr) $tldCurrency = $curr;
                        }

                        if (!$savedAnyPrice) {
                            // TLD-2B (D): "missing cost" (لا سعر صالح ضمن أي عقدة) كانت تمر بصمت — الآن issue مُهيكل.
                            $issues[] = ['tld' => $tld, 'action' => strtolower($action), 'reason' => 'no_valid_price_data'];
                        }

                        if ($tldCurrency) $tldRow->currency = $tldCurrency;
                        $tldRow->synced_at = now();
                        $tldRow->save();
                    }
                });
            }
        }

        if ($added === 0 && $updated === 0 && $message === '') {
            $message = 'لم يصل أي سعر فعلي من المزوّد لهذه القائمة رغم محاولة الاتصال — راجع بيانات الاعتماد أو الـ TLDs المطلوبة.';
        }

        return [
            'added' => $added,
            'updated' => $updated,
            'tlds' => $tldsTouched,
            'message' => $message,
            'requested' => $requested,
            'issues' => $issues,
        ];
    }

    protected function syncFromEnom(\App\Models\DomainProvider $p, array $onlyTlds = []): array
    {
        // نفس ترتيب الاختيار الموحّد المستخدم في Namecheap: صريح → in_catalog → bootstrap مشترك (TLD-1)
        $tlds = $this->resolveTldsToSync($p->id, $onlyTlds);
        $requested = count($tlds); // TLD-2B: يُعاد في التقرير لتمييزه عن عدّادات صفوف الأسعار

        $client  = app(\App\Services\Domains\Clients\EnomClient::class);
        $added = 0;
        $updated = 0;
        $tldsTouched = 0;
        $msgParts = [];
        $issues = []; // TLD-2B: structured issues [tld, action, reason]

        foreach ($tlds as $tld) {
            $tld = ltrim(strtolower($tld), '.');

            $row = \App\Models\DomainTld::firstOrCreate(
                ['provider_id' => $p->id, 'tld' => $tld],
                ['provider' => $p->type, 'currency' => 'USD', 'enabled' => true, 'supports_premium' => true, 'in_catalog' => true]
            );
            $row->synced_at = now();
            $row->save();
            $tldsTouched++;

            foreach (['register', 'renew', 'transfer'] as $act) {
                // TLD-2B (C): التقاط محلي لأي استثناء نقل (transport exception) من EnomClient — بدون أي
                // تغيير على contract الخاص بـ EnomClient (لا يُمسّ availability/provisioning). يمنع سقوط
                // sync() بخطأ 500 خام عند انقطاع الاتصال بالمزوّد، ويحوّله إلى issue مُهيكل بدل إخفائه.
                try {
                    $r = $client->getAnyPrice($p, $tld, $act, 1);
                } catch (\Throwable $e) {
                    $msgParts[] = "{$tld} {$act}: transport_exception ({$e->getMessage()})";
                    $issues[] = ['tld' => $tld, 'action' => $act, 'reason' => 'transport_exception: ' . $e->getMessage()];
                    continue;
                }

                if ($r['ok'] && $r['price'] !== null) {
                    $pr = \App\Models\DomainTldPrice::firstOrNew([
                        'domain_tld_id' => $row->id,
                        'action' => $act,
                        'years' => 1,
                    ]);
                    $ex = $pr->exists;
                    $pr->cost = (float)$r['price'];
                    $pr->save();
                    $ex ? $updated++ : $added++;

                    if (!empty($r['currency'])) {
                        $row->currency = $r['currency'];
                        $row->save();
                    }
                } else {
                    // خزّن سطر تشخيصي مفيد يظهر لك في الفلاش
                    $reason = $r['reason'] ?? ($r['source'] ?? 'unknown');
                    $m = $r['message'] ?? 'no price';
                    $msgParts[] = "{$tld} {$act}: {$reason}" . ($m ? " ({$m})" : '');
                    $issues[] = ['tld' => $tld, 'action' => $act, 'reason' => $reason . ($m ? ": {$m}" : '')];
                    // نترك السعر كما هو (قد يكون موجودًا من مزامنة سابقة)
                }
            }
        }

        $enomMessage = implode(' | ', array_slice($msgParts, 0, 8));
        if ($added === 0 && $updated === 0 && $enomMessage === '') {
            $enomMessage = 'لم يصل أي سعر فعلي من المزوّد لهذه القائمة رغم محاولة الاتصال — راجع بيانات الاعتماد أو الـ TLDs المطلوبة.';
        }

        return [
            'added' => $added,
            'updated' => $updated,
            'tlds' => $tldsTouched,
            'message' => $enomMessage,
            'requested' => $requested,
            'issues' => $issues,
        ];
    }



    public function saveCatalog(Request $req)
    {
        $this->authorize('update', DomainTld::class);
        $visible  = $req->input('visible_ids', []);            // الصفوف المعروضة في الصفحة الحالية
        $selected = array_keys($req->input('catalog', []));    // المختارة في هذه الصفحة

        if (!empty($visible)) {
            DomainTld::whereIn('id', $visible)->update(['in_catalog' => false]);
        }
        if (!empty($selected)) {
            DomainTld::whereIn('id', $selected)->update(['in_catalog' => true]);
        }

        $providerId = (int)$req->input('provider_id');
        return redirect()
            ->route('dashboard.domain_tlds.index', array_filter(['provider_id' => $providerId ?: null]))
            ->with('ok', 'تم تحديث كتالوج TLD للصفحة الحالية بنجاح.');
    }

    public function saveAll(Request $req)
    {
        $this->authorize('update', DomainTld::class);
        // Unified save: catalog selection + sale price updates for visible rows only
        $visible  = $req->input('visible_ids', []);            // current page row ids
        $selected = array_keys($req->input('catalog', []));    // checked catalog ids
        $items    = $req->input('items', []);                  // sale prices keyed by price id

        // Basic validation (lightweight, per-field).
        //
        // TLD-3A.3 (P1b) — إصلاح: Laravel's default ConvertEmptyStringsToNull middleware يحوّل أي
        // "" وارد من حقل sale إلى null قبل وصول الطلب إلى هنا. الكود السابق كان يفحص فقط
        // ($data['sale'] === '') ليقرر NULL، وهذه المقارنة لا تتحقق أبداً بعد الـmiddleware، فكان
        // يقع في الفرع الآخر وينفّذ (float) null = 0.0 خطأً بدل NULL. الإصلاح: '' و null كلاهما
        // "أُرسل فارغاً" ويُعاملان معاملة واحدة → NULL صراحة. ونفرّق كذلك بين "الحقل لم يُرسل
        // إطلاقاً" (مفتاح sale غائب من data — نتجاهل الصف بلا أي تحديث لسعر البيع الحالي) و"أُرسل
        // فارغاً" (يُخزَّن NULL). cost لا يُقرأ ولا يُكتب هنا إطلاقاً، كما كان.
        $validatedItems = [];
        foreach ($items as $priceId => $data) {
            if (!isset($data['id']) || (int)$data['id'] !== (int)$priceId) continue; // integrity check

            if (!array_key_exists('sale', $data)) continue; // لم يُرسل إطلاقاً — لا تغيير على sale الحالي

            $rawSale = $data['sale'];
            if ($rawSale !== null && $rawSale !== '' && !is_numeric($rawSale)) continue; // قيمة غير صالحة — تجاهل الصف

            $validatedItems[$priceId] = [
                'id' => (int)$data['id'],
                'sale' => ($rawSale === null || $rawSale === '') ? null : (float)$rawSale,
            ];
        }

        DB::transaction(function () use ($visible, $selected, $validatedItems) {
            if (!empty($visible)) {
                DomainTld::whereIn('id', $visible)->update(['in_catalog' => false]);
            }
            if (!empty($selected)) {
                DomainTld::whereIn('id', $selected)->update(['in_catalog' => true]);
            }

            foreach ($validatedItems as $row) {
                DomainTldPrice::where('id', $row['id'])->update(['sale' => $row['sale']]);
            }
        });

        $providerId = (int)$req->input('provider_id');
        return redirect()
            ->route('dashboard.domain_tlds.index', array_filter(['provider_id' => $providerId ?: null]))
            ->with('ok', 'تم حفظ الكتالوج وأسعار البيع لهذه الصفحة.');
    }

    public function applyPricing(Request $req)
    {
        $this->authorize('update', DomainTld::class);
        $v = $req->validate([
            'scope'            => ['required', 'in:page,provider'],
            'provider_id'      => ['nullable', 'integer', 'exists:domain_providers,id'],
            'only_in_catalog'  => ['sometimes', 'boolean'],
            'actions'          => ['required', 'array'],
            'actions.*'        => ['required', 'in:register,renew,transfer'],
            'mode'             => ['required', 'in:percent,fixed_margin,fixed_final'],
            'value'            => ['required', 'numeric', 'min:0'],
            'rounding'         => ['required', 'in:2dp,99'],
            'overwrite'        => ['sometimes', 'boolean'],
            'visible_ids'      => ['array'],
            'years'            => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [], [
            'value' => 'قيمة التسعير',
        ]);

        $scope          = $v['scope'];
        $providerId     = (int)($v['provider_id'] ?? 0);
        $onlyInCatalog  = (bool)($v['only_in_catalog'] ?? false);
        $actions        = $v['actions'];
        $mode           = $v['mode'];
        $val            = (float)$v['value'];
        $rounding       = $v['rounding'];
        $overwrite      = (bool)($v['overwrite'] ?? false);
        $years          = (int)($v['years'] ?? 1);
        $visibleIds     = array_map('intval', (array)($v['visible_ids'] ?? []));

        $q = DomainTldPrice::query()
            ->where('years', $years)
            ->whereIn('action', $actions)
            ->whereHas('tld', function ($q) use ($scope, $providerId, $onlyInCatalog, $visibleIds) {
                if ($scope === 'page') {
                    if (!empty($visibleIds)) {
                        $q->whereIn('id', $visibleIds);
                    } else {
                        $q->whereRaw('1=0');
                    }
                } else { // provider scope
                    if ($providerId) $q->where('provider_id', $providerId);
                    if ($onlyInCatalog) $q->where('in_catalog', true);
                }
            });

        $updated = 0;
        $skippedNoCost = 0;
        $skippedProtected = 0;

        $roundFn = function (float $n) use ($rounding): float {
            if ($rounding === '99') {
                if ($n < 1) return 0.99;
                return floor($n) + 0.99;
            }
            return round($n, 2);
        };

        $calcFn = function (?float $cost) use ($mode, $val): ?float {
            if ($cost === null) return null;
            return match ($mode) {
                'percent' => $cost * (1 + ($val / 100.0)),
                'fixed_margin' => $cost + $val,
                'fixed_final' => $val,
                default => null,
            };
        };

        $q->with(['tld'])->chunkById(500, function ($rows) use (&$updated, &$skippedNoCost, &$skippedProtected, $calcFn, $roundFn, $overwrite) {
            DB::transaction(function () use ($rows, &$updated, &$skippedNoCost, &$skippedProtected, $calcFn, $roundFn, $overwrite) {
                foreach ($rows as $pr) {
                    $cost = $pr->cost;
                    if ($cost === null) {
                        $skippedNoCost++;
                        continue;
                    }
                    if (!$overwrite && $pr->sale !== null) {
                        $skippedProtected++;
                        continue;
                    }

                    $sale = $calcFn((float)$cost);
                    if ($sale === null) {
                        $skippedNoCost++;
                        continue;
                    }
                    $sale = max(0.0, $roundFn((float)$sale));

                    $pr->sale = $sale;
                    $pr->save();
                    $updated++;
                }
            });
        });

        $note = "حدّثنا {$updated} | تخطّينا بدون تكلفة {$skippedNoCost}" . ($overwrite ? '' : " | محمية {$skippedProtected}");
        return back()->with('ok', "تم تطبيق التسعير تلقائيًا. {$note}");
    }

    public function destroy(DomainTld $domainTld)
    {
        $this->authorize('delete', $domainTld);
        DB::transaction(function () use ($domainTld) {
            $domainTld->prices()->delete();
            $domainTld->delete();
        });
        return back()->with('ok', 'تم حذف الـ TLD بنجاح.');
    }

    public function bulkDestroy(Request $req)
    {
        $this->authorize('delete', DomainTld::class);
        $data = $req->validate([
            'delete_ids'   => ['required', 'array'],
            'delete_ids.*' => ['integer', 'exists:domain_tlds,id'],
        ]);
        $ids = $data['delete_ids'];
        DB::transaction(function () use ($ids) {
            DomainTldPrice::whereIn('domain_tld_id', $ids)->delete();
            DomainTld::whereIn('id', $ids)->delete();
        });
        return back()->with('ok', 'تم حذف العناصر المحددة.');
    }
}
