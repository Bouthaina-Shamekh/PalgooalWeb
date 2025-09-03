<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;

class NamecheapClient
{
    protected DomainProvider $p;
    protected string $baseUrl;

    public function __construct(DomainProvider $p)
    {
        $this->p = $p;
        $this->baseUrl = ($p->mode === 'test')
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response';
    }

    protected function call(array $params)
    {
        $query = array_merge([
            'ApiUser'  => trim($this->p->username ?? ''),
            'ApiKey'   => trim($this->p->api_key ?? ''),
            'UserName' => trim($this->p->username ?? ''),
            'ClientIp' => trim($this->p->client_ip ?? ''), // يجب أن يكون نفس الـ IP المبيّض في لوحة Namecheap
        ], $params);

        $res = Http::timeout(20)->get($this->baseUrl, $query);
        if (!$res->ok()) {
            return ['ok' => false, 'message' => "HTTP {$res->status()}", 'raw' => $res->body()];
        }

        $xml = @simplexml_load_string($res->body());
        if (!$xml) {
            return ['ok' => false, 'message' => 'Failed to parse XML', 'raw' => $res->body()];
        }

        if (isset($xml->Errors) && $xml->Errors->count() > 0) {
            $msgs = [];
            foreach ($xml->Errors->Error as $e) $msgs[] = trim((string)$e);
            return ['ok' => false, 'message' => implode(' | ', $msgs), 'raw' => $xml];
        }

        return ['ok' => true, 'xml' => $xml];
    }

    /** اختبار سريع: جلب الرصيد */
    public function getBalance(): array
    {
        $r = $this->call(['Command' => 'namecheap.users.getBalances']);
        if (!$r['ok']) return $r;

        $res = $r['xml']->CommandResponse->UserGetBalancesResult ?? null;
        return [
            'ok'       => true,
            'message'  => 'تم الاتصال بنجاح.',
            'currency' => (string)($res['Currency'] ?? ''),
            'available' => (float)($res['AvailableBalance'] ?? 0),
            'account'  => (float)($res['AccountBalance'] ?? 0),
        ];
    }
}
