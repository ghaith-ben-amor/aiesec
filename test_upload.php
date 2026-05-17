<?php
declare(strict_types=1);

// This script simulates uploading a test CV and displays results
// Access it via: http://localhost/Aiesec/test_upload.php

session_start();

require 'config/bootstrap.php';

// Get the latest test PDF that we know exists
$testPdf = 'uploads/cv_69f4f1896ef126.35033781.pdf';

if (!file_exists($testPdf)) {
    die("Test PDF not found at: $testPdf");
}

// Parse the CV
$cvModel = new Cv();
try {
    $cvData = $cvModel->createFromUploadedFile($testPdf);
} catch (Exception $e) {
    die("Error parsing CV: " . $e->getMessage());
}

// Get opportunities
$opportunityModel = new Opportunity();
$opportunities = $opportunityModel->syncFromScraper();

if (empty($opportunities)) {
    die("No opportunities found");
}

// Generate matches
$matcher = new MatchResult();
$matches = $matcher->generateMatches($cvData['parsed_data'], $opportunities, (int) $cvData['id']);

// Set session data for results view
$_SESSION['flash_success'] = 'Test CV uploaded and analyzed successfully.';
$_SESSION['last_cv_id'] = (int) $cvData['id'];
$_SESSION['last_cv_profile'] = $cvData['parsed_data'];

// Enhance matches with full opportunity data
$enhancedMatches = [];
foreach ($matches as $match) {
    $enhancedMatches[] = [
        'title' => $match['title'] ?? 'Unknown',
        'description' => $match['description'] ?? '',
        'location' => $match['location'] ?? 'Global',
        'score' => $match['score'] ?? 0,
        'matched_skills' => $match['matched_skills'] ?? [],
        'source_url' => $match['source_url'] ?? 'https://aiesec.org/search?programmes=8',
    ];
}

$_SESSION['last_matches'] = $enhancedMatches;

// Redirect to results page
header('Location: /Aiesec/results');
exit;
