<?php
require 'config/bootstrap.php';
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$stmt = $pdo->query('SELECT COUNT(*) FROM ep_applications');
echo "Total EPs in database: " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $pdo->query('SELECT status, COUNT(*) as cnt FROM ep_applications GROUP BY status');
echo "Status distribution:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  {$row['status']}: {$row['cnt']}\n";
}