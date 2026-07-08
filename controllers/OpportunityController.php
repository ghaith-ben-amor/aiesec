<?php
declare(strict_types=1);

final class OpportunityController extends BaseController
{
    public function home(): void
    {
		if (is_authenticated()) {
			$this->redirect('/upload');
		}

		$this->redirect('/login');
    }

    public function cvBuilder(): void
    {
        $this->requireAuth();

        $profile = $this->buildCvBuilderProfile($_SESSION['last_cv_profile'] ?? [], current_user() ?? []);

        $this->view('opportunity/cv_builder', [
            'config' => config(),
            'profile' => $profile,
        ]);
    }

    public function create(): void
    {
		$this->requireAuth();
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
		$this->requireAuth();

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
        // Collect optional filters from the form
        $selectedDuration  = $_POST['duration_filter']  ?? '';
        $selectedCountry   = $_POST['country_filter']   ?? '';
        $selectedProgramme = $_POST['programme_filter'] ?? '';

        $opportunityModel = new Opportunity();

        // Load opportunities from DB with filters
        $opportunities = $opportunityModel->syncFromScraper([
            'duration'   => $selectedDuration  ?: null,
            'country'    => $selectedCountry   ?: null,
            'programme'  => $selectedProgramme ?: null,
        ]);

        // Detect if the country filter produced no exact results
        $countryNoMatch = false;
        if (!empty($selectedCountry) && !empty($opportunities)) {
            $countryLower    = strtolower(trim($selectedCountry));
            $hasExactCountry = false;
            foreach ($opportunities as $opp) {
                if (str_contains(strtolower($opp['location'] ?? ''), $countryLower)) {
                    $hasExactCountry = true;
                    break;
                }
            }
            if (!$hasExactCountry) {
                $countryNoMatch = true;
            }
        }

        $matcher = new MatchResult();
        $matches = $matcher->generateMatches($cv['parsed_data'], $opportunities, (int) $cv['id']);

        $_SESSION['flash_success']            = 'CV uploaded and matches generated successfully.';
        $_SESSION['last_cv_id']               = (int) $cv['id'];
        $_SESSION['last_cv_profile']          = $cv['parsed_data'];
        $_SESSION['last_matches']             = $matches;
        $_SESSION['last_selected_country']    = $selectedCountry   ?: null;
        $_SESSION['last_selected_duration']   = $selectedDuration  ?: null;
        $_SESSION['last_selected_programme']  = $selectedProgramme ?: null;
        $_SESSION['last_country_no_match']    = $countryNoMatch;
        $_SESSION['last_opportunity_source']  = $this->summarizeOpportunitySource($opportunities, $selectedCountry, $countryNoMatch);

        $this->redirect('/results');
    }

