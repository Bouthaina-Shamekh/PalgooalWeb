<?php

namespace App\Services\Tenancy;

use App\Models\Tenancy\Subscription;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SubscriptionSyncService
{
    public const RESULT_SUCCESS = 'success';
    public const RESULT_FAILED = 'failed';
    public const RESULT_UNKNOWN = 'unknown';
    public const RESULT_SKIPPED = 'skipped';

    /** Backwards-compatible message API used by existing controllers/jobs. */
    public function sync(Subscription $subscription, bool $dryRun = false): string
    {
        return $this->provisionAccount($subscription, $dryRun)['message'];
    }

    /**
     * Atomically claim a subscription and issue at most one WHM createacct call.
     *
     * @return array{result: string, state: string, message: string, username: string|null}
     */
    public function provisionAccount(Subscription $subscription, bool $dryRun = false): array
    {
        if ($dryRun) {
            return $this->dryRunResult($subscription);
        }

        $claim = DB::transaction(function () use ($subscription): array {
            $locked = Subscription::query()
                ->with(['client', 'plan', 'server'])
                ->lockForUpdate()
                ->findOrFail($subscription->id);

            $state = $locked->provisioning_status ?: Subscription::PROVISIONING_PENDING;

            if (in_array($state, [
                Subscription::PROVISIONING_ACTIVE,
                Subscription::PROVISIONING_IN_PROGRESS,
                Subscription::PROVISIONING_UNKNOWN,
            ], true)) {
                return $this->skippedResult($locked, $state);
            }

            $validationError = $this->configurationError($locked);
            if ($validationError !== null) {
                $locked->forceFill([
                    'provisioning_status' => Subscription::PROVISIONING_FAILED,
                    'last_sync_message' => $validationError,
                ])->save();

                return $this->result(self::RESULT_FAILED, Subscription::PROVISIONING_FAILED, $validationError, $locked->username);
            }

            $accountUsername = $this->sanitizeUsername(
                (string) ($locked->username ?: $this->generateDefaultUsername($locked))
            );

            if ($accountUsername === '') {
                $message = 'WHM provisioning cannot start without a valid account username.';
                $locked->forceFill([
                    'provisioning_status' => Subscription::PROVISIONING_FAILED,
                    'last_sync_message' => $message,
                ])->save();

                return $this->result(self::RESULT_FAILED, Subscription::PROVISIONING_FAILED, $message, null);
            }

            $locked->forceFill([
                'username' => $accountUsername,
                'cpanel_username' => $accountUsername,
                'cpanel_password' => $locked->cpanel_password ?: Str::random(14) . '!A9',
                'provisioning_status' => Subscription::PROVISIONING_IN_PROGRESS,
                'last_sync_message' => 'WHM account creation claimed and in progress.',
            ])->save();

            return [
                'result' => null,
                'subscription' => $locked->fresh(['client', 'plan', 'server']),
            ];
        });

        if ($claim['result'] !== null) {
            return $claim;
        }

        /** @var Subscription $claimed */
        $claimed = $claim['subscription'];

        try {
            $response = $this->sendCreateAccountRequest($claimed);
        } catch (Throwable $exception) {
            return $this->finalizeUnknown(
                $claimed,
                'WHM createacct outcome is unknown: ' . $exception->getMessage(),
            );
        }

        if ($response->serverError() || in_array($response->status(), [408, 425, 429], true)) {
            return $this->finalizeUnknown(
                $claimed,
                'WHM createacct returned a transient HTTP status; reconciliation is required.',
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            return $this->finalizeUnknown(
                $claimed,
                'WHM createacct returned an empty or malformed response; reconciliation is required.',
            );
        }

        if (($data['metadata']['result'] ?? null) == 1) {
            return $this->finalizeResult(
                $claimed,
                Subscription::PROVISIONING_ACTIVE,
                self::RESULT_SUCCESS,
                'WHM account created successfully.',
            );
        }

        $reason = trim((string) ($data['metadata']['reason'] ?? $data['reason'] ?? ''));
        if ($reason === '' || $this->reasonIsAmbiguous($reason)) {
            return $this->finalizeUnknown(
                $claimed,
                $reason !== ''
                    ? 'WHM createacct outcome requires reconciliation: ' . $reason
                    : 'WHM createacct returned no conclusive result; reconciliation is required.',
            );
        }

        return $this->finalizeResult(
            $claimed,
            Subscription::PROVISIONING_FAILED,
            self::RESULT_FAILED,
            'WHM rejected account creation: ' . $reason,
        );
    }

    protected function sendCreateAccountRequest(Subscription $subscription): Response
    {
        $server = $subscription->server;
        $host = filled($server?->hostname) ? trim((string) $server->hostname) : $server?->ip;

        return Http::withHeaders([
            'Authorization' => 'whm ' . $server->username . ':' . $server->api_token,
        ])->withOptions([
            'verify' => config('services.whm.ssl_verify', true),
        ])->connectTimeout(10)->timeout(20)->get(
            "https://{$host}:2087/json-api/createacct",
            [
                'api.version' => 1,
                'username' => $subscription->username,
                'domain' => $subscription->domain_name,
                'plan' => (string) ($subscription->server_package ?: $subscription->plan?->server_package),
                'contactemail' => $subscription->client?->email ?? '',
                'password' => $subscription->cpanel_password,
            ],
        );
    }

    protected function finalizeUnknown(Subscription $subscription, string $message): array
    {
        return $this->finalizeResult(
            $subscription,
            Subscription::PROVISIONING_UNKNOWN,
            self::RESULT_UNKNOWN,
            $message,
        );
    }

    protected function finalizeResult(
        Subscription $subscription,
        string $state,
        string $result,
        string $message,
    ): array {
        return DB::transaction(function () use ($subscription, $state, $result, $message): array {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($locked->provisioning_status !== Subscription::PROVISIONING_IN_PROGRESS) {
                return $this->skippedResult($locked, (string) $locked->provisioning_status);
            }

            $updates = [
                'provisioning_status' => $state,
                'last_sync_message' => $message,
                'last_synced_at' => now(),
            ];

            if ($state === Subscription::PROVISIONING_ACTIVE) {
                $updates['provisioned_at'] = now();
                $updates['cpanel_username'] = $locked->username;
            }

            $locked->forceFill($updates)->save();

            Log::info('Subscription WHM provisioning result.', [
                'subscription_id' => $locked->id,
                'result' => $result,
                'provisioning_status' => $state,
            ]);

            return $this->result($result, $state, $message, $locked->username);
        });
    }

    protected function configurationError(Subscription $subscription): ?string
    {
        $server = $subscription->server;
        $host = filled($server?->hostname) ? trim((string) $server->hostname) : $server?->ip;

        if (! $server || ! $host || ! $server->username || ! $server->api_token) {
            return 'WHM server credentials are incomplete.';
        }

        if (! ($subscription->server_package ?: $subscription->plan?->server_package)) {
            return 'WHM server_package is not configured for this subscription or plan.';
        }

        if (! filled($subscription->domain_name)) {
            return 'WHM account domain is missing.';
        }

        return null;
    }

    protected function reasonIsAmbiguous(string $reason): bool
    {
        $reason = Str::lower($reason);

        return str_contains($reason, 'already exists')
            || str_contains($reason, 'already owned')
            || str_contains($reason, 'already has an account')
            || str_contains($reason, 'duplicate')
            || str_contains($reason, 'timeout')
            || str_contains($reason, 'timed out')
            || str_contains($reason, 'connection reset')
            || str_contains($reason, 'temporarily unavailable')
            || str_contains($reason, 'try again');
    }

    protected function skippedResult(Subscription $subscription, string $state): array
    {
        $message = match ($state) {
            Subscription::PROVISIONING_ACTIVE => 'WHM provisioning already completed; createacct skipped.',
            Subscription::PROVISIONING_IN_PROGRESS => 'WHM provisioning is already in progress; createacct skipped.',
            Subscription::PROVISIONING_UNKNOWN => 'WHM provisioning outcome is unknown; reconciliation is required before retry.',
            default => 'WHM provisioning state changed before completion; createacct skipped.',
        };

        return $this->result(self::RESULT_SKIPPED, $state, $message, $subscription->username);
    }

    protected function result(string $result, string $state, string $message, ?string $username): array
    {
        return compact('result', 'state', 'message', 'username');
    }

    protected function dryRunResult(Subscription $subscription): array
    {
        $subscription->loadMissing(['client', 'plan', 'server']);
        $error = $this->configurationError($subscription);
        if ($error !== null) {
            return $this->result(self::RESULT_FAILED, (string) $subscription->provisioning_status, $error, $subscription->username);
        }

        $username = $this->sanitizeUsername(
            (string) ($subscription->username ?: $this->generateDefaultUsername($subscription))
        );
        $host = filled($subscription->server?->hostname)
            ? trim((string) $subscription->server->hostname)
            : $subscription->server?->ip;
        $message = "DRY RUN - createacct URL: https://{$host}:2087/json-api/createacct username={$username}";

        return $this->result(self::RESULT_SKIPPED, (string) $subscription->provisioning_status, $message, $username);
    }

    private function generateDefaultUsername(Subscription $subscription): string
    {
        return 'palgoal' . $subscription->id;
    }

    private function sanitizeUsername(string $username): string
    {
        $sanitized = mb_strtolower($username);
        $sanitized = preg_replace('/[^a-z0-9]/', '', $sanitized);

        return substr((string) $sanitized, 0, 16);
    }

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
                $sslVerify = config('services.whm.ssl_verify', true);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
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
