<?php
declare(strict_types=1);

final class Opportunity extends BaseModel
{
    public function syncFromScraper(array $filters = []): array
    {
        $items = $this->loadFromCsv($filters);

        if ($items === []) {
            $items = $this->fallbackOpportunities();
        }

        $this->pdo->exec('DELETE FROM opportunities');

        $saved = [];
        foreach ($items as $item) {
            $stmt = $this->pdo->prepare('INSERT INTO opportunities (title, description, skills, location, source_url) VALUES (:title, :description, :skills, :location, :source_url)');
            $stmt->execute([
                'title' => $item['title'] ?? 'Untitled opportunity',
                'description' => $item['description'] ?? '',
                'skills' => json_encode($item['skills'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'location' => $item['location'] ?? 'Remote',
                'source_url' => $item['source_url'] ?? 'https://aiesec.org/search?programmes=8',
            ]);

            $saved[] = array_merge($item, ['id' => (int) $this->pdo->lastInsertId()]);
        }

        return $saved;
    }

    public function latest(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM opportunities ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Return filter options discovered in the CSV: durations and countries
     *
     * @return array{durations: string[], countries: string[]}
     */
    public function getCsvFilterOptions(): array
    {
        $csvPath = $this->resolveCsvPath();
        $durations = [];
        $countries = [];

        if ($csvPath === null || !is_file($csvPath)) {
            return ['durations' => [], 'countries' => []];
        }

        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            return ['durations' => [], 'countries' => []];
        }

        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map([$this, 'normalizeHeader'], $row);
                continue;
            }
            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $record = [];
            foreach ($header as $index => $columnName) {
                $record[$columnName] = $row[$index] ?? '';
            }

            $title = $record['opportunity_name'] ?? '';
            if (preg_match('/\[(\d+\s*weeks?)\]/i', $title, $m)) {
                $durations[] = trim($m[1]);
            }

            $mc = trim((string) ($record['mc_name'] ?? ''));
            if ($mc !== '') {
                $countries[] = $mc;
            }
        }

        fclose($handle);

        $durations = array_values(array_unique($durations));
        sort($durations);
        $countries = array_values(array_unique($countries));
        sort($countries);

        return ['durations' => $durations, 'countries' => $countries];
    }

    private function loadFromCsv(array $filters = []): array
    {
        $csvPath = $this->resolveCsvPath();
        if ($csvPath === null || !is_file($csvPath)) {
            return [];
        }

        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = null;
        $items = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map([$this, 'normalizeHeader'], $row);
                continue;
            }

            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $record = [];
            foreach ($header as $index => $columnName) {
                $record[$columnName] = $row[$index] ?? '';
            }

            $title = trim((string) ($record['opportunity_name'] ?? ''));
            if ($title === '') {
                continue;
            }

            $product = trim((string) ($record['product'] ?? ''));
            $mcName = trim((string) ($record['mc_name'] ?? ''));
            $lcName = trim((string) ($record['lc_name'] ?? ''));
            $openings = trim((string) ($record['openings'] ?? ''));
            $link = trim((string) ($record['link_on_yop'] ?? ''));

            // extract duration token like '4 weeks' from title if present
            $duration = null;
            if (preg_match('/\[(\d+\s*weeks?)\]/i', $title, $m)) {
                $duration = trim($m[1]);
            }

            // Apply filters if provided
            if (!empty($filters['duration']) && $duration !== $filters['duration']) {
                continue;
            }

            if (!empty($filters['country']) && strcasecmp($mcName, $filters['country']) !== 0) {
                continue;
            }

            $descriptionParts = [];
            if ($product !== '') {
                $descriptionParts[] = 'Product: ' . $product;
            }
            if ($mcName !== '') {
                $descriptionParts[] = 'MC: ' . $mcName;
            }
            if ($lcName !== '') {
                $descriptionParts[] = 'LC: ' . $lcName;
            }
            if ($openings !== '') {
                $descriptionParts[] = 'Openings: ' . $openings;
            }

