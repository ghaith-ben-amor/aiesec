<?php
declare(strict_types=1);

require 'config/bootstrap.php';

echo "=== Testing OpportunityController Flow ===\n\n";

// Manually set $_FILES as if a form was submitted
$_FILES['cv'] = [
    'name' => 'test_cv.pdf',
    'type' => 'application/pdf',
    'tmp_name' => __DIR__ . '/uploads/cv_69f4f1896ef126.35033781.pdf',
    'error' => UPLOAD_ERR_OK,
    'size' => filesize(__DIR__ . '/uploads/cv_69f4f1896ef126.35033781.pdf'),
];

// Initialize the controller
$controller = new OpportunityController();

echo "1. Testing create() - Display upload form\n";
try {
    $controller->create();
    echo "   ✓ Upload form would be displayed\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "2. Testing store() - Process upload and set session\n";
try {
    $controller->store();
    echo "   ✓ Store method executed\n";
    echo "   Session has last_cv_id: " . (isset($_SESSION['last_cv_id']) ? "YES (" . $_SESSION['last_cv_id'] . ")" : "NO") . "\n";
    echo "   Session has last_cv_profile: " . (isset($_SESSION['last_cv_profile']) ? "YES" : "NO") . "\n";
    
    if (isset($_SESSION['last_cv_profile'])) {
        $profile = $_SESSION['last_cv_profile'];
        echo "\n   Profile Data:\n";
        echo "   - Skills: " . json_encode($profile['skills'] ?? []) . "\n";
        echo "   - Languages: " . json_encode($profile['languages'] ?? []) . "\n";
        echo "   - Education: " . json_encode($profile['education'] ?? []) . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "3. Testing results() - Display results page\n";
try {
    $controller->results();
    echo "   ✓ Results method would display page\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "4. Testing dashboard() - Show dashboard\n";
try {
    $controller->dashboard();
    echo "   ✓ Dashboard method would display\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "=== Controller Flow Test Complete ===\n";
