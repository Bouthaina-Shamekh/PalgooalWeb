<?php
// ADR-005 Wave 2 Audit — standalone (no Laravel bootstrap)
// DELETE after use.
header('Content-Type: application/json; charset=utf-8');

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=palgoalsnewtest1;charset=utf8mb4',
    'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Counts
$total    = $pdo->query("SELECT COUNT(*) FROM templates")->fetchColumn();
$withImg  = $pdo->query("SELECT COUNT(*) FROM templates WHERE image IS NOT NULL AND image != ''")->fetchColumn();
$noImg    = $total - $withImg;

// All template images
$rows = $pdo->query("SELECT id, image FROM templates WHERE image IS NOT NULL AND image != '' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// All media paths (for matching)
$mediaPaths = $pdo->query("SELECT id, file_path FROM media")->fetchAll(PDO::FETCH_ASSOC);
$mediaByPath = [];
foreach ($mediaPaths as $m) { $mediaByPath[$m['file_path']] = (int)$m['id']; }

$storageBase = __DIR__ . '/../storage/app/public/';

$matched = []; $orphanDisk = []; $orphanMissing = [];

foreach ($rows as $r) {
    $img   = ltrim((string)$r['image'], '/');
    $tplId = (int)$r['id'];

    if (isset($mediaByPath[$img])) {
        $matched[] = ['template_id' => $tplId, 'image' => $img, 'media_id' => $mediaByPath[$img]];
        continue;
    }

    $diskPath = $storageBase . $img;
    if (file_exists($diskPath)) {
        $orphanDisk[] = [
            'template_id' => $tplId,
            'image'        => $img,
            'size_bytes'   => filesize($diskPath),
            'extension'    => strtolower(pathinfo($diskPath, PATHINFO_EXTENSION)),
            'mime'         => mime_content_type($diskPath),
        ];
    } else {
        $orphanMissing[] = ['template_id' => $tplId, 'image' => $img];
    }
}

echo json_encode([
    'summary' => [
        'total_templates'          => (int)$total,
        'templates_with_image'     => (int)$withImg,
        'templates_without_image'  => (int)$noImg,
        'matched_to_media'         => count($matched),
        'orphan_on_disk'           => count($orphanDisk),
        'orphan_missing_disk'      => count($orphanMissing),
    ],
    'matched'        => $matched,
    'orphan_on_disk' => $orphanDisk,
    'orphan_missing' => $orphanMissing,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
