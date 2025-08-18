<?php
namespace App\Services;

use GuzzleHttp\Client;

class ProvisioningService
{
    protected Client $http;

    public function __construct()
    {
        $baseUrl = rtrim(config('services.whm.url'), '/'); // مثال: https://server.palgoals.com:2087
        $token   = config('services.whm.token');          // مثال: CRAYI60KUOFQCOLCZ887JG51JLF6N94S

        $this->http = new Client([
            'base_uri' => $baseUrl . '/',
            'verify'   => false,  // فقط للبيئة التجريبية، غيرها true في الإنتاج
            'headers'  => [
                'Authorization' => "WHM root:{$token}",
            ],
        ]);
    }

    /**
     * تنشئ حساب استضافة جديد في WHM
     *
     * @param string $domain
     * @param string $username
     * @param string $password
     * @param string $plan
     * @return array
     */
    public function createAccount(string $domain, string $username, string $password, string $plan = '', int $quota = 0, int $bwlimit = 0, string $contactEmail = ''): array
    {
        $params = [
            'api.version' => 1,
            'username'    => $username,
            'domain'      => $domain,
            'password'    => $password,
        ];

        if ($plan !== '') {
            $params['plan'] = $plan;
        }

        if ($quota > 0) {
            $params['quota'] = $quota;
        }

        if ($bwlimit > 0) {
            $params['bwlimit'] = $bwlimit;
        }

        if ($contactEmail !== '') {
            $params['contactemail'] = $contactEmail;
        }

        $response = $this->http->get('json-api/createacct', [
            'query' => $params,
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
