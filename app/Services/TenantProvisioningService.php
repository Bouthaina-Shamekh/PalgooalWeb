<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenancy\Subscription;
use App\Models\User;
use App\Notifications\AdminSubscriptionProvisioned;
use App\Notifications\SubscriptionProvisionedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class TenantProvisioningService
{
    public function __construct(
        protected SubscriptionSyncService $syncService,
        protected TemplateBlueprintService $blueprintService
    ) {
    }

    /**
     * Provision subscription tenant (WHM + blueprint cloning).
     */
    public function provision(Subscription $subscription, bool $force = false): Subscription
    {
        if (! $force && $subscription->provisioning_status === Subscription::PROVISIONING_ACTIVE) {
            return $subscription;
        }

        $subscription->forceFill([
            'provisioning_status' => Subscription::PROVISIONING_IN_PROGRESS,
        ])->save();

        $domain = $this->ensureDomain($subscription);

        $planType = $subscription->plan?->plan_type ?? Plan::TYPE_MULTI_TENANT;

        try {
            if ($planType === Plan::TYPE_HOSTING) {
                $message = $this->syncService->sync($subscription);

                $subscription->forceFill([
                    'last_synced_at' => now(),
                    'last_sync_message' => $message,
                ])->save();
            } else {
                $message = $this->provisionMultiTenant($subscription);
                $subscription->forceFill([
                    'last_sync_message' => $message,
                ])->save();
            }

            $subscription->forceFill([
                'provisioning_status' => Subscription::PROVISIONING_ACTIVE,
                'provisioned_at' => now(),
                'domain_name' => $domain,
            ])->save();

            $this->notifyClient($subscription);
            $this->notifyAdmins($subscription);
        } catch (Throwable $exception) {
            $subscription->forceFill([
                'provisioning_status' => Subscription::PROVISIONING_FAILED,
                'last_sync_message' => $exception->getMessage(),
            ])->save();

            Log::error('Tenant provisioning failed', [
                'subscription_id' => $subscription->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $subscription;
    }

    protected function ensureDomain(Subscription $subscription): string
    {
        $domainName = $subscription->domain_name ? strtolower(trim($subscription->domain_name)) : null;

        if (! empty($domainName)) {
            $updates = ['domain_name' => $domainName];

            if ($subscription->domain_option === 'subdomain' && empty($subscription->subdomain)) {
                $sub = Str::before($domainName, '.');
                if (! empty($sub)) {
                    $updates['subdomain'] = $sub;
                }
            }

            if (count($updates) > 0) {
                $subscription->forceFill($updates)->save();
            }

            return $domainName;
        }

        $subdomain = $subscription->subdomain ?: $this->generateSubdomain($subscription);
        $root = ltrim(config('tenancy.subdomain_root'), '.');
        $root = $root ?: 'wpgoals.com';

        $fqdn = "{$subdomain}.{$root}";

        $subscription->forceFill([
            'domain_option' => 'subdomain',
            'domain_name' => $fqdn,
            'subdomain' => $subdomain,
        ])->save();

        return $fqdn;
    }

    protected function generateSubdomain(Subscription $subscription): string
    {
        $maxLength = (int) config('tenancy.subdomain_max_length', 24);
        $base = $subscription->client?->company_name
            ?? $subscription->client?->first_name
            ?? $subscription->template?->translation()?->name
            ?? 'site';
        $base = Str::slug($base, '-');
        if ($base === '') {
            $base = 'site';
        }

        $base = Str::limit($base, max(8, $maxLength - 5), '');
        $attempt = 0;
        do {
            $suffix = $attempt === 0 ? '' : '-' . Str::lower(Str::random(4));
            $candidate = trim(Str::limit($base . $suffix, $maxLength, ''), '-');
            $attempt++;
        } while ($this->subdomainExists($candidate) && $attempt < 10);

        if ($candidate === '' || $this->subdomainExists($candidate)) {
            $candidate = Str::lower(Str::random(min(12, $maxLength)));
        }

        return $candidate;
    }

    protected function subdomainExists(string $candidate): bool
    {
        return Subscription::where('subdomain', $candidate)->exists();
    }

    protected function provisionMultiTenant(Subscription $subscription): string
    {
        $this->cloneTemplateBlueprint($subscription);

        return 'Multi-tenant instance initialized.';
    }

    protected function cloneTemplateBlueprint(Subscription $subscription): void
    {
        $message = $this->blueprintService->seedSubscription($subscription);

        Log::info($message, [
            'subscription_id' => $subscription->id,
            'template_id' => $subscription->template_id,
        ]);
    }

    protected function notifyClient(Subscription $subscription): void
    {
        if ($subscription->client) {
            $subscription->client->notify(new SubscriptionProvisionedNotification($subscription));
        }
    }

    protected function notifyAdmins(Subscription $subscription): void
    {
        $admins = User::query()
            ->where('super_admin', true)
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AdminSubscriptionProvisioned($subscription));
        }
    }
}
