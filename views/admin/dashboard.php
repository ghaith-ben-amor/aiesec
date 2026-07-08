<?php
$stats = $stats ?? [];
$recentUsers = $recentUsers ?? [];
$recentCvs = $recentCvs ?? [];
$recentMatches = $recentMatches ?? [];
$recentOpportunities = $recentOpportunities ?? [];
$syncInfo = $syncInfo ?? [];
$adminUser = $adminUser ?? null;
?>
<div class="admin-shell">
    <div class="hero-card card border-0 mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                <div class="admin-hero-copy">
                    <span class="spark-line mb-3">Admin command center</span>
                    <h1 class="hero-title fw-bold mb-3">Dashboard overview for AIESEC Matcher</h1>
                    <p class="hero-subtitle mb-0">Monitor users, opportunities, CV uploads, match activity, and sync live opportunities from the AIESEC API.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-light" href="#statistics">View statistics</a>
                    <a class="btn btn-dark" href="#api-sync">API Sync</a>
                    <a class="btn btn-dark" href="<?= htmlspecialchars(url_path('/ep-management#register-ep')) ?>">Add EP</a>
                    <a class="btn btn-outline-light" href="<?= htmlspecialchars(url_path('/dashboard')) ?>">Public dashboard</a>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                <?php if (!empty($isAdmin)): ?>
                    <span class="badge rounded-pill text-bg-success px-3 py-2">Admin access enabled</span>
                <?php endif; ?>
                <?php if (!empty($adminUser)): ?>
                    <span class="badge rounded-pill text-bg-dark px-3 py-2">Signed in as <?= htmlspecialchars((string) ($adminUser['email'] ?? '')) ?></span>
                <?php endif; ?>
                <?php if (!empty($syncInfo['last_sync']) && $syncInfo['last_sync'] !== 'Never'): ?>
                    <span class="badge rounded-pill text-bg-primary px-3 py-2">API Synced: <?= htmlspecialchars((string) $syncInfo['last_sync']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $flashSuccess) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
    <?php endif; ?>

    <div id="statistics" class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Total users</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['totalUsers'] ?? 0)) ?></div>
                <div class="stat-meta">Registered accounts across the platform</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Members</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['memberUsers'] ?? 0)) ?></div>
                <div class="stat-meta">CV upload and match users</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Admins</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['adminUsers'] ?? 0)) ?></div>
                <div class="stat-meta">Backoffice accounts</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">API Source</div>
                <div class="stat-value"><?= !empty($syncInfo['count']) ? htmlspecialchars((string) $syncInfo['count']) : '0' ?></div>
                <div class="stat-meta">Opportunities synced from API</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">CV uploads</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['totalCvs'] ?? 0)) ?></div>
                <div class="stat-meta">Stored CV documents</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Opportunities</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['totalOpportunities'] ?? 0)) ?></div>
                <div class="stat-meta">Current database records</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Matches</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['totalMatches'] ?? 0)) ?></div>
                <div class="stat-meta">Generated ranking results</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Favorites</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['favoriteMatches'] ?? 0)) ?></div>
                <div class="stat-meta">Saved match results</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <span class="spark-line mb-2">API Integration</span>
                            <h2 class="h4 fw-bold mb-1" id="api-sync">AIESEC GraphQL API Feed</h2>
                            <p class="text-secondary mb-0">Systematically fetches live opportunities from the AIESEC GIS portal.</p>
                        </div>
                        <a class="btn btn-dark" href="<?= htmlspecialchars(url_path('/admin')) ?>">Refresh dashboard</a>
                    </div>

                    <div class="info-grid mb-4">
                        <div class="info-tile">
                            <div class="info-label">API Connection</div>
                            <div class="info-value">
                                <?php if (!empty($config['aiesec_access_token'])): ?>
                                    <span class="text-success">● Connected</span>
                                <?php else: ?>
                                    <span class="text-danger">● Token Missing</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-tile">
                            <div class="info-label">Last Synchronization</div>
                            <div class="info-value"><?= htmlspecialchars((string) ($syncInfo['last_sync'] ?? 'Never')) ?></div>
                        </div>
                        <div class="info-tile">
                            <div class="info-label">Opportunities Count</div>
                            <div class="info-value"><?= htmlspecialchars((string) ($syncInfo['count'] ?? 0)) ?></div>
                        </div>
                    </div>

                    <form action="<?= htmlspecialchars(url_path('/admin/sync-opportunities')) ?>" method="post" class="row g-3 align-items-end">
                        <div class="col-12 col-md-8">
                            <div class="text-secondary small mb-2">
                                <strong>Configured Token:</strong> <code><?= !empty($config['aiesec_access_token']) ? htmlspecialchars(substr($config['aiesec_access_token'], 0, 8) . '...' . substr($config['aiesec_access_token'], -8)) : 'Not configured' ?></code>
                            </div>
                            <p class="mb-0 text-secondary">Click the button to start synchronization. This will fetch opportunities from <strong>all AIESEC programmes</strong> (GV, GTa, GTe) across all countries and update the local database.</p>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-dark btn-lg w-100 d-flex align-items-center justify-content-center gap-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><polyline points="21 3 21 8 16 8"/></svg>
                                Synchronize now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <span class="spark-line mb-3">Quick actions</span>
                    <h2 class="h4 fw-bold mb-3">Admin shortcuts</h2>
                    <div class="d-grid gap-3">
                        <a class="admin-action" href="<?= htmlspecialchars(url_path('/upload')) ?>">
                            <span>Open member upload flow</span>
                            <small>Preview the public CV submission page</small>
                        </a>
                        <a class="admin-action" href="<?= htmlspecialchars(url_path('/dashboard')) ?>">
                            <span>Open public dashboard</span>
                            <small>Review the user-facing dashboard experience</small>
                        </a>
                        <a class="admin-action" href="<?= htmlspecialchars(url_path('/ep-management')) ?>">
                            <span>Open EP management</span>
                            <small>Track participant status and documents</small>
                        </a>
                        <a class="admin-action" href="<?= htmlspecialchars(url_path('/ep-management#register-ep')) ?>">
                            <span>Add a new EP</span>
                            <small>Jump straight to the EP registration form</small>
                        </a>
                        <a class="admin-action" href="<?= htmlspecialchars(url_path('/logout')) ?>">
                            <span>Logout session</span>
                            <small>End the current signed-in session</small>
                        </a>
                    </div>

                    <div class="mt-4 pt-3 border-top" style="border-color: rgba(145, 160, 255, 0.16) !important;">
                        <div class="text-secondary small mb-2">System status</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-pill text-bg-success px-3 py-2">Auth online</span>
                            <span class="badge rounded-pill text-bg-primary px-3 py-2">AIESEC API ready</span>
                            <span class="badge rounded-pill text-bg-dark px-3 py-2">Matching enabled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <span class="spark-line mb-3">Recent users</span>
                    <h2 class="h4 fw-bold mb-3">Latest accounts</h2>
                    <div class="activity-list">
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="activity-item">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($user['name'] ?? 'Unknown user')) ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></div>
                                    </div>
                                    <span class="badge text-bg-dark"><?= htmlspecialchars((string) ($user['role'] ?? 'member')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No users found yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <span class="spark-line mb-3">Recent CVs</span>
                    <h2 class="h4 fw-bold mb-3">Latest uploads</h2>
                    <div class="activity-list">
                        <?php if (!empty($recentCvs)): ?>
                            <?php foreach ($recentCvs as $cv): ?>
                                <div class="activity-item">
                                    <div>
                                        <div class="fw-semibold">CV #<?= htmlspecialchars((string) ($cv['id'] ?? '')) ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string) ($cv['file_path'] ?? '')) ?></div>
                                    </div>
                                    <span class="badge text-bg-primary"><?= htmlspecialchars((string) ($cv['created_at'] ?? '')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No CV uploads yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <span class="spark-line mb-3">Recent matches</span>
                    <h2 class="h4 fw-bold mb-3">Latest results</h2>
                    <div class="activity-list">
                        <?php if (!empty($recentMatches)): ?>
                            <?php foreach ($recentMatches as $match): ?>
                                <div class="activity-item">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($match['title'] ?? 'Unlinked match')) ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string) ($match['location'] ?? '')) ?></div>
                                    </div>
                                    <span class="badge text-bg-success"><?= htmlspecialchars(number_format((float) ($match['score'] ?? 0), 0)) ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-secondary">No matches generated yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <span class="spark-line mb-3">Recent opportunities</span>
                    <h2 class="h4 fw-bold mb-3">Current opportunity pool</h2>
                    <div class="table-responsive">
                        <table class="table table-dark table-borderless align-middle mb-0 admin-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentOpportunities)): ?>
                                    <?php foreach ($recentOpportunities as $opportunity): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($opportunity['title'] ?? 'Untitled')) ?></td>
                                            <td><?= htmlspecialchars((string) ($opportunity['location'] ?? '')) ?></td>
                                            <td><span class="badge text-bg-primary"><?= htmlspecialchars((string) (strtoupper($opportunity['source_type'] ?? 'API'))) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-secondary">No opportunities loaded yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hero-card card border-0 h-100">
                <div class="card-body p-4">
                    <span class="spark-line mb-3">API Parameters</span>
                    <h2 class="h4 fw-bold mb-3">Query settings</h2>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div>
                                <div class="fw-semibold">Programmes</div>
                                <div class="text-secondary small">GV (7), GTa (8), GTe (9) — all available programmes</div>
                            </div>
                            <span class="badge text-bg-dark">ALL</span>
                        </div>
                        <div class="activity-item">
                            <div>
                                <div class="fw-semibold">Pages per programme</div>
                                <div class="text-secondary small">100 items/page × up to <?= htmlspecialchars((string) (getenv('AIESEC_MAX_PAGES') ?: '5')) ?> pages</div>
                            </div>
                            <span class="badge text-bg-dark">Paginated</span>
                        </div>
                        <div class="activity-item">
                            <div>
                                <div class="fw-semibold">GraphQL Endpoint</div>
                                <div class="text-secondary small">gis-api.aiesec.org</div>
                            </div>
                            <span class="badge text-bg-primary">HTTPS</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
