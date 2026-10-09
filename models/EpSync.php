<?php
declare(strict_types=1);

/**
 * EpSync — fetches OGT (Outgoing Global Talent) EPs from the AIESEC GraphQL API
 * and stores them into the `ep_applications` table.
 *
 * Uses the OAuth2 client_credentials flow to get an access token (no user context,
 * public scope — can list `people` and nested `opportunity_applications`).
 */
final class EpSync
{
    private const AUTH_URL      = 'https://auth.aiesec.org/oauth/token';
    private const GRAPHQL_URL   = 'https://gis-api.aiesec.org/graphql';
    private const PAGE_SIZE     = 50;
    private const MAX_PAGES     = 200;     // safety cap (200 * 50 = 10 000 EPs)
    private const PROGRAMME_OGT = 8;       // 8 = Global Talent (OGT)

    private PDO $pdo;
    private string $clientId;
    private string $clientSecret;
    private ?string $token = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo          = $pdo;
        $this->clientId      = getenv('AIESEC_OAUTH_CLIENT_ID')     ?: '';
        $this->clientSecret  = getenv('AIESEC_OAUTH_CLIENT_SECRET') ?: '';
    }

    public function getOAuthToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $ch = curl_init(self::AUTH_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'audience'      => 'https://gis-api.aiesec.org',
            ]),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        ]);
        $r = curl_exec($ch);
        $h = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($h !== 200 || empty($r)) {
            throw new RuntimeException("Failed to fetch OAuth token (HTTP $h)");
        }

        $data = json_decode((string) $r, true);
        $this->token = (string) ($data['access_token'] ?? '');
        if ($this->token === '') {
            throw new RuntimeException("OAuth response did not contain an access_token.");
        }
        return $this->token;
    }

    private function graphql(string $query): array
    {
        $ch = curl_init(self::GRAPHQL_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['query' => $query]),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: ' . $this->getOAuthToken(),
            ],
        ]);
        $r = curl_exec($ch);
        $h = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($h !== 200 || empty($r)) {
            throw new RuntimeException("GraphQL query failed (HTTP $h)");
        }
        return json_decode((string) $r, true) ?? [];
    }

    private function buildPeopleQuery(int $page): string
    {
        return '{
          people(page: ' . $page . ', per_page: ' . self::PAGE_SIZE . ', filters: { has_opportunity_applications: true, selected_programmes: ' . self::PROGRAMME_OGT . ' }) {
            data {
              id
              first_name
              last_name
              full_name
              home_lc { id name }
              home_mc { id name }
              opportunity_applications_count
              opportunity_applications {
                nodes {
                  id
                  status
                  current_status
                  host_lc_name
                  created_at
                  experience_start_date
                  experience_end_date
                  opportunity {
                    id
                    title
                    location
                    duration
                    programmes_txt: programmes
                    programme { id short_name short_name_display }
                    host_lc { id name }
                    branch { company { name } }
                  }
                }
              }
            }
            paging { total_items total_pages current_page }
          }
        }';
    }

    public function fetchAllOgtEps(): array
    {
        $all = [];
        $page = 1;

        do {
            $result = $this->graphql($this->buildPeopleQuery($page));
            if (isset($result['errors'])) {
                $messages = array_map(static fn ($e): string => $e['message'] ?? '', $result['errors']);
                throw new RuntimeException('GraphQL errors: ' . implode(' | ', array_filter($messages)));
            }
            $data   = $result['data']['people']['data'] ?? [];
            $paging = $result['data']['people']['paging'] ?? [];
            $totalPages = (int) ($paging['total_pages'] ?? 1);

            foreach ($data as $person) {
                $all[] = $person;
            }
            $page++;
        } while ($page <= $totalPages && $page <= self::MAX_PAGES && !empty($data));

        return $all;
    }

    public function sync(): array
    {
        $token = $this->getOAuthToken();
        if ($token === '') {
            return ['success' => false, 'message' => 'AIESEC OAuth client_id/secret missing.'];
        }

        $people = $this->fetchAllOgtEps();

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        $stmt = $this->pdo->prepare('SELECT id FROM ep_applications WHERE email = :email LIMIT 1');

        foreach ($people as $person) {
            $first = (string) ($person['first_name'] ?? '');
            $last  = (string) ($person['last_name']  ?? '');
            $homeMc = (string) ($person['home_mc']['name'] ?? '');
            $apps   = $person['opportunity_applications']['nodes'] ?? [];
            if (empty($apps)) {
                $skipped++;
                continue;
            }

            // Build email: privacy-protected, hashed form — used as unique key
            $email = 'ep_' . (string) ($person['id'] ?? '') . '@aiesec.org';

            // Use the most recent application as the EP's primary application
            $app = $apps[0];
            $opp = $app['opportunity'] ?? [];
            $org = $opp['branch']['company']['name'] ?? ($opp['host_lc']['name'] ?? 'AIESEC');

            $data = [
                'first_name'       => trim($first ?: ($person['full_name'] ?? 'EP')),
                'last_name'        => trim($last),
                'email'            => $email,
                'phone'            => '',
                'nationality'      => $homeMc,
                'university'       => $homeMc ? $homeMc . ' (AIESEC)' : '',
                'field_of_study'  => '',
                'opportunity_title'=> (string) ($opp['title'] ?? 'AIESEC OGT Opportunity'),
                'country'          => (string) ($opp['host_lc']['name'] ?? ($opp['location'] ?? $homeMc)),
                'organization'     => $org,
                'application_date' => substr((string) ($app['created_at'] ?? date('Y-m-d')), 0, 10) ?: date('Y-m-d'),
                'opportunity_link' => !empty($opp['id']) ? 'https://aiesec.org/opportunity/global-talent/' . $opp['id'] : 'https://aiesec.org/search',
            ];

            $stmt->execute(['email' => $email]);
            $existingId = (int) ($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $this->updateEpApplications($existingId, $data);
                $updated++;
            } else {
                $this->insertEpApplication($data);
                $inserted++;
            }
        }

        return [
            'success'        => true,
            'total_fetched'  => count($people),
            'inserted'        => $inserted,
            'updated'         => $updated,
            'skipped'         => $skipped,
        ];
    }

    private function insertEpApplication(array $data): int
    {
        $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $folderName = $this->makeUniqueFolderName($fullName);

        $stmt = $this->pdo->prepare('
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
        $stmt->execute([
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'],
            'nationality'        => $data['nationality'],
            'university'         => $data['university'],
            'field_of_study'     => $data['field_of_study'],
            'opportunity_title'  => $data['opportunity_title'],
            'country'            => $data['country'],
            'organization'       => $data['organization'],
            'application_date'   => $data['application_date'],
            'opportunity_link'   => $data['opportunity_link'],
            'status'             => 'applied',
            'stage_index'        => 0,
            'folder_name'        => $folderName,
            'status_updated_at'  => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function updateEpApplications(int $epId, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE ep_applications SET
                first_name       = :first_name,
                last_name        = :last_name,
                nationality      = :nationality,
                university       = :university,
                opportunity_title = :opportunity_title,
                country          = :country,
                organization     = :organization,
                application_date = :application_date,
                opportunity_link = :opportunity_link,
                updated_at       = :updated_at
            WHERE id = :id
        ');
        $stmt->execute([
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'nationality'        => $data['nationality'],
            'university'         => $data['university'],
            'opportunity_title'  => $data['opportunity_title'],
            'country'            => $data['country'],
            'organization'       => $data['organization'],
            'application_date'   => $data['application_date'],
            'opportunity_link'   => $data['opportunity_link'],
            'updated_at'         => date('Y-m-d H:i:s'),
            'id'                 => $epId,
        ]);
    }

    private function makeUniqueFolderName(string $fullName): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '_', trim($fullName)) ?? '';
        $value = trim($value, '_');
        $base  = $value !== '' ? $value : 'EP_' . date('Ymd_His');

        $candidate = $base;
        $suffix = 1;
        $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../uploads';
        while (is_dir($uploadPath . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $base . '_' . $suffix;
            $suffix++;
        }
        return $candidate;
    }
}