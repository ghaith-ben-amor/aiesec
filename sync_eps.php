<?php
declare(strict_types=1);

require 'config/bootstrap.php';

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

function fetchEpsPage(int $page, int $perPage, string $token): ?array
{
    $payload = [
        'operationName' => 'GetPeopleQuery',
        'query' => $GLOBALS['query'],
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

// Main execution
echo "=== AIESEC EP Synchronization ===\n\n";

$perPage = 100;
$page = 1;
$totalInserted = 0;
$totalSkipped = 0;
$totalErrors = 0;
$maxPages = 100; // Safety limit

do {
    echo "Fetching page $page (per_page=$perPage)...\n";
    $result = fetchEpsPage($page, $perPage, $token);
    
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

    foreach ($people as $person) {
        $fullName = trim($person['full_name'] ?? '');
        $email = trim($person['email'] ?? '');
        $phone = trim($person['phone'] ?? '');
        $homeLc = $person['home_lc']['name'] ?? 'UNIVERSITY';
        $homeMc = $person['home_mc']['name'] ?? 'Tunisia';
        $externalId = $person['id'] ?? '';

        // Skip if no email or name
        if ($fullName === '' || $email === '') {
            echo "  Skipping: missing name or email\n";
            $totalSkipped++;
            continue;
        }

        // Check if already exists by email
        $stmt = pdo()->prepare('SELECT COUNT(*) FROM ep_applications WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ((int)$stmt->fetchColumn() > 0) {
            echo "  Skipping: $email already exists\n";
            $totalSkipped++;
            continue;
        }

        $nameParts = splitName($fullName);
        
        try {
            $stmt = pdo()->prepare('
                INSERT INTO ep_applications (
                    first_name, last_name, email, phone, nationality, university, field_of_study,
                    opportunity_title, country, organization, application_date, opportunity_link,
                    status, stage_index, folder_name, status_updated_at
                ) VALUES (
                    :first_name, :last_name, :email, :phone, :nationality, :university, :field_of_study,
                    :opportunity_title, :country, :organization, :application_date, :opportunity_link,
                    :status, :stage_index, :folder_name, :status_updated_at
                )
            ');
            
            $folderName = 'EP_' . preg_replace('/[^A-Za-z0-9]+/', '_', $fullName) . '_' . $externalId;
            
            $stmt->execute([
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'email' => $email,
                'phone' => $phone ?: 'N/A',
                'nationality' => $homeMc,
                'university' => $homeLc,
                'field_of_study' => 'Unknown',
                'opportunity_title' => 'AIESEC Opportunity',
                'country' => $homeMc,
                'organization' => 'AIESEC ' . $homeMc,
                'application_date' => date('Y-m-d'),
                'opportunity_link' => 'https://aiesec.org/search?programmes=8',
                'status' => 'applied',
                'stage_index' => 0,
                'folder_name' => $folderName,
                'status_updated_at' => date('Y-m-d H:i:s'),
            ]);

            $epId = (int)pdo()->lastInsertId();
            
            // Create folder for documents
            $folderPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $folderName;
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0775, true);
            }

            // Add status history
            $stmt = pdo()->prepare('
                INSERT INTO ep_status_history (ep_id, status, stage_index, changed_by_label, created_at)
                VALUES (:ep_id, :status, :stage_index, :changed_by_label, :created_at)
            ');
            $stmt->execute([
                'ep_id' => $epId,
                'status' => 'applied',
                'stage_index' => 0,
                'changed_by_label' => 'API Sync',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Add notification
            $stmt = pdo()->prepare('
                INSERT INTO ep_notifications (ep_id, notification_type, message, is_read, created_at)
                VALUES (:ep_id, :notification_type, :message, 0, :created_at)
            ');
            $stmt->execute([
                'ep_id' => $epId,
                'notification_type' => 'created',
                'message' => 'EP synchronized from AIESEC API.',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            echo "  ✓ Inserted: $fullName ($email) - ID: $epId\n";
            $totalInserted++;
            
        } catch (Throwable $e) {
            echo "  ✗ Error inserting $fullName: " . $e->getMessage() . "\n";
            $totalErrors++;
        }
    }

    $page++;
    
    // Small delay to be nice to the API
    if ($page <= $totalPages) {
        usleep(200000); // 200ms
    }

} while ($page <= $totalPages && $page <= $maxPages);

echo "\n=== Summary ===\n";
echo "Total inserted: $totalInserted\n";
echo "Total skipped:  $totalSkipped\n";
echo "Total errors:   $totalErrors\n";
echo "Pages processed: " . ($page - 1) . "\n";
echo "\nDone!\n";