<?php

namespace App\Jobs;

use App\Models\Tenancy\Subscription;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $subscriptionId,
        public bool $force = false
    ) {
        $this->onQueue('provisioning');
    }

    public function handle(TenantProvisioningService $service): void
    {
        $subscription = Subscription::with(['client', 'plan', 'server', 'template'])
            ->find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        try {
            $service->provision($subscription, $this->force);
        } catch (\Throwable $exception) {
            Log::error('ProvisionSubscription job failed', [
                'subscription_id' => $subscription->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