            $items[] = [
                'title' => $title,
                'description' => implode('. ', $descriptionParts),
                'skills' => $this->parseSkillsFromRecord($record) ?: $this->deriveSkills($title, $product),
                'location' => $lcName !== '' ? $lcName : ($mcName !== '' ? $mcName : 'Global'),
                'source_url' => $link !== '' ? $link : 'https://aiesec.org/search?programmes=8',
                'category' => $product !== '' ? $product : 'AIESEC opportunity',
                'company' => $mcName !== '' ? $mcName : null,
                'source_type' => 'csv',
                'source_id' => $record['opportunity_id'] ?? null,
                'duration' => $duration,
            ];
        }

        fclose($handle);

        return $items;
    }

    private function parseSkillsFromRecord(array $record): array
    {
        $candidateColumns = [
            'skills',
            'skill',
            'required_skills',
            'required_skill',
            'key_skills',
            'competencies',
            'requirements',
        ];

        $raw = '';
        foreach ($candidateColumns as $column) {
            if (!empty($record[$column])) {
                $raw = trim((string) $record[$column]);
                break;
            }
        }

        if ($raw === '') {
            return [];
        }

        $skills = [];

        // JSON array support: ["C++", "Python"]
        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $skills[] = trim($item);
                    }
                }
            }
        }

        // Delimited text support: C++, Python; Mechanics | Problem Solving
        if ($skills === []) {
            $parts = preg_split('/\s*(?:,|;|\||\/|\\n|\\r)+\s*/', $raw) ?: [];
            foreach ($parts as $part) {
                $part = trim((string) $part);
                if ($part !== '') {
                    $skills[] = $part;
                }
            }
        }

        // Normalize duplicates while keeping original casing display-friendly
        $normalized = [];
        $unique = [];
        foreach ($skills as $skill) {
            $key = strtolower(trim($skill));
            if ($key === '' || isset($normalized[$key])) {
                continue;
            }
            $normalized[$key] = true;
            $unique[] = $skill;
        }

        return $unique;
    }

    private function resolveCsvPath(): ?string
    {
        $candidates = [
            getenv('OPPORTUNITIES_CSV_PATH') ?: null,
            defined('BASE_PATH') ? BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'opportunities.csv' : null,
            'C:\\Users\\HP\\Downloads\\opportunities.csv',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
        return trim($value, '_');
    }

    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function deriveSkills(string ...$values): array
    {
        // Aliases to match Python normalisation in matcher.py
        $aliases = [
            'js' => 'javascript',
            'nodejs' => 'javascript',
            'node.js' => 'javascript',
            'mysql' => 'sql',
            'postgresql' => 'sql',
            'postgres' => 'sql',
            'database' => 'sql',
            'ui' => 'design',
            'ux' => 'design',
            'figma' => 'design',
            'content' => 'content creation',
            'writing' => 'content creation',
            'communicat' => 'communication',
            'collaboration' => 'teamwork',
            'team work' => 'teamwork',
        ];

        $stopWords = [
            'and', 'the', 'for', 'with', 'from', 'into', 'your', 'you', 'our', 'their', 'this', 'that',
            'global', 'opportunity', 'opportunities', 'program', 'programme', 'product', 'openings',
            'mc', 'lc', 'name', 'location', 'remote', 'intern', 'internship', 'volunteer', 'teacher',
            'weeks', 'week', 'support', 'project', 'role', 'new', 'join', 'on', 'at', 'of', 'a', 'an',
        ];

        $tokens = [];
        foreach ($values as $value) {
            $value = strtolower((string) $value);

            // Extract words and tokens keeping + and # and . inside tokens (e.g., c++, c#)
            preg_match_all('/[a-z0-9+#\.]{1,}/i', $value, $matches);
            $words = array_filter(array_map('trim', $matches[0] ?? []));

            // add unigrams and bigrams to increase chance of matches (e.g., 'data analyst')
            $count = count($words);
            for ($i = 0; $i < $count; $i++) {
                $w = $words[$i];
                if ($w === '' || in_array($w, $stopWords, true)) {
                    continue;
                }

                // apply alias mapping
                $norm = $aliases[$w] ?? $w;
                $tokens[] = $norm;

                // bigram
                if ($i + 1 < $count) {
                    $bigram = $w . ' ' . $words[$i + 1];
                    if (!in_array($bigram, $stopWords, true)) {
                        $tokens[] = $aliases[$bigram] ?? $bigram;
                    }
                }
            }
        }

        // Normalize tokens: remove punctuation except +,#,., normalize spacing
        $clean = [];
        foreach ($tokens as $t) {
            $t = trim(preg_replace('/[^a-z0-9+#\. ]+/', '', (string) $t));
            $t = preg_replace('/\s+/', ' ', $t);
            if ($t === '' || in_array($t, $stopWords, true)) {
                continue;
            }
            $clean[] = $t;
        }

        $clean = array_values(array_unique($clean));
        return $clean;
    }

    private function fallbackOpportunities(): array
    {
        return [
            [
                'title' => 'Global Talent - Marketing Intern',
                'description' => 'Support campaign execution, content creation, and market research.',
                'skills' => ['marketing', 'content creation', 'research', 'communication'],
                'location' => 'Remote',
                'source_url' => 'https://aiesec.org/search?programmes=8',
                'category' => 'Global Talent',
                'source_type' => 'sample',
            ],
            [
                'title' => 'Global Volunteer - Community Outreach',
                'description' => 'Work with NGOs on community engagement and event support.',
                'skills' => ['communication', 'teamwork', 'leadership'],
                'location' => 'Brazil',
                'source_url' => 'https://aiesec.org/search?programmes=8',
                'category' => 'Global Volunteer',
                'source_type' => 'sample',
            ],
        ];
    }
}
