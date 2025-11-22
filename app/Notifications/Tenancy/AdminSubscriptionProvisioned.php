<?php

namespace App\Notifications\Tenancy;

use App\Models\Tenancy\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminSubscriptionProvisioned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Subscription $subscription)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'client' => $this->subscription->client?->email,
            'domain' => $this->subscription->domain_name,
            'message' => __('تم تفعيل اشتراك جديد للعميل :client على النطاق :domain', [
                'client' => $this->subscription->client?->first_name ?? $this->subscription->client?->email,
                'domain' => $this->subscription->domain_name,
            ]),
            'link' => route('dashboard.subscriptions.edit', $this->subscription),
        ];
    }
}
