<?php
// ⚠️ احذف هذا الملف فوراً بعد الاستخدام
$base = dirname(__DIR__);

$files = [
    $base . '/bootstrap/cache/routes-v7.php',
    $base . '/bootstrap/cache/config.php',
    $base . '/bootstrap/cache/packages.php',
    $base . '/bootstrap/cache/services.php',
];

echo '<pre>';
foreach ($files as $f) {
    if (file_exists($f)) {
        unlink($f) ? print("✅ Deleted: $f\n") : print("❌ Failed: $f\n");
    } else {
        echo "— Not found: $f\n";
    }
}

// Clear compiled views
$views = glob($base . '/storage/framework/views/*.php');
$count = 0;
foreach ($views ?? [] as $v) {
    if (unlink($v)) $count++;
}
echo "✅ Cleared $count compiled views\n";

echo "\nDone. DELETE THIS FILE NOW.\n";
echo '</pre>';
