<?php
declare(strict_types=1);

require 'config/bootstrap.php';

// Force MySQL connection
$dbConfig = db_config();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

echo "Connected to MySQL database: {$dbConfig['database']}\n\n";

$token = 'rBDFoNEUYG8a_0DeVXnE4h5Vrnq1EZfkrexKagwtNBc';

$query = <<<'GRAPHQL'
query GetPeopleQuery($page: Int, $per_page: Int, $q: String) {
  allPeople: allPeople(page: $page, per_page: $per_page, q: $q) {
    data {
      id
      full_name
      email
      phone
      home_lc {
        name
        __typename
      }
      home_mc {
        name
        __typename
      }
      __typename
    }
    paging {
      total_items
      total_pages
      current_page
      __typename
    }
    __typename
  }
}
GRAPHQL;

function fetchEpsPage(int $page, int $perPage, string $token, string $query): ?array
{
    $payload = [
        'operationName' => 'GetPeopleQuery',
        'query' => $query,
        'variables' => [
            'page' => $page,
            'per_page' => $perPage,
            'q' => '',
        ]
    ];

    $ch = curl_init('https://gis-api.aiesec.org/graphql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Origin: https://aiesec.org',
        'Referer: https://aiesec.org/search?programmes=8',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Authorization: ' . $token
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        echo "Error fetching page $page: HTTP $httpCode\n";
        if ($error) echo "Curl error: $error\n";
        return null;
    }

    $data = json_decode((string)$response, true);
    
    if (isset($data['errors'])) {
        echo "GraphQL errors on page $page:\n";
        print_r($data['errors']);
        return null;
    }

    return $data['data']['allPeople'] ?? null;
}

function splitName(string $fullName): array
{
    $parts = array_filter(array_map('trim', explode(' ', $fullName)));
    if (count($parts) <= 1) {
        return ['first_name' => $fullName, 'last_name' => ''];
    }
    $lastName = array_pop($parts);
    $firstName = implode(' ', $parts);
    return ['first_name' => $firstName, 'last_name' => $lastName];
}

// Configuration
$perPage = 100;
$page = 1;
$totalInserted = 0;
$totalSkipped = 0;
$totalErrors = 0;
$maxPages = 280; // Full sync - all pages
$batchSize = 500; // Batch size for bulk inserts

// Columns in the exact order for INSERT
$epColumns = [
    'first_name', 'last_name', 'email', 'phone', 'nationality', 'university', 
    'field_of_study', 'opportunity_title', 'country', 'organization', 
    'application_date', 'opportunity_link', 'status', 'stage_index', 
    'folder_name', 'status_updated_at', 'created_at', 'updated_at'
];

$colNames = implode(', ', array_map(fn($c) => "`$c`", $epColumns));
$placeholdersSingle = '(' . implode(', ', array_fill(0, count($epColumns), '?')) . ')';

