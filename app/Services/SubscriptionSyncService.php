<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionSyncService
{
    /**
     * Sync a subscription to its provider and return human message
     * @param Subscription $subscription
     * @return string
     */
    public function sync(Subscription $subscription, bool $dryRun = false): string
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
            // Determine the package to send to WHM. Prefer subscription.server_package (persisted),
            // then plan.server_package. Do NOT fall back to slug automatically — we require an explicit
            // server package to avoid accidental mismatches.
            $package = null;
            try {
                $package = $subscription->server_package ?? $subscription->plan->server_package ?? null;
            } catch (\Exception $e) {
                $package = null;
            }

            if (empty($package)) {
                $error = 'لا توجد قيمة "server_package" مهيأة للخطة أو الاشتراك. الرجاء تعيين حزمة السيرفر على الخطة قبل محاولة المزامنة.';
                Log::warning('Subscription sync aborted - missing server_package for subscription ' . $subscription->id);
                return $error;
            }
            Log::debug('Subscription ' . $subscription->id . ' will use server_package: ' . $package);

            // Build base params (username will be set per attempt)
            $baseParams = [
                'domain' => $subscription->domain_name,
                'plan' => (string)$package,
                'contactemail' => $subscription->client->email ?? '',
                'password' => $subscription->password ?? 'TempPass!123',
            ];

            // If dry run requested, show the URL/params for the first candidate only
            if ($dryRun) {
                $firstUsername = $subscription->username ?? $this->generateDefaultUsername($subscription);
                $firstUsername = $this->sanitizeUsername($firstUsername);
                $params = array_merge(['username' => $firstUsername], $baseParams);
                $apiUrl = "https://{$host}:{$port}/json-api/createacct?api.version=1&" . http_build_query($params);
                $info = 'DRY RUN - createacct URL: ' . $apiUrl . "\n" . 'params: ' . json_encode($params, JSON_UNESCAPED_UNICODE);
                Log::info('Subscription dry-run sync for subscription ' . $subscription->id . ': ' . $info);
                return $info;
            }

            // Prepare username candidates and attempt createacct up to N times
            $candidates = $this->generateUsernameCandidates($subscription, 5);
            Log::debug('Subscription ' . $subscription->id . ' username candidates: ' . json_encode($candidates, JSON_UNESCAPED_UNICODE));
            $attempt = 0;
            $success = false;
            $lastResponse = null;
            $header = [
                'Authorization: whm ' . $username . ':' . $apiToken,
            ];

            foreach ($candidates as $candidate) {
                $attempt++;
                $candidate = $this->sanitizeUsername($candidate);
                $params = array_merge(['username' => $candidate], $baseParams);
                $apiUrl = "https://{$host}:{$port}/json-api/createacct?api.version=1&" . http_build_query($params);
                Log::info("Subscription sync attempt {$attempt} for subscription {$subscription->id} - username={$candidate}");
                Log::debug('Createacct request for subscription ' . $subscription->id . ' attempt ' . $attempt . ': url=' . $apiUrl . ' params=' . json_encode($params, JSON_UNESCAPED_UNICODE) . ' headers=' . json_encode($header));
                try {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    $response = curl_exec($ch);
                    if (curl_errno($ch)) {
                        $error = 'curl error: ' . curl_error($ch);
                        Log::warning('Curl error during createacct for subscription ' . $subscription->id . ': ' . $error);
                        $lastResponse = $error;
                        curl_close($ch);
                        continue; // try next candidate
                    }
                    curl_close($ch);
                    $lastResponse = $response;
                    Log::debug('Raw WHM response for subscription ' . $subscription->id . ' attempt ' . $attempt . ': ' . (is_string($response) ? $response : print_r($response, true)));
                    $data = json_decode($response, true);
                    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                        Log::debug('WHM response for subscription ' . $subscription->id . ' attempt ' . $attempt . ' is not valid JSON: ' . json_last_error_msg());
                    }
                    if (isset($data['metadata']['result']) && $data['metadata']['result'] == 1) {
                        $result = 'تم إنشاء الحساب بنجاح على المزود.';
                        $success = true;
                        // update subscription username if we used a different candidate
                        try {
                            if ($subscription->username !== $candidate) {
                                $subscription->update(['username' => $candidate]);
                                Log::info('Subscription ' . $subscription->id . ' username updated to ' . $candidate . ' after successful provisioning.');
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to persist updated username for subscription ' . $subscription->id . ': ' . $e->getMessage());
                        }
                        Log::debug('WHM createacct success metadata for subscription ' . $subscription->id . ': ' . json_encode($data['metadata'], JSON_UNESCAPED_UNICODE));
                        break;
                    } else {
                        // log reason and try next candidate if available
                        $msg = $data['metadata']['reason'] ?? $data['reason'] ?? 'فشل إنشاء الحساب.';
                        $raw = is_string($response) ? $response : print_r($response, true);
                        $error = $msg . "\nResponse: " . $raw;
                        Log::warning('WHM createacct attempt ' . $attempt . ' failed for subscription ' . $subscription->id . ': ' . $error);
                        Log::debug('Parsed WHM response for subscription ' . $subscription->id . ' attempt ' . $attempt . ': ' . print_r($data, true));
                        // if the reason explicitly says reserved username, try next candidate
                        // else continue to next candidate as well (to be robust)
                        continue;
                    }
                } catch (\Exception $e) {
                    $lastResponse = $e->getMessage();
                    Log::error('Exception during createacct for subscription ' . $subscription->id . ' attempt ' . $attempt . ': ' . $e->getMessage());
                    continue;
                }
            }

            if (!$success) {
                $error = 'فشل إنشاء الحساب بعد محاولات متعددة. آخر استجابة: ' . ($lastResponse ?? 'لا توجد استجابة');
                Log::error('WHM createacct failed for subscription ' . $subscription->id . ': ' . $error);
            }
        } else {
            $error = 'بيانات السيرفر غير مكتملة.';
        }
        $message = $result ?: $error;
        Log::info('Subscription sync result for subscription ' . $subscription->id . ': ' . $message);
        return $message;
    }

    /**
     * Generate a small list of username candidates. First candidate is current username if exists.
     * Then fallback generators using subscription id and client slug.
     * @param Subscription $subscription
     * @param int $limit
     * @return array
     */
    private function generateUsernameCandidates(Subscription $subscription, int $limit = 5): array
    {
        $list = [];
        if (!empty($subscription->username)) {
            $list[] = $subscription->username;
        }
        // client slug or name
        $clientSlug = null;
        try {
            $clientSlug = $subscription->client->slug ?? null;
        } catch (\Exception $e) {
            $clientSlug = null;
        }
        if ($clientSlug) {
            $list[] = $clientSlug . $subscription->id;
        }
        // default palgoal{subscription_id}
        $list[] = 'palgoal' . $subscription->id;
        // add numerical suffixes if still more needed
        $i = 1;
        while (count($list) < $limit) {
            $list[] = 'palgoal' . $subscription->id . $i;
            $i++;
        }
        return array_slice($list, 0, $limit);
    }

    /**
     * Generate a default username when none exists
     */
    private function generateDefaultUsername(Subscription $subscription): string
    {
        return 'palgoal' . $subscription->id;
    }

    /**
     * Sanitize username to WHM-friendly format: lowercase, alphanumeric, max 16 chars.
     */
    private function sanitizeUsername(string $username): string
    {
        $s = mb_strtolower($username);
        // keep only a-z0-9
        $s = preg_replace('/[^a-z0-9]/', '', $s);
        // limit length (cPanel typically allows up to 16)
        return substr($s, 0, 16);
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
