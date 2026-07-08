<?php
require 'config/bootstrap.php';
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== EP Tables in Database ===\n";
$stmt = $pdo->query('SHOW TABLES LIKE "ep_%"');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\n=== ep_applications Structure ===\n";
$stmt = $pdo->query('DESCRIBE ep_applications');
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== Sample Data (5 rows) ===\n";
$stmt = $pdo->query('SELECT id, first_name, last_name, email, status FROM ep_applications LIMIT 5');
foreach ($stmt->fetchAll() as $row) {
    echo "ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}, Email: {$row['email']}, Status: {$row['status']}\n";
}

echo "\n=== All EP-related tables ===\n";
$stmt = $pdo->query('SHOW TABLES LIKE "ep_%"');
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    $count = $stmt->fetchColumn();
    echo "$table: $count records\n";
}