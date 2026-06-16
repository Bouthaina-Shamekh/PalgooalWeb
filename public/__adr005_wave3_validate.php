<?php
/**
 * ADR-005 Wave 3 — Browser-runnable validation script
 *
 * Checks:
 *  1. portfolios.images — detects any remaining path arrays (not yet backfilled)
 *  2. header_variant_settings.purple_topbar.logo_override — string vs object
 *  3. footer_variant_settings.palgoals_marketing.logo_override — string vs object
 *  4. footer_variant_settings.palgoals_marketing.payment_logos — flat array vs {ids,paths}
 *
 * USAGE: Open https://your-domain/public/__adr005_wave3_validate.php in a browser.
 * DELETE this file after use — it must not remain on production.
 */

// ── Bootstrap Laravel ─────────────────────────────────────────────────────────
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ── Helpers ──────────────────────────────────────────────────────────────────
function row(string $label, bool $ok, string $detail = ''): string
{
    $icon  = $ok ? '✅' : '❌';
    $color = $ok ? '#15803d' : '#b91c1c';
    $bg    = $ok ? '#f0fdf4' : '#fff1f2';
    return "<tr style='background:{$bg}'>"
         . "<td style='padding:6px 10px;font-size:18px'>{$icon}</td>"
         . "<td style='padding:6px 10px;color:{$color};font-weight:600'>{$label}</td>"
         . "<td style='padding:6px 10px;color:#374151'>" . htmlspecialchars($detail) . "</td>"
         . "</tr>";
}

function warn(string $label, string $detail): string
{
    return "<tr style='background:#fffbeb'>"
         . "<td style='padding:6px 10px;font-size:18px'>⚠️</td>"
         . "<td style='padding:6px 10px;color:#b45309;font-weight:600'>{$label}</td>"
         . "<td style='padding:6px 10px;color:#374151'>" . htmlspecialchars($detail) . "</td>"
         . "</tr>";
}

$rows = '';

// ── 1. portfolios.images ──────────────────────────────────────────────────────
$portfolios = DB::table('portfolios')
    ->whereNotNull('images')
    ->where('images', '!=', '')
    ->whereNull('deleted_at')
    ->select('id', 'images')
    ->get();

$alreadyId    = 0;
$stillPaths   = 0;
$nullEmpty    = 0;
$problemIds   = [];

foreach ($portfolios as $p) {
    $decoded = json_decode($p->images, true);
    if (!is_array($decoded) || empty($decoded)) {
        $nullEmpty++;
        continue;
    }
    $first = reset($decoded);
    if (is_int($first) || (is_string($first) && ctype_digit((string) $first))) {
        $alreadyId++;
    } else {
        $stillPaths++;
        $problemIds[] = $p->id;
    }
}

$totalPortfolios = $portfolios->count();

if ($stillPaths === 0) {
    $rows .= row(
        "portfolios.images — all converted",
        true,
        "{$alreadyId} portfolio(s) using ID format, {$nullEmpty} empty/null"
    );
} else {
    $rows .= row(
        "portfolios.images — NOT fully converted",
        false,
        "{$stillPaths} portfolio(s) still use path format: IDs " . implode(', ', $problemIds)
    );
}

// ── 2. header_variant_settings — purple_topbar.logo_override ────────────────
$setting = DB::table('general_settings')->first();
$headerSettings = $setting ? json_decode($setting->header_variant_settings ?? '{}', true) : [];
$pvSettings     = $headerSettings['purple_topbar'] ?? null;

if ($pvSettings === null) {
    $rows .= warn('header_variant_settings.purple_topbar', 'No purple_topbar key — variant may not be configured yet');
} else {
    $lo = $pvSettings['logo_override'] ?? null;
    if ($lo === null) {
        $rows .= row('purple_topbar.logo_override', true, 'null (no override set)');
    } elseif (is_array($lo) && isset($lo['id'], $lo['path'])) {
        $rows .= row('purple_topbar.logo_override', true, "object {id:{$lo['id']}, path:'{$lo['path']}'}");
    } elseif (is_string($lo)) {
        $rows .= row('purple_topbar.logo_override — NOT converted', false, "still a raw string: '{$lo}'");
    } else {
        $rows .= row('purple_topbar.logo_override — unexpected format', false, json_encode($lo));
    }
}

