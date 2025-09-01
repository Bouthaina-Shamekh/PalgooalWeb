<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

class EnomClient
{
    protected function endpointFor(DomainProvider $p): string
    {
        return $p->mode === 'test'
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    protected function baseParams(DomainProvider $p): array
    {
        // ملاحظة: eNom التقليدي يعتمد UID/PW. بعض الحسابات تدعم ApiToken مع ApiUser.
        // إن كنت تستخدم ApiToken، غيّر إلى ['ApiUser' => ..., 'ApiToken' => ...]
        return [
            'UID'          => $p->username,  // test uid أو live uid
            'PW'           => $p->password,  // test pw أو live pw
            'ResponseType' => 'XML',
        ];
    }

    protected function getXml(string $body): SimpleXMLElement
    {
        // eNom يعيد XML منسّقًا يتضمن RRPCode/RRPText وأخطاء ErrCount
        return new SimpleXMLElement($body);
    }

    protected function request(DomainProvider $p, array $params): SimpleXMLElement
    {
        $endpoint = $this->endpointFor($p);

        $res = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Palgoals/1.0'])
            ->get($endpoint, $params);

        if (!$res->ok()) {
            throw new \RuntimeException("HTTP error: {$res->status()} - {$res->body()}");
        }

        $xml = $this->getXml($res->body());

        // فحص أخطاء eNom القياسية
        if (isset($xml->ErrCount) && (int)$xml->ErrCount > 0) {
            // اجمع الرسائل إن وجدت
            $errors = [];
            foreach ($xml->errors->Err1 ?? [] as $e) {
                $errors[] = (string) $e;
            }
            // أحيانًا تكون بصيغة ResponseString1...
            if (isset($xml->responses->response->ResponseString)) {
                $errors[] = (string) $xml->responses->response->ResponseString;
            }
            $msg = $errors ? implode(' | ', $errors) : 'Unknown eNom API error';
            throw new \RuntimeException("eNom API error: {$msg}");
        }

        return $xml;
    }

    public function getBalance(DomainProvider $p): array
    {
        $params = array_merge($this->baseParams($p), [
            'command' => 'GetBalance',
        ]);

        try {
            $xml = $this->request($p, $params);

            return [
                'available' => isset($xml->GetBalance->AvailableBalance) ? (float)$xml->GetBalance->AvailableBalance : null,
                'hold'      => isset($xml->GetBalance->HoldBalance) ? (float)$xml->GetBalance->HoldBalance : null,
                'raw'       => $xml,
            ];
        } catch (Throwable $e) {
            // سجّل للتشخيص
            logger()->error('Enom GetBalance failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function checkAvailability(DomainProvider $p, string $sld, string $tld): array
    {
        $params = array_merge($this->baseParams($p), [
            'command' => 'check',
            'SLD'     => $sld,
            'TLD'     => strtoupper($tld),
        ]);

        $xml = $this->request($p, $params);

        return [
            'rrp_code' => isset($xml->RRPCode) ? (int)$xml->RRPCode : null,
            'rrp_text' => isset($xml->RRPText) ? (string)$xml->RRPText : null,
            'available'=> isset($xml->DomainName) && isset($xml->RRPCode) ? ((int)$xml->RRPCode === 210) : null, // 210 عادة متاح
            'raw'      => $xml,
        ];
    }
}
