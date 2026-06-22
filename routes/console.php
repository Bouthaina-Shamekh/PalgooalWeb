<?php

use App\Models\Client;
use App\Models\GeneralSetting;
use App\Models\Portfolio;
use App\Models\Template;
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

// ---------------------------------------------------------------------------
// ADR-005 Wave 2 — Backfill templates.image_media_id from templates.image
// ---------------------------------------------------------------------------
// Run once after `php artisan migrate`:
//   php artisan adr005:backfill-wave2-templates
//   php artisan adr005:backfill-wave2-templates --dry-run
// ---------------------------------------------------------------------------
Artisan::command('adr005:backfill-wave2-templates {--dry-run : Preview changes without writing to DB}', function () {
    $isDry    = (bool) $this->option('dry-run');
    $linked   = 0;
    $orphaned = 0;
    $skipped  = 0;

    $this->info('ADR-005 Wave 2 — templates.image → image_media_id' . ($isDry ? ' (DRY RUN)' : '') . ' …');
    $this->newLine();

    Template::whereNotNull('image')
        ->where('image', '!=', '')
        ->whereNull('image_media_id')
        ->orderBy('id')
        ->each(function (Template $template) use ($isDry, &$linked, &$orphaned, &$skipped) {
            $mediaId = MediaPathNormalizer::resolveToMediaId($template->image);

            if ($mediaId === null) {
                $this->warn("  Orphan template #{$template->id}: image='{$template->image}' — no media record, image_media_id stays NULL");
                $orphaned++;
            } else {
                $this->line("  template #{$template->id}: image='{$template->image}' → image_media_id={$mediaId}");
                if (! $isDry) {
                    DB::table('templates')->where('id', $template->id)->update(['image_media_id' => $mediaId]);
                }
                $linked++;
            }
        });

    // Report any rows that already have image_media_id set
    $alreadySet = Template::whereNotNull('image_media_id')->count();
    if ($alreadySet > 0) {
        $this->line("  {$alreadySet} template(s) already had image_media_id set — skipped.");
    }

    $this->newLine();
    if ($isDry) {
        $this->comment("DRY RUN complete. Would link: {$linked}, orphans (stay NULL): {$orphaned}. Re-run without --dry-run to apply.");
    } else {
        $this->info("Backfill complete. Linked: {$linked}, orphans left NULL: {$orphaned}.");
    }
})->purpose('ADR-005 Wave 2: populate templates.image_media_id FK from templates.image path column.');

