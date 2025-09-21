<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class SubscriptionSyncService
{
    /**
     * Sync a subscription to its provider and return human message
     * @param Subscription $subscription
     * @return string
     */
    public function sync(Subscription $subscription): string
    {
        $server = $subscription->server;
        if (!$server) {
            return 'لا يوجد سيرفر مرتبط بهذا الاشتراك.';
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $result = null;
        if ($host && $username && $apiToken) {
            // Prefer server_package (actual package name on the provisioning server)
            $planPackage = null;
            try {
                $planPackage = $subscription->plan->server_package ?? null;
            } catch (\Exception $e) {
                $planPackage = null;
            }

            $params = [
                'username' => $subscription->username,
                'domain' => $subscription->domain_name,
                // use server_package when available; fall back to slug or plan name
                'plan' => $planPackage ? (string)$planPackage : ($subscription->plan->slug ?? $subscription->plan->name),
                'contactemail' => $subscription->client->email ?? '',
                'password' => $subscription->password ?? 'TempPass!123',
            ];
            $apiUrl = "https://{$host}:{$port}/json-api/createacct?api.version=1&" . http_build_query($params);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken,
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم إنشاء الحساب بنجاح على المزود.';
                    } else {
                        $error = ($data['metadata']['reason'] ?? $data['reason'] ?? 'فشل إنشاء الحساب.') . '\n' . print_r($data, true);
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        $message = $result ?: $error;
        Log::info('Subscription sync result for subscription ' . $subscription->id . ': ' . $message);
        return $message;
    }

    /**
     * Terminate subscription on provider (remove account)
     * returns human message
     */
    public function terminate(Subscription $subscription): string
    {
        $server = $subscription->server;
        if (!$server) {
            return 'لا يوجد سيرفر مرتبط بهذا الاشتراك.';
        }
        $host = (!empty($server->hostname) && trim($server->hostname) !== '') ? $server->hostname : $server->ip;
        $port = 2087;
        $username = $server->username;
        $apiToken = $server->api_token;
        $error = null;
        $result = null;
        if ($host && $username && $apiToken) {
            $params = [
                'user' => $subscription->username,
            ];
            $apiUrl = "https://{$host}:{$port}/json-api/removeacct?api.version=1&" . http_build_query($params);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $header = [
                    'Authorization: whm ' . $username . ':' . $apiToken
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم حذف الموقع (Terminate) بنجاح من السيرفر.';
                        try {
                            $subscription->update(['status' => 'cancelled']);
                        } catch (\Exception $e) {
                            // ignore
                        }
                    } else {
                        $error = ($data['metadata']['reason'] ?? $data['reason'] ?? 'فشل حذف الموقع.') . '\n' . print_r($data, true);
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        $message = $result ?: $error;
        Log::info('Subscription terminate result for subscription ' . $subscription->id . ': ' . $message);
        return $message;
    }
}
