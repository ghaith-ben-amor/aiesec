<?php
// Pagination helpers (offset not passed from controller — derive it here)
$offset = (($currentPage ?? 1) - 1) * ($perPage ?? 9);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Matching Results</h1>
        <p class="text-secondary mb-0">Ranked opportunities based on skill overlap and profile fit.</p>
    </div>
    <a href="<?= htmlspecialchars(url_path('/upload')) ?>" class="btn btn-outline-primary">Upload another CV</a>
</div>

<?php if (!empty($flashSuccess) && empty($countryNoMatch)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<?php if (!empty($countryNoMatch) && !empty($selectedCountry)): ?>
    <div class="alert alert-warning d-flex align-items-start gap-3">
        <span style="font-size:1.4rem;">⚠️</span>
        <div>
            <strong>No AIESEC opportunities available in <?= htmlspecialchars($selectedCountry) ?> for this account.</strong><br>
            <span class="small">Showing the best matches from all available countries instead, ranked by your CV skills.</span>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($opportunitySource)): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center gap-3">
        <span><?= htmlspecialchars($opportunitySource) ?></span>
        <a href="https://aiesec.org/search" target="_blank" class="btn btn-sm btn-outline-primary">Open AIESEC search</a>
    </div>
<?php endif; ?>

<!-- CV Analysis Card -->
<div class="card hero-card mb-4">
    <div class="card-body">
        <h2 class="h5 fw-semibold mb-3">CV Analysis</h2>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 bg-white">
                    <div class="small text-uppercase text-secondary fw-semibold mb-2">Skills</div>
                    <?php if (!empty($profile['skills'])): ?>
                        <?php foreach ($profile['skills'] as $skill): ?>
                            <span class="badge text-bg-primary me-1 mb-1"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-secondary small">No explicit skills detected.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 bg-white">
                    <div class="small text-uppercase text-secondary fw-semibold mb-2">Languages</div>
                    <?php if (!empty($profile['languages'])): ?>
                        <?php foreach ($profile['languages'] as $language): ?>
                            <span class="badge text-bg-success me-1 mb-1"><?= htmlspecialchars($language) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-secondary small">No languages detected.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 bg-white">
                    <div class="small text-uppercase text-secondary fw-semibold mb-2">Education</div>
                    <?php if (!empty($profile['education'])): ?>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($profile['education'] as $educationItem): ?>
                                <li><?= htmlspecialchars($educationItem) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-secondary small">No education details detected.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($profile['experience']['roles']) || !empty($profile['experience']['years'])): ?>
            <div class="mt-3 border-top pt-3">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Experience</div>
                <?php if (!empty($profile['experience']['years'])): ?>
                    <div class="small mb-2"><strong>Years / Periods:</strong> <?= htmlspecialchars(implode(', ', $profile['experience']['years'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($profile['experience']['roles'])): ?>
                    <?php foreach ($profile['experience']['roles'] as $role): ?>
                        <div class="small text-white fw-semibold mb-1"><?= htmlspecialchars($role) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['projects'])): ?>
            <div class="mt-3 border-top pt-3">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Projects & Portfolio</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($profile['projects'] as $proj): ?>
                        <span class="badge text-bg-secondary me-1 mb-1"><?= htmlspecialchars($proj) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['job_titles'])): ?>
            <div class="mt-3 border-top pt-3">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Target Job Roles</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($profile['job_titles'] as $job): ?>
                        <span class="badge text-bg-dark border me-1 mb-1"><?= htmlspecialchars($job) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['summary']) || !empty($profile['suggestions'])): ?>
            <div class="mt-3 border-top pt-3">
                <?php if (!empty($profile['summary'])): ?>
                    <div class="small text-uppercase text-secondary fw-semibold mb-2">Summary</div>
                    <p class="small text-secondary mb-2"><?= htmlspecialchars((string) $profile['summary']) ?></p>
                <?php endif; ?>

                <?php if (!empty($profile['suggestions']) && is_array($profile['suggestions'])): ?>
                    <div class="small text-uppercase text-secondary fw-semibold mb-2">Suggestions</div>
                    <ul class="mb-0 ps-3 small text-secondary">
                        <?php foreach ($profile['suggestions'] as $suggestion): ?>
                            <li><?= htmlspecialchars((string) $suggestion) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['_debug'])): ?>
            <div class="mt-3 border-top pt-3">
                <details>
                    <summary class="small text-muted cursor-pointer">Debug Log</summary>
                    <pre class="small bg-light p-2 mt-2 text-monospace" style="font-size: 0.8rem; max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($profile['_debug']) ?></pre>
                </details>
            </div>
        <?php endif; ?>
    </div>
