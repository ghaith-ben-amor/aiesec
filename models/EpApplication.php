<?php
declare(strict_types=1);

final class EpApplication extends BaseModel
{
    private const STAGES = [
        'applied',
        'accepted',
        'payment',
        'confirmed',
        'preparation_survey',
        'midway_survey',
        'experience_survey',
        'completed',
    ];

    private const REQUIRED_DOCUMENT_TYPES = [
        'cv' => 'CV',
        'passport' => 'Passport Copy',
        'contract' => 'Contract',
        'acceptance_note' => 'Acceptance Note',
        'visa_documents' => 'Visa Documents',
        'insurance_documents' => 'Insurance Documents',
        'flight_ticket' => 'Flight Ticket',
        'profile_picture' => 'Profile Picture',
    ];

    public function stages(): array
    {
        return array_map(
            static fn (string $status): array => [
                'key' => $status,
                'label' => self::stageLabel($status),
            ],
            self::STAGES
        );
    }

    public static function stageLabel(string $status): string
    {
        return match ($status) {
            'applied' => 'Applied',
            'accepted' => 'Accepted',
            'payment' => 'Payment',
            'confirmed' => 'Confirmed',
            'preparation_survey' => 'Preparation Survey',
            'midway_survey' => 'Midway Survey',
            'experience_survey' => 'Experience Survey',
            'completed' => 'Completed',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function stageIndex(string $status): int
    {
        $index = array_search($status, self::STAGES, true);
        return $index === false ? 0 : (int) $index;
    }

    public static function stagePercentage(string $status): int
    {
        $totalStages = count(self::STAGES) - 1;
        if ($totalStages <= 0) {
            return 0;
        }

        return (int) round((self::stageIndex($status) / $totalStages) * 100);
    }

    public function create(array $data): int
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
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'nationality' => trim((string) ($data['nationality'] ?? '')),
            'university' => trim((string) ($data['university'] ?? '')),
            'field_of_study' => trim((string) ($data['field_of_study'] ?? '')),
            'opportunity_title' => trim((string) ($data['opportunity_title'] ?? '')),
            'country' => trim((string) ($data['country'] ?? '')),
            'organization' => trim((string) ($data['organization'] ?? '')),
            'application_date' => (string) ($data['application_date'] ?? date('Y-m-d')),
            'opportunity_link' => trim((string) ($data['opportunity_link'] ?? '')),
            'status' => 'applied',
            'stage_index' => 0,
            'folder_name' => $folderName,
            'status_updated_at' => date('Y-m-d H:i:s'),
        ]);