// ---------------------------------------------------------------------------
// ADR-005 Wave 3 — Backfill JSON media fields in portfolios + general_settings
// ---------------------------------------------------------------------------
// Targets:
//   1. portfolios.images        : JSON path array → JSON ID array
//   2. header_variant_settings  : purple_topbar.logo_override string → {id, path}
//   3. footer_variant_settings  : palgoals_marketing.logo_override string → {id, path}
//                                 palgoals_marketing.payment_logos path array → {ids, paths}
//
// Run once after deploying the Wave 3 code:
//   php artisan adr005:backfill-wave3
//   php artisan adr005:backfill-wave3 --dry-run
// ---------------------------------------------------------------------------
Artisan::command('adr005:backfill-wave3 {--dry-run : Preview changes without writing to DB}', function () {
    $isDry     = (bool) $this->option('dry-run');
    $converted = 0;
    $orphaned  = 0;
    $skipped   = 0;
    $total     = 0;

    $this->info('ADR-005 Wave 3 backfill' . ($isDry ? ' (DRY RUN — no writes)' : '') . ' …');
    $this->newLine();

    // ── 1. portfolios.images — path array → ID array ─────────────────────────
    $this->info('1/3  portfolios.images — JSON path array → JSON integer ID array');

    Portfolio::whereNotNull('images')
        ->orderBy('id')
        ->each(function (Portfolio $portfolio) use ($isDry, &$converted, &$orphaned, &$skipped, &$total) {
            $raw = $portfolio->images; // cast to array

            if (empty($raw) || ! is_array($raw)) {
                $skipped++;
                return;
            }

            $first = reset($raw);

            // Already in ID format — skip
            if (is_int($first) || (is_string($first) && ctype_digit((string) $first))) {
                $this->line("  portfolio #{$portfolio->id}: already ID format — skipped");
                $skipped++;
                return;
            }

            // Old format: array of paths — resolve to IDs
            $ids         = [];
            $localOrphan = 0;
            foreach ($raw as $path) {
                $mediaId = MediaPathNormalizer::resolveToMediaId((string) $path);
                if ($mediaId === null) {
                    $this->warn("    Orphan path '{$path}' — no media record, will be dropped");
                    $localOrphan++;
                    $orphaned++;
                } else {
                    $ids[] = $mediaId;
                }
            }

            if (empty($ids) && $localOrphan > 0) {
                $this->warn("  portfolio #{$portfolio->id}: all paths orphaned — images set to NULL");
                if (! $isDry) {
                    DB::table('portfolios')->where('id', $portfolio->id)->update(['images' => null]);
                }
            } else {
                $this->line("  portfolio #{$portfolio->id}: " . count($raw) . " path(s) → " . count($ids) . " ID(s)" . ($localOrphan ? " ({$localOrphan} orphan(s) dropped)" : ''));
                if (! $isDry) {
                    DB::table('portfolios')->where('id', $portfolio->id)->update(['images' => json_encode($ids)]);
                }
            }

            $converted++;
            $total++;
        });

    $this->info("  → {$converted} portfolio(s) converted, {$skipped} skipped, {$orphaned} orphan path(s) dropped");

    $converted = 0;
    $orphaned  = 0;

    // ── 2. header_variant_settings.purple_topbar.logo_override ───────────────
    $this->newLine();
    $this->info('2/3  header_variant_settings.purple_topbar.logo_override — string → {id, path}');

    $setting = GeneralSetting::first();
    if (! $setting) {
        $this->warn('  No general_settings row found — skipping.');
    } else {
        $headerSettings = is_array($setting->header_variant_settings) ? $setting->header_variant_settings : [];
        $pvSettings     = $headerSettings['purple_topbar'] ?? [];

        $logoOverride = $pvSettings['logo_override'] ?? null;

        if ($logoOverride === null || is_array($logoOverride)) {
            $this->line('  purple_topbar.logo_override: already converted or null — skipped');
            $skipped++;
        } else {
            $path    = MediaPathNormalizer::normalize((string) $logoOverride);
            $mediaId = MediaPathNormalizer::resolveToMediaId((string) $logoOverride);

            if ($path === null) {
                $this->warn("  purple_topbar.logo_override: cannot normalize '{$logoOverride}' — set to null");
                $newValue = null;
                $orphaned++;
            } else {
                $newValue = ['id' => $mediaId, 'path' => $path];
                if ($mediaId === null) {
                    $this->warn("  purple_topbar.logo_override: path='{$path}' has no media record (id=null)");
                    $orphaned++;
                } else {
                    $this->line("  purple_topbar.logo_override: '{$logoOverride}' → {id:{$mediaId}, path:'{$path}'}");
                }
            }

            if (! $isDry) {
                $pvSettings['logo_override']   = $newValue;
                $headerSettings['purple_topbar'] = $pvSettings;
                DB::table('general_settings')->where('id', $setting->id)
                    ->update(['header_variant_settings' => json_encode($headerSettings)]);
            }
            $converted++;
            $total++;
        }
    }

    $this->info("  → {$converted} converted, {$skipped} skipped, {$orphaned} orphan(s)");

    $converted = 0;
    $orphaned  = 0;
    $skipped   = 0;

    // ── 3. footer_variant_settings.palgoals_marketing ────────────────────────
    $this->newLine();
    $this->info('3/3  footer_variant_settings.palgoals_marketing — logo_override + payment_logos');

    $setting = GeneralSetting::first();
    if (! $setting) {
        $this->warn('  No general_settings row found — skipping.');
    } else {
        $footerSettings = is_array($setting->footer_variant_settings) ? $setting->footer_variant_settings : [];
        $fmSettings     = $footerSettings['palgoals_marketing'] ?? [];
        $modified       = false;

        // 3a. logo_override
        $logoOverride = $fmSettings['logo_override'] ?? null;
        if ($logoOverride === null || is_array($logoOverride)) {
            $this->line('  palgoals_marketing.logo_override: already converted or null — skipped');
            $skipped++;
        } else {
            $path    = MediaPathNormalizer::normalize((string) $logoOverride);
            $mediaId = MediaPathNormalizer::resolveToMediaId((string) $logoOverride);

            if ($path === null) {
                $this->warn("  palgoals_marketing.logo_override: cannot normalize '{$logoOverride}' — set to null");
                $fmSettings['logo_override'] = null;
                $orphaned++;
            } else {
                $newValue = ['id' => $mediaId, 'path' => $path];
                if ($mediaId === null) {
                    $this->warn("  palgoals_marketing.logo_override: path='{$path}' has no media record (id=null)");
                    $orphaned++;
                } else {
                    $this->line("  palgoals_marketing.logo_override: '{$logoOverride}' → {id:{$mediaId}, path:'{$path}'}");
                }
                $fmSettings['logo_override'] = $newValue;
            }
            $modified = true;
            $converted++;
            $total++;
        }

        // 3b. payment_logos
        $paymentLogos = $fmSettings['payment_logos'] ?? [];
        if (is_array($paymentLogos) && isset($paymentLogos['paths'])) {
            $this->line('  palgoals_marketing.payment_logos: already converted — skipped');
            $skipped++;
        } elseif (empty($paymentLogos)) {
            $this->line('  palgoals_marketing.payment_logos: empty array — skipped');
            $skipped++;
        } else {
            // Old format: flat array of paths
            $paths       = is_array($paymentLogos) ? array_values(array_filter($paymentLogos)) : [];
            $resolvedIds = [];
            $resolvedPaths = [];
            $localOrphan = 0;

            foreach ($paths as $path) {
                $normalizedPath = MediaPathNormalizer::normalize((string) $path);
                $mediaId        = MediaPathNormalizer::resolveToMediaId((string) $path);

                if ($normalizedPath === null) {
                    $this->warn("    payment_logo path '{$path}': cannot normalize — dropped");
                    $localOrphan++;
                    $orphaned++;
                } else {
                    if ($mediaId === null) {
                        $this->warn("    payment_logo path '{$path}': no media record (id=null kept)");
                        $orphaned++;
                    } else {
                        $this->line("    payment_logo '{$path}' → id={$mediaId}");
                    }
                    $resolvedIds[]   = $mediaId;
                    $resolvedPaths[] = $normalizedPath;
                }
            }

            if (empty($resolvedPaths)) {
                $this->warn('  palgoals_marketing.payment_logos: all paths orphaned — set to []');
                $fmSettings['payment_logos'] = [];
            } else {
                $fmSettings['payment_logos'] = ['ids' => $resolvedIds, 'paths' => $resolvedPaths];
                $this->line("  palgoals_marketing.payment_logos: " . count($paths) . " path(s) → {ids+paths} object" . ($localOrphan ? " ({$localOrphan} dropped)" : ''));
            }

            $modified = true;
            $converted++;
            $total++;
        }

        if ($modified && ! $isDry) {
            $footerSettings['palgoals_marketing'] = $fmSettings;
            DB::table('general_settings')->where('id', $setting->id)
                ->update(['footer_variant_settings' => json_encode($footerSettings)]);
        }
    }

    $this->info("  → {$converted} field(s) converted, {$skipped} skipped, {$orphaned} orphan(s)");

    $this->newLine();
    if ($isDry) {
        $this->comment("DRY RUN complete. Total operations that would run: {$total}. Re-run without --dry-run to apply.");
    } else {
        $this->info("Backfill complete. Total operations: {$total}.");
        $this->comment('Run `php artisan cache:clear` to flush cached general_settings.');
    }
})->purpose('ADR-005 Wave 3: convert portfolios.images path arrays → ID arrays; convert logo_override + payment_logos to dual-write objects in header/footer variant settings.');

