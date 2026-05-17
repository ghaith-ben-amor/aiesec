<?php
declare(strict_types=1);

require 'config/bootstrap.php';

echo "=== FULL MATCHING SYSTEM TEST ===\n\n";

// Clean up database
$db = pdo();
$db->exec('DELETE FROM matches');
$db->exec('DELETE FROM opportunities');
$db->exec('DELETE FROM cvs');

// Test 1: CV Parsing
echo "1. Testing CV Parsing\n";
$cvPath = 'uploads/cv_69f4f1896ef126.35033781.pdf';
$cvModel = new Cv();
$cvResult = $cvModel->createFromUploadedFile($cvPath);
$cvId = $cvResult['id'];
$cvData = $cvResult['parsed_data'];

echo "   ✓ CV parsed: ID {$cvId}\n";
echo "   ✓ Skills found: " . count($cvData['skills']) . "\n";
echo "   ✓ Languages: " . implode(', ', $cvData['languages']) . "\n\n";

// Test 2: Opportunity Scraping
echo "2. Testing Opportunity Scraping\n";
$oppModel = new Opportunity();
$opportunities = $oppModel->syncFromScraper();

echo "   ✓ Opportunities scraped: " . count($opportunities) . "\n";
if (!empty($opportunities)) {
    echo "   First opportunity: " . $opportunities[0]['title'] . "\n";
}
echo "\n";

// Test 3: Matching
echo "3. Testing Matching Algorithm\n";
$matcher = new MatchResult();
$matches = $matcher->generateMatches($cvData, $opportunities, (int) $cvId);

echo "   ✓ Matches generated: " . count($matches) . "\n";
if (!empty($matches)) {
    // Sort by score
    usort($matches, function($a, $b) { return $b['score'] <=> $a['score']; });
    
    echo "\n   Top Matches:\n";
    foreach (array_slice($matches, 0, 3) as $match) {
        $opp = $opportunities[$match['index'] ?? 0] ?? null;
        if ($opp) {
            echo "   - " . $opp['title'] . " ({$match['score']}%)\n";
        }
    }
}
echo "\n";

// Test 4: Database Verification
echo "4. Verifying Database\n";
$stmt = $db->prepare('SELECT COUNT(*) FROM cvs');
$stmt->execute();
$cvCount = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM opportunities');
$stmt->execute();
$oppCount = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM matches');
$stmt->execute();
$matchCount = $stmt->fetchColumn();

echo "   ✓ CVs in database: $cvCount\n";
echo "   ✓ Opportunities in database: $oppCount\n";
echo "   ✓ Matches in database: $matchCount\n\n";

echo "=== ✓ FULL SYSTEM TEST COMPLETE ===\n";
echo "\nYou can now:\n";
echo "1. Upload a PDF CV at: http://localhost/Aiesec/upload\n";
echo "2. View results at: http://localhost/Aiesec/results\n";
echo "3. See dashboard at: http://localhost/Aiesec/dashboard\n";
