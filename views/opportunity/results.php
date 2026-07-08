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
                    <div class="small mb-2"><strong>Years detected:</strong> <?= htmlspecialchars(implode(', ', $profile['experience']['years'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($profile['experience']['roles'])): ?>
                    <?php foreach ($profile['experience']['roles'] as $role): ?>
                        <div class="small text-secondary"><?= htmlspecialchars($role) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

<!-- Results or No-Match page -->
<?php if (empty($matches)): ?>

    <div class="text-center py-5">
        <div class="mb-4" style="font-size: 4.5rem; line-height: 1;">
            <?= !empty($selectedCountry) ? '🌍' : '🔍' ?>
        </div>

        <?php if (!empty($selectedCountry)): ?>

            <h2 class="h3 fw-bold mb-2">
                No opportunities matched your CV in <span style="color: var(--color-accent, #6366f1);"><?= htmlspecialchars($selectedCountry) ?></span>
            </h2>
            <p class="text-secondary mb-4" style="max-width: 540px; margin: 0 auto;">
                Your profile was compared against all available AIESEC opportunities in
                <strong><?= htmlspecialchars($selectedCountry) ?></strong>.
                No matching positions were found for your current skill set in this country.
            </p>

            <?php if (!empty($profile['skills'])): ?>
                <div class="mb-4">
                    <div class="small text-uppercase text-secondary fw-semibold mb-2">Skills searched from your CV</div>
                    <?php foreach ($profile['skills'] as $skill): ?>
                        <span class="badge text-bg-primary me-1 mb-1"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                <a href="<?= htmlspecialchars(url_path('/upload')) ?>" class="btn btn-primary btn-lg px-5">
                    🔄 Try without country filter
                </a>
                <a href="https://aiesec.org/search" target="_blank" class="btn btn-outline-light btn-lg px-4">
                    Browse AIESEC opportunities
                </a>
            </div>

            <div class="card hero-card p-4 mx-auto text-start" style="max-width: 440px;">
                <div class="small fw-semibold mb-3">💡 What you can do next</div>
                <ul class="mb-0 small text-secondary ps-3" style="line-height: 1.8;">
                    <li>Try selecting a different country from the list</li>
                    <li>Leave the country filter empty to see all opportunities</li>
                    <li>Add more skills to your CV to increase match chances</li>
                    <li>Check AIESEC.org directly for the latest listings</li>
                </ul>
            </div>

        <?php else: ?>

            <h2 class="h3 fw-bold mb-2">No matches found</h2>
            <p class="text-secondary mb-4" style="max-width: 460px; margin: 0 auto;">
                No AIESEC opportunities were found matching your profile.
                Make sure the opportunity database has been synced from the admin panel, then try again.
            </p>
            <a href="<?= htmlspecialchars(url_path('/upload')) ?>" class="btn btn-primary btn-lg px-5">
                Upload another CV
            </a>

        <?php endif; ?>
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
            <?php if (!empty($selectedCountry) && !$countryNoMatch): ?>
                in <strong class="text-white"><?= htmlspecialchars($selectedCountry) ?></strong>
            <?php endif; ?>
            <?php if (!empty($selectedProgramme)): ?>
                · <span class="badge text-bg-primary"><?= htmlspecialchars($selectedProgramme) ?></span>
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
                        <?php if (!empty($match['category']) || !empty($match['duration'])): ?>
                            <div class="small text-secondary mb-2">
                                <?php if (!empty($match['category'])): ?>
                                    <span><?= htmlspecialchars($match['category']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($match['duration'])): ?>
                                    <span><?= !empty($match['category']) ? ' · ' : '' ?><?= htmlspecialchars($match['duration']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                        <?php if (!empty($match['matched_languages'])): ?>
                            <div class="mb-3">
                                <div class="small fw-semibold text-uppercase text-secondary mb-2">Matched languages</div>
                                <?php foreach (($match['matched_languages'] ?? []) as $language): ?>
                                    <span class="badge text-bg-success me-1 mb-1"><?= htmlspecialchars($language) ?></span>
                                <?php endforeach; ?>
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
    <?php if ($totalPages > 1): ?>
    <nav class="mt-5 d-flex justify-content-center" aria-label="Results pagination">
        <ul class="pagination pagination-lg gap-1 flex-wrap justify-content-center" id="paginationNav">

            <!-- Previous -->
            <?php $prevPage = $currentPage - 1; ?>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link paginator-link rounded-3" href="?page=<?= $prevPage ?>" aria-label="Previous">
                    ‹
                </a>
            </li>

            <?php
            // Show smart page range: always show first, last, and ±2 around current
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
                    <a class="page-link paginator-link rounded-3" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php $prev = $p; endforeach; ?>

            <!-- Next -->
            <?php $nextPage = $currentPage + 1; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link paginator-link rounded-3" href="?page=<?= $nextPage ?>" aria-label="Next">
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

