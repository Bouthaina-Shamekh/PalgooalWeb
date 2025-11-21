<?php

namespace App\Notifications;

use App\Models\Tenancy\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionProvisionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Subscription $subscription)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $domain = $this->subscription->domain_name ?? $this->subscription->subdomain;

        return (new MailMessage)
            ->subject(__('تم تفعيل موقعك على بال قول'))
            ->greeting(__('مرحباً :name', ['name' => $notifiable->first_name ?? $notifiable->name ?? '']))
            ->line(__('تم تجهيز موقعك وربطه بالقالب ":template" بنجاح.', [
                'template' => $this->subscription->template?->translation()?->name
                    ?? $this->subscription->template?->name
                    ?? __('القالب'),
            ]))
            ->line(__('يمكنك زيارة موقعك عبر: :domain', ['domain' => $domain]))
            ->action(__('لوحة العميل'), url('/client/login'))
            ->line(__('شكراً لاختيارك Palgoals Templates!'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'domain' => $this->subscription->domain_name,
        ];
    }
}
