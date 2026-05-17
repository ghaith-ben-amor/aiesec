<?php
declare(strict_types=1);

session_start();

require 'config/bootstrap.php';

// Get the latest CV and matches from database
$db = pdo();

// Get latest CV
$stmt = $db->prepare('SELECT id, parsed_data FROM cvs ORDER BY id DESC LIMIT 1');
$stmt->execute();
$cvData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cvData) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'No CV found']);
    exit;
}

$cvId = (int) $cvData['id'];
$profile = json_decode($cvData['parsed_data'], true) ?? [];

// Get matches with full opportunity details
$stmt = $db->prepare(
    'SELECT m.*, o.title, o.description, o.location, o.source_url, o.skills
     FROM matches m
     JOIN opportunities o ON m.opportunity_id = o.id
     WHERE m.cv_id = ?
     ORDER BY m.score DESC'
);
$stmt->execute([$cvId]);
$dbMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare matches with proper structure
$matches = array_map(function($match) {
    return [
        'title' => $match['title'],
        'description' => $match['description'],
        'location' => $match['location'],
        'score' => (float) $match['score'],
        'matched_skills' => [],
        'source_url' => $match['source_url'],
    ];
}, $dbMatches);

// Set session
$_SESSION['last_cv_id'] = $cvId;
$_SESSION['last_cv_profile'] = $profile;
$_SESSION['last_matches'] = $matches;
$_SESSION['flash_success'] = 'CV and results loaded successfully.';

// Redirect to results page
header('Location: /Aiesec/results');
exit;
