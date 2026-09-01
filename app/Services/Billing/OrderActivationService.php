<?php

namespace App\Services\Billing;

use App\Jobs\ProvisionSubscription;
use App\Models\Order;
use App\Models\Tenancy\Subscription;
use App\Services\Domains\RegistrarProvisioningService;
use Illuminate\Support\Facades\Log;

/**
 * Handles order activation: updating invoices, creating/updating subscriptions,
 * dispatching provisioning jobs, and optionally registering domains.
 *
 * Extracted from OrderController::processActivation() to eliminate the
 * controller-from-controller antipattern (app(OrderController::class)->processActivation()).
 */
class OrderActivationService
{
    public function __construct(
        protected RegistrarProvisioningService $registrar,
    ) {}

    /**
     * Activate an order: flip related invoices to unpaid, provision subscriptions,
     * and optionally register/renew a domain.
     *
     * @param  \App\Models\Order  $order
     * @param  string|null        $paymentMethod
     * @return array{domain_registration: array|null, subscriptions: \Illuminate\Support\Collection}
     */
    public function activate(Order $order, ?string $paymentMethod = null): array
    {
        $result = [
            'domain_registration' => null,
            'subscriptions'       => collect(),
        ];

        // Read current database state instead of a possibly stale loaded relation.
        // The status predicate guarantees that a concurrently/currently paid invoice
        // can never be written back to unpaid by order activation.
        $order->invoices()
            ->where('status', 'draft')
            ->update(['status' => 'unpaid']);

        $domainItems = $order->items
            ->filter(fn ($item) => filled($item->domain))
            ->values();
        $domainName = null;
        $domainOption = null;

        if ($domainItems->count() === 1) {
            $domainName = $domainItems[0]->domain;
            $domainOption = $domainItems[0]->item_option;
        }

        $subscriptionIds = $order->invoices
            ->flatMap(fn ($invoice) => $invoice->items
                ->filter(fn ($item) => $item->item_type === 'subscription' && filled($item->reference_id))
                ->pluck('reference_id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        // Register or renew domain if requested.
        $hasProvisionableDomain = $domainItems->contains(
            fn ($item) => in_array(strtolower((string) $item->item_option), ['register', 'renew'], true)
        );

        if ($hasProvisionableDomain) {
            try {
                $result['domain_registration'] = $this->registrar
                    ->provisionOrderDomain($order, $paymentMethod);
            } catch (\Throwable $e) {
                Log::error('Failed to provision registrar domain for order ' . $order->id . ': ' . $e->getMessage());
                $result['domain_registration'] = [
                    'ok'      => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Activate existing subscriptions referenced by invoice items.
        if ($subscriptionIds->isNotEmpty()) {
            $subscriptions = \App\Models\Tenancy\Subscription::with(['client', 'plan', 'server', 'template'])
                ->whereIn('id', $subscriptionIds)
                ->get()
                ->keyBy('id');

            $activated = collect();

            foreach ($subscriptionIds as $subscriptionId) {
                $subscriptionModel = $subscriptions->get($subscriptionId);
                if (!$subscriptionModel) {
                    continue;
                }

                $startsAt     = now();
                $billingCycle = strtolower((string) ($subscriptionModel->billing_cycle ?? 'annually'));
                $nextDueDate  = str_contains($billingCycle, 'month')
                    ? $startsAt->copy()->addMonth()
                    : $startsAt->copy()->addYear();

                $updateData = [
                    'status'        => 'active',
                    'starts_at'     => $startsAt,
                    'ends_at'       => $nextDueDate,
                    'next_due_date' => $nextDueDate,
                ];

                if (!empty($domainOption)) {
                    $updateData['domain_option'] = $domainOption;
                }
                if (!empty($domainName)) {
                    $updateData['domain_name'] = $domainName;
                }

                $subscriptionModel->fill($updateData)->save();

                $this->queueProvisioning($subscriptionModel);

                $activated->push($subscriptionModel->fresh(['client', 'plan', 'server', 'template']));
            }

            $result['subscriptions'] = $activated;

            return $result;
        }

        // Fallback: create a new subscription from the first subscription-type invoice item.
        $templateId = null;
        if ($order->invoices && $order->invoices->count()) {
            $leadingInvoice = $order->invoices[0] ?? null;
            $leadingItem = $leadingInvoice?->items[0] ?? null;
            if ($leadingItem && $leadingItem->item_type === 'subscription') {
                $templateId = $leadingItem->reference_id;
            }
        }

        if (!$templateId) {
            return $result;
        }

        $template = \App\Models\Template::find($templateId);
        if (!($template && $template->plan_id)) {
            return $result;
        }

        $duration = 'month';
        if (isset($template->plan) && method_exists($template->plan, 'getDurationUnit')) {
            $duration = $template->plan->getDurationUnit();
        } elseif (isset($template->plan) && isset($template->plan->duration_unit)) {
            $duration = $template->plan->duration_unit;
        }

        $startsAt = now();
        $endsAt   = $duration === 'year' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth();

        $serverId = null;
        if (isset($template->plan->server_id)) {
            $serverId = $template->plan->server_id;
        } elseif (isset($order->server_id)) {
            $serverId = $order->server_id;
        }

        $existingSubQuery = \App\Models\Tenancy\Subscription::where('client_id', $order->client_id)
            ->where('plan_id', $template->plan_id);
        if (!empty($domainName)) {
            $existingSubQuery->where('domain_name', $domainName);
        }
        $existingSub = $existingSubQuery->first();

        $generateUsername = function () use ($order, $domainName) {
            if (!empty($domainName)) {
                $base = strtolower(preg_replace('/[^a-z0-9]/i', '', str_replace('.', '', $domainName)));
                $base = substr($base, 0, 12);
            } elseif (isset($order->extra['username'])) {
                $base = preg_replace('/[^a-z0-9]/i', '', $order->extra['username']);
            } else {
                $client = \App\Models\Client::find($order->client_id);
                if ($client) {
                    $base = filled($client->email) && str_contains($client->email, '@')
                        ? explode('@', $client->email)[0]
                        : ($client->first_name ?? '') . ($client->last_name ?? '');
                }
                $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $base ?? 'user'));
                $base = substr($base, 0, 12);
            }

            $candidate = $base ?? 'user';
            $suffix    = 0;
            while (\App\Models\Tenancy\Subscription::where('username', $candidate)->exists()) {
                $suffix++;
                $candidate = ($base ?? 'user') . $suffix;
                if ($suffix > 1000) break;
            }

            return $candidate;
        };

        if ($existingSub) {
            $updateData = [
                'status'        => 'active',
                'starts_at'     => $startsAt,
                'ends_at'       => $endsAt,
                'domain_option' => $domainOption,
                'domain_name'   => $domainName,
            ];
            if (empty($existingSub->server_id) && $serverId) {
                $updateData['server_id'] = $serverId;
            }
            if (empty($existingSub->username)) {
                $updateData['username'] = $generateUsername();
            }
            $existingSub->update($updateData);
            $subscriptionModel = $existingSub;
        } else {
            $subscriptionModel = \App\Models\Tenancy\Subscription::create([
                'client_id'      => $order->client_id,
                'plan_id'        => $template->plan_id,
                'status'         => 'active',
                'price_cents'    => $template->resolvedPriceCents(),
                'starts_at'      => $startsAt,
                'ends_at'        => $endsAt,
                'server_id'      => $serverId,
                'server_package' => $template->plan?->server_package ?? ($template->plan?->name ?? null),
                'username'       => $generateUsername(),
                'domain_option'  => $domainOption,
                'domain_name'    => $domainName,
            ]);
        }

        if ($subscriptionModel instanceof Subscription) {
            $this->queueProvisioning($subscriptionModel);
        }

        return $result;
    }

    protected function queueProvisioning(Subscription $subscription): void
    {
        ProvisionSubscription::dispatch($subscription->id)->afterCommit();
    }

}
