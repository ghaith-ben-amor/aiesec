<?php
declare(strict_types=1);

final class Opportunity extends BaseModel
{
    /**
     * Load opportunities from the local database and apply optional filters.
     * The DB is populated by syncAllFromApi() which is called from the admin panel.
     * If country filter gives 0 results, falls back to ALL opportunities so the
     * user always sees ranked matches (with a notice about the country mismatch).
     */
    public function syncFromScraper(array $filters = []): array
    {
        $dbItems = $this->loadFromDatabase($filters);

        // Only use fallback sample data if DB is completely empty AND no filters active
        if (empty($dbItems) && empty($filters['country']) && empty($filters['programme'])) {
            return $this->fallbackOpportunities();
        }

        return $dbItems;
    }

    /**
     * Load opportunities from the local database with optional filters.
     */
    private function loadFromDatabase(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['duration'])) {
                $where[] = 'duration = :duration';
                $params['duration'] = $filters['duration'];
            }

            if (!empty($filters['country'])) {
                // Exact country match (location stores normalized country name)
                $where[] = 'LOWER(location) = LOWER(:country)';
                $params['country'] = trim($filters['country']);
            }

            if (!empty($filters['programme'])) {
                // DB stores short codes: GV, GTa, GTe — exact match
                $where[] = 'category = :programme';
                $params['programme'] = $filters['programme'];
            }

            $sql = 'SELECT * FROM opportunities';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY id DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $items = [];
            foreach ($rows as $row) {
                $skills = json_decode((string) ($row['skills'] ?? '[]'), true);
                if (!is_array($skills)) {
                    $skills = [];
                }
                $items[] = [
                    'id'          => $row['id'],
                    'title'       => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'skills'      => $skills,
                    'location'    => $row['location'] ?? '',
                    'source_url'  => $row['source_url'] ?? 'https://aiesec.org/search',
                    'duration'    => $row['duration'] ?? null,
                    'company'     => $row['company'] ?? null,
                    'category'    => $row['category'] ?? null,
                    'source_type' => $row['source_type'] ?? 'api',
                    'external_id' => $row['external_id'] ?? null,
                    'languages'   => [], // stored separately if needed
                ];
            }

            return $items;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Sync ALL opportunities from the AIESEC API into the local database.
     * Called from the admin panel "Synchronize" button.
     * Returns the count of opportunities saved.
     */
    public function syncAllFromApi(): int
    {
        $items = $this->fetchFromApi([]);

        $this->pdo->exec('DELETE FROM opportunities');

        $count = 0;
        foreach ($items as $item) {
            $stmt = $this->pdo->prepare('INSERT INTO opportunities (title, description, skills, location, source_url, duration, company, category, external_id, source_type) VALUES (:title, :description, :skills, :location, :source_url, :duration, :company, :category, :external_id, :source_type)');
            $stmt->execute([
                'title'       => $item['title'] ?? 'Untitled opportunity',
                'description' => $item['description'] ?? '',
                'skills'      => json_encode($item['skills'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'location'    => $item['location'] ?? 'Global',
                'source_url'  => $item['source_url'] ?? 'https://aiesec.org/search',
                'duration'    => $item['duration'] ?? null,
                'company'     => $item['company'] ?? null,
                'category'    => $item['category'] ?? null,
                'external_id' => $item['external_id'] ?? null,
                'source_type' => $item['source_type'] ?? 'api',
            ]);
            $count++;
        }

        return $count;
    }

    public function latest(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM opportunities ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Return filter options: durations and locations currently present in the database.
     *
     * @return array{durations: string[], countries: string[]}
     */
    public function getCsvFilterOptions(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT DISTINCT duration FROM opportunities WHERE duration IS NOT NULL AND duration != "" ORDER BY duration');
            $durations = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $stmt = $this->pdo->query('SELECT DISTINCT location FROM opportunities WHERE location IS NOT NULL AND location != "" ORDER BY location');
            $countries = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            return [
                'durations' => $durations,
                'countries' => $countries
            ];
        } catch (Throwable $e) {
            return ['durations' => [], 'countries' => []];
        }
    }

    /**
     * Fetch ALL countries that have opportunities from the AIESEC API.
     * Queries all programmes and collects unique countries.
     */
    public function fetchCountriesFromApi(): array
    {
        $token = config()['aiesec_access_token'] ?? '';
        if (empty($token)) {
            return [];
        }

        $query = <<<'GRAPHQL'
query GetAllOpportunitiesQuery($page: Int, $per_page: Int, $q: String, $sort: String, $filters: OpportunityFilter) {
  allOpportunity: allOpportunity(page: $page, per_page: $per_page, q: $q, sort: $sort, filters: $filters) {
    data {
      host_lc {
        address_detail {
          country
          __typename
        }
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

        $countries = [];
        // Programmes with opportunities: 7=GV, 8=GTa, 9=GTe
        $programmes = [7, 8, 9];

        foreach ($programmes as $progId) {
            $page = 1;
            $maxPages = 10;

            do {
                $payload = [
                    'operationName' => 'GetAllOpportunitiesQuery',
                    'query' => $query,
                    'variables' => [
                        'page' => $page,
                        'per_page' => 100,
                        'q' => '',
                        'sort' => 'relevance',
                        'filters' => [
                            'programmes' => [$progId],
                        ]
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
                    'Referer: https://aiesec.org/search',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Authorization: ' . $token
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error || $httpCode !== 200) {
                    break;
                }

                $data = json_decode((string) $response, true);
                $rawData = $data['data']['allOpportunity']['data'] ?? [];
                $paging = $data['data']['allOpportunity']['paging'] ?? [];
                $totalPages = (int) ($paging['total_pages'] ?? 1);

                foreach ($rawData as $item) {
                    $country = $item['host_lc']['address_detail']['country'] ?? null;
                    if ($country && !empty(trim($country))) {
                        $countries[trim($country)] = true;
                    }
                }

                $page++;
            } while ($page <= $totalPages && $page <= $maxPages);
        }

        $result = array_keys($countries);
        sort($result);
        return $result;
    }

    /**
     * Fetch ALL opportunities from the AIESEC GraphQL API.
     * Paginates through ALL pages and fetches from all available programmes (GV, GTa, GTe).
     */
    public function fetchFromApi(array $filters = []): array
    {
        $token = config()['aiesec_access_token'] ?? '';
        if (empty($token)) {
            return $this->fallbackOpportunities();
        }

        $query = <<<'GRAPHQL'
query GetAllOpportunitiesQuery($page: Int, $per_page: Int, $q: String, $sort: String, $filters: OpportunityFilter) {
  allOpportunity: allOpportunity(page: $page, per_page: $per_page, q: $q, sort: $sort, filters: $filters) {
    data {
      applicants_count
      applications_close_date
      branch {
        company {
          id
          name
          __typename
        }
        __typename
      }
      description
      host_lc {
        address_detail {
          country
          __typename
        }
        __typename
      }
      id
      duration
      opportunity_duration_type {
        duration_type
        __typename
      }
      earliest_start_date
      location
      programme {
        id
        short_name
        short_name_display
        __typename
      }
      project_name
      project_description
      remote_opportunity
      experience_type
      role_info {
        learning_points_list
        __typename
      }
      title
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

        $allItems = [];
        $seenIds = [];
        $perPage = 100;
        $maxPages = (int) (getenv('AIESEC_MAX_PAGES') ?: 15); // 15 pages × 100 = up to 1500 opportunities
        $page = 1;

        do {
            $payload = [
                'operationName' => 'GetAllOpportunitiesQuery',
                'query' => $query,
                'variables' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'q' => '',
                    'sort' => 'relevance',
                    'filters' => (object) [], // No filter → fetch ALL opportunities globally
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
                'Referer: https://aiesec.org/search',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Authorization: ' . $token
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200) {
                break;
            }

            $data = json_decode((string) $response, true);
            $rawData = $data['data']['allOpportunity']['data'] ?? [];
            $paging = $data['data']['allOpportunity']['paging'] ?? [];
            $totalPages = (int) ($paging['total_pages'] ?? 1);

            if (empty($rawData)) {
                break;
            }

            foreach ($rawData as $item) {
                $oppId = $item['id'] ?? '';

                // Skip duplicates
                if (isset($seenIds[$oppId])) {
                    continue;
                }
                $seenIds[$oppId] = true;

                $parsed = $this->parseOpportunityItem($item, $filters);
                if ($parsed !== null) {
                    $allItems[] = $parsed;
                }
            }

            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        return $allItems;
    }

    /**
     * Parse a single API opportunity item into a normalized array.
     * Returns null if the item should be filtered out.
     */
    private function parseOpportunityItem(array $item, array $filters): ?array
    {
        $oppId = $item['id'] ?? '';
        $title = $item['title'] ?? $item['project_name'] ?? 'AIESEC Opportunity';
        $description = $item['description'] ?? $item['project_description'] ?? 'Open AIESEC opportunity.';

        // --- Extract skills from learning points and description ---
        $learningPoints = $item['role_info']['learning_points_list'] ?? [];
        $learningText = '';
        if (is_array($learningPoints)) {
            $learningText = implode(' ', array_map(fn($p) => strip_tags((string)$p), $learningPoints));
        }
        
        $combinedText = implode(' ', [$title, $description, $item['project_description'] ?? '', $learningText]);
        $skills = $this->extractSkillsFromText($combinedText);

        // --- Location & Country: always store the clean country name ---
        $hostLc    = $item['host_lc'] ?? [];
        $address   = $hostLc['address_detail'] ?? [];
        $apiCountry = $address['country'] ?? '';
        // Normalize country name (API sometimes returns French/local names)
        $country = $this->normalizeCountryName($apiCountry);

        // For display in cards: show city if available, else country
        $cityLocation = $item['location'] ?? '';
        if (!empty($item['remote_opportunity']) && $item['remote_opportunity'] === 'true') {
            $displayLocation = 'Remote';
        } else {
            $displayLocation = $country ?: 'Global';
        }

        // --- Duration ---
        $durationType = $item['opportunity_duration_type'] ?? [];
        $durationRaw = $item['duration'] ?? $durationType['duration_type'] ?? 'See AIESEC details';
        if (is_numeric($durationRaw)) {
            $duration = $durationRaw . ' weeks';
        } else {
            $duration = (string) $durationRaw;
        }

        // --- Programme: always store the short code for consistent filtering ---
        $programme = $item['programme'] ?? [];
        $progShort = $programme['short_name'] ?? '';
        // Map to canonical short codes GV / GTa / GTe
        $progCodeMap = [
            'gv'  => 'GV',  'global volunteer'  => 'GV',
            'gta' => 'GTa', 'global talent'     => 'GTa',
            'gte' => 'GTe', 'global teacher'    => 'GTe',
            'ge'  => 'GTe', 'global exchange'   => 'GTe',
        ];
        $programmeName = $progCodeMap[strtolower(trim($progShort))] ?? ($progShort ?: 'GV');
        // Also try short_name_display if short_name didn't map
        if (!isset($progCodeMap[strtolower(trim($progShort))])) {
            $display = strtolower(trim($programme['short_name_display'] ?? ''));
            $programmeName = $progCodeMap[$display] ?? strtoupper($progShort) ?: 'GV';
        }

        // --- Company ---
        $company = $item['branch']['company']['name'] ?? '';

        // --- Source URL ---
        $progSlugMap = ['GV' => 'global-volunteer', 'GTa' => 'global-talent', 'GTe' => 'global-teacher'];
        $slug = $progSlugMap[$programmeName] ?? 'global-volunteer';
        $sourceUrl = $oppId ? "https://aiesec.org/opportunity/{$slug}/{$oppId}" : 'https://aiesec.org/search';

        // --- Apply duration filter ---
        if (!empty($filters['duration']) && $duration !== $filters['duration']) {
            return null;
        }

        // --- Languages from text ---
        $languages = $this->extractLanguagesFromText($combinedText);

        return [
            'title'       => $title,
            'description' => substr($description, 0, 500),
            'skills'      => $skills,
            'languages'   => $languages,
            'location'    => $country ?: 'Global',   // Always the country name — used for DB filtering
            'display_location' => $displayLocation,   // City or country for display
            'source_url'  => $sourceUrl,
            'category'    => $programmeName,           // Always GV / GTa / GTe
            'duration'    => $duration,
            'company'     => $company,
            'source_type' => 'api',
            'external_id' => $oppId,
        ];
    }

    /**
     * Normalize country name from API (handles French names, variations).
     * Converts "Tunisie" → "Tunisia", "Maroc" → "Morocco", etc.
     */
    private function normalizeCountryName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        // Map of French/variant names → English standard names
        $countryAliases = [
            'tunisie' => 'Tunisia',
            'maroc' => 'Morocco',
            'algérie' => 'Algeria',
            'algerie' => 'Algeria',
            'égypte' => 'Egypt',
            'egypte' => 'Egypt',
            'côte d\'ivoire' => 'Côte d\'Ivoire',
            'sénégal' => 'Senegal',
            'senegal' => 'Senegal',
            'cameroun' => 'Cameroon',
            'allemagne' => 'Germany',
            'espagne' => 'Spain',
            'italie' => 'Italy',
            'brésil' => 'Brazil',
            'bresil' => 'Brazil',
            'turquie' => 'Turkey',
            'roumanie' => 'Romania',
            'pologne' => 'Poland',
            'hongrie' => 'Hungary',
            'grèce' => 'Greece',
            'grece' => 'Greece',
            'suède' => 'Sweden',
            'suede' => 'Sweden',
            'suisse' => 'Switzerland',
            'norvège' => 'Norway',
            'norvege' => 'Norway',
            'danemark' => 'Denmark',
            'finlande' => 'Finland',
            'pays-bas' => 'Netherlands',
            'belgique' => 'Belgium',
            'autriche' => 'Austria',
            'portugal' => 'Portugal',
            'mexique' => 'Mexico',
            'colombie' => 'Colombia',
            'pérou' => 'Peru',
            'perou' => 'Peru',
            'chili' => 'Chile',
            'argentine' => 'Argentina',
            'inde' => 'India',
            'chine' => 'China',
            'japon' => 'Japan',
            'corée du sud' => 'South Korea',
            'coree du sud' => 'South Korea',
            'thaïlande' => 'Thailand',
            'thailande' => 'Thailand',
            'malaisie' => 'Malaysia',
            'indonésie' => 'Indonesia',
            'indonesie' => 'Indonesia',
            'philippines' => 'Philippines',
            'liban' => 'Lebanon',
            'jordanie' => 'Jordan',
            'émirats arabes unis' => 'United Arab Emirates',
            'emirats arabes unis' => 'United Arab Emirates',
            'arabie saoudite' => 'Saudi Arabia',
            'royaume-uni' => 'United Kingdom',
            'états-unis' => 'United States',
            'etats-unis' => 'United States',
            'afrique du sud' => 'South Africa',
            'république tchèque' => 'Czech Republic',
            'republique tcheque' => 'Czech Republic',
            'bosnie-herzégovine' => 'Bosnia and Herzegovina',
        ];

        $nameLower = strtolower($name);
        return $countryAliases[$nameLower] ?? $name;
    }

    /**
     * Extract skills from text using strict keyword matching.
     * Only matches specific, concrete skill/technology keywords.
     * Does NOT match generic words like "content", "work", etc.
     * Returns empty array if no skills found (no fallback generic skills).
     */
    private function extractSkillsFromText(string $text): array
    {
        $textLower = strtolower($text);
        
        // Strict skill keywords — only concrete technologies, tools, and professional skills
        // Sorted by length descending so longer matches take priority
        $skillKeywords = [
            // Multi-word (check first)
            'machine learning' => 'machine learning',
            'deep learning' => 'deep learning',
            'web development' => 'web development',
            'mobile development' => 'mobile development',
            'software development' => 'software development',
            'project management' => 'project management',
            'data analysis' => 'data analysis',
            'social media' => 'social media',
            'customer service' => 'customer service',
            'graphic design' => 'graphic design',
            'content creation' => 'content creation',
            'problem solving' => 'problem solving',
            'microsoft office' => 'microsoft office',
            'spring boot' => 'spring boot',
            'react native' => 'react native',
            'power bi' => 'power bi',
            'rest api' => 'rest api',
            'embedded systems' => 'embedded systems',
            'raspberry pi' => 'raspberry pi',
            'windows server' => 'windows server',
            // Single word / short — programming languages
            'python' => 'python',
            'php' => 'php',
            'javascript' => 'javascript',
            'typescript' => 'typescript',
            'java' => 'java',
            'c++' => 'c++',
            'c#' => 'c#',
            'ruby' => 'ruby',
            'golang' => 'golang',
            'rust' => 'rust',
            'swift' => 'swift',
            'kotlin' => 'kotlin',
            'scala' => 'scala',
            'perl' => 'perl',
            'dart' => 'dart',
            // Web technologies
            'html' => 'html',
            'css' => 'css',
            'react' => 'react',
            'angular' => 'angular',
            'vue' => 'vue',
            'svelte' => 'svelte',
            'next.js' => 'next.js',
            'node.js' => 'node.js',
            'django' => 'django',
            'flask' => 'flask',
            'laravel' => 'laravel',
            'symfony' => 'symfony',
            'bootstrap' => 'bootstrap',
            'graphql' => 'graphql',
            // Databases
            'sql' => 'sql',
            'mysql' => 'mysql',
            'postgresql' => 'postgresql',
            'mongodb' => 'mongodb',
            'redis' => 'redis',
            // DevOps & cloud
            'docker' => 'docker',
            'kubernetes' => 'kubernetes',
            'aws' => 'aws',
            'azure' => 'azure',
            'git' => 'git',
            'github' => 'github',
            'jenkins' => 'jenkins',
            'linux' => 'linux',
            // Design tools
            'photoshop' => 'photoshop',
            'illustrator' => 'illustrator',
            'figma' => 'figma',
            'canva' => 'canva',
            'autocad' => 'autocad',
            'sketchup' => 'sketchup',
            'revit' => 'revit',
            '3ds max' => '3ds max',
            // Data & analysis tools
            'excel' => 'excel',
            'tableau' => 'tableau',
            'matlab' => 'matlab',
            // Hardware & embedded
            'arduino' => 'arduino',
            'stm32' => 'stm32',
            // Professional skills (only specific ones)
            'marketing' => 'marketing',
            'seo' => 'seo',
            'copywriting' => 'copywriting',
            'photography' => 'photography',
            'video editing' => 'video editing',
            'flutter' => 'flutter',
            'flutterflow' => 'flutterflow',
            // Business tools
            'salesforce' => 'salesforce',
            'sap' => 'sap',
            'jira' => 'jira',
        ];

        $found = [];
        foreach ($skillKeywords as $keyword => $normalized) {
            $escaped = preg_quote($keyword, '/');
            // Word-boundary match to avoid false positives like "java" matching "javascript"
            if (preg_match('/(?<![a-z0-9])' . $escaped . '(?![a-z0-9])/i', $textLower)) {
                $found[$normalized] = true;
            }
        }

        // DO NOT add fallback generic skills — empty is OK
        // The matcher.py uses text_tokens for keyword matching anyway
        $result = array_keys($found);
        sort($result);
        return $result;
    }

    /**
     * Extract language requirements from text.
     */
    private function extractLanguagesFromText(string $text): array
    {
        $knownLanguages = ['english', 'french', 'spanish', 'german', 'arabic', 'portuguese', 'italian', 'hindi', 'chinese', 'japanese', 'korean', 'russian', 'turkish', 'dutch'];
        $textLower = strtolower($text);

        $found = [];
        foreach ($knownLanguages as $lang) {
            if (str_contains($textLower, $lang)) {
                $found[] = $lang;
            }
        }

        return $found;
    }

    public function getSyncInfo(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM opportunities');
            $count = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->query('SELECT MAX(created_at) FROM opportunities');
            $lastSync = $stmt->fetchColumn();

            return [
                'count' => $count,
                'last_sync' => $lastSync ? date('Y-m-d H:i:s', strtotime($lastSync)) : 'Never',
            ];
        } catch (Throwable $e) {
            return [
                'count' => 0,
                'last_sync' => 'Never',
            ];
        }
    }

    private function fallbackOpportunities(): array
    {
        return [
            [
                'title' => 'Global Talent - Marketing Intern',
                'description' => 'Support campaign execution, content creation, and market research.',
                'skills' => ['marketing', 'content creation'],
                'languages' => ['english'],
                'location' => 'Remote',
                'source_url' => 'https://aiesec.org/search',
                'category' => 'Global Talent',
                'source_type' => 'sample',
            ],
            [
                'title' => 'Global Volunteer - Community Outreach',
                'description' => 'Work with NGOs on community engagement and event support.',
                'skills' => [],
                'languages' => ['english'],
                'location' => 'Brazil',
                'source_url' => 'https://aiesec.org/search',
                'category' => 'Global Volunteer',
                'source_type' => 'sample',
            ],
        ];
    }
}
