<?php
require 'config/bootstrap.php';

$token = 'rBDFoNEUYG8a_0DeVXnE4h5Vrnq1EZfkrexKagwtNBc';

$query = <<<'GRAPHQL'
query GetPeopleQuery($page: Int, $per_page: Int, $q: String, $filters: PersonFilter) {
  allPeople: allPeople(page: $page, per_page: $per_page, q: $q, filters: $filters) {
    data {
      id
      full_name
      email
      phone
      birthdate
      nationality
      home_lc {
        name
        __typename
      }
      home_mc {
        name
        __typename
      }
      current_role
      programme {
        short_name
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

$payload = [
    'operationName' => 'GetPeopleQuery',
    'query' => $query,
    'variables' => [
        'page' => 1,
        'per_page' => 10,
        'q' => '',
        'filters' => [
            'programmes' => [8],
        ]
    ]
];

$ch = curl_init('https://gis-api.aiesec.org/graphql');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "Curl error: $error\n";
} else {
    echo "Response:\n";
    echo $response;
}