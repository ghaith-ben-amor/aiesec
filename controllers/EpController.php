<?php
declare(strict_types=1);

final class EpController extends BaseController
{
    public function dashboard(): void
    {
        $this->requireAdmin();

        $epModel = new EpApplication();
        $filters = [
            'name' => trim((string) $this->request('name', '')),
            'country' => trim((string) $this->request('country', '')),
            'opportunity' => trim((string) $this->request('opportunity', '')),
            'status' => trim((string) $this->request('status', '')),
        ];
        $selectedEpId = (int) $this->request('ep_id', 0);

        $eps = $epModel->all($filters, 50);
        $selectedEp = $this->findSelectedEp($eps, $selectedEpId) ?? ($eps[0] ?? null);
        if (is_array($selectedEp) && !empty($selectedEp['id'])) {
            $epModel->ensureMissingDocumentNotification((int) $selectedEp['id']);
            $selectedEp = $epModel->findById((int) $selectedEp['id']);
        }

        $this->view('ep/dashboard', [
            'config' => config(),
            'filters' => $filters,
            'eps' => $eps,
            'selectedEp' => $selectedEp,
            'stats' => $epModel->stats(),
            'countrySeries' => $epModel->countryCounts(),
            'statusSeries' => $epModel->statusCounts(),
            'monthlySeries' => $epModel->monthlyApplications(),
            'notifications' => $epModel->notifications(),
            'stages' => $epModel->stages(),
            'flashSuccess' => $_SESSION['ep_flash_success'] ?? null,
            'error' => $_SESSION['ep_flash_error'] ?? null,
        ]);

        unset($_SESSION['ep_flash_success'], $_SESSION['ep_flash_error']);
    }

    public function store(): void
    {
        $this->requireAdmin();

        $required = ['first_name', 'last_name', 'email', 'phone', 'nationality', 'university', 'field_of_study', 'opportunity_title', 'country', 'organization', 'application_date', 'opportunity_link'];
        foreach ($required as $field) {
            if (trim((string) $this->request($field, '')) === '') {
                $_SESSION['ep_flash_error'] = 'Please complete all required EP fields.';
                $this->redirect('/ep-management');
            }
        }

        $epModel = new EpApplication();
        $existing = $epModel->findById((int) $this->request('ep_id', 0));
        if ($existing) {
            $_SESSION['ep_flash_error'] = 'The selected EP already exists.';
            $this->redirect('/ep-management?ep_id=' . (int) $existing['id']);
        }

        $cvFile = $_FILES['cv_pdf'] ?? null;
        if (!is_array($cvFile) || (($cvFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
            $_SESSION['ep_flash_error'] = 'CV upload is required for a new EP.';
            $this->redirect('/ep-management');
        }

        $email = trim((string) $this->request('email', ''));
        if ($this->emailExists($email)) {
            $_SESSION['ep_flash_error'] = 'This EP email already exists.';
            $this->redirect('/ep-management');
        }

        $epId = $epModel->create([
            'first_name' => $this->request('first_name', ''),
            'last_name' => $this->request('last_name', ''),
            'email' => $email,
            'phone' => $this->request('phone', ''),
            'nationality' => $this->request('nationality', ''),
            'university' => $this->request('university', ''),
            'field_of_study' => $this->request('field_of_study', ''),
            'opportunity_title' => $this->request('opportunity_title', ''),
            'country' => $this->request('country', ''),
            'organization' => $this->request('organization', ''),
            'application_date' => $this->request('application_date', date('Y-m-d')),
            'opportunity_link' => $this->request('opportunity_link', ''),
        ]);

        $ep = $epModel->findById($epId);
        if ($ep) {
            $folderPath = $epModel->folderPath($ep);
            if (!is_dir($folderPath) && !mkdir($folderPath, 0775, true) && !is_dir($folderPath)) {
                $_SESSION['ep_flash_error'] = 'EP folder could not be created.';
                $this->redirect('/ep-management?ep_id=' . $epId);
            }
        }

        $storedDocuments = [];
        $storedDocuments[] = $epModel->storeDocument($epId, 'cv', $cvFile);
        if (isset($_FILES['passport_file'])) {
            $storedDocuments[] = $epModel->storeDocument($epId, 'passport', $_FILES['passport_file']);
        }
        foreach ($this->normalizeFilesArray($_FILES['additional_documents'] ?? null) as $file) {
            $storedDocuments[] = $epModel->storeDocument($epId, 'additional_proof', $file);
        }

        $storedCount = count(array_filter($storedDocuments, static fn (array $result): bool => ($result['success'] ?? false) === true));
        $_SESSION['ep_flash_success'] = 'EP created successfully with ' . $storedCount . ' uploaded document(s).';
        $this->redirect('/ep-management?ep_id=' . $epId);
    }

    public function updateStatus(): void
    {
        $this->requireAdmin();

        $epId = (int) $this->request('ep_id', 0);
        $status = (string) $this->request('status', 'applied');
        $epModel = new EpApplication();
        $ep = $epModel->findById($epId);

        if (!$ep) {
            $this->jsonResponse(['success' => false, 'message' => 'EP not found.'], 404);
        }

        $changedBy = (string) (($this->currentAdmin()['name'] ?? 'Admin'));
        $updated = $epModel->updateStatus($epId, $status, $changedBy);
        $epModel->ensureMissingDocumentNotification($epId);

        if (!$updated) {
            $this->jsonResponse(['success' => false, 'message' => 'Status update failed.'], 422);
        }

        $fresh = $epModel->findById($epId);
        $this->jsonResponse([
            'success' => true,
            'message' => 'EP status updated successfully.',
            'ep' => $fresh,
            'missing_documents' => $fresh['missing_documents'] ?? [],
        ]);
    }

    public function uploadDocument(): void
    {
        $this->requireAdmin();

        $epId = (int) $this->request('ep_id', 0);
        $documentType = trim((string) $this->request('document_type', 'additional_proof'));
        $epModel = new EpApplication();

        if (!isset($_FILES['document_file'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Please select a file.'], 422);
        }

        $result = $epModel->storeDocument($epId, $documentType, $_FILES['document_file']);
        $epModel->ensureMissingDocumentNotification($epId);

        if (($result['success'] ?? false) !== true) {
            $this->jsonResponse($result, 422);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'document' => $result,
        ]);
    }

    public function update(): void
    {
        $this->requireAdmin();

        $epId = (int) $this->request('ep_id', 0);
        $epModel = new EpApplication();
        $ep = $epModel->findById($epId);

        if (!$ep) {
            $this->jsonResponse(['success' => false, 'message' => 'EP not found.'], 404);
        }

        $email = trim((string) $this->request('email', ''));
        if ($email === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Email is required.'], 422);
        }

        if ($this->emailExistsForOther($email, $epId)) {
            $this->jsonResponse(['success' => false, 'message' => 'This email already exists for another EP.'], 422);
        }

        $updated = $epModel->updateProfile($epId, [
            'first_name' => $this->request('first_name', ''),
            'last_name' => $this->request('last_name', ''),
            'email' => $email,
            'phone' => $this->request('phone', ''),
            'nationality' => $this->request('nationality', ''),
            'university' => $this->request('university', ''),
            'field_of_study' => $this->request('field_of_study', ''),
            'opportunity_title' => $this->request('opportunity_title', ''),
            'country' => $this->request('country', ''),
            'organization' => $this->request('organization', ''),
            'application_date' => $this->request('application_date', date('Y-m-d')),
            'opportunity_link' => $this->request('opportunity_link', ''),
            'status' => $this->request('status', 'applied'),
        ], (string) ($this->currentAdmin()['name'] ?? 'Admin'));

        if (!$updated) {
            $this->jsonResponse(['success' => false, 'message' => 'Unable to update EP.'], 422);
        }

        $epModel->ensureMissingDocumentNotification($epId);
        $fresh = $epModel->findById($epId);
        $this->jsonResponse([
            'success' => true,
            'message' => 'EP updated successfully.',
            'ep' => $fresh,
        ]);
    }

    public function downloadFolder(): void
    {
        $this->requireAdmin();

        $epId = (int) $this->request('ep_id', 0);
        $epModel = new EpApplication();
        $ep = $epModel->findById($epId);
        if (!$ep) {
            $_SESSION['ep_flash_error'] = 'EP not found.';
            $this->redirect('/ep-management');
        }

        $folderPath = $epModel->folderPath($ep);
        if (!is_dir($folderPath)) {
            $_SESSION['ep_flash_error'] = 'EP document folder not found.';
            $this->redirect('/ep-management?ep_id=' . $epId);
        }

        $downloadDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($downloadDir) && !mkdir($downloadDir, 0775, true) && !is_dir($downloadDir)) {
            $_SESSION['ep_flash_error'] = 'Unable to prepare ZIP download folder.';
            $this->redirect('/ep-management?ep_id=' . $epId);
        }

        $zipName = $ep['folder_name'] . '.zip';
        $zipPath = $downloadDir . DIRECTORY_SEPARATOR . $zipName;
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        if (!$this->createZipArchive($folderPath, $zipPath, (string) $ep['folder_name'])) {
            $_SESSION['ep_flash_error'] = 'Unable to create ZIP archive.';
            $this->redirect('/ep-management?ep_id=' . $epId);
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . (string) filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    public function statusData(): void
    {
        $this->requireAdmin();

        $epId = (int) $this->request('ep_id', 0);
        $epModel = new EpApplication();
        $ep = $epModel->findById($epId);
        if (!$ep) {
            $this->jsonResponse(['success' => false, 'message' => 'EP not found.'], 404);
        }

        $epModel->ensureMissingDocumentNotification($epId);
        $fresh = $epModel->findById($epId);

        $this->jsonResponse([
            'success' => true,
            'ep' => $fresh,
            'stages' => $epModel->stages(),
            'documents' => $fresh['documents'] ?? [],
            'history' => $fresh['history'] ?? [],
            'missing_documents' => $fresh['missing_documents'] ?? [],
            'progress_percent' => $fresh['progress_percent'] ?? 0,
        ]);
    }

    private function emailExists(string $email): bool
    {
        $stmt = pdo()->prepare('SELECT COUNT(*) FROM ep_applications WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function emailExistsForOther(string $email, int $epId): bool
    {
        $stmt = pdo()->prepare('SELECT COUNT(*) FROM ep_applications WHERE email = :email AND id <> :id');
        $stmt->execute([
            'email' => $email,
            'id' => $epId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function normalizeFilesArray(mixed $files): array
    {
        if (!is_array($files) || !isset($files['name'])) {
            return [];
        }

        if (is_array($files['name'])) {
            $normalized = [];
            foreach ($files['name'] as $index => $name) {
                $normalized[] = [
                    'name' => $name,
                    'type' => $files['type'][$index] ?? '',
                    'tmp_name' => $files['tmp_name'][$index] ?? '',
                    'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$index] ?? 0,
                ];
            }
            return $normalized;
        }

        return [$files];
    }

    private function findSelectedEp(array $eps, int $selectedEpId): ?array
    {
        foreach ($eps as $ep) {
            if ((int) ($ep['id'] ?? 0) === $selectedEpId) {
                return $ep;
            }
        }

        return null;
    }

    private function currentAdmin(): array
    {
        return admin_user() ?? [];
    }

    private function createZipArchive(string $folderPath, string $zipPath, string $baseFolder): bool
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return false;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folderPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $zip->addEmptyDir($baseFolder);
            foreach ($files as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $filePath = $file->getPathname();
                $relativePath = $baseFolder . DIRECTORY_SEPARATOR . substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }

            $zip->close();
            return is_file($zipPath);
        }

        if (PHP_OS_FAMILY === 'Windows' && function_exists('shell_exec')) {
            $folderArg = $this->powershellQuote($folderPath);
            $zipArg = $this->powershellQuote($zipPath);
            $command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "Compress-Archive -LiteralPath ' . $folderArg . ' -DestinationPath ' . $zipArg . ' -Force"';
            shell_exec($command);
            return is_file($zipPath);
        }

        return false;
    }

    private function powershellQuote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function jsonResponse(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function requireAdmin(): void
    {
        if (!is_admin_authenticated()) {
            $this->redirect('/login');
        }
    }
}
