<?php

namespace App\Jobs;

use App\Models\Tenancy\Subscription;
use App\Services\Tenancy\SubscriptionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TerminateSubscriptionOnProvider implements ShouldQueue
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
        $sub = Subscription::find($this->subscriptionId);
        if (!$sub) return;
        $message = $service->terminate($sub);
        try {
            $sub->update(['last_sync_message' => $message]);
        } catch (\Exception $e) {
            Log::error('Failed to save last_sync_message for subscription ' . $this->subscriptionId . ': ' . $e->getMessage());
        }
    }
}
