<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\SubscriptionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSubscriptionToProvider implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subscriptionId;

    public $tries = 3;

    public function __construct(int $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    public function handle(SubscriptionSyncService $service)
    {
        $subscription = Subscription::find($this->subscriptionId);
        if (!$subscription) return;
        $message = $service->sync($subscription);
        // Optionally persist last_sync_message on subscription
        try {
            $subscription->update(['last_sync_message' => $message]);
        } catch (\Exception $e) {
            // ignore
        }
    }
}
