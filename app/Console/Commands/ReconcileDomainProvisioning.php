<?php

namespace App\Console\Commands;

use App\Models\DomainProvisioningAttempt;
use App\Models\OrderItem;
use App\Services\Domains\DomainProvisioningReconciliationService;
use Illuminate\Console\Command;

class ReconcileDomainProvisioning extends Command
{
    protected $signature = 'domains:reconcile-provisioning
        {--attempt= : Reconcile one attempt ID}
        {--order-item= : Reconcile attempts for one order item ID}
        {--limit=50 : Maximum attempts to inspect}
        {--older-than=30 : Minimum age in minutes based on provisioning_started_at}
        {--dry-run : Explicitly preview without database writes}
        {--apply : Apply only conclusive registered_by_us results}';

    protected $description = 'Manually inspect stuck domain registration attempts without retrying registration';

    public function handle(DomainProvisioningReconciliationService $service): int
    {
        $apply = (bool) $this->option('apply');
        $explicitDryRun = (bool) $this->option('dry-run');

        if ($apply && $explicitDryRun) {
            $this->error('Choose either --apply or --dry-run, not both.');

            return self::INVALID;
        }

        $dryRun = !$apply;
        $limit = min(500, max(1, (int) $this->option('limit')));
        $olderThan = max(0, (int) $this->option('older-than'));
        $threshold = now()->subMinutes($olderThan);

        $query = DomainProvisioningAttempt::query()
            ->with(['orderItem', 'domain', 'provider'])
            ->where('operation', DomainProvisioningAttempt::OPERATION_REGISTER)
            ->whereIn('status', [
                DomainProvisioningAttempt::STATUS_INITIATED,
                DomainProvisioningAttempt::STATUS_INDETERMINATE,
            ])
            ->whereHas('orderItem', function ($query) use ($threshold): void {
                $query
                    ->where('provisioning_status', OrderItem::PROVISIONING_IN_PROGRESS)
                    ->where('item_option', DomainProvisioningAttempt::OPERATION_REGISTER)
                    ->whereNotNull('provisioning_started_at')
                    ->where('provisioning_started_at', '<=', $threshold);
            })
            ->orderBy('id');

        if (filled($this->option('attempt'))) {
            $query->whereKey((int) $this->option('attempt'));
        }

        if (filled($this->option('order-item'))) {
            $query->where('order_item_id', (int) $this->option('order-item'));
        }

        $attempts = $query->limit($limit)->get();
        $rows = [];

        foreach ($attempts as $attempt) {
            try {
                $result = $service->reconcileAttempt($attempt, $apply);
            } catch (\Throwable $e) {
                $result = [
                    'status' => DomainProvisioningReconciliationService::STATUS_INDETERMINATE,
                    'action' => 'error_no_change',
                ];
            }

            $rows[] = [
                $attempt->getKey(),
                $attempt->order_item_id,
                $attempt->domain?->domain_name ?: $attempt->orderItem?->domain,
                strtolower((string) $attempt->provider_type) . '#' . $attempt->provider_id,
                $attempt->status,
                $result['status'] ?? DomainProvisioningReconciliationService::STATUS_INDETERMINATE,
                $result['action'] ?? 'no_change',
                $threshold->toDateTimeString(),
            ];
        }

        $this->info($dryRun
            ? 'DRY RUN: provider lookups will run, but the database will not be changed.'
            : 'APPLY: only conclusive registered_by_us results may be committed.');

        $this->table([
            'attempt id',
            'order item id',
            'domain',
            'provider',
            'current status',
            'reconciliation result',
            'action taken',
            'threshold',
        ], $rows);

        $this->line(sprintf(
            'Inspected %d attempt(s); threshold is %d minute(s).',
            count($rows),
            $olderThan
        ));

        return self::SUCCESS;
    }
}