// ---------------------------------------------------------------------------
// ADR-003 Phase 1 — Backfill templates.price_cents / discount_price_cents
// ---------------------------------------------------------------------------
// Run once after `php artisan migrate`:
//   php artisan adr003:backfill-template-prices --dry-run
//   php artisan adr003:backfill-template-prices
// ---------------------------------------------------------------------------
Artisan::command('adr003:backfill-template-prices {--dry-run : Preview changes without writing to DB}', function () {
    $dryRun = (bool) $this->option('dry-run');

    if ($dryRun) {
        $this->info('[dry-run] No writes will be performed.');
    }

    $templates = Template::select('id', 'price', 'price_cents', 'discount_price', 'discount_price_cents')
        ->orderBy('id')
        ->get();

    $processed     = 0;
    $updated       = 0;
    $skipped       = 0;
    $mismatches    = 0;
    $nullDiscounts = 0;

    foreach ($templates as $template) {
        $processed++;

        $expectedPriceCents    = (int) round((float) $template->price * 100);
        $discountDecimal       = $template->discount_price;
        $expectedDiscountCents = ($discountDecimal !== null && (float) $discountDecimal > 0)
            ? (int) round((float) $discountDecimal * 100)
            : null;

        if ($discountDecimal === null || (float) $discountDecimal <= 0) {
            $nullDiscounts++;
        }

        $currentPriceCents    = $template->price_cents !== null ? (int) $template->price_cents : null;
        $currentDiscountCents = $template->discount_price_cents !== null ? (int) $template->discount_price_cents : null;

        // Detect mismatches on already-populated rows
        if ($currentPriceCents !== null && $currentPriceCents !== $expectedPriceCents) {
            $mismatches++;
            $this->warn(sprintf(
                '  MISMATCH template #%d: price_cents has %d, expected %d (price=%.2f)',
                $template->id, $currentPriceCents, $expectedPriceCents, $template->price
            ));
        }
        if ($currentDiscountCents !== null && $currentDiscountCents !== $expectedDiscountCents) {
            $mismatches++;
            $this->warn(sprintf(
                '  MISMATCH template #%d: discount_price_cents has %s, expected %s (discount_price=%s)',
                $template->id,
                $currentDiscountCents,
                $expectedDiscountCents ?? 'NULL',
                $discountDecimal ?? 'NULL'
            ));
        }

        $needsUpdate = $currentPriceCents !== $expectedPriceCents
            || $currentDiscountCents !== $expectedDiscountCents;

        if (! $needsUpdate) {
            $skipped++;
            continue;
        }

        if ($dryRun) {
            $this->line(sprintf(
                '  [dry-run] template #%d: price_cents %s→%d | discount_price_cents %s→%s',
                $template->id,
                $currentPriceCents ?? 'NULL',
                $expectedPriceCents,
                $currentDiscountCents ?? 'NULL',
                $expectedDiscountCents ?? 'NULL'
            ));
        } else {
            DB::table('templates')->where('id', $template->id)->update([
                'price_cents'          => $expectedPriceCents,
                'discount_price_cents' => $expectedDiscountCents,
            ]);
            $this->line(sprintf(
                '  template #%d: price_cents=%d, discount_price_cents=%s ✓',
                $template->id,
                $expectedPriceCents,
                $expectedDiscountCents ?? 'NULL'
            ));
        }

        $updated++;
    }

    $this->newLine();
    $this->info('ADR-003 Phase 1 — templates.price_cents / discount_price_cents backfill');
    $this->table(
        ['processed', 'updated', 'skipped', 'mismatches', 'null_discounts', 'dry_run'],
        [[
            $processed,
            $updated,
            $skipped,
            $mismatches,
            $nullDiscounts,
            $dryRun ? 'YES' : 'NO',
        ]]
    );

    if ($mismatches > 0) {
        $this->warn("⚠ {$mismatches} mismatch(es) found — review before dropping decimal columns.");
    }
    if ($dryRun && $updated > 0) {
        $this->comment("Run without --dry-run to apply {$updated} update(s).");
    } elseif (! $dryRun && $updated > 0) {
        $this->info("✓ {$updated} template(s) backfilled successfully.");
    } elseif ($updated === 0) {
        $this->info('All rows already up to date — nothing to do.');
    }
})->purpose('ADR-003 Phase 1: backfill templates.price_cents and discount_price_cents from the decimal price columns.');