</div>

    <?php
        // Count per programme from ALL session matches (consistent with controller logic)
        $allSessionMatches = $_SESSION['last_matches'] ?? [];
        $countAll = count($allSessionMatches);
        $countGTe = count(array_filter($allSessionMatches, function($m) {
            $cat = strtoupper(trim($m['category'] ?? ''));
            $title = strtolower($m['title'] ?? '');
            $desc = strtolower($m['description'] ?? '');
            return ($cat === 'GTE' || str_contains($title, 'teach') || str_contains($title, 'instructor') || str_contains($title, 'educator') || str_contains($title, 'trainer') || str_contains($title, 'coach') || str_contains($desc, 'global teacher'));
        }));
        $countGTa = count(array_filter($allSessionMatches, function($m) {
            $cat = strtoupper(trim($m['category'] ?? ''));
            $title = strtolower($m['title'] ?? '');
            $desc = strtolower($m['description'] ?? '');
            $isTeacher = ($cat === 'GTE' || str_contains($title, 'teach') || str_contains($title, 'instructor') || str_contains($title, 'educator') || str_contains($title, 'trainer') || str_contains($title, 'coach') || str_contains($desc, 'global teacher'));
            $isVolunteer = ($cat === 'GV' || str_contains($desc, 'global volunteer'));
            return ($cat === 'GTA' || (!$isTeacher && !$isVolunteer));
        }));
        $countGV  = count(array_filter($allSessionMatches, function($m) {
            $cat = strtoupper(trim($m['category'] ?? ''));
            $desc = strtolower($m['description'] ?? '');
            return ($cat === 'GV' || str_contains($desc, 'global volunteer'));
        }));

        $activeProg      = $progFilter ?? 'ALL';
        $activeProgUpper = strtoupper(trim($activeProg));
        $activeCountry   = $countryFilter ?? '';
        $activeDuration  = $durationFilter ?? '';
        $activeQuery     = $searchQuery ?? '';

        // Helper to build URL with preserved query parameters
        $buildFilterUrl = function(array $overrides = []) use ($activeProg, $activeCountry, $activeDuration, $activeQuery): string {
            $params = [
                'prog'     => $activeProg !== 'ALL' ? $activeProg : null,
                'country'  => $activeCountry !== '' ? $activeCountry : null,
                'duration' => $activeDuration !== '' ? $activeDuration : null,
                'q'        => $activeQuery !== '' ? $activeQuery : null,
                'page'     => 1,
            ];
            foreach ($overrides as $k => $v) {
                if ($v === null || $v === '' || $v === 'ALL') {
                    unset($params[$k]);
                } else {
                    $params[$k] = $v;
                }
            }
            $filtered = array_filter($params, fn($val) => $val !== null && $val !== '');
            return '?' . http_build_query($filtered);
        };
    ?>

    <!-- Modern Filter Toolbar (Always visible) -->
    <div class="card bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-4 p-3 mb-4 shadow-sm">
        <form method="get" action="" class="row g-2 align-items-center">

            <!-- Programme Filter Pills -->
            <div class="col-12 mb-2 d-flex gap-2 flex-wrap align-items-center">
                <span class="text-secondary small fw-bold me-1 text-uppercase" style="letter-spacing:0.5px;">Programme:</span>
                <?php
                $tabs = [
                    'ALL' => ['label' => '🌐 All Opportunities', 'count' => $countAll],
                    'GTa' => ['label' => '💼 Global Talent',      'count' => $countGTa],
                    'GTe' => ['label' => '🎓 Global Teacher',     'count' => $countGTe],
                    'GV'  => ['label' => '🌍 Global Volunteer',  'count' => $countGV],
                ];
                foreach ($tabs as $code => $tab):
                    $codeUpper = strtoupper($code);
                    $isActive = ($activeProgUpper === $codeUpper || 
                                ($codeUpper === 'GTA' && in_array($activeProgUpper, ['GT', 'TALENT', 'GLOBAL-TALENT'], true)) ||
                                ($codeUpper === 'GTE' && in_array($activeProgUpper, ['GTE', 'TEACHER', 'GLOBAL-TEACHER'], true)) ||
                                ($codeUpper === 'GV' && in_array($activeProgUpper, ['VOLUNTEER', 'GLOBAL-VOLUNTEER'], true)));
                    $url = $buildFilterUrl(['prog' => $code]);
                ?>
                    <a href="<?= htmlspecialchars($url) ?>" class="btn btn-sm <?= $isActive ? 'btn-primary shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1">
                        <?= $tab['label'] ?>
                        <?php if ($tab['count'] > 0): ?>
                            <span class="badge <?= $isActive ? 'bg-white text-primary' : 'bg-secondary' ?> ms-1"><?= $tab['count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Keep active prog in form when submitting other filters -->
            <?php if ($activeProg !== 'ALL'): ?>
                <input type="hidden" name="prog" value="<?= htmlspecialchars($activeProg) ?>">
            <?php endif; ?>

            <!-- Search input -->
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary">🔍</span>
                    <input type="text" name="q" class="form-control bg-dark border-secondary border-opacity-25 text-white"
                           placeholder="Search title, skills, keyword..." value="<?= htmlspecialchars($activeQuery) ?>">
                </div>
            </div>

            <!-- Location dropdown -->
            <div class="col-sm-6 col-md-3">
                <select name="country" class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" onchange="this.form.submit()">
                    <option value="">🌐 All Locations / Countries</option>
                    <?php foreach (($availableLocations ?? []) as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>" <?= $activeCountry === $loc ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Duration dropdown -->
            <div class="col-sm-6 col-md-3">
                <select name="duration" class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" onchange="this.form.submit()">
                    <option value="">⏱️ All Durations</option>
                    <?php foreach (($availableDurations ?? []) as $dur): ?>
                        <option value="<?= htmlspecialchars($dur) ?>" <?= $activeDuration === $dur ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dur) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-md-2 d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-sm btn-outline-primary px-3">Filter</button>
                <?php if ($activeProg !== 'ALL' || $activeCountry !== '' || $activeDuration !== '' || $activeQuery !== ''): ?>
                    <a href="?page=1" class="btn btn-sm btn-outline-danger px-2" title="Reset all filters">✕ Reset</a>
                <?php endif; ?>
            </div>

        </form>
    </div>

<?php if (empty($matches)): ?>

    <div class="text-center py-5">
        <div class="mb-4" style="font-size: 4rem; line-height: 1;">🔍</div>
        <h2 class="h4 fw-bold mb-2">No opportunities found for the selected filters</h2>
        <p class="text-secondary mb-4" style="max-width: 500px; margin: 0 auto;">
            Try relaxing your filter criteria (e.g. choose "All Programmes", clear the search query, or select another country).
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="?page=1" class="btn btn-primary px-4">
                🔄 Clear All Filters
            </a>
            <a href="<?= htmlspecialchars(url_path('/upload')) ?>" class="btn btn-outline-light px-4">
                Upload New CV
            </a>
        </div>
    </div>

<?php else: ?>

<!-- Results summary bar -->
    <?php
        $goodMatches = count(array_filter($matches, fn($m) => ($m['score'] ?? 0) >= 30));
        $allGood     = count(array_filter(
            array_slice($_SESSION['last_matches'] ?? [], 0),
            fn($m) => ($m['score'] ?? 0) >= 30
        ));
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="text-secondary small">
            Showing
            <strong class="text-white"><?= $offset + 1 ?>–<?= min($offset + $perPage, $allMatchesCount) ?></strong>
            of <strong class="text-white"><?= $allMatchesCount ?></strong> opportunities
            <?php if (!empty($activeCountry)): ?>
                in <strong class="text-white"><?= htmlspecialchars($activeCountry) ?></strong>
            <?php endif; ?>
            <?php if (!empty($activeDuration)): ?>
                · <span class="badge text-bg-secondary"><?= htmlspecialchars($activeDuration) ?></span>
            <?php endif; ?>
            <?php if ($activeProg !== 'ALL'): ?>
                · <span class="badge text-bg-primary"><?= htmlspecialchars($activeProg === 'GTe' ? 'Global Teacher' : ($activeProg === 'GTa' ? 'Global Talent' : 'Global Volunteer')) ?></span>
            <?php endif; ?>
            <?php if (!empty($activeQuery)): ?>
                · matching &ldquo;<span class="text-info"><?= htmlspecialchars($activeQuery) ?></span>&rdquo;
            <?php endif; ?>
            <?php if ($allGood > 0): ?>
                · <span class="text-success fw-semibold"><?= $allGood ?> good match<?= $allGood > 1 ? 'es' : '' ?> (≥30%)</span>
            <?php endif; ?>
        </div>
        <div class="text-secondary small">Page <?= $currentPage ?> / <?= $totalPages ?></div>
    </div>

    <!-- Opportunity cards -->
    <div class="row g-4" id="results-grid">
        <?php foreach ($matches as $match): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 hero-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="h5 fw-semibold mb-1"><?= htmlspecialchars($match['title']) ?></h2>
                                <?php if (!empty($match['company'])): ?>
                                    <div class="small text-secondary"><?= htmlspecialchars($match['company']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php
                                $sc = (float)($match['score'] ?? 0);
                                if ($sc >= 60)      { $badgeClass = 'score-high';   $badgeLabel = '🟢'; }
                                elseif ($sc >= 30)  { $badgeClass = 'score-med';    $badgeLabel = '🟡'; }
                                elseif ($sc >= 10)  { $badgeClass = 'score-low';    $badgeLabel = '🟠'; }
                                else                { $badgeClass = 'score-none';   $badgeLabel = '⚪'; }
                            ?>
                            <span class="score-badge-pill <?= $badgeClass ?>"><?= $badgeLabel ?> <?= $sc ?>%</span>
                        </div>
                        <div class="text-secondary mb-2"><strong>Location:</strong> <?= htmlspecialchars($match['location']) ?></div>
                        <div class="small text-secondary mb-2 d-flex gap-1 align-items-center flex-wrap">
                            <?php
                                $cat = $match['category'] ?? '';
                                $title = $match['title'] ?? '';
                                if ($cat === 'GTe' || stripos($title, 'teach') !== false || stripos($title, 'instructor') !== false || stripos($title, 'educator') !== false) {
                                    $catBadge = '🎓 Global Teacher';
                                    $catClass = 'text-bg-warning';
                                } elseif ($cat === 'GTa') {
                                    $catBadge = '💼 Global Talent';
                                    $catClass = 'text-bg-primary';
                                } else {
                                    $catBadge = '🌍 Global Volunteer';
                                    $catClass = 'text-bg-success';
                                }
                            ?>
                            <span class="badge <?= $catClass ?> me-1"><?= $catBadge ?></span>
                            <?php if (!empty($match['duration'])): ?>
                                <span class="badge text-bg-dark border border-secondary border-opacity-50"><?= htmlspecialchars($match['duration']) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-secondary flex-grow-1"><?= htmlspecialchars($match['description']) ?></p>
                        <?php $exactMatchedSkills = is_array($match['matched_skills'] ?? null) ? $match['matched_skills'] : []; ?>
                        <div class="mb-3">
                            <div class="small fw-semibold text-uppercase text-secondary mb-2">Matched skills</div>
                            <?php if (!empty($exactMatchedSkills)): ?>
                                <?php foreach ($exactMatchedSkills as $skill): ?>
                                    <span class="badge text-bg-primary me-1 mb-1"><?= htmlspecialchars($skill) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="small text-secondary">No direct skill overlap — ranked by profile relevance.</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($match['matched_experience'])): ?>
                            <div class="mb-3">
                                <div class="small fw-semibold text-uppercase text-secondary mb-1">💼 Matched experience</div>
                                <?php foreach ($match['matched_experience'] as $exp): ?>
                                    <span class="badge text-bg-info text-dark me-1 mb-1"><?= htmlspecialchars($exp) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($match['matched_job_roles'])): ?>
                            <div class="mb-3">
                                <div class="small fw-semibold text-uppercase text-secondary mb-1">🎯 Job role match</div>
                                <?php foreach ($match['matched_job_roles'] as $jobRole): ?>
                                    <span class="badge text-bg-secondary me-1 mb-1"><?= htmlspecialchars($jobRole) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($match['matched_projects'])): ?>
                            <div class="mb-3">
                                <div class="small fw-semibold text-uppercase text-secondary mb-1">🚀 Matched projects</div>
                                <?php foreach ($match['matched_projects'] as $proj): ?>
                                    <span class="badge text-bg-warning text-dark me-1 mb-1"><?= htmlspecialchars($proj) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($match['matched_languages'])): ?>
                            <div class="mb-3">
                                <div class="small fw-semibold text-uppercase text-secondary mb-1">🌐 Matched languages</div>
                                <?php foreach (($match['matched_languages'] ?? []) as $language): ?>
                                    <span class="badge text-bg-success me-1 mb-1"><?= htmlspecialchars($language) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($match['reasons']) && is_array($match['reasons'])): ?>
                            <div class="mb-3 p-2 rounded border bg-dark-subtle">
                                <div class="text-secondary fw-semibold mb-1" style="font-size:0.75rem; letter-spacing: 0.05em;">WHY THIS MATCHES</div>
                                <ul class="mb-0 ps-3 small text-secondary" style="font-size: 0.8rem; line-height: 1.4;">
                                    <?php foreach (array_slice($match['reasons'], 0, 3) as $reason): ?>
                                        <li><?= htmlspecialchars($reason) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($match['source_type'])): ?>
                            <div class="small text-success mb-3"><?= htmlspecialchars(ucfirst((string) $match['source_type'])) ?> opportunity</div>
                        <?php endif; ?>
                        <?php if (!empty($match['source_url'])): ?>
                            <?php $isDirectUrl = str_contains((string) $match['source_url'], '/opportunity/'); ?>
                            <a href="<?= htmlspecialchars($match['source_url']) ?>" target="_blank" class="btn btn-sm btn-outline-dark mt-auto">
                                <?= $isDirectUrl ? 'View on AIESEC' : 'Open AIESEC search' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php
        $pagQuery = [];
        if (!empty($activeProg) && $activeProg !== 'ALL') $pagQuery['prog'] = $activeProg;
        if (!empty($activeCountry))                       $pagQuery['country'] = $activeCountry;
        if (!empty($activeDuration))                      $pagQuery['duration'] = $activeDuration;
        if (!empty($activeQuery))                         $pagQuery['q'] = $activeQuery;

        $makePageUrl = function(int $pageNum) use ($pagQuery): string {
            $q = $pagQuery;
            $q['page'] = $pageNum;
            return '?' . http_build_query($q);
        };
    ?>
    <?php if ($totalPages > 1): ?>
    <nav class="mt-5 d-flex justify-content-center" aria-label="Results pagination">
        <ul class="pagination pagination-lg gap-1 flex-wrap justify-content-center" id="paginationNav">

            <!-- Previous -->
            <?php $prevPage = $currentPage - 1; ?>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link paginator-link rounded-3" href="<?= $makePageUrl($prevPage) ?>" aria-label="Previous">
                    ‹
                </a>
            </li>

            <?php
            $range = [];
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2) {
                    $range[] = $i;
                }
            }
            $prev = null;
            foreach ($range as $p):
                if ($prev !== null && $p - $prev > 1): ?>
                    <li class="page-item disabled"><span class="page-link paginator-link rounded-3">…</span></li>
                <?php endif; ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link paginator-link rounded-3" href="<?= $makePageUrl($p) ?>"><?= $p ?></a>
                </li>
            <?php $prev = $p; endforeach; ?>

            <!-- Next -->
            <?php $nextPage = $currentPage + 1; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link paginator-link rounded-3" href="<?= $makePageUrl($nextPage) ?>" aria-label="Next">
                    ›
                </a>
            </li>
        </ul>
    </nav>

    <style>
        /* ── Score badge pills ────────────────────────────────────── */
        .score-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .score-high  { background: rgba(34,197,94,0.15);  border-color: rgba(34,197,94,0.4);  color: #4ade80; }
        .score-med   { background: rgba(234,179,8,0.15);  border-color: rgba(234,179,8,0.4);  color: #facc15; }
        .score-low   { background: rgba(249,115,22,0.15); border-color: rgba(249,115,22,0.4); color: #fb923c; }
        .score-none  { background: rgba(148,163,184,0.1); border-color: rgba(148,163,184,0.2);color: #64748b; }

        /* ── Paginator ───────────────────────────────────────────── */
        .paginator-link {
            background: rgba(255,255,255,0.05) !important;
            border-color: rgba(255,255,255,0.1) !important;
            color: #cbd5e1 !important;
            min-width: 44px;
            text-align: center;
            font-weight: 500;
            transition: all 0.18s ease;
        }
        .paginator-link:hover {
            background: rgba(99,102,241,0.2) !important;
            border-color: #6366f1 !important;
            color: #a5b4fc !important;
        }
        .page-item.active .paginator-link {
            background: #6366f1 !important;
            border-color: #6366f1 !important;
            color: #fff !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.3);
        }
        .page-item.disabled .paginator-link {
            opacity: 0.35 !important;
            cursor: not-allowed;
        }
    </style>

    <script>
        // Scroll to top of results grid when paginating
        document.querySelectorAll('.paginator-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                if (!this.closest('.page-item').classList.contains('disabled')) {
                    setTimeout(function() {
                        var grid = document.getElementById('results-grid');
                        if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 50);
                }
            });
        });
    </script>
    <?php endif; ?>

<?php endif; ?>

