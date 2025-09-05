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

    protected function syncFromNamecheap(DomainProvider $p, array $onlyTlds = []): array
    {
        $headers = [
            'Accept'          => 'application/xml',
            'Accept-Encoding' => 'gzip',
            'User-Agent'      => 'PalgoalsBot/1.0',
        ];
        $options = [
            'curl' => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4, \CURLOPT_ENCODING => ''],
            'http_errors' => false,
        ];
        $endpoint = $p->endpoint ?: ($p->mode === 'test'
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response');

        $base = [
            'ApiUser'  => trim((string)$p->username),
            'ApiKey'   => trim((string)$p->api_key),
            'UserName' => trim((string)$p->username),
            'ClientIp' => trim((string)$p->client_ip),
            'ProductType' => 'DOMAIN',
        ];

        $actions = ['REGISTER', 'RENEW', 'TRANSFER'];
        $added = 0;
        $updated = 0;
        $tldsTouched = 0;
        $message = '';

        $fetchXml = function (array $params, int $timeout = 45) use ($endpoint, $headers, $options) {
            $resp = Http::withHeaders($headers)
                ->withOptions($options)->connectTimeout(10)->timeout($timeout)->retry(2, 400)
                ->get($endpoint, $params);
            if (!$resp->ok() || stripos((string)$resp->header('Content-Type'), 'xml') === false) {
                return [null, "HTTP {$resp->status()} أو غير XML"];
            }
            $xml = @simplexml_load_string((string)$resp->body(), 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
            return $xml ? [$xml, null] : [null, 'XML parse error'];
        };

        if (empty($onlyTlds)) {
            $onlyTlds = DomainTld::where('provider_id', $p->id)
                ->where('in_catalog', true)->pluck('tld')->all();
        }
        if (empty($onlyTlds)) {
            return ['added' => 0, 'updated' => 0, 'tlds' => 0, 'message' => 'لا توجد TLDs مختارة (in_catalog).'];
        }

        foreach ($actions as $action) {
            foreach ($onlyTlds as $tld) {
                [$xml, $err] = $fetchXml($base + [
                    'Command'     => 'namecheap.users.getPricing',
                    'ActionName'  => $action,
                    'ProductName' => strtoupper(ltrim($tld, '.')),
                ], 30);

                if (!$xml) {
                    $message .= " [{$tld} {$action}: $err]";
                    continue;
                }
                if (strcasecmp((string)($xml['Status'] ?? ''), 'OK') !== 0) {
                    $message .= " [{$tld} {$action}: provider error]";
                    continue;
                }

                $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');
                $products = $xml->xpath('//nc:Product') ?? [];

                DB::transaction(function () use ($products, $action, $p, &$added, &$updated, &$tldsTouched) {
                    foreach ($products as $prod) {
                        $attrs = $prod->attributes();
                        $name  = (string)($attrs['Name'] ?? $attrs['name'] ?? '');
                        $tld   = ltrim(strtolower($name), '.');
                        if ($tld === '') continue;

                        $tldRow = DomainTld::firstOrCreate(
                            ['provider_id' => $p->id, 'tld' => $tld],
                            ['provider' => $p->type, 'currency' => 'USD', 'enabled' => true, 'supports_premium' => true, 'in_catalog' => true]
                        );
                        $tldsTouched++;

                        foreach ($prod->DurationRange ?? [] as $rng) {
                            $a = $rng->attributes();
                            $years = (int) ($a['Duration'] ?? 1);
                            if ($years < 1 || $years > 10) continue;

                            $price = (string) ($a['Price'] ?? $a['price'] ?? '');
                            $cost  = $price !== '' ? (float) $price : null;

                            $pr = DomainTldPrice::firstOrNew([
                                'domain_tld_id' => $tldRow->id,
                                'action' => strtolower($action),
                                'years'  => $years,
                            ]);
                            $ex = $pr->exists;
                            $pr->cost = $cost;
                            $pr->save();
                            $ex ? $updated++ : $added++;
                        }

                        $tldRow->synced_at = now();
                        $tldRow->currency  = 'USD';
                        $tldRow->save();
                    }
                });
            }
        }

        return ['added' => $added, 'updated' => $updated, 'tlds' => $tldsTouched, 'message' => $message];
    }

    protected function syncFromEnom(DomainProvider $p): array
    {
        $tlds = ['com', 'net', 'org', 'shop', 'xyz', 'live', 'news', 'rocks', 'ninja'];

        $added = 0;
        $updated = 0;
        DB::transaction(function () use ($tlds, $p, &$added, &$updated) {
            foreach ($tlds as $tld) {
                $row = DomainTld::firstOrCreate(
                    ['provider_id' => $p->id, 'tld' => $tld],
                    ['provider' => $p->type, 'currency' => 'USD', 'enabled' => true, 'supports_premium' => false]
                );
                $row->synced_at = now();
                $row->save();

                foreach (['register', 'renew', 'transfer'] as $act) {
                    $pr = DomainTldPrice::firstOrNew([
                        'domain_tld_id' => $row->id,
                        'action' => $act,
                        'years' => 1
                    ]);
                    $ex = $pr->exists;
                    $pr->cost = $pr->cost ?? null;
                    $pr->save();
                    $ex ? $updated++ : $added++;
                }
            }
        });

        return ['added' => $added, 'updated' => $updated, 'tlds' => count($tlds), 'message' => '(Enom fallback)'];
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
}
