<?php
/**
 * ADR-005 Wave 2 — Validation Script
 * Run: http://127.0.0.1/palgoals/public/__adr005_wave2_validate.php
 * DELETE this file after use!
 */
header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=palgoalsnewtest1;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    die('<b>DB connect failed:</b> ' . htmlspecialchars($e->getMessage()));
}

$results = [];

// ── 1. Column presence ────────────────────────────────────────────────────────
$cols = $pdo->query("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'palgoalsnewtest1'
      AND TABLE_NAME   = 'templates'
      AND COLUMN_NAME IN ('image', 'image_media_id')
    ORDER BY ORDINAL_POSITION
")->fetchAll();

$results['1_columns'] = $cols;

// ── 2. FK constraint ──────────────────────────────────────────────────────────
$fk = $pdo->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME,
           DELETE_RULE, UPDATE_RULE
    FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
      ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
     AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
    WHERE kcu.TABLE_SCHEMA     = 'palgoalsnewtest1'
      AND kcu.TABLE_NAME       = 'templates'
      AND kcu.COLUMN_NAME      = 'image_media_id'
")->fetchAll();

$results['2_fk_constraint'] = $fk;

// ── 3. Row counts ─────────────────────────────────────────────────────────────
$counts = $pdo->query("
    SELECT
      COUNT(*)                                                               AS total_templates,
      SUM(image IS NOT NULL AND image != '')                                 AS has_image_path,
      SUM(image_media_id IS NOT NULL)                                        AS has_media_id,
      SUM(image IS NOT NULL AND image != '' AND image_media_id IS NOT NULL)  AS both_set,
      SUM(image IS NOT NULL AND image != '' AND image_media_id IS NULL)      AS path_only_orphaned,
      SUM(image IS NULL OR image = '')                                       AS no_image_at_all
    FROM templates
")->fetch();

$results['3_row_counts'] = $counts;

// ── 4. Detail: templates with path but no FK (orphaned after backfill) ────────
$orphaned = $pdo->query("
    SELECT id, name, image, image_media_id
    FROM templates
    WHERE image IS NOT NULL AND image != '' AND image_media_id IS NULL
    ORDER BY id
")->fetchAll();

$results['4_orphaned_templates'] = $orphaned;

// ── 5. Verify media rows that are linked ──────────────────────────────────────
$linked = $pdo->query("
    SELECT t.id AS template_id, t.image AS path_col, m.id AS media_id, m.file_path AS media_path
    FROM templates t
    JOIN media m ON m.id = t.image_media_id
    ORDER BY t.id
")->fetchAll();

$results['5_linked_templates'] = $linked;

// ── 6. File existence check for orphaned paths ────────────────────────────────
$storagePath = dirname(__DIR__) . '/storage/app/public/';
foreach ($orphaned as &$row) {
    $full = $storagePath . ltrim($row['image'], '/');
    $row['file_exists_on_disk'] = file_exists($full) ? 'YES' : 'NO';
}
unset($row);
$results['4_orphaned_templates'] = $orphaned; // update with disk check

// ── Output ────────────────────────────────────────────────────────────────────
function pass(bool $ok): string {
    return $ok ? '<span style="color:green;font-weight:bold">✓ PASS</span>'
               : '<span style="color:red;font-weight:bold">✗ FAIL</span>';
}

$colNames   = array_column($results['1_columns'], 'COLUMN_NAME');
$hasImage   = in_array('image', $colNames);
$hasMediaId = in_array('image_media_id', $colNames);
$hasFk      = count($results['2_fk_constraint']) > 0;
$fkRule     = $results['2_fk_constraint'][0]['DELETE_RULE'] ?? '—';
$orphanCount = (int)($results['3_row_counts']['path_only_orphaned'] ?? 0);
$linkedCount = (int)($results['3_row_counts']['has_media_id'] ?? 0);
?>
<!DOCTYPE html>
<html dir="ltr">
<head><meta charset="utf-8"><title>ADR-005 Wave 2 Validation</title>
<style>
body{font-family:monospace;padding:24px;background:#0f172a;color:#e2e8f0}
h2{color:#38bdf8}h3{color:#94a3b8;margin-top:2em}
table{border-collapse:collapse;width:100%;margin:8px 0}
th{background:#1e293b;color:#94a3b8;padding:6px 12px;text-align:left}
td{padding:5px 12px;border-bottom:1px solid #1e293b}
.check{margin:6px 0;font-size:1.05em}
pre{background:#1e293b;padding:12px;border-radius:6px;overflow-x:auto}
</style>
</head>
<body>
<h2>ADR-005 Wave 2 — Validation Report</h2>
<p style="color:#64748b">Generated: <?= date('Y-m-d H:i:s') ?></p>

<h3>Checklist</h3>
<div class="check"><?= pass($hasImage) ?> Column <code>templates.image</code> exists (not dropped — no-drop policy)</div>
<div class="check"><?= pass($hasMediaId) ?> Column <code>templates.image_media_id</code> exists</div>
<div class="check"><?= pass($hasFk) ?> FK constraint exists (references media.id)</div>
<div class="check"><?= pass($fkRule === 'SET NULL') ?> ON DELETE = <code><?= htmlspecialchars($fkRule) ?></code> (expected: SET NULL)</div>
<div class="check"><?= pass($linkedCount > 0) ?> At least one template has <code>image_media_id</code> linked (<?= $linkedCount ?> linked)</div>
<div class="check"><?= pass($orphanCount <= 2) ?> Orphaned after backfill ≤ 2 (actual: <?= $orphanCount ?> — these have lost files)</div>

<h3>1. Column Definitions</h3>
<table>
<tr><th>COLUMN_NAME</th><th>DATA_TYPE</th><th>IS_NULLABLE</th><th>COLUMN_TYPE</th></tr>
<?php foreach ($results['1_columns'] as $c): ?>
<tr><td><?= htmlspecialchars($c['COLUMN_NAME']) ?></td><td><?= $c['DATA_TYPE'] ?></td>
    <td><?= $c['IS_NULLABLE'] ?></td><td><?= htmlspecialchars($c['COLUMN_TYPE']) ?></td></tr>
<?php endforeach ?>
</table>

<h3>2. FK Constraint</h3>
<pre><?= htmlspecialchars(json_encode($results['2_fk_constraint'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>

<h3>3. Row Counts</h3>
<table>
<?php foreach ($results['3_row_counts'] as $k => $v): ?>
<tr><th><?= htmlspecialchars($k) ?></th><td><?= (int)$v ?></td></tr>
<?php endforeach ?>
</table>

<h3>4. Orphaned Templates (path set, no FK — should be ≤ 2 lost files)</h3>
<?php if (empty($results['4_orphaned_templates'])): ?>
<p style="color:green">None — all paths resolved to a media record ✓</p>
<?php else: ?>
<table>
<tr><th>id</th><th>name</th><th>image (path)</th><th>image_media_id</th><th>file on disk?</th></tr>
<?php foreach ($results['4_orphaned_templates'] as $r): ?>
<tr>
  <td><?= $r['id'] ?></td>
  <td><?= htmlspecialchars($r['name'] ?? '—') ?></td>
  <td style="font-size:.85em;color:#94a3b8"><?= htmlspecialchars($r['image']) ?></td>
  <td><?= htmlspecialchars($r['image_media_id'] ?? 'NULL') ?></td>
  <td style="color:<?= $r['file_exists_on_disk'] === 'YES' ? '#4ade80' : '#f87171' ?>"><?= $r['file_exists_on_disk'] ?></td>
</tr>
<?php endforeach ?>
</table>
<?php endif ?>

<h3>5. Linked Templates (image_media_id → media)</h3>
<table>
<tr><th>template_id</th><th>path_col (image)</th><th>media_id</th><th>media.file_path</th></tr>
<?php foreach ($results['5_linked_templates'] as $r): ?>
<tr>
  <td><?= $r['template_id'] ?></td>
  <td style="font-size:.85em;color:#94a3b8"><?= htmlspecialchars($r['path_col'] ?? '—') ?></td>
  <td><?= $r['media_id'] ?></td>
  <td style="font-size:.85em;color:#94a3b8"><?= htmlspecialchars($r['media_path']) ?></td>
</tr>
<?php endforeach ?>
</table>

<p style="margin-top:3em;color:#475569;font-size:.85em">⚠ Delete this file after use: <code>public/__adr005_wave2_validate.php</code></p>
</body>
</html>
