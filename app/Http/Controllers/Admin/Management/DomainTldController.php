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
            ->with('ok', "طھظ…طھ ط§ظ„ظ…ط²ط§ظ…ظ†ط©: ط£ط¶ظپظ†ط§ {$report['added']} ظˆط­ط¯ط«ظ†ط§ {$report['updated']} â€¢ {$report['message']}");
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

        return back()->with('ok', 'طھظ… ط­ظپط¸ ط£ط³ط¹ط§ط± ط§ظ„ط¨ظٹط¹.');
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

        // ظ…ط؛ظ„ظ‘ظپ XPath ط¢ظ…ظ†: ظٹط¶ظ…ظ† طھط³ط¬ظٹظ„ namespace 'nc' ط¹ظ„ظ‰ ظ†ظپط³ ط§ظ„ط³ظٹط§ظ‚ ظ‚ط¨ظ„ ظƒظ„ ط§ط³طھط¹ظ„ط§ظ…
        $NS = 'http://api.namecheap.com/xml.response';
        $xp = function (\SimpleXMLElement $ctx, string $expr) use ($NS): array {
            $ctx->registerXPathNamespace('nc', $NS);
            $res = @$ctx->xpath($expr);        // @ ظ„ظ…ظ†ط¹ ط§ظ„طھط­ط°ظٹط±ط§طھ
            return is_array($res) ? $res : [];
        };

        $fetchXml = function (array $params, int $timeout = 45) use ($endpoint, $headers, $options) {
            $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withOptions($options)->connectTimeout(10)->timeout($timeout)->retry(2, 400)
                ->get($endpoint, $params);

            if (!$resp->ok() || stripos((string)$resp->header('Content-Type'), 'xml') === false) {
                return [null, "HTTP {$resp->status()} ط£ظˆ ط؛ظٹط± XML"];
            }
            $xml = @simplexml_load_string((string)$resp->body(), 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOERROR | \LIBXML_NOWARNING);
            return $xml ? [$xml, null] : [null, 'XML parse error'];
        };

        // ظ„ظˆ ظ…ط§ ظ…ط±ظ‘ط±طھ ظ‚ط§ط¦ظ…ط©طŒ ط§ط³طھط®ط¯ظ… ظ…ط§ ظ‡ظˆ ظ…ط¹ظ„ظ‘ظ… in_catalog
        if (empty($onlyTlds)) {
            $onlyTlds = \App\Models\DomainTld::where('provider_id', $p->id)
                ->where('in_catalog', true)->pluck('tld')->all();
        }
        if (empty($onlyTlds)) {
            return ['added' => 0, 'updated' => 0, 'tlds' => 0, 'message' => 'ظ„ط§ طھظˆط¬ط¯ TLDs ظ…ط®طھط§ط±ط© (in_catalog).'];
        }

        // ط£ط¯ط§ط© ط§ط³طھط®ط±ط§ط¬ (years, price, currency) ظ…ظ† ط£ظٹ ط¹ظ‚ط¯ط© طھط³ط¹ظٹط±
        $extractPricing = function (\SimpleXMLElement $node): array {
            $a = $node->attributes();

            // Years
            $durationRaw = (string)($a['Duration'] ?? $a['duration'] ?? $node->Duration ?? $node->duration ?? '');
            if ($durationRaw === '') $durationRaw = (string)$node; // fallback
            $years = (int)preg_replace('/\D+/', '', $durationRaw);
            if ($years <= 0) $years = 1;

            // Price: YourPrice > Price (Attribute ط£ظˆ Child)
            $priceStr = (string)($a['YourPrice'] ?? $a['Price'] ?? '');
            if ($priceStr === '') $priceStr = (string)($node->YourPrice ?? $node->Price ?? '');
            $price = ($priceStr !== '' ? (float)$priceStr : null);

            // Currency (ط§ط®طھظٹط§ط±ظٹ)
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
                // ط¬ظ‡ظ‘ط² ط§ظ„ظ€namespace ط¹ظ„ظ‰ ط§ظ„ط¬ط°ط± ظ‚ط¨ظ„ ط£ظٹ XPath
                $xp($xml, '.');
                $statusOk = strcasecmp((string)($xml['Status'] ?? ''), 'OK') === 0;
                if (!$statusOk) {
                    $message .= " [{$tldWanted} {$action}: provider error]";
                    continue;
                }

                // ظ…ظ†طھط¬ط§طھ = TLDs
                $products = $xp($xml, '//nc:Product');
                if (empty($products)) {
                    // fallback ظ„ظˆ ط±ط¯ ط¨ط¯ظˆظ† namespace (ظ†ط§ط¯ط±)
                    $products = $xp($xml, '//Product');
                }
                if (empty($products)) continue;

                DB::transaction(function () use ($products, $action, $p, &$added, &$updated, &$tldsTouched, $xp, $extractPricing) {
                    foreach ($products as $prod) {
                        // ط³ط¬ظ„ namespace ط¹ظ„ظ‰ ط§ظ„ظ†ظˆط¯ ظ†ظپط³ظ‡ط§ ظ‚ط¨ظ„ ط£ظٹ xpath ط¹ظ„ظٹظ‡ط§
                        $xp($prod, '.');

                        $nameAttr = $prod['Name'] ?? $prod['name'] ?? null;
                        $tld = ltrim(strtolower((string)$nameAttr), '.');
                        if ($tld === '') continue;

                        $tldRow = \App\Models\DomainTld::firstOrCreate(
                            ['provider_id' => $p->id, 'tld' => $tld],
                            ['provider' => $p->type, 'currency' => 'USD', 'enabled' => true, 'supports_premium' => true, 'in_catalog' => true]
                        );
                        $tldsTouched++;

                        // ط§ط¬ظ…ط¹ ظƒظ„ ط¹ظ‚ط¯ ط§ظ„طھط³ط¹ظٹط± ط§ظ„ظ…ط­طھظ…ظ„ط© طھط­طھ ط§ظ„ظ…ظ†طھط¬
                        $nodes = $xp($prod, './/nc:DurationRange|.//nc:Price');
                        if (empty($nodes)) {
                            // fallback ط¨ط¯ظˆظ† namespace
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
                            $pr->cost = $cost; // ظ†ط­ط¯ظ‘ط« ط§ظ„طھظƒظ„ظپط© ظپظ‚ط·
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
        // ط§ط®طھط± ط§ظ„ظ€TLDs ظ…ظ† ط§ظ„ظƒطھط§ظ„ظˆط¬ ط£ظˆ ط«ط¨ظ‘طھ ظ‚ط§ط¦ظ…ط© طµط؛ظٹط±ط© ظƒط¨ط¯ط§ظٹط©
        $tlds = \App\Models\DomainTld::where('provider_id', $p->id)
            ->where('in_catalog', true)
            ->pluck('tld')->all();

        if (empty($tlds)) {
            $tlds = ['com', 'net', 'org', 'shop', 'xyz', 'live', 'news', 'rocks', 'ninja'];
        }

        $client  = app(\App\Services\Domains\Clients\EnomClient::class);
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
                    // ط®ط²ظ‘ظ† ط³ط·ط± طھط´ط®ظٹطµظٹ ظ…ظپظٹط¯ ظٹط¸ظ‡ط± ظ„ظƒ ظپظٹ ط§ظ„ظپظ„ط§ط´
                    $reason = $r['reason'] ?? ($r['source'] ?? 'unknown');
                    $m = $r['message'] ?? 'no price';
                    $msgParts[] = "{$tld} {$act}: {$reason}" . ($m ? " ({$m})" : '');
                    // ظ†طھط±ظƒ ط§ظ„ط³ط¹ط± ظƒظ…ط§ ظ‡ظˆ (ظ‚ط¯ ظٹظƒظˆظ† ظ…ظˆط¬ظˆط¯ظ‹ط§ ظ…ظ† ظ…ط²ط§ظ…ظ†ط© ط³ط§ط¨ظ‚ط©)
                }
            }
        }

        return ['added' => $added, 'updated' => $updated, 'tlds' => $tldsTouched, 'message' => implode(' | ', array_slice($msgParts, 0, 8))];
    }



    public function saveCatalog(Request $req)
    {
        $visible  = $req->input('visible_ids', []);            // ط§ظ„طµظپظˆظپ ط§ظ„ظ…ط¹ط±ظˆط¶ط© ظپظٹ ط§ظ„طµظپط­ط© ط§ظ„ط­ط§ظ„ظٹط©
        $selected = array_keys($req->input('catalog', []));    // ط§ظ„ظ…ط®طھط§ط±ط© ظپظٹ ظ‡ط°ظ‡ ط§ظ„طµظپط­ط©

        if (!empty($visible)) {
            DomainTld::whereIn('id', $visible)->update(['in_catalog' => false]);
        }
        if (!empty($selected)) {
            DomainTld::whereIn('id', $selected)->update(['in_catalog' => true]);
        }

        $providerId = (int)$req->input('provider_id');
        return redirect()
            ->route('dashboard.domain_tlds.index', array_filter(['provider_id' => $providerId ?: null]))
            ->with('ok', 'طھظ… طھط­ط¯ظٹط« ظƒطھط§ظ„ظˆط¬ TLD ظ„ظ„طµظپط­ط© ط§ظ„ط­ط§ظ„ظٹط© ط¨ظ†ط¬ط§ط­.');
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
            ->with('ok', 'طھظ… ط­ظپط¸ ط§ظ„ظƒطھط§ظ„ظˆط¬ ظˆط£ط³ط¹ط§ط± ط§ظ„ط¨ظٹط¹ ظ„ظ‡ط°ظ‡ ط§ظ„طµظپط­ط©.');
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
            'value' => 'ظ‚ظٹظ…ط© ط§ظ„طھط³ط¹ظٹط±',
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

        $note = "ط­ط¯ظ‘ط«ظ†ط§ {$updated} | طھط®ط·ظ‘ظٹظ†ط§ ط¨ط¯ظˆظ† طھظƒظ„ظپط© {$skippedNoCost}" . ($overwrite ? '' : " | ظ…ط­ظ…ظٹط© {$skippedProtected}");
        return back()->with('ok', "طھظ… طھط·ط¨ظٹظ‚ ط§ظ„طھط³ط¹ظٹط± طھظ„ظ‚ط§ط¦ظٹظ‹ط§. {$note}");
    }

    public function destroy(DomainTld $domainTld)
    {
        DB::transaction(function () use ($domainTld) {
            $domainTld->prices()->delete();
            $domainTld->delete();
        });
        return back()->with('ok', 'طھظ… ط­ط°ظپ ط§ظ„ظ€ TLD ط¨ظ†ط¬ط§ط­.');
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
        return back()->with('ok', 'طھظ… ط­ط°ظپ ط§ظ„ط¹ظ†ط§طµط± ط§ظ„ظ…ط­ط¯ط¯ط©.');
    }
}


