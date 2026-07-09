<?php
require 'config/bootstrap.php';
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== Current EP Data Summary ===\n";
echo "Total EPs: " . $pdo->query('SELECT COUNT(*) FROM ep_applications')->fetchColumn() . "\n";

echo "\nEPs by status:\n";
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM ep_applications GROUP BY status ORDER BY cnt DESC");
foreach ($stmt as $row) {
    echo "  {$row['status']}: {$row['cnt']}\n";
}

echo "\nEPs by year of application_date:\n";
$stmt = $pdo->query("SELECT YEAR(application_date) as yr, COUNT(*) as cnt FROM ep_applications WHERE application_date IS NOT NULL GROUP BY yr ORDER BY yr DESC");
foreach ($stmt as $row) {
    echo "  {$row['yr']}: {$row['cnt']}\n";
}

echo "\nEPs with NULL application_date: ";
echo $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE application_date IS NULL")->fetchColumn() . "\n";

echo "\nEPs applied in 2024 (all statuses): ";
echo $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE YEAR(application_date) = 2024")->fetchColumn() . "\n";

echo "EPs with status='completed' (finished experience): ";
echo $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE status = 'completed'")->fetchColumn() . "\n";

echo "\n=== To be DELETED ===\n";
echo "Applied in 2024: ";
echo $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE YEAR(application_date) = 2024")->fetchColumn() . "\n";

echo "Status completed: ";
echo $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE status = 'completed'")->fetchColumn() . "\n";

echo "\nRemaining after delete (not 2024 and not completed): ";
echo $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE (YEAR(application_date) != 2024 OR application_date IS NULL) AND status != 'completed'")->fetchColumn() . "\n";