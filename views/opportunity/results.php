<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Matching Results</h1>
        <p class="text-secondary mb-0">Ranked opportunities based on skill overlap and profile fit.</p>
    </div>
    <a href="<?= htmlspecialchars(url_path('/upload')) ?>" class="btn btn-outline-primary">Upload another CV</a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>

<?php if (!empty($opportunitySource)): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center gap-3">
        <span><?= htmlspecialchars($opportunitySource) ?></span>
        <a href="https://aiesec.org/search?programmes=8" target="_blank" class="btn btn-sm btn-outline-primary">Open AIESEC search</a>
    </div>
<?php endif; ?>

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

<?php if (empty($matches)): ?>
    <div class="alert alert-warning">No matches are available yet. Upload a CV to generate recommendations.</div>
<?php else: ?>
    <div class="row g-4">
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
                            <span class="badge bg-success score-badge"><?= htmlspecialchars((string) $match['score']) ?>%</span>
                        </div>
                        <div class="text-secondary mb-2"><strong>Location:</strong> <?= htmlspecialchars($match['location']) ?></div>
                        <?php if (!empty($match['category']) || !empty($match['duration'])): ?>
                            <div class="small text-secondary mb-2">
                                <?php if (!empty($match['category'])): ?>
                                    <span><?= htmlspecialchars($match['category']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($match['duration'])): ?>
                                    <span><?= !empty($match['category']) ? ' - ' : '' ?><?= htmlspecialchars($match['duration']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <p class="text-secondary flex-grow-1"><?= htmlspecialchars($match['description']) ?></p>
                        <?php
                            $exactMatchedSkills = is_array($match['matched_skills'] ?? null) ? $match['matched_skills'] : [];
                            $keywordMatchedSkills = is_array($match['matched_keywords'] ?? null) ? $match['matched_keywords'] : [];
                            $displayMatchedSkills = array_values(array_unique(array_merge($exactMatchedSkills, $keywordMatchedSkills)));
                        ?>
                        <div class="mb-3">
                            <div class="small fw-semibold text-uppercase text-secondary mb-2">Matched skills</div>
                            <?php if (!empty($displayMatchedSkills)): ?>
                                <?php foreach ($displayMatchedSkills as $skill): ?>
                                    <span class="badge text-bg-primary me-1 mb-1"><?= htmlspecialchars($skill) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="small text-secondary">No exact skill overlap, ranked by profile keywords.</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($match['matched_languages'])): ?>
                            <div class="mb-3">
                                <div class="small fw-semibold text-uppercase text-secondary mb-2">Other fit signals</div>
                                <?php foreach (($match['matched_languages'] ?? []) as $language): ?>
                                    <span class="badge text-bg-success me-1 mb-1"><?= htmlspecialchars($language) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($match['source_type'])): ?>
                            <div class="small text-success mb-3"><?= htmlspecialchars(ucfirst((string) $match['source_type'])) ?> opportunity source</div>
                        <?php endif; ?>
                        <?php if (!empty($match['source_url'])): ?>
                            <?php $hasDirectOpportunityUrl = str_contains((string) $match['source_url'], '/opportunity/'); ?>
                            <a href="<?= htmlspecialchars($match['source_url']) ?>" target="_blank" class="btn btn-sm btn-outline-dark mt-auto">
                                <?= $hasDirectOpportunityUrl ? 'View opportunity on AIESEC' : 'Open AIESEC search' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
