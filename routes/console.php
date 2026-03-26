<?php

use App\Models\Tenancy\TenantRuntimeMetric;
use App\Services\Domains\DomainRenewalService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('domains:process-auto-renewals {--dry-run}', function () {
    $summary = app(DomainRenewalService::class)->processDueAutoRenewals((bool) $this->option('dry-run'));

    $this->table(
        ['scanned', 'due', 'created', 'existing', 'renewed', 'awaiting_payment', 'failed'],
        [[
            $summary['scanned'],
            $summary['due'],
            $summary['created'],
            $summary['existing'],
            $summary['renewed'],
            $summary['awaiting_payment'],
            $summary['failed'],
        ]]
    );
})->purpose('Create and process due domain auto-renewals before their renewal date.');

Artisan::command('tenancy:runtime-usage {--tenant_id=} {--source=} {--limit=25}', function () {
    if (! Schema::hasTable('tenant_runtime_metrics')) {
        $this->warn('The tenant_runtime_metrics table does not exist yet. Run migrations first.');

        return;
    }

    $tenantId = $this->option('tenant_id');
    $source = $this->option('source');
    $limit = max(1, (int) $this->option('limit'));

    $baseQuery = TenantRuntimeMetric::query();

    if ($tenantId !== null && $tenantId !== '') {
        $baseQuery->where('tenant_id', (int) $tenantId);
    }

    if ($source !== null && $source !== '') {
        $baseQuery->where('source', (string) $source);
    }

    $summaryRows = (clone $baseQuery)
        ->select([
            'tenant_id',
            'source',
            DB::raw('SUM(hits) as total_hits'),
            DB::raw('COUNT(*) as tracked_pages'),
            DB::raw('MAX(last_seen_at) as last_seen_at'),
        ])
        ->groupBy('tenant_id', 'source')
        ->orderByDesc('total_hits')
        ->limit($limit)
        ->get();

    $this->newLine();
    $this->info('Tenant runtime usage summary');

    if ($summaryRows->isEmpty()) {
        $this->warn('No runtime usage metrics recorded yet.');
    } else {
        $this->table(
            ['tenant_id', 'source', 'hits', 'tracked_pages', 'last_seen_at'],
            $summaryRows->map(fn ($row) => [
                $row->tenant_id,
                $row->source,
                (int) $row->total_hits,
                (int) $row->tracked_pages,
                $row->last_seen_at,
            ])->all()
        );
    }

    $pageRows = (clone $baseQuery)
        ->select([
            'tenant_id',
            'source',
            'path',
            'resolved_slug',
            'page_model',
            'page_id',
            'hits',
            'last_seen_at',
        ])
        ->orderByDesc('hits')
        ->orderByDesc('last_seen_at')
        ->limit($limit)
        ->get();

    $this->newLine();
    $this->info('Most visited tenant pages');

    if ($pageRows->isEmpty()) {
        $this->warn('No tenant page usage has been recorded for the current filter.');
    } else {
        $this->table(
            ['tenant_id', 'source', 'path', 'resolved_slug', 'page_model', 'page_id', 'hits', 'last_seen_at'],
            $pageRows->map(fn ($row) => [
                $row->tenant_id,
                $row->source,
                $row->path,
                $row->resolved_slug,
                $row->page_model,
                $row->page_id,
                (int) $row->hits,
                $row->last_seen_at,
            ])->all()
        );
    }
})->purpose('Report tenant runtime usage by tenant and page.');

Schedule::command('domains:process-auto-renewals')
    ->dailyAt('02:00')
    ->withoutOverlapping();