// ADR-003 Phase 2 — Backfill subscriptions.price_cents
// ---------------------------------------------------------------------------
// Run once after `php artisan migrate`:
//   php artisan adr003:backfill-subscription-prices --dry-run
//   php artisan adr003:backfill-subscription-prices
// ---------------------------------------------------------------------------
Artisan::command('adr003:backfill-subscription-prices {--dry-run : Preview changes without writing to DB}', function () {
    $dryRun = (bool) $this->option('dry-run');

    if ($dryRun) {
        $this->warn('--- DRY RUN: no changes will be written ---');
    }

    $processed  = 0;
    $updated    = 0;
    $skipped    = 0;
    $mismatches = 0;

    \App\Models\Tenancy\Subscription::withTrashed()
        ->select(['id', 'price', 'price_cents'])
        ->orderBy('id')
        ->chunk(200, function ($rows) use (
            $dryRun,
            &$processed,
            &$updated,
            &$skipped,
            &$mismatches
        ) {
            foreach ($rows as $row) {
                $processed++;

                $currentCents  = $row->getRawOriginal('price_cents');
                $rawDecimal    = $row->getRawOriginal('price');
                $expectedCents = (int) round((float) ($rawDecimal ?? 0) * 100);

                if ($currentCents !== null && (int) $currentCents !== $expectedCents) {
                    $mismatches++;
                    $this->warn(sprintf(
                        '  ⚠ subscription #%d: stored price_cents=%d but expected %d (price=%s)',
                        $row->id,
                        (int) $currentCents,
                        $expectedCents,
                        $rawDecimal ?? 'NULL'
                    ));
                }

                if ($currentCents !== null && (int) $currentCents === $expectedCents) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '  [dry-run] subscription #%d: price_cents %s → %d (price=%s)',
                        $row->id,
                        $currentCents ?? 'NULL',
                        $expectedCents,
                        $rawDecimal ?? 'NULL'
                    ));
                } else {
                    DB::table('subscriptions')->where('id', $row->id)->update([
                        'price_cents' => $expectedCents,
                    ]);
                    $this->line(sprintf(
                        '  subscription #%d: price_cents=%d ✓',
                        $row->id,
                        $expectedCents
                    ));
                }

                $updated++;
            }
        });

    $this->newLine();
    $this->info('ADR-003 Phase 2 — subscriptions.price_cents backfill');
    $this->table(
        ['processed', 'updated', 'skipped', 'mismatches', 'dry_run'],
        [[
            $processed,
            $updated,
            $skipped,
            $mismatches,
            $dryRun ? 'YES' : 'NO',
        ]]
    );

    if ($mismatches > 0) {
        $this->warn("⚠ {$mismatches} mismatch(es) found — review rows before switching reads to price_cents.");
    }
    if ($dryRun && $updated > 0) {
        $this->comment("Run without --dry-run to apply {$updated} update(s).");
    } elseif (! $dryRun && $updated > 0) {
        $this->info("✓ {$updated} subscription(s) backfilled successfully.");
    } elseif ($updated === 0) {
        $this->info('All rows already up to date — nothing to do.');
    }
})->purpose('ADR-003 Phase 2: backfill subscriptions.price_cents from the decimal price column.');

