<?php
declare(strict_types=1);

require 'config/bootstrap.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the latest CV and matches from database
$db = pdo();

// Get latest CV
$stmt = $db->prepare('SELECT id, parsed_data FROM cvs ORDER BY id DESC LIMIT 1');
$stmt->execute();
$cvData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cvData) {
    die("No CV found in database\n");
}

$cvId = (int) $cvData['id'];
$profile = json_decode($cvData['parsed_data'], true) ?? [];

// Get matches for this CV
$stmt = $db->prepare(
    'SELECT m.*, o.title, o.description, o.location, o.source_url, o.skills
     FROM matches m
     JOIN opportunities o ON m.opportunity_id = o.id
     WHERE m.cv_id = ?
     ORDER BY m.score DESC'
);
$stmt->execute([$cvId]);
$dbMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set session data for results page
$_SESSION['last_cv_id'] = $cvId;
$_SESSION['last_cv_profile'] = $profile;
$_SESSION['last_matches'] = array_map(function($match) {
    return [
        'title' => $match['title'],
        'description' => $match['description'],
        'location' => $match['location'],
        'score' => (float) $match['score'],
        'matched_skills' => [],
        'source_url' => $match['source_url'],
    ];
}, $dbMatches);
$_SESSION['flash_success'] = 'Test: Loaded latest CV and matches from database.';

echo "=== TEST RESULTS DISPLAY ===\n\n";
echo "✓ CV loaded: ID {$cvId}\n";
echo "✓ Skills: " . count($profile['skills']) . " detected\n";
echo "✓ Languages: " . count($profile['languages']) . " detected\n";
echo "✓ Matches: " . count($_SESSION['last_matches']) . " opportunities\n\n";

echo "Top 3 Matches with Links:\n";
foreach (array_slice($_SESSION['last_matches'], 0, 3) as $match) {
    echo "- " . $match['title'] . " ({$match['score']}%)\n";
    echo "  Link: " . $match['source_url'] . "\n";
}

echo "\n✓ Session data prepared. Navigate to: http://localhost/Aiesec/results\n";