do {
    echo "Fetching page $page (per_page=$perPage)...\n";
    $result = fetchEpsPage($page, $perPage, $token, $query);
    
    if (!$result) {
        echo "Failed to fetch page $page, stopping.\n";
        break;
    }

    $people = $result['data'] ?? [];
    $paging = $result['paging'] ?? [];
    $totalPages = (int)($paging['total_pages'] ?? 1);
    
    echo "  Found " . count($people) . " people on page $page of $totalPages\n";

    if (empty($people)) {
        echo "  No more people, stopping.\n";
        break;
    }

    // Collect all emails from this page for bulk existence check
    $emails = [];
    $validPeople = [];
    foreach ($people as $person) {
        $fullName = trim($person['full_name'] ?? '');
        $email = trim($person['email'] ?? '');
        
        if ($fullName === '' || $email === '') {
            $totalSkipped++;
            continue;
        }
        
        $emails[] = $email;
        $validPeople[] = $person;
    }

    if (empty($validPeople)) {
        echo "  No valid people on this page\n";
        $page++;
        continue;
    }

    // Bulk check existing emails in one query
    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    $stmt = $pdo->prepare("SELECT email FROM ep_applications WHERE email IN ($placeholders)");
    $stmt->execute($emails);
    $existingEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingEmailSet = array_flip($existingEmails);

    // Filter out existing emails
    $newPeople = [];
    foreach ($validPeople as $person) {
        $email = trim($person['email'] ?? '');
        if (isset($existingEmailSet[$email])) {
            $totalSkipped++;
        } else {
            $newPeople[] = $person;
        }
    }

    if (empty($newPeople)) {
        echo "  All emails already exist in database\n";
        $page++;
        continue;
    }

    echo "  Processing " . count($newPeople) . " new EPs...\n";

    // Prepare data for batch insert
    $epInsertData = [];
    $folderPaths = [];
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $oppLink = 'https://aiesec.org/search?programmes=8';

    foreach ($newPeople as $person) {
        $fullName = trim($person['full_name'] ?? '');
        $email = trim($person['email'] ?? '');
        $phone = trim($person['phone'] ?? '');
        $homeLc = $person['home_lc']['name'] ?? 'UNIVERSITY';
        $homeMc = $person['home_mc']['name'] ?? 'Tunisia';
        $externalId = $person['id'] ?? '';

        $nameParts = splitName($fullName);
        $folderName = 'EP_' . preg_replace('/[^A-Za-z0-9]+/', '_', $fullName) . '_' . $externalId;
        $folderPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $folderName;

        // EP application data - matching exact column order
        $epInsertData[] = [
            $nameParts['first_name'],
            $nameParts['last_name'],
            $email,
            $phone ?: 'N/A',
            $homeMc,
            $homeLc,
            'Unknown',
            'AIESEC Opportunity',
            $homeMc,
            'AIESEC ' . $homeMc,
            $today,
            $oppLink,
            'applied',
            0,
            $folderName,
            $now,
            $now,  // created_at
            $now,  // updated_at
        ];

        $folderPaths[] = $folderPath;
    }

    // Batch insert in chunks
    $insertedCount = 0;
    for ($i = 0; $i < count($epInsertData); $i += $batchSize) {
        $chunk = array_slice($epInsertData, $i, $batchSize);
        $chunkSize = count($chunk);
        
        // Build multi-row INSERT
        $placeholders = implode(', ', array_fill(0, $chunkSize, $placeholdersSingle));
        $sql = "INSERT INTO `ep_applications` ($colNames) VALUES $placeholders";
        $stmt = $pdo->prepare($sql);
        
        // Flatten values
        $values = [];
        foreach ($chunk as $row) {
            $values = array_merge($values, $row);
        }
        
        $stmt->execute($values);
        $insertedCount += $chunkSize;
    }

    // Get the inserted IDs
    $firstId = (int)$pdo->lastInsertId() - $insertedCount + 1;
    
    // Create folders
    foreach ($folderPaths as $folderPath) {
        if (!is_dir($folderPath)) {
            @mkdir($folderPath, 0775, true);
        }
    }

    // Batch insert status history
    if ($insertedCount > 0) {
        $historyPlaceholders = implode(', ', array_fill(0, $insertedCount, '(?, ?, ?, ?, ?)'));
        $sql = "INSERT INTO `ep_status_history` (`ep_id`, `status`, `stage_index`, `changed_by_label`, `created_at`) VALUES $historyPlaceholders";
        $stmt = $pdo->prepare($sql);
        
        $historyValues = [];
        for ($i = 0; $i < $insertedCount; $i++) {
            $epId = $firstId + $i;
            $historyValues[] = $epId;
            $historyValues[] = 'applied';
            $historyValues[] = 0;
            $historyValues[] = 'API Sync';
            $historyValues[] = $now;
        }
        
        $stmt->execute($historyValues);

        // Batch insert notifications
        $notifPlaceholders = implode(', ', array_fill(0, $insertedCount, '(?, ?, ?, ?, ?)'));
        $sql = "INSERT INTO `ep_notifications` (`ep_id`, `notification_type`, `message`, `is_read`, `created_at`) VALUES $notifPlaceholders";
        $stmt = $pdo->prepare($sql);
        
        $notifValues = [];
        for ($i = 0; $i < $insertedCount; $i++) {
            $epId = $firstId + $i;
            $notifValues[] = $epId;
            $notifValues[] = 'created';
            $notifValues[] = 'EP synchronized from AIESEC API.';
            $notifValues[] = 0;
            $notifValues[] = $now;
        }
        
        $stmt->execute($notifValues);
    }

    $totalInserted += $insertedCount;
    echo "  ✓ Batch inserted $insertedCount EPs (IDs $firstId to " . ($firstId + $insertedCount - 1) . ")\n";

    $page++;
    
    // Reduced delay for efficiency
    if ($page <= $totalPages) {
        usleep(50000); // 50ms instead of 200ms
    }

} while ($page <= $totalPages && $page <= $maxPages);

echo "\n=== Summary ===\n";
echo "Total inserted: $totalInserted\n";
echo "Total skipped:  $totalSkipped\n";
echo "Total errors:   $totalErrors\n";
echo "Pages processed: " . ($page - 1) . "\n";
echo "\nDone!\n";