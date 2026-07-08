<?php
require 'config/bootstrap.php';
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== ep_documents Structure ===\n";
$stmt = $pdo->query('DESCRIBE ep_documents');
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== ep_documents Data ===\n";
$stmt = $pdo->query('SELECT * FROM ep_documents');
foreach ($stmt->fetchAll() as $row) {
    print_r($row);
}

echo "\n=== Check if CVs have file content in database ===\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM ep_documents WHERE document_type = 'cv'");
$cvCount = $stmt->fetchColumn();
echo "CV documents: $cvCount\n";

$stmt = $pdo->query("SELECT ep_id, document_type, file_name, file_path FROM ep_documents WHERE document_type = 'cv' LIMIT 5");
foreach ($stmt->fetchAll() as $row) {
    echo "EP {$row['ep_id']}: {$row['file_name']} - {$row['file_path']}\n";
    if (file_exists($row['file_path'])) {
        $size = filesize($row['file_path']);
        echo "  File exists: $size bytes\n";
    } else {
        echo "  File NOT found at path\n";
    }
}