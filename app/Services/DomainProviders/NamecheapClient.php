<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class NamecheapClient
{
    protected DomainProvider $p;

    public function __construct(DomainProvider $p)
    {
        $this->p = $p;
    }

    /** حدد الـ endpoint بدقة (يحترم قيمة endpoint من الـ DB وإلا يعتمد على mode صريح) */
    protected function endpoint(): string
    {
        if (!empty($this->p->endpoint)) {
            return rtrim($this->p->endpoint);
        }

        $mode = strtolower((string) $this->p->mode);
        if ($mode === 'test') return 'https://api.sandbox.namecheap.com/xml.response';
        if ($mode === 'live') return 'https://api.namecheap.com/xml.response';

        throw new \RuntimeException('وضع المزود (mode) غير محدد. عيّنه إلى test أو live أو حدّد endpoint.');
    }

    /** فك التشفير إن لزم (يدعم حالتي التخزين: نص صريح أو encrypted string) */
    protected function readApiKey(): string
    {
        $raw = (string) $this->p->api_key;
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw; // ليست مُشفّرة
        }
    }

    /** تحضير البارامترات الأساسية مع فحص صارم للـ client_ip */
    protected function baseParams(): array
    {
        $ip = $this->p->client_ip;
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \RuntimeException('Client IP مفقود/غير صالح في سجل المزود.');
        }

        return [
            'ApiUser'  => trim((string) $this->p->username),
            'ApiKey'   => trim((string) $this->readApiKey()),
            'UserName' => trim((string) $this->p->username),
            'ClientIp' => trim((string) $ip),
        ];
    }

    /** طلب داخلي موحّد: يعيد XML أو سبب الفشل مع reason/status/snippet للتشخيص */
    protected function request(array $params): array
    {
        $endpoint = $this->endpoint();
        $query    = array_merge($this->baseParams(), $params);

        // لوج تمهيدي آمن
        Log::info('Namecheap preflight', [
            'endpoint'  => $endpoint,
            'mode'      => $this->p->mode,
            'username'  => $this->p->username,
            'client_ip' => $this->p->client_ip,
            'api_key_len' => strlen($this->readApiKey() ?? ''),
        ]);

        $resp = Http::withHeaders([
            'Accept'     => 'application/xml',
            'User-Agent' => 'PalgoalsBot/1.0',
        ])
            ->withOptions([
                'curl' => [
                    \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4, // إجبار IPv4
                ],
            ])
            ->timeout(20)
            ->get($endpoint, $query);

        $status = $resp->status();
        $ct     = $resp->header('Content-Type');
        $body   = (string) $resp->body();

        if (!$resp->ok()) {
            Log::warning('Namecheap HTTP error', [
                'status' => $status,
                'ct'     => $ct,
                'snippet' => mb_substr($body, 0, 400),
            ]);
            return [
                'ok'        => false,
                'reason'    => 'http_error',
                'http_code' => $status,
                'message'   => "فشل HTTP ($status). تحقق من Whitelist IP والـ endpoint.",
            ];
        }

        if (!is_string($ct) || stripos($ct, 'xml') === false) {
            Log::warning('Namecheap non-XML response', [
                'status' => $status,
                'ct'     => $ct,
                'snippet' => mb_substr($body, 0, 400),
            ]);
            return [
                'ok'      => false,
                'reason'  => 'non_xml',
                'message' => 'الاستجابة ليست XML (غالبًا HTML/Proxy أو IP غير مُصرّح).',
            ];
        }

        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $xml  = @simplexml_load_string($body, 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
        if ($xml === false) {
            Log::warning('Namecheap XML parse failed', [
                'status'  => $status,
                'ct'      => $ct,
                'snippet' => mb_substr($body, 0, 400),
            ]);
            return [
                'ok'      => false,
                'reason'  => 'xml_parse_error',
                'message' => 'رد غير صالح (تعذر تحليل XML).',
            ];
        }

        // namespace
        $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');

        // فحص Status="OK"
        $statusAttr = (string)($xml['Status'] ?? '');
        if (strcasecmp($statusAttr, 'OK') !== 0) {
            $errNode = $xml->xpath('//nc:Errors/nc:Error')[0] ?? null;
            $errMsg  = $errNode ? (string)$errNode : 'تعذّر تنفيذ الطلب.';
            return [
                'ok'      => false,
                'reason'  => 'provider_error',
                'message' => $errMsg,
                'xml'     => $xml,
            ];
        }

        return ['ok' => true, 'xml' => $xml];
    }

    /** جلب الرصيد */
    public function getBalance(): array
    {
        try {
            $r = $this->request(['Command' => 'namecheap.users.getBalances']);
            if (!$r['ok']) return array_merge($r, ['balance' => null, 'currency' => null]);

            /** @var \SimpleXMLElement $xml */
            $xml = $r['xml'];
            $node = $xml->xpath('//nc:UserGetBalancesResult')[0] ?? null;
            if (!$node) {
                return [
                    'ok'       => false,
                    'reason'   => 'missing_result_node',
                    'message'  => 'لم نجد UserGetBalancesResult في الرد.',
                    'balance'  => null,
                    'currency' => null,
                ];
            }

            $attrs = $node->attributes();
            return [
                'ok'        => true,
                'reason'    => 'ok',
                'message'   => 'تم الاتصال بنجاح.',
                'balance'   => isset($attrs->AccountBalance) ? (float)$attrs->AccountBalance : null,
                'available' => isset($attrs->AvailableBalance) ? (float)$attrs->AvailableBalance : null,
                'currency'  => isset($attrs->Currency) ? (string)$attrs->Currency : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Namecheap getBalance exception', ['error' => $e->getMessage()]);
            return [
                'ok'      => false,
                'reason'  => 'exception',
                'message' => 'استثناء أثناء طلب الرصيد: ' . $e->getMessage(),
                'balance' => null,
                'currency' => null,
            ];
        }
    }

    /** مثال لأمر عام (يمكنك استخدامه بدل الدالة call القديمة) */
    public function callGeneric(string $command, array $extraParams = []): array
    {
        try {
            $r = $this->request(array_merge(['Command' => $command], $extraParams));
            if (!$r['ok']) return $r;
            return ['ok' => true, 'xml' => $r['xml']];
        } catch (\Throwable $e) {
            Log::error('Namecheap callGeneric exception', ['command' => $command, 'error' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }
}
