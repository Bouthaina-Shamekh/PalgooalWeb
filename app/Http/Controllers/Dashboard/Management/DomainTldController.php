<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DomainTldController extends Controller
{
    public function index(Request $req)
    {
        $providerId = (int) $req->query('provider_id', 0);
        $providers  = DomainProvider::active()->whereIn('type', ['namecheap', 'enom'])->get();

        $q = DomainTld::query()->with(['prices' => function ($q) {
            $q->whereIn('action', ['register', 'renew', 'transfer'])->where('years', 1);
        }])->orderBy('tld');

        if ($providerId) $q->where('provider_id', $providerId);

        $rows = $q->paginate(50);

        return view('dashboard.management.domain_tlds.index', compact('rows', 'providers', 'providerId'));
    }

    public function sync(Request $req)
    {
        $provider = DomainProvider::active()
            ->where('id', (int) $req->input('provider_id'))
            ->whereIn('type', ['namecheap', 'enom'])
            ->firstOrFail();

        $onlyTlds = collect(explode(',', (string)$req->input('tlds', '')))
            ->map(fn($s) => strtolower(trim(ltrim($s, '.'))))
            ->filter()->unique()->values()->all();

        $report = ($provider->type === 'namecheap')
            ? $this->syncFromNamecheap($provider, $onlyTlds)
            : $this->syncFromEnom($provider);

        return redirect()
            ->route('dashboard.domain_tlds.index', ['provider_id' => $provider->id])
            ->with('ok', "تمت المزامنة: أضفنا {$report['added']} وحدثنا {$report['updated']} • {$report['message']}");
    }

    public function updateSale(Request $req)
    {
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

        // لو ما مرّرت قائمة، استخدم ما هو معلّم in_catalog
        if (empty($onlyTlds)) {
            $onlyTlds = \App\Models\DomainTld::where('provider_id', $p->id)
                ->where('in_catalog', true)->pluck('tld')->all();
        }
        if (empty($onlyTlds)) {
            return ['added' => 0, 'updated' => 0, 'tlds' => 0, 'message' => 'لا توجد TLDs مختارة (in_catalog).'];
        }

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
                    continue;
                }
                // جهّز الـnamespace على الجذر قبل أي XPath
                $xp($xml, '.');
                $statusOk = strcasecmp((string)($xml['Status'] ?? ''), 'OK') === 0;
                if (!$statusOk) {
                    $message .= " [{$tldWanted} {$action}: provider error]";
                    continue;
                }

                // منتجات = TLDs
                $products = $xp($xml, '//nc:Product');
                if (empty($products)) {
                    // fallback لو رد بدون namespace (نادر)
                    $products = $xp($xml, '//Product');
                }
                if (empty($products)) continue;

                DB::transaction(function () use ($products, $action, $p, &$added, &$updated, &$tldsTouched, $xp, $extractPricing) {
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

                            if (!$tldCurrency && $curr) $tldCurrency = $curr;
                        }

                        if ($tldCurrency) $tldRow->currency = $tldCurrency;
                        $tldRow->synced_at = now();
                        $tldRow->save();
                    }
                });
            }
        }

        return ['added' => $added, 'updated' => $updated, 'tlds' => $tldsTouched, 'message' => $message];
    }

    protected function syncFromEnom(\App\Models\DomainProvider $p): array
    {
        // اختر الـTLDs من الكتالوج أو ثبّت قائمة صغيرة كبداية
        $tlds = \App\Models\DomainTld::where('provider_id', $p->id)
            ->where('in_catalog', true)
            ->pluck('tld')->all();

        if (empty($tlds)) {
            $tlds = ['com', 'net', 'org', 'shop', 'xyz', 'live', 'news', 'rocks', 'ninja'];
        }

        $client  = app(\App\Services\DomainProviders\EnomClient::class);
        $added = 0;
        $updated = 0;
        $tldsTouched = 0;
        $msgParts = [];

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
                $r = $client->getAnyPrice($p, $tld, $act, 1);

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
                    // نترك السعر كما هو (قد يكون موجودًا من مزامنة سابقة)
                }
            }
        }

        return ['added' => $added, 'updated' => $updated, 'tlds' => $tldsTouched, 'message' => implode(' | ', array_slice($msgParts, 0, 8))];
    }



    public function saveCatalog(Request $req)
    {
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
        // Unified save: catalog selection + sale price updates for visible rows only
        $visible  = $req->input('visible_ids', []);            // current page row ids
        $selected = array_keys($req->input('catalog', []));    // checked catalog ids
        $items    = $req->input('items', []);                  // sale prices keyed by price id

        // Basic validation (lightweight, per-field).
        $validatedItems = [];
        foreach ($items as $priceId => $data) {
            if (!isset($data['id']) || (int)$data['id'] !== (int)$priceId) continue; // integrity check
            if (isset($data['sale']) && $data['sale'] !== '' && !is_numeric($data['sale'])) continue; // skip invalid
            $validatedItems[$priceId] = [
                'id' => (int)$data['id'],
                'sale' => $data['sale'] === '' ? null : (float)$data['sale'],
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
        DB::transaction(function () use ($domainTld) {
            $domainTld->prices()->delete();
            $domainTld->delete();
        });
        return back()->with('ok', 'تم حذف الـ TLD بنجاح.');
    }

    public function bulkDestroy(Request $req)
    {
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
