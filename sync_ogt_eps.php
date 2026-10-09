<?php
/**
 * CLI runner: sync OGT (Global Talent) EPs from the AIESEC API into ep_applications.
 *
 * Usage:  php sync_ogt_eps.php
 */
declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';

echo "AIESEC OGT EP Sync\n";
echo "==================\n";

try {
    $sync = new EpSync(pdo());
    $result = $sync->sync();

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "\nDone.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}