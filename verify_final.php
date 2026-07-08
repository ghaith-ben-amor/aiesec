<?php
require 'config/bootstrap.php';

// Force MySQL connection
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

echo "=== Final Verification ===\n\n";

// Total count
$stmt = $pdo->query('SELECT COUNT(*) FROM ep_applications');
echo "Total EPs in MySQL: " . $stmt->fetchColumn() . "\n\n";

// Status distribution
$stmt = $pdo->query('SELECT status, COUNT(*) as cnt FROM ep_applications GROUP BY status');
echo "Status distribution:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  {$row['status']}: {$row['cnt']}\n";
}
echo "\n";

// Country distribution (top 10)
$stmt = $pdo->query('SELECT country, COUNT(*) as cnt FROM ep_applications GROUP BY country ORDER BY cnt DESC LIMIT 10');
echo "Top 10 countries:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  {$row['country']}: {$row['cnt']}\n";
}
echo "\n";

// Sample records
$stmt = $pdo->query('SELECT id, first_name, last_name, email, university, country, status FROM ep_applications ORDER BY id DESC LIMIT 10');
echo "Latest 10 records:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  ID: {$row['id']} - {$row['first_name']} {$row['last_name']} - {$row['email']} - {$row['university']} - {$row['country']} - {$row['status']}\n";
}
echo "\n";

// Check for status history
$stmt = $pdo->query('SELECT COUNT(*) FROM ep_status_history');
echo "Status history records: " . $stmt->fetchColumn() . "\n";

// Check for notifications
$stmt = $pdo->query('SELECT COUNT(*) FROM ep_notifications');
echo "Notifications: " . $stmt->fetchColumn() . "\n";