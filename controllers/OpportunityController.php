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
        $selectedDuration  = trim($_POST['duration_filter']  ?? '');
        $selectedCountry   = trim($_POST['country_filter']   ?? '');
        $selectedProgramme = trim($_POST['programme_filter'] ?? '');

        $opportunityModel = new Opportunity();

        // Match against ALL opportunities so the user can freely filter on the results page
        $opportunities = $opportunityModel->syncFromScraper([]);

        $matcher = new MatchResult();
        $matches = $matcher->generateMatches($cv['parsed_data'], $opportunities, (int) $cv['id']);

        $_SESSION['flash_success']            = 'CV uploaded and matches generated successfully.';
        $_SESSION['last_cv_id']               = (int) $cv['id'];
        $_SESSION['last_cv_profile']          = $cv['parsed_data'];
        $_SESSION['last_matches']             = $matches;
        $_SESSION['last_opportunity_source']  = $this->summarizeOpportunitySource($opportunities, $selectedCountry, false);

        // Build redirect query with any initial filters selected on upload form
        $redirectParams = [];
        if (!empty($selectedProgramme)) {
            $redirectParams['prog'] = $selectedProgramme;
        }
        if (!empty($selectedCountry)) {
            $redirectParams['country'] = $selectedCountry;
        }
        if (!empty($selectedDuration)) {
            $redirectParams['duration'] = $selectedDuration;
        }

        $redirectUrl = '/results' . (!empty($redirectParams) ? '?' . http_build_query($redirectParams) : '');
        $this->redirect($redirectUrl);
    }

    public function results(): void
    {
        $this->requireAuth();

        $allMatches = $_SESSION['last_matches'] ?? [];
        $cvId       = $_SESSION['last_cv_id'] ?? null;
        $profile    = $_SESSION['last_cv_profile'] ?? [];

        // Extract available locations and durations for filter dropdowns
        $availableLocations = [];
        $availableDurations = [];
        foreach ($allMatches as $m) {
            $loc = trim($m['location'] ?? '');
            if ($loc !== '' && !in_array($loc, $availableLocations, true)) {
                $availableLocations[] = $loc;
            }
            $dur = trim($m['duration'] ?? '');
            if ($dur !== '' && !in_array($dur, $availableDurations, true)) {
                $availableDurations[] = $dur;
            }
        }
        sort($availableLocations, SORT_STRING | SORT_FLAG_CASE);
        sort($availableDurations, SORT_STRING | SORT_FLAG_CASE);

        if (!in_array('Egypt', $availableLocations, true)) {
            array_unshift($availableLocations, 'Egypt');
        }

        // --- Filters from GET request ---
        $progFilter     = $_GET['prog'] ?? 'ALL';
        $countryFilter  = trim($_GET['country'] ?? '');
        $durationFilter = trim($_GET['duration'] ?? '');
        $searchQuery    = trim($_GET['q'] ?? '');

        $filteredMatches = $allMatches;

        // 1. Programme Filter
        if ($progFilter !== 'ALL' && $progFilter !== '') {
            $filteredMatches = array_values(array_filter($filteredMatches, function($m) use ($progFilter) {
                $cat = $m['category'] ?? '';
                $title = $m['title'] ?? '';
                $isTeacher = ($cat === 'GTe' || stripos($title, 'teach') !== false || stripos($title, 'instructor') !== false || stripos($title, 'educator') !== false);
                $isVolunteer = ($cat === 'GV');
                $isTalent = ($cat === 'GTa' && !$isTeacher);

                if ($progFilter === 'GTe') return $isTeacher;
                if ($progFilter === 'GTa') return $isTalent;
                if ($progFilter === 'GV')  return $isVolunteer;
                return true;
            }));
        }

        // 2. Country / Location Filter
        if ($countryFilter !== '' && $countryFilter !== 'ALL') {
            $cLower = strtolower($countryFilter);
            $filteredMatches = array_values(array_filter($filteredMatches, function($m) use ($cLower) {
                $locLower   = strtolower($m['location'] ?? '');
                $descLower  = strtolower($m['description'] ?? '');
                $titleLower = strtolower($m['title'] ?? '');
                return str_contains($locLower, $cLower)
                    || str_contains($descLower, $cLower)
                    || str_contains($descLower, 'mc: ' . $cLower)
                    || str_contains($titleLower, $cLower);
            }));
        }

        // 3. Duration Filter
        if ($durationFilter !== '' && $durationFilter !== 'ALL') {
            $dLower = strtolower($durationFilter);
            $filteredMatches = array_values(array_filter($filteredMatches, function($m) use ($dLower) {
                $durLower   = strtolower($m['duration'] ?? '');
                $titleLower = strtolower($m['title'] ?? '');
                return str_contains($durLower, $dLower) || str_contains($titleLower, $dLower);
            }));
        }

        // 4. Search Query Filter
        if ($searchQuery !== '') {
            $qLower = strtolower($searchQuery);
            $filteredMatches = array_values(array_filter($filteredMatches, function($m) use ($qLower) {
                $title  = strtolower($m['title'] ?? '');
                $loc    = strtolower($m['location'] ?? '');
                $desc   = strtolower($m['description'] ?? '');
                $comp   = strtolower($m['company'] ?? '');
                $skills = is_array($m['skills'] ?? null) ? implode(' ', $m['skills']) : strtolower((string)($m['skills'] ?? ''));
                return str_contains($title, $qLower)
                    || str_contains($loc, $qLower)
                    || str_contains($desc, $qLower)
                    || str_contains($comp, $qLower)
                    || str_contains(strtolower($skills), $qLower);
            }));
        }

        // --- Pagination ---
        $perPage      = 9;
        $totalMatches = count($filteredMatches);
        $totalPages   = max(1, (int) ceil($totalMatches / $perPage));
        $currentPage  = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset       = ($currentPage - 1) * $perPage;
        $matches      = array_slice($filteredMatches, $offset, $perPage);

        $this->view('opportunity/results', [
            'config'             => config(),
            'matches'            => $matches,
            'allMatchesCount'    => $totalMatches,
            'currentPage'        => $currentPage,
            'totalPages'         => $totalPages,
            'perPage'            => $perPage,
            'cvId'               => $cvId,
            'profile'            => $profile,
            'progFilter'         => $progFilter,
            'countryFilter'      => $countryFilter,
            'durationFilter'     => $durationFilter,
            'searchQuery'        => $searchQuery,
            'availableLocations' => $availableLocations,
            'availableDurations' => $availableDurations,
            'opportunitySource'  => $_SESSION['last_opportunity_source'] ?? null,
            'flashSuccess'       => $_SESSION['flash_success'] ?? null,
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
