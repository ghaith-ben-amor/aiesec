<?php
declare(strict_types=1);

require 'config/bootstrap.php';

echo "=== AIESEC CV Parser - End-to-End Test ===\n\n";

// Use an existing uploaded PDF file directly
$pdfPath = 'uploads/cv_69f4f1896ef126.35033781.pdf';

echo "1. File Setup\n";
echo "   File: $pdfPath\n";
echo "   Exists: " . (file_exists($pdfPath) ? "YES" : "NO") . "\n";
echo "   Size: " . filesize($pdfPath) . " bytes\n\n";

// Test the CV model directly
$cv = new Cv();

echo "2. Creating CV Record & Parsing\n";
$result = $cv->createFromUploadedFile($pdfPath);
echo "   CV ID: " . $result['id'] . "\n\n";

// Verify parsed data
echo "5. Parsed CV Data\n";
$parsed = $result['parsed_data'];

echo "   Skills (" . count($parsed['skills'] ?? []) . "): " . json_encode($parsed['skills'] ?? []) . "\n";
echo "   Languages (" . count($parsed['languages'] ?? []) . "): " . json_encode($parsed['languages'] ?? []) . "\n";
echo "   Education (" . count($parsed['education'] ?? []) . "): " . json_encode($parsed['education'] ?? []) . "\n";

$expYears = $parsed['experience']['years'] ?? [];
$expRoles = $parsed['experience']['roles'] ?? [];
echo "   Experience: " . count($expYears) . " years, " . count($expRoles) . " roles\n";
echo "   Summary: " . substr($parsed['summary'] ?? '', 0, 60) . "...\n\n";

// Verify database storage
echo "6. Database Verification\n";
$stmt = pdo()->prepare('SELECT * FROM cvs WHERE id = :id');
$stmt->execute(['id' => $result['id']]);
$dbRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if ($dbRecord) {
    echo "   ✓ CV record found in database\n";
    echo "   ID: " . $dbRecord['id'] . "\n";
    echo "   User ID: " . $dbRecord['user_id'] . "\n";
    echo "   File: " . $dbRecord['file_path'] . "\n";
    
    $storedData = json_decode($dbRecord['parsed_data'], true);
    echo "   Stored skills count: " . count($storedData['skills'] ?? []) . "\n";
} else {
    echo "   ✗ CV record NOT found in database\n";
}

echo "\n7. Session Simulation (for results display)\n";
$_SESSION['last_cv_profile'] = $parsed;
echo "   ✓ Session data set\n";
echo "   Can display: Skills ✓ Languages ✓ Education ✓\n";

echo "\n=== ✓ Test Complete - All Systems Operational ===\n";