// ---------------------------------------------------------------------------
// Admin Brand Phase 2 — Sync design token options in existing fields
// ---------------------------------------------------------------------------
// Updates the `options` JSON column on all SectionDefinitionField rows where
// field_key is 'background_token' or 'text_token' to include the custom_1..5
// options added to DesignTokenRegistry in Phase 2.
//
// SAFE:
//   - Only updates SectionDefinitionField.options (the dropdown choices list)
//   - Does NOT touch section content / Section model values (separate table)
//   - Idempotent: rows already up-to-date are skipped (no unnecessary writes)
//
// Run once after deploying Phase 2 code:
//   php artisan admin-brand:sync-token-options
//   php artisan admin-brand:sync-token-options --dry-run
// ---------------------------------------------------------------------------
Artisan::command('admin-brand:sync-token-options {--dry-run : Preview changes without writing to DB}', function () {
    $isDry   = (bool) $this->option('dry-run');
    $updated = 0;
    $skipped = 0;

    $this->info('Admin Brand Phase 2 — syncing design token options' . ($isDry ? ' (DRY RUN)' : '') . ' …');
    $this->newLine();

    $tokenKeys = ['background_token', 'text_token'];

    foreach ($tokenKeys as $tokenKey) {
        $canonicalOptions = \App\Support\Sections\DesignTokenRegistry::options($tokenKey);

        if (empty($canonicalOptions)) {
            $this->warn("  {$tokenKey}: not found in DesignTokenRegistry — skipping");
            continue;
        }

        $rows = DB::table('section_definition_fields')
            ->where('field_key', $tokenKey)
            ->get(['id', 'options']);

        $this->info("  {$tokenKey}: {$rows->count()} row(s) found");

        foreach ($rows as $row) {
            $currentOptions = is_string($row->options) ? json_decode($row->options, true) : $row->options;
            $currentValues  = array_column((array) $currentOptions, 'value');
            $canonicalValues = array_column($canonicalOptions, 'value');

            // Already up-to-date: same values in same order
            if ($currentValues === $canonicalValues) {
                $this->line("    field #{$row->id}: already up-to-date — skipped");
                $skipped++;
                continue;
            }

            if ($isDry) {
                $added = array_diff($canonicalValues, $currentValues);
                $this->line("    field #{$row->id} [dry-run]: would add " . implode(', ', $added));
            } else {
                DB::table('section_definition_fields')
                    ->where('id', $row->id)
                    ->update(['options' => json_encode($canonicalOptions)]);
                $this->line("    field #{$row->id}: updated ✓");
            }

            $updated++;
        }
    }

    $this->newLine();
    if ($isDry) {
        $this->comment("DRY RUN complete. Would update: {$updated} field(s). Already up-to-date: {$skipped}.");
    } else {
        $this->info("Sync complete. Updated: {$updated} field(s). Skipped (already current): {$skipped}.");
    }
})->purpose('Admin Brand Phase 2: sync background_token/text_token field options with DesignTokenRegistry (idempotent).');

Schedule::command('domains:process-auto-renewals')
    ->dailyAt('02:00')
    ->withoutOverlapping();
