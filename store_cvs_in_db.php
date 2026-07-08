<?php
declare(strict_types=1);

require 'config/bootstrap.php';

$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

echo "=== Adding file_content column to ep_documents ===\n";

try {
    $pdo->exec("ALTER TABLE ep_documents ADD COLUMN file_content LONGBLOB NULL AFTER mime_type");
    echo "✓ Added file_content LONGBLOB column\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "✓ file_content column already exists\n";
    } else {
        throw $e;
    }
}

echo "\n=== Reading existing CV files and storing content in database ===\n";

$stmt = $pdo->query("SELECT id, file_path FROM ep_documents WHERE document_type = 'cv' AND (file_content IS NULL OR file_content = '')");
$cvDocs = $stmt->fetchAll();

if (empty($cvDocs)) {
    echo "No CV documents need updating\n";
} else {
    echo "Found " . count($cvDocs) . " CV documents to update\n";
    
    $updateStmt = $pdo->prepare("UPDATE ep_documents SET file_content = ? WHERE id = ?");
    
    foreach ($cvDocs as $doc) {
        $filePath = $doc['file_path'];
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $updateStmt->execute([$content, $doc['id']]);
                echo "✓ Updated document ID {$doc['id']} (" . strlen($content) . " bytes)\n";
            } else {
                echo "✗ Failed to read file: $filePath\n";
            }
        } else {
            echo "✗ File not found: $filePath\n";
        }
    }
}

echo "\n=== Verification ===\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM ep_documents WHERE document_type = 'cv' AND file_content IS NOT NULL AND file_content != ''");
$storedCount = $stmt->fetchColumn();
echo "CVs with content stored in database: $storedCount\n";

$stmt = $pdo->query("SELECT id, ep_id, file_name, LENGTH(file_content) as content_size FROM ep_documents WHERE document_type = 'cv'");
foreach ($stmt->fetchAll() as $row) {
    echo "  ID {$row['id']}: EP {$row['ep_id']}, {$row['file_name']}, {$row['content_size']} bytes\n";
}

echo "\n=== Done! CVs are now stored IN the database as BLOB ===\n";