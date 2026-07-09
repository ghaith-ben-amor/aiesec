<?php
require 'config/bootstrap.php';
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== Before Deletion ===\n";
echo "Total EPs: " . $pdo->query('SELECT COUNT(*) FROM ep_applications')->fetchColumn() . "\n";
echo "Status completed: " . $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE status = 'completed'")->fetchColumn() . "\n";

// Show the completed EP
echo "\nCompleted EP details:\n";
$stmt = $pdo->query("SELECT id, first_name, last_name, email, status, application_date FROM ep_applications WHERE status = 'completed'");
foreach ($stmt as $row) {
    print_r($row);
}

echo "\n\n=== Deleting EPs with status 'completed' (finished experience) ===\n";
$count = $pdo->exec("DELETE FROM ep_applications WHERE status = 'completed'");
echo "Deleted: $count records\n";

echo "\n=== After Deletion ===\n";
echo "Total EPs remaining: " . $pdo->query('SELECT COUNT(*) FROM ep_applications')->fetchColumn() . "\n";
echo "Status completed: " . $pdo->query("SELECT COUNT(*) FROM ep_applications WHERE status = 'completed'")->fetchColumn() . "\n";

echo "\n=== Note ===\n";
echo "There are 0 EPs applied in 2024 in the database.\n";
echo "All 10,002 EPs have application_date in 2026.\n";
echo "If you also want to delete EPs from years BEFORE 2025, please specify.\n";