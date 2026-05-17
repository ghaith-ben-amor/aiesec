<?php
declare(strict_types=1);

final class MatchResult extends BaseModel
{
    public function generateMatches(array $cvData, array $opportunities, int $cvId): array
    {
        $tempDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            return [];
        }

        $cvFile = tempnam($tempDir, 'cv_');
        $opportunitiesFile = tempnam($tempDir, 'opportunities_');
        file_put_contents($cvFile, json_encode($cvData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        file_put_contents($opportunitiesFile, json_encode($opportunities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $command = escapeshellarg(config()['python_bin']) . ' ' . escapeshellarg(PYTHON_PATH . '/matcher.py') . ' ' . escapeshellarg($cvFile) . ' ' . escapeshellarg($opportunitiesFile);
        $json = shell_exec($command);
        $results = json_decode((string) $json, true);

        if (!is_array($results)) {
            $results = [];
        }

        $insertStmt = $this->pdo->prepare('INSERT INTO matches (cv_id, opportunity_id, score) VALUES (:cv_id, :opportunity_id, :score)');
        $findByIdStmt = $this->pdo->prepare('SELECT id FROM opportunities WHERE id = :id LIMIT 1');
        $findByUrlStmt = $this->pdo->prepare('SELECT id FROM opportunities WHERE source_url = :url LIMIT 1');
        $findByTitleStmt = $this->pdo->prepare('SELECT id FROM opportunities WHERE title = :title LIMIT 1');

        foreach ($results as $result) {
            $opportunityIdToUse = null;

            // If matcher returned an id, verify it exists in DB
            if (!empty($result['id']) && is_numeric($result['id'])) {
                $findByIdStmt->execute([':id' => (int) $result['id']]);
                $row = $findByIdStmt->fetch();
                if ($row && isset($row['id'])) {
                    $opportunityIdToUse = (int) $row['id'];
                }
            }

            // Fall back to matching by source_url when available
            if ($opportunityIdToUse === null && !empty($result['source_url'])) {
                $findByUrlStmt->execute([':url' => $result['source_url']]);
                $row = $findByUrlStmt->fetch();
                if ($row && isset($row['id'])) {
                    $opportunityIdToUse = (int) $row['id'];
                }
            }

            // Final fallback: try matching by title
            if ($opportunityIdToUse === null && !empty($result['title'])) {
                $findByTitleStmt->execute([':title' => $result['title']]);
                $row = $findByTitleStmt->fetch();
                if ($row && isset($row['id'])) {
                    $opportunityIdToUse = (int) $row['id'];
                }
            }

            // Insert match (opportunity_id may be null if no match found)
            $insertStmt->execute([
                ':cv_id' => $cvId,
                ':opportunity_id' => $opportunityIdToUse,
                ':score' => $result['score'] ?? 0,
            ]);
        }

        foreach ([$cvFile, $opportunitiesFile] as $file) {
            if (is_string($file) && file_exists($file)) {
                @unlink($file);
            }
        }

        return $results;
    }

    public function latest(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare('SELECT m.*, c.file_path, o.title, o.location FROM matches m LEFT JOIN cvs c ON c.id = m.cv_id LEFT JOIN opportunities o ON o.id = m.opportunity_id ORDER BY m.id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