// ── 3. footer_variant_settings — palgoals_marketing ─────────────────────────
$footerSettings = $setting ? json_decode($setting->footer_variant_settings ?? '{}', true) : [];
$fmSettings     = $footerSettings['palgoals_marketing'] ?? null;

if ($fmSettings === null) {
    $rows .= warn('footer_variant_settings.palgoals_marketing', 'No palgoals_marketing key — variant may not be configured yet');
} else {
    // 3a. logo_override
    $lo = $fmSettings['logo_override'] ?? null;
    if ($lo === null) {
        $rows .= row('palgoals_marketing.logo_override', true, 'null (no override set)');
    } elseif (is_array($lo) && isset($lo['id'], $lo['path'])) {
        $rows .= row('palgoals_marketing.logo_override', true, "object {id:{$lo['id']}, path:'{$lo['path']}'}");
    } elseif (is_string($lo)) {
        $rows .= row('palgoals_marketing.logo_override — NOT converted', false, "still a raw string: '{$lo}'");
    } else {
        $rows .= row('palgoals_marketing.logo_override — unexpected format', false, json_encode($lo));
    }

    // 3b. payment_logos
    $pl = $fmSettings['payment_logos'] ?? [];
    if (empty($pl)) {
        $rows .= row('palgoals_marketing.payment_logos', true, 'empty (no payment logos configured)');
    } elseif (is_array($pl) && isset($pl['ids'], $pl['paths'])) {
        $count = count($pl['paths']);
        $rows .= row('palgoals_marketing.payment_logos', true, "{$count} logo(s) in {ids+paths} dual-write format");
    } elseif (is_array($pl)) {
        $count = count($pl);
        $rows .= row('palgoals_marketing.payment_logos — NOT converted', false, "{$count} path(s) still in flat array format");
    } else {
        $rows .= row('palgoals_marketing.payment_logos — unexpected format', false, json_encode($pl));
    }
}

// ── 4. Orphan check: portfolios with IDs pointing to deleted media ───────────
$idPortfolios = DB::table('portfolios')
    ->whereNotNull('images')
    ->where('images', '!=', '')
    ->whereNull('deleted_at')
    ->select('id', 'images')
    ->get();

$orphanCount = 0;
foreach ($idPortfolios as $p) {
    $decoded = json_decode($p->images, true);
    if (!is_array($decoded) || empty($decoded)) continue;
    $first = reset($decoded);
    if (!is_int($first) && !(is_string($first) && ctype_digit((string) $first))) continue;

    $ids   = array_map('intval', $decoded);
    $found = DB::table('media')->whereIn('id', $ids)->count();
    if ($found < count($ids)) {
        $orphanCount++;
        $rows .= warn("Portfolio #{$p->id} has orphan media IDs", count($ids) - $found . " ID(s) not found in media table");
    }
}
if ($orphanCount === 0 && $alreadyId > 0) {
    $rows .= row('No orphan media IDs in portfolios.images', true, "All {$alreadyId} converted portfolio(s) have valid media references");
}

// ── Output ────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>ADR-005 Wave 3 Validation</title>
<style>
body { font-family: system-ui, sans-serif; background: #f9fafb; padding: 32px; color: #111; }
h1 { color: #1e40af; }
h2 { color: #374151; font-size: 1rem; margin-top: 2rem; }
table { border-collapse: collapse; width: 100%; max-width: 900px; }
td, th { border: 1px solid #e5e7eb; }
th { background: #f3f4f6; padding: 8px 10px; text-align: left; }
.note { color: #6b7280; font-size: 0.85rem; margin-top: 1rem; }
</style>
</head>
<body>
<h1>ADR-005 Wave 3 — Validation Checklist</h1>
<p>Run: <?= date('Y-m-d H:i:s') ?></p>
<p class="note">⚠️ Delete this file from production after use.</p>

<h2>Results</h2>
<table>
<thead>
  <tr>
    <th>Status</th>
    <th>Check</th>
    <th>Detail</th>
  </tr>
</thead>
<tbody>
<?= $rows ?>
</tbody>
</table>

<h2>Next steps if any ❌ rows appear</h2>
<pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:8px;overflow:auto">
php artisan adr005:backfill-wave3 --dry-run
php artisan adr005:backfill-wave3
php artisan cache:clear
</pre>

<p class="note">
  Reminder: old path columns are NOT dropped — this is a no-drop migration per ADR-005 policy.
</p>
</body>
</html>
