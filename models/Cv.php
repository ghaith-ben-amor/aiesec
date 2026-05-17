<?php
declare(strict_types=1);

final class Cv extends BaseModel
{
    public function storeUploadedCv(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed.'];
        }

        $config = config();
        if (($file['size'] ?? 0) > $config['max_upload_size']) {
            return ['success' => false, 'message' => 'The CV exceeds the 8MB limit.'];
        }

        $mime = mime_content_type($file['tmp_name'] ?? '');
        if (!in_array($mime, $config['allowed_upload_types'], true)) {
            return ['success' => false, 'message' => 'Only PDF files are allowed.'];
        }

        if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0775, true) && !is_dir(UPLOAD_PATH)) {
            return ['success' => false, 'message' => 'Unable to create upload directory.'];
        }

        $filename = uniqid('cv_', true) . '.pdf';
        $target = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['success' => false, 'message' => 'Unable to save the uploaded file.'];
        }

        return ['success' => true, 'path' => $target, 'filename' => $filename];
    }

    public function createFromUploadedFile(string $path): array
    {
        $parsed = $this->parseCvWithPython($path);
        $userId = $this->ensureDemoUser();
        $stmt = $this->pdo->prepare('INSERT INTO cvs (user_id, file_path, parsed_data) VALUES (:user_id, :file_path, :parsed_data)');
        $stmt->execute([
            'user_id' => $userId,
            'file_path' => $path,
            'parsed_data' => json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'file_path' => $path,
            'parsed_data' => $parsed,
        ];
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cvs ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function parseCvWithPython(string $path): array
    {
        $command = escapeshellarg(config()['python_bin']) . ' ' . escapeshellarg(PYTHON_PATH . '/parse_cv.py') . ' ' . escapeshellarg($path) . ' 2>&1';
        $output = shell_exec($command);
        $lines = explode("\n", (string) $output);
        $json = '';
        $debugLogs = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '[DEBUG]') === 0) {
                $debugLogs[] = trim(substr($line, 7));
            } else if ($line && trim($line) && $line[0] === '{') {
                $json = $line;
            }
        }
        
        $decoded = json_decode($json, true);
        
        if (is_array($decoded)) {
            if (!empty($debugLogs)) {
                $decoded['_debug'] = implode("\n", $debugLogs);
            }
            return $decoded;
        }
        
        return [
            'raw_text' => '',
            'skills' => [],
            'languages' => [],
            'experience' => [],
            'summary' => 'Unable to parse CV. Falling back to empty profile.',
            '_debug' => implode("\n", $debugLogs),
        ];
    }

    private function ensureDemoUser(): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => 'guest@aiesec.local']);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            return (int) $existing;
        }

        $insert = $this->pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
        $insert->execute([
            'name' => 'Guest User',
            'email' => 'guest@aiesec.local',
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