    public function results(): void
    {
		$this->requireAuth();

        $allMatches = $_SESSION['last_matches'] ?? [];
        $cvId       = $_SESSION['last_cv_id'] ?? null;
        $profile    = $_SESSION['last_cv_profile'] ?? [];

        // --- Pagination ---
        $perPage     = 9;
        $totalMatches = count($allMatches);
        $totalPages  = max(1, (int) ceil($totalMatches / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset      = ($currentPage - 1) * $perPage;
        $matches     = array_slice($allMatches, $offset, $perPage);

        $this->view('opportunity/results', [
            'config'            => config(),
            'matches'           => $matches,
            'allMatchesCount'   => $totalMatches,
            'currentPage'       => $currentPage,
            'totalPages'        => $totalPages,
            'perPage'           => $perPage,
            'cvId'              => $cvId,
            'profile'           => $profile,
            'selectedCountry'   => $_SESSION['last_selected_country']   ?? null,
            'selectedDuration'  => $_SESSION['last_selected_duration']  ?? null,
            'selectedProgramme' => $_SESSION['last_selected_programme'] ?? null,
            'countryNoMatch'    => $_SESSION['last_country_no_match']   ?? false,
            'opportunitySource' => $_SESSION['last_opportunity_source'] ?? null,
            'flashSuccess'      => $_SESSION['flash_success'] ?? null,
        ]);

        unset($_SESSION['flash_success']);

    }

    public function dashboard(): void
    {
		$this->requireAuth();

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

    private function summarizeOpportunitySource(array $opportunities, string $selectedCountry = '', bool $countryNoMatch = false): string
    {
        $total = count($opportunities);
        if ($total === 0) {
            return '';
        }

        $apiCount = count(array_filter($opportunities, static fn (array $item): bool => ($item['source_type'] ?? '') === 'api'));

        if ($apiCount > 0) {
            if ($countryNoMatch && $selectedCountry !== '') {
                return sprintf('No opportunities found in %s — showing %d best matches from all available countries.', $selectedCountry, $apiCount);
            }
            return sprintf('%d opportunities loaded from the AIESEC API.', $apiCount);
        }

        $sampleCount = count(array_filter($opportunities, static fn (array $item): bool => ($item['source_type'] ?? '') === 'sample'));
        if ($sampleCount > 0) {
            return sprintf('%d sample opportunities shown — sync from the admin panel to load real data.', $sampleCount);
        }

        return '';
    }

    private function buildCvBuilderProfile(array $parsedProfile, array $user): array
    {
        $parsedSkills = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $parsedProfile['skills'] ?? [])));
        $parsedLanguages = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $parsedProfile['languages'] ?? [])));
        $parsedEducation = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $parsedProfile['education'] ?? [])));
        $parsedRoles = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $parsedProfile['experience']['roles'] ?? [])));

        return [
            'header' => [
                'name' => (string) ($user['name'] ?? 'Your Name'),
                'subtitle' => 'Integrated Preparatory Cycle – 1st Year Engineering Cycle',
                'phone' => '',
                'email' => (string) ($user['email'] ?? ''),
                'address' => '',
            ],
            'summary' => (string) ($parsedProfile['summary'] ?? 'Engineering student passionate about software development, embedded systems, IoT, fintech platforms, and web technologies.'),
            'education' => [
                [
                    'title' => (string) ($parsedEducation[0] ?? 'Mathematics Baccalaureate'),
                    'date' => '2022',
                ],
                [
                    'title' => (string) ($parsedEducation[1] ?? 'Engineering Cycle Student, ESPRIT'),
                    'date' => '2023 – Present',
                ],
            ],
            'experience' => [
                [
                    'title' => (string) ($parsedRoles[0] ?? 'Seller – LC Waikiki'),
                    'date' => '2025 – Present',
                    'bullets' => [
                        'Assisted customers and provided product recommendations.',
                        'Managed product organization and stock presentation in store.',
                        'Developed communication, teamwork, and sales skills.',
                    ],
                ],
                [
                    'title' => 'Freelance Graphic Designer',
                    'date' => '2023 – Present',
                    'bullets' => [
                        'Designed logos, posters, banners, and social media visuals.',
                        'Created UI/UX prototypes for mobile and web interfaces.',
                        'Delivered branding kits and visual identities using Adobe Illustrator and Photoshop.',
                    ],
                ],
                [
                    'title' => 'Intern – Web Developer (ETAP)',
                    'date' => 'July 2024',
                    'bullets' => [
                        'Developed a web application for employee management (attendance, leave requests).',
                        'Technologies: HTML, CSS, JS, PHP, MVC.',
                    ],
                ],
            ],
            'projects' => [
                [
                    'title' => 'FinTrack – Fintech Platform',
                    'bullets' => [
                        'Fintech platform developed using JavaFX for desktop application and Symfony for web application.',
                        'Implemented user management, authentication, transactions, and financial tracking.',
                        'Technologies: Java, JavaFX, Symfony, MySQL.',
                    ],
                ],
                [
                    'title' => 'InnoVest – Web App then Mobile',
                    'bullets' => [
                        'University project: platform connecting investors and entrepreneurs.',
                        'Technologies: HTML, CSS, JS, PHP, MVC, FlutterFlow.',
                    ],
                ],
                [
                    'title' => 'SmartFix Desktop App',
                    'bullets' => [
                        'Maintenance management desktop app with advanced UI and Arduino integration.',
                        'Technologies: C++, QT Creator, Arduino Uno.',
                    ],
                ],
                [
                    'title' => 'Smart Greenhouse',
                    'bullets' => [
                        'Automatic greenhouse with temperature/humidity monitoring and ventilation control.',
                        'Technologies: PIC16F877, Proteus ISIS, MikroC, MPLAB.',
                    ],
                ],
                [
                    'title' => 'Network Project',
                    'bullets' => [
                        'Complete multi-router topology with VLSM and static/dynamic routing.',
                        'Technologies: GNS3, Unix.',
                    ],
                ],
                [
                    'title' => 'TMUZYA Game',
                    'bullets' => [
                        '2D game developed in my first year showcasing Tunisian culture.',
                        'Technologies: C, SDL.',
                    ],
                ],
            ],
            'community' => [
                'organization' => 'IEEE ISSAT Mateur Student Branch',
                'roles' => [
                    'Active Member',
                    'Vice-Chair – IAS Chapter',
                ],
                'certifications' => 'Innovation Camp – Injaz, STM32 Initiation, UI/UX Design – 9antra.',
            ],
            'skills' => [
                [
                    'label' => 'Software Development',
                    'value' => $this->commaList($parsedSkills, ['C', 'C#', 'C++', 'Java', 'JavaFX', 'Symfony', 'SDL', 'QT Creator', 'PHP', 'HTML', 'CSS', 'JavaScript', 'FlutterFlow']),
                ],
                [
                    'label' => 'Sales & Communication Skills',
                    'value' => 'Customer service, Sales, Teamwork, Communication.',
                ],
                [
                    'label' => 'Adobe & Creative Tools',
                    'value' => 'Adobe Photoshop, Adobe Illustrator, Adobe Premiere Pro.',
                ],
                [
                    'label' => 'Embedded Systems / IoT',
                    'value' => 'Arduino, ESP32, STM32 (initiation), MikroC, PIC16F877, Proteus ISIS.',
                ],
                [
                    'label' => 'Languages',
                    'value' => $this->commaList($parsedLanguages, ['Arabic', 'French', 'English']),
                ],
            ],
        ];
    }

    private function commaList(array $preferred, array $fallback): string
    {
        $items = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $preferred)));
        if ($items === []) {
            $items = $fallback;
        }

        return implode(', ', $items) . '.';
    }
}