        $folderPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $folderName;
        if (!is_dir($folderPath) && !mkdir($folderPath, 0775, true) && !is_dir($folderPath)) {
            throw new RuntimeException('Unable to create EP folder.');
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM ep_applications WHERE 1=1';
        $params = [];

        if (!empty($filters['name'])) {
            $sql .= ' AND (first_name LIKE :name OR last_name LIKE :name)';
            $params['name'] = '%' . trim((string) $filters['name']) . '%';
        }

        if (!empty($filters['country'])) {
            $sql .= ' AND country LIKE :country';
            $params['country'] = '%' . trim((string) $filters['country']) . '%';
        }

        if (!empty($filters['opportunity'])) {
            $sql .= ' AND opportunity_title LIKE :opportunity';
            $params['opportunity'] = '%' . trim((string) $filters['opportunity']) . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $this->normalizeStatus((string) $filters['status']);
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $eps = $stmt->fetchAll();
        return array_map(function (array $ep): array {
            return $this->decorateEp($ep);
        }, $eps);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ep_applications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $ep = $stmt->fetch();
        return is_array($ep) ? $this->decorateEp($ep) : null;
    }

    public function stats(): array
    {
        $stats = [];
        foreach (self::STAGES as $status) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM ep_applications WHERE status = :status');
            $stmt->execute(['status' => $status]);
            $stats[$status] = (int) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM ep_applications');
        $stats['total'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    public function countryCounts(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare('SELECT country, COUNT(*) AS total FROM ep_applications GROUP BY country ORDER BY total DESC, country ASC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function statusCounts(): array
    {
        $stmt = $this->pdo->query('SELECT status, COUNT(*) AS total FROM ep_applications GROUP BY status ORDER BY total DESC');
        return $stmt->fetchAll();
    }

    public function monthlyApplications(int $months = 6): array
    {
        $months = max(1, $months);
        $stmt = $this->pdo->query('SELECT application_date FROM ep_applications ORDER BY application_date DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = date('Y-m', strtotime("-{$i} months"));
            $result[$monthKey] = 0;
        }

        foreach ($rows as $date) {
            $monthKey = substr((string) $date, 0, 7);
            if (array_key_exists($monthKey, $result)) {
                $result[$monthKey]++;
            }
        }

        return array_map(static fn (string $month, int $count): array => [
            'month' => $month,
            'total' => $count,
        ], array_keys($result), array_values($result));
    }

    public function documentsForEp(int $epId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ep_documents WHERE ep_id = :ep_id ORDER BY id DESC');
        $stmt->execute(['ep_id' => $epId]);
        $documents = $stmt->fetchAll();
        foreach ($documents as &$document) {
            $document['web_path'] = $this->toWebPath((string) ($document['file_path'] ?? ''));
        }
        return $documents;
    }

    public function historyForEp(int $epId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ep_status_history WHERE ep_id = :ep_id ORDER BY id DESC');
        $stmt->execute(['ep_id' => $epId]);
        $history = $stmt->fetchAll();
        foreach ($history as &$item) {
            $item['status_label'] = self::stageLabel((string) ($item['status'] ?? 'applied'));
        }
        return $history;
    }

    public function notifications(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare('
            SELECT n.*, e.first_name, e.last_name
            FROM ep_notifications n
            INNER JOIN ep_applications e ON e.id = n.ep_id
            ORDER BY n.id DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['ep_name'] = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        }
        return $rows;
    }

    public function updateStatus(int $epId, string $status, string $changedByLabel = 'Admin'): bool
    {
        $status = $this->normalizeStatus($status);
        $stageIndex = self::stageIndex($status);

        $stmt = $this->pdo->prepare('UPDATE ep_applications SET status = :status, stage_index = :stage_index, status_updated_at = :status_updated_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'stage_index' => $stageIndex,
            'status_updated_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $epId,
        ]);

        if ($stmt->rowCount() > 0) {
            $history = $this->pdo->prepare('INSERT INTO ep_status_history (ep_id, status, stage_index, changed_by_label) VALUES (:ep_id, :status, :stage_index, :changed_by_label)');
            $history->execute([
                'ep_id' => $epId,
                'status' => $status,
                'stage_index' => $stageIndex,
                'changed_by_label' => $changedByLabel,
            ]);

            $this->createNotification($epId, 'status_change', 'EP status updated to ' . self::stageLabel($status) . '.');
            return true;
        }

        return false;
    }

    public function updateProfile(int $epId, array $data, string $changedByLabel = 'Admin'): bool
    {
        $current = $this->fetchApplicationRow($epId);
        if (!$current) {
            return false;
        }

        $currentStatus = (string) ($current['status'] ?? 'applied');
        $nextStatus = $this->normalizeStatus((string) ($data['status'] ?? $currentStatus));

        $stmt = $this->pdo->prepare('
            UPDATE ep_applications SET
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                nationality = :nationality,
                university = :university,
                field_of_study = :field_of_study,
                opportunity_title = :opportunity_title,
                country = :country,
                organization = :organization,
                application_date = :application_date,
                opportunity_link = :opportunity_link,
                updated_at = :updated_at
            WHERE id = :id
        ');
        $stmt->execute([
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'nationality' => trim((string) ($data['nationality'] ?? '')),
            'university' => trim((string) ($data['university'] ?? '')),
            'field_of_study' => trim((string) ($data['field_of_study'] ?? '')),
            'opportunity_title' => trim((string) ($data['opportunity_title'] ?? '')),
            'country' => trim((string) ($data['country'] ?? '')),
            'organization' => trim((string) ($data['organization'] ?? '')),
            'application_date' => (string) ($data['application_date'] ?? date('Y-m-d')),
            'opportunity_link' => trim((string) ($data['opportunity_link'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $epId,
        ]);

        if ($nextStatus !== $currentStatus) {
            $this->updateStatus($epId, $nextStatus, $changedByLabel);
        }

        return true;
    }

    public function storeDocument(int $epId, string $documentType, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Document upload failed.'];
        }

        $ep = $this->findById($epId);
        if (!$ep) {
            return ['success' => false, 'message' => 'EP not found.'];
        }

        $folderPath = $this->folderPath($ep);
        if (!is_dir($folderPath) && !mkdir($folderPath, 0775, true) && !is_dir($folderPath)) {
            return ['success' => false, 'message' => 'Unable to create EP folder.'];
        }

        $originalName = (string) ($file['name'] ?? 'document');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = $this->documentFileLabel($documentType);
        $fileName = $this->uniqueDocumentFileName($folderPath, $baseName, $extension);
        $targetPath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Unable to store the document.'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO ep_documents (ep_id, document_type, original_name, file_name, file_path, mime_type) VALUES (:ep_id, :document_type, :original_name, :file_name, :file_path, :mime_type)');
        $stmt->execute([
            'ep_id' => $epId,
            'document_type' => $this->normalizeDocumentType($documentType),
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $targetPath,
            'mime_type' => (string) ($file['type'] ?? ''),
        ]);

        $this->createNotification($epId, 'document_upload', $this->normalizeDocumentType($documentType) . ' uploaded.');

        return ['success' => true, 'file_path' => $targetPath, 'file_name' => $fileName];
    }

    public function missingDocuments(int $epId): array
    {
        $documents = $this->documentsForEp($epId);
        $presentTypes = [];

        foreach ($documents as $document) {
            $presentTypes[$this->normalizeDocumentType((string) ($document['document_type'] ?? ''))] = true;
        }

        $missing = [];
        foreach (self::REQUIRED_DOCUMENT_TYPES as $key => $label) {
            if (empty($presentTypes[$key])) {
                $missing[$key] = $label;
            }
        }

        return $missing;
    }

    public function ensureMissingDocumentNotification(int $epId): void
    {
        $missing = $this->missingDocuments($epId);
        if ($missing === []) {
            return;
        }

        $message = 'Missing required documents: ' . implode(', ', array_values($missing)) . '.';
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM ep_notifications WHERE ep_id = :ep_id AND notification_type = :notification_type AND message = :message');
        $stmt->execute([
            'ep_id' => $epId,
            'notification_type' => 'missing_documents',
            'message' => $message,
        ]);

        if ((int) $stmt->fetchColumn() === 0) {
            $this->createNotification($epId, 'missing_documents', $message);
        }
    }

    public function folderPath(array $ep): string
    {
        $folderName = (string) ($ep['folder_name'] ?? '');
        return UPLOAD_PATH . DIRECTORY_SEPARATOR . $folderName;
    }

    public function fullName(array $ep): string
    {
        return trim((string) ($ep['first_name'] ?? '') . ' ' . (string) ($ep['last_name'] ?? ''));
    }

    private function createNotification(int $epId, string $type, string $message): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO ep_notifications (ep_id, notification_type, message) VALUES (:ep_id, :notification_type, :message)');
        $stmt->execute([
            'ep_id' => $epId,
            'notification_type' => $type,
            'message' => $message,
        ]);
    }

    private function fetchApplicationRow(int $epId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ep_applications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $epId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function makeUniqueFolderName(string $fullName): string
    {
        $base = $this->sanitizeFolderName($fullName);
        $base = $base !== '' ? $base : 'EP_' . date('Ymd_His');

        $candidate = $base;
        $suffix = 1;
        while (is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $base . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function sanitizeFolderName(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '_', trim($value)) ?? '';
        return trim($value, '_');
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, self::STAGES, true) ? $status : 'applied';
    }

    private function normalizeDocumentType(string $documentType): string
    {
        $documentType = strtolower(trim($documentType));
        $documentType = str_replace([' ', '-'], '_', $documentType);

        $aliases = [
            'cv' => 'cv',
            'passport' => 'passport',
            'passport_copy' => 'passport',
            'contract' => 'contract',
            'acceptance_note' => 'acceptance_note',
            'visa' => 'visa_documents',
            'visa_documents' => 'visa_documents',
            'insurance' => 'insurance_documents',
            'insurance_documents' => 'insurance_documents',
            'flight_ticket' => 'flight_ticket',
            'photo' => 'profile_picture',
            'profile_picture' => 'profile_picture',
            'additional' => 'additional_proof',
            'additional_proof' => 'additional_proof',
        ];

        return $aliases[$documentType] ?? $documentType;
    }

    private function uniqueDocumentFileName(string $folderPath, string $baseName, string $extension): string
    {
        $extension = $extension !== '' ? '.' . $extension : '';
        $baseName = $this->sanitizeFolderName($baseName);
        if ($baseName === '') {
            $baseName = 'document';
        }

        $candidate = $baseName . $extension;
        $suffix = 1;
        while (file_exists($folderPath . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $baseName . '_' . $suffix . $extension;
            $suffix++;
        }

        return $candidate;
    }

    private function documentFileLabel(string $documentType): string
    {
        $documentType = $this->normalizeDocumentType($documentType);
        return match ($documentType) {
            'cv' => 'CV',
            'passport' => 'Passport',
            'contract' => 'Contract',
            'acceptance_note' => 'Acceptance_Note',
            'visa_documents' => 'Visa_Documents',
            'insurance_documents' => 'Insurance_Documents',
            'flight_ticket' => 'Flight_Ticket',
            'profile_picture' => 'Photo',
            'additional_proof' => 'Additional_Proof',
            default => ucwords(str_replace('_', ' ', $documentType)),
        };
    }

    private function decorateEp(array $ep): array
    {
        $ep['full_name'] = $this->fullName($ep);
        $ep['stage_label'] = self::stageLabel((string) ($ep['status'] ?? 'applied'));
        $ep['stage_index'] = self::stageIndex((string) ($ep['status'] ?? 'applied'));
        $ep['progress_percent'] = self::stagePercentage((string) ($ep['status'] ?? 'applied'));
        $ep['documents'] = $this->documentsForEp((int) ($ep['id'] ?? 0));
        $ep['history'] = $this->historyForEp((int) ($ep['id'] ?? 0));
        $ep['missing_documents'] = $this->missingDocuments((int) ($ep['id'] ?? 0));
        return $ep;
    }

    private function toWebPath(string $filePath): string
    {
        $normalizedPath = str_replace('\\', '/', $filePath);
        $basePath = str_replace('\\', '/', BASE_PATH);
        if (str_starts_with($normalizedPath, $basePath)) {
            return url_path(substr($normalizedPath, strlen($basePath)));
        }

        return $normalizedPath;
    }
}
