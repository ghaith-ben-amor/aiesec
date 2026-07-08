<?php
require 'config/bootstrap.php';

// Force MySQL
$dbConfig = db_config();
echo "Config driver: " . $dbConfig['driver'] . PHP_EOL;

// Create MySQL PDO directly
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$mysqlPdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$stmt = $mysqlPdo->query('SELECT COUNT(*) FROM ep_applications');
echo 'Total EPs in MySQL: ' . $stmt->fetchColumn() . PHP_EOL;

$stmt = $mysqlPdo->query('SELECT * FROM ep_applications LIMIT 5');
foreach ($stmt->fetchAll() as $row) {
    echo "ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}, Email: {$row['email']}\n";
}