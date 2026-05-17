<?php
declare(strict_types=1);

final class OpportunityController extends BaseController
{
    public function home(): void
    {
        $this->redirect('/upload');
    }

    public function create(): void
    {
        $opportunityModel = new Opportunity();
        $filters = $opportunityModel->getCsvFilterOptions();

        $this->view('opportunity/upload', [
            'config' => config(),
            'error' => null,
            'filterOptions' => $filters,
        ]);
    }

    public function store(): void
    {
        if (!isset($_FILES['cv_pdf'])) {
            $this->view('opportunity/upload', [
                'config' => config(),
                'error' => 'Please upload a PDF CV.',
            ]);
            return;
        }

        $uploader = new Cv();
        $result = $uploader->storeUploadedCv($_FILES['cv_pdf']);

        if (!$result['success']) {
            $this->view('opportunity/upload', [
                'config' => config(),
                'error' => $result['message'],
            ]);
            return;
        }

        $cv = $uploader->createFromUploadedFile($result['path']);
        // Collect optional CSV filters from the form
        $selectedDuration = $_POST['duration_filter'] ?? '';
        $selectedCountry = $_POST['country_filter'] ?? '';

        $opportunityModel = new Opportunity();
        $opportunities = $opportunityModel->syncFromScraper([
            'duration' => $selectedDuration ?: null,
            'country' => $selectedCountry ?: null,
        ]);
        $matcher = new MatchResult();
        $matches = $matcher->generateMatches($cv['parsed_data'], $opportunities, (int) $cv['id']);

        $_SESSION['flash_success'] = 'CV uploaded and matches generated successfully.';
        $_SESSION['last_cv_id'] = (int) $cv['id'];
        $_SESSION['last_cv_profile'] = $cv['parsed_data'];
        $_SESSION['last_matches'] = $matches;
        $_SESSION['last_opportunity_source'] = $this->summarizeOpportunitySource($opportunities);

        $this->redirect('/results');
    }

    public function results(): void
    {
        $matches = $_SESSION['last_matches'] ?? [];
        $cvId = $_SESSION['last_cv_id'] ?? null;
        $profile = $_SESSION['last_cv_profile'] ?? [];

        $this->view('opportunity/results', [
            'config' => config(),
            'matches' => $matches,
            'cvId' => $cvId,
            'profile' => $profile,
            'opportunitySource' => $_SESSION['last_opportunity_source'] ?? null,
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_success']);
    }

    public function dashboard(): void
    {
        $cvModel = new Cv();
        $opportunityModel = new Opportunity();
        $matchModel = new MatchResult();

        $this->view('dashboard/index', [
            'config' => config(),
            'cvs' => $cvModel->latest(5),
            'opportunities' => $opportunityModel->latest(8),
            'matches' => $matchModel->latest(8),
        ]);
    }

    private function summarizeOpportunitySource(array $opportunities): string
    {
        $csvCount = count(array_filter($opportunities, static fn (array $item): bool => ($item['source_type'] ?? '') === 'csv'));
        if ($csvCount > 0) {
            return sprintf('%d opportunities loaded from the CSV feed.', $csvCount);
        }

        $sampleCount = count(array_filter($opportunities, static fn (array $item): bool => ($item['source_type'] ?? '') === 'sample'));
        if ($sampleCount > 0) {
            return sprintf('%d fallback sample opportunities loaded.', $sampleCount);
        }

        return 'No opportunity feed could be loaded.';
    }
}
