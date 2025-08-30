<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;

class EnomClient
{
    protected function endpointFor(DomainProvider $p): string
    {
        if ($p->endpoint) return $p->endpoint;
        return $p->mode === 'test'
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    public function getBalance(DomainProvider $p): array
    {
        $endpoint = $this->endpointFor($p);

        $payload = [
            'command'      => 'GetBalance',
            'UID'          => $p->username,
            'PW'           => $p->password,
            'ApiToken'     => $p->api_token,
            'ResponseType' => 'JSON',
        ];

        $response = Http::asForm()
            ->timeout(10)
            ->retry(1, 300)
            ->post($endpoint, $payload)
            ->throw();

        $data = $response->json();

        if (isset($data['ErrCount']) && (int)$data['ErrCount'] === 0) {
            return [
                'ok'      => true,
                'message' => 'تم الاتصال بنجاح.',
                'balance' => $data['Balance'] ?? null,
            ];
        }

        $errors = [];
        if (!empty($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $e) {
                $errors[] = is_string($e) ? $e : json_encode($e, JSON_UNESCAPED_UNICODE);
            }
        }

        return [
            'ok'      => false,
            'message' => $errors ? implode(' | ', $errors) : 'فشل الاتصال أو بيانات الاعتماد غير صحيحة.',
        ];
    }
}
