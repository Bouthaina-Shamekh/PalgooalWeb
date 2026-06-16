<?php

use App\Models\Client;
use App\Models\GeneralSetting;
use App\Models\Portfolio;
use App\Models\Tenancy\TenantRuntimeMetric;
use App\Services\Domains\DomainRenewalService;
use App\Support\Media\MediaPathNormalizer;
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

// ---------------------------------------------------------------------------
// ADR-005 Wave 1 — Backfill *_media_id FK columns from existing path columns
// ---------------------------------------------------------------------------
// Run once after `php artisan migrate`:
//   php artisan adr005:backfill-wave1
//   php artisan adr005:backfill-wave1 --dry-run
// ---------------------------------------------------------------------------
Artisan::command('adr005:backfill-wave1 {--dry-run : Preview changes without writing to DB}', function () {
    $isDry    = (bool) $this->option('dry-run');
    $updated  = 0;
    $orphaned = 0;

    $this->info('ADR-005 Wave 1 backfill' . ($isDry ? ' (DRY RUN — no writes)' : '') . ' …');
    $this->newLine();

    // ── 1. clients.avatar → avatar_media_id ─────────────────────────────────
    $this->info('1/3  clients.avatar → avatar_media_id');
    $clients = Client::whereNotNull('avatar')->whereNull('avatar_media_id')->get(['id', 'avatar']);
    foreach ($clients as $client) {
        $mediaId = MediaPathNormalizer::resolveToMediaId($client->avatar);
        if ($mediaId === null) {
            $this->warn("     Orphan client #{$client->id}: avatar='{$client->avatar}' — no media record found, will set NULL");
            $orphaned++;
        } else {
            $this->line("     client #{$client->id}: avatar='{$client->avatar}' → media_id={$mediaId}");
        }
        if (! $isDry) {
            DB::table('clients')->where('id', $client->id)->update(['avatar_media_id' => $mediaId]);
        }
        $updated++;
    }
    $this->info("     → {$updated} row(s) processed ({$orphaned} orphan(s))");

    $rowsTotal = $updated;
    $updated   = 0;
    $orphaned  = 0;

    // ── 2. portfolios.default_image → default_image_media_id ────────────────
    $this->newLine();
    $this->info('2/3  portfolios.default_image → default_image_media_id');
    $portfolios = Portfolio::whereNotNull('default_image')->whereNull('default_image_media_id')->get(['id', 'default_image']);
    foreach ($portfolios as $portfolio) {
        $mediaId = MediaPathNormalizer::resolveToMediaId($portfolio->default_image);
        if ($mediaId === null) {
            $this->warn("     Orphan portfolio #{$portfolio->id}: default_image='{$portfolio->default_image}' — no media record found, will set NULL");
            $orphaned++;
        } else {
            $this->line("     portfolio #{$portfolio->id}: default_image='{$portfolio->default_image}' → media_id={$mediaId}");
        }
        if (! $isDry) {
            DB::table('portfolios')->where('id', $portfolio->id)->update(['default_image_media_id' => $mediaId]);
        }
        $updated++;
    }
    $this->info("     → {$updated} row(s) processed ({$orphaned} orphan(s))");

    $rowsTotal += $updated;
    $updated    = 0;
    $orphaned   = 0;

    // ── 3. general_settings ×7 logo/favicon columns ─────────────────────────
    $this->newLine();
    $this->info('3/3  general_settings ×7 logo/favicon columns');
    $logoMap = [
        'logo'             => 'logo_media_id',
        'dark_logo'        => 'dark_logo_media_id',
        'sticky_logo'      => 'sticky_logo_media_id',
        'dark_sticky_logo' => 'dark_sticky_logo_media_id',
        'admin_logo'       => 'admin_logo_media_id',
        'admin_dark_logo'  => 'admin_dark_logo_media_id',
        'favicon'          => 'favicon_media_id',
    ];
    $setting = GeneralSetting::first();
    if (! $setting) {
        $this->warn('     No general_settings row found — skipping.');
    } else {
        foreach ($logoMap as $pathCol => $idCol) {
            $rawPath = $setting->getRawOriginal($pathCol);
            if ($rawPath === null || $rawPath === '') {
                $this->line("     {$pathCol}: NULL — skip");
                continue;
            }
            if ($setting->getRawOriginal($idCol) !== null) {
                $this->line("     {$idCol}: already set — skip");
                continue;
            }
            $mediaId = MediaPathNormalizer::resolveToMediaId($rawPath);
            if ($mediaId === null) {
                $this->warn("     Orphan {$pathCol}='{$rawPath}' — no media record found, will set NULL");
                $orphaned++;
            } else {
                $this->line("     {$pathCol}='{$rawPath}' → {$idCol}={$mediaId}");
            }
            if (! $isDry) {
                DB::table('general_settings')->where('id', $setting->id)->update([$idCol => $mediaId]);
            }
            $updated++;
        }
    }
    $this->info("     → {$updated} row(s) processed ({$orphaned} orphan(s))");

    $rowsTotal += $updated;

    $this->newLine();
    if ($isDry) {
        $this->comment("DRY RUN complete. Total rows that would be touched: {$rowsTotal}. Re-run without --dry-run to apply.");
    } else {
        $this->info("Backfill complete. Total rows updated: {$rowsTotal}.");
    }
})->purpose('ADR-005 Wave 1: populate *_media_id FK columns from existing path columns (clients, portfolios, general_settings).');

Schedule::command('domains:process-auto-renewals')
    ->dailyAt('02:00')
    ->withoutOverlapping();
