<?php
$stats = $stats ?? [];
$filters = $filters ?? [];
$eps = $eps ?? [];
$selectedEp = $selectedEp ?? null;
$stages = $stages ?? [];
$notifications = $notifications ?? [];
$countrySeries = $countrySeries ?? [];
$statusSeries = $statusSeries ?? [];
$monthlySeries = $monthlySeries ?? [];

$selectedStatus = (string) ($selectedEp['status'] ?? 'applied');
$selectedProgress = (int) ($selectedEp['progress_percent'] ?? 0);
$selectedEpId = (int) ($selectedEp['id'] ?? 0);

$countryChartData = array_map(static fn (array $row): array => [
    'label' => (string) ($row['country'] ?? 'Unknown'),
    'value' => (int) ($row['total'] ?? 0),
], $countrySeries);

$statusChartData = array_map(static fn (array $row): array => [
    'label' => EpApplication::stageLabel((string) ($row['status'] ?? 'applied')),
    'value' => (int) ($row['total'] ?? 0),
], $statusSeries);

$monthlyChartData = array_map(static fn (array $row): array => [
    'label' => (string) ($row['month'] ?? ''),
    'value' => (int) ($row['total'] ?? 0),
], $monthlySeries);

$epSlides = array_chunk($eps, 3);
$epClientData = array_values(array_map(static function (array $ep): array {
    return [
        'id' => (int) ($ep['id'] ?? 0),
        'first_name' => (string) ($ep['first_name'] ?? ''),
        'last_name' => (string) ($ep['last_name'] ?? ''),
        'full_name' => (string) ($ep['full_name'] ?? ''),
        'email' => (string) ($ep['email'] ?? ''),
        'phone' => (string) ($ep['phone'] ?? ''),
        'nationality' => (string) ($ep['nationality'] ?? ''),
        'university' => (string) ($ep['university'] ?? ''),
        'field_of_study' => (string) ($ep['field_of_study'] ?? ''),
        'opportunity_title' => (string) ($ep['opportunity_title'] ?? ''),
        'country' => (string) ($ep['country'] ?? ''),
        'organization' => (string) ($ep['organization'] ?? ''),
        'application_date' => (string) ($ep['application_date'] ?? ''),
        'opportunity_link' => (string) ($ep['opportunity_link'] ?? ''),
        'status' => (string) ($ep['status'] ?? 'applied'),
        'stage_label' => (string) ($ep['stage_label'] ?? 'Applied'),
        'progress_percent' => (int) ($ep['progress_percent'] ?? 0),
        'documents' => $ep['documents'] ?? [],
        'history' => $ep['history'] ?? [],
        'missing_documents' => $ep['missing_documents'] ?? [],
    ];
}, $eps));
?>
<div class="ep-dashboard" data-selected-ep-id="<?= htmlspecialchars((string) $selectedEpId) ?>">
    <div class="hero-card card border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                <div>
                    <span class="spark-line mb-3">EP Management</span>
                    <h1 class="hero-title fw-bold mb-3">AIESEC EP dashboard</h1>
                    <p class="hero-subtitle mb-0">Manage Exchange Participants, track applications, upload documents, and follow the full internship journey from Applied to Completed.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-dark" href="#register-ep">Register EP</a>
                    <a class="btn btn-outline-light" href="#ep-table">View EP list</a>
                    <?php if (!empty($selectedEpId)): ?>
                        <a class="btn btn-outline-light" href="<?= htmlspecialchars(url_path('/ep-management/download?ep_id=' . $selectedEpId)) ?>">Download EP Folder</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
    <?php endif; ?>

    <div class="hero-card card border-0">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <span class="spark-line mb-2">EP carousel</span>
                    <h2 class="h4 fw-bold mb-1">Click any EP to open the editor window</h2>
                    <p class="text-secondary mb-0">Use the carousel to browse participants, then edit details, proofs, and process inside the modal.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light" type="button" data-bs-target="#epCarousel" data-bs-slide="prev">Prev</button>
                    <button class="btn btn-outline-light" type="button" data-bs-target="#epCarousel" data-bs-slide="next">Next</button>
                </div>
            </div>

            <div id="epCarousel" class="carousel slide" data-bs-interval="false">
                <div class="carousel-inner">
                    <?php if (!empty($epSlides)): ?>
                        <?php foreach ($epSlides as $slideIndex => $slide): ?>
                            <div class="carousel-item <?= $slideIndex === 0 ? 'active' : '' ?>">
                                <div class="row g-3">
                                    <?php foreach ($slide as $ep): ?>
                                        <div class="col-12">
                                            <div class="ep-card-preview ep-card-preview-horizontal js-ep-open" role="button" tabindex="0" data-ep-id="<?= htmlspecialchars((string) ($ep['id'] ?? 0)) ?>">
                                                <div class="ep-card-glow"></div>
                                                <div class="ep-card-main">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                                        <div>
                                                            <div class="fw-bold fs-5 js-ep-name"><?= htmlspecialchars((string) ($ep['full_name'] ?? '')) ?></div>
                                                            <div class="text-secondary small"><?= htmlspecialchars((string) ($ep['email'] ?? '')) ?></div>
                                                        </div>
                                                        <span class="badge rounded-pill text-bg-dark js-ep-status"><?= htmlspecialchars((string) ($ep['stage_label'] ?? 'Applied')) ?></span>
                                                    </div>
                                                    <div class="ep-card-meta-row">
                                                        <div class="ep-card-meta-block">
                                                            <div class="ep-card-meta-label">Opportunity</div>
                                                            <div class="ep-card-meta-value"><?= htmlspecialchars((string) ($ep['opportunity_title'] ?? '')) ?></div>
                                                        </div>
                                                        <div class="ep-card-meta-block">
                                                            <div class="ep-card-meta-label">Country</div>
                                                            <div class="ep-card-meta-value"><?= htmlspecialchars((string) ($ep['country'] ?? '')) ?></div>
                                                        </div>
                                                        <div class="ep-card-meta-block">
                                                            <div class="ep-card-meta-label">Organization</div>
                                                            <div class="ep-card-meta-value"><?= htmlspecialchars((string) ($ep['organization'] ?? '')) ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="ep-card-side">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-secondary small">Progress</span>
                                                        <strong class="js-ep-progress-text"><?= htmlspecialchars((string) ($ep['progress_percent'] ?? 0)) ?>%</strong>
                                                    </div>
                                                    <div class="progress ep-progress-shell mb-3">
                                                        <div class="progress-bar ep-progress-bar js-ep-progress-bar" style="width: <?= htmlspecialchars((string) ($ep['progress_percent'] ?? 0)) ?>%"></div>
                                                    </div>
                                                    <div class="d-flex gap-2 ep-card-actions">
                                                        <span class="btn btn-dark btn-sm flex-grow-1">Open window</span>
                                                        <a class="btn btn-outline-light btn-sm js-ep-zip" href="<?= htmlspecialchars(url_path('/ep-management/download?ep_id=' . (int) ($ep['id'] ?? 0))) ?>">ZIP</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-secondary">No EP records available yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-xl-2">
            <div class="ep-stat-card">
                <div class="stat-label">Total EPs</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['total'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="ep-stat-card">
                <div class="stat-label">Applied</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['applied'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="ep-stat-card">
                <div class="stat-label">Accepted</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['accepted'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="ep-stat-card">
                <div class="stat-label">Confirmed</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['confirmed'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="ep-stat-card">
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= htmlspecialchars((string) ($stats['completed'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="ep-stat-card">
                <div class="stat-label">Notifications</div>
                <div class="stat-value"><?= htmlspecialchars((string) count($notifications)) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="hero-card card border-0 h-100" id="register-ep">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <span class="spark-line mb-2">Registration</span>
                            <h2 class="h3 fw-bold mb-2">Add a new EP</h2>
                            <p class="text-secondary mb-0">Store the participant profile, opportunity details, and first documents in one flow.</p>
                        </div>
                    </div>

                    <form action="<?= htmlspecialchars(url_path('/ep-management')) ?>" method="post" enctype="multipart/form-data" class="row g-4 align-items-start">
                        <div class="col-12 col-xl-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="nationality" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">University</label>
                                    <input type="text" name="university" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Field of Study</label>
                                    <input type="text" name="field_of_study" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Application Date</label>
                                    <input type="date" name="application_date" class="form-control form-control-lg" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Opportunity Title</label>
                                    <input type="text" name="opportunity_title" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="country" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Organization</label>
                                    <input type="text" name="organization" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Opportunity Link</label>
                                    <input type="url" name="opportunity_link" class="form-control form-control-lg" placeholder="https://..." required>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="ep-form-panel">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="fw-semibold">Documents</div>
                                        <div class="text-secondary small">Upload the first EP files here.</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">CV Upload</label>
                                    <input type="file" name="cv_pdf" class="form-control form-control-lg" accept=".pdf,application/pdf" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Passport Upload</label>
                                    <input type="file" name="passport_file" class="form-control form-control-lg" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Additional Documents</label>
                                    <input type="file" name="additional_documents[]" class="form-control form-control-lg" multiple>
                                </div>
                                <button type="submit" class="btn btn-dark btn-lg w-100">Create EP Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="epEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content hero-card border-0">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <span class="spark-line mb-2">EP window</span>
                    <h2 class="h4 fw-bold mb-1" id="ep-modal-title">Open an EP</h2>
                    <div class="text-secondary" id="ep-modal-subtitle">Select an EP card to edit the participant profile.</div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-light" id="ep-modal-download" href="#" target="_blank" rel="noopener">Download EP Folder</a>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary">Progress</span>
                            <strong id="ep-modal-progress-text">0%</strong>
                        </div>
                        <div class="progress ep-progress-shell">
                            <div class="progress-bar ep-progress-bar" id="ep-modal-progress-bar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="ep-stage-track mb-0" id="ep-modal-stage-track"></div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-tile">
                                    <div class="info-label">Opportunity</div>
                                    <div class="info-value" id="ep-modal-summary-opportunity">-</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-tile">
                                    <div class="info-label">Country</div>
                                    <div class="info-value" id="ep-modal-summary-country">-</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-tile">
                                    <div class="info-label">Organization</div>
                                    <div class="info-value" id="ep-modal-summary-organization">-</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-tile">
                                    <div class="info-label">Applied On</div>
                                    <div class="info-value" id="ep-modal-summary-application-date">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="hero-card card border-0">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 class="h6 text-uppercase mb-0">Missing required documents</h3>
                                </div>
                                <div class="d-flex flex-wrap gap-2" id="ep-modal-missing-docs"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="hero-card card border-0 h-100">
                            <div class="card-body p-4">
                                <span class="spark-line mb-3">Edit EP</span>
                                <form id="ep-modal-update-form" class="row g-3">
                                    <input type="hidden" name="ep_id" id="ep-modal-ep-id">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" id="ep-modal-first-name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" id="ep-modal-last-name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" id="ep-modal-email" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="phone" id="ep-modal-phone" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nationality</label>
                                        <input type="text" name="nationality" id="ep-modal-nationality" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">University</label>
                                        <input type="text" name="university" id="ep-modal-university" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Field of Study</label>
                                        <input type="text" name="field_of_study" id="ep-modal-field-of-study" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Application Date</label>
                                        <input type="date" name="application_date" id="ep-modal-application-date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Opportunity Title</label>
                                        <input type="text" name="opportunity_title" id="ep-modal-opportunity-title" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="country" id="ep-modal-country" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Organization</label>
                                        <input type="text" name="organization" id="ep-modal-organization" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Opportunity Link</label>
                                        <input type="url" name="opportunity_link" id="ep-modal-opportunity-link" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="ep-modal-status" class="form-select">
                                            <?php foreach ($stages as $stage): ?>
                                                <option value="<?= htmlspecialchars((string) $stage['key']) ?>"><?= htmlspecialchars((string) $stage['label']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-dark btn-lg">Save changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="hero-card card border-0 h-100">
                            <div class="card-body p-4">
                                <span class="spark-line mb-3">Proofs & documents</span>
                                <form id="ep-modal-document-form" class="row g-3" enctype="multipart/form-data">
                                    <input type="hidden" name="ep_id" id="ep-modal-document-ep-id">
                                    <div class="col-md-5">
                                        <label class="form-label">Document Type</label>
                                        <select name="document_type" class="form-select">
                                            <option value="contract">Contract</option>
                                            <option value="acceptance_note">Acceptance Note</option>
                                            <option value="passport">Passport Copy</option>
                                            <option value="visa_documents">Visa Documents</option>
                                            <option value="insurance_documents">Insurance Documents</option>
                                            <option value="flight_ticket">Flight Ticket</option>
                                            <option value="profile_picture">Profile Picture</option>
                                            <option value="additional_proof">Additional Proof</option>
                                        </select>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label">File</label>
                                        <input type="file" name="document_file" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-outline-light">Upload proof</button>
                                    </div>
                                </form>

                                <div class="mt-4">
                                    <h3 class="h6 text-uppercase mb-3">Current documents</h3>
                                    <div class="ep-doc-list" id="ep-modal-documents"></div>
                                </div>

                                <div class="mt-4">
                                    <h3 class="h6 text-uppercase mb-3">Status history</h3>
                                    <div class="ep-history-list" id="ep-modal-history"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const epRecords = <?= json_encode($epClientData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const modalEl = document.getElementById('epEditorModal');
        let modalInstance = null;
        const getModal = () => {
            if (!modalEl || !window.bootstrap) {
                return null;
            }
            if (!modalInstance) {
                modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            return modalInstance;
        };
        const openButtons = document.querySelectorAll('.js-ep-open');
        const modalUpdateForm = document.getElementById('ep-modal-update-form');
        const modalDocumentForm = document.getElementById('ep-modal-document-form');
        const modalTitle = document.getElementById('ep-modal-title');
        const modalSubtitle = document.getElementById('ep-modal-subtitle');
        const modalDownload = document.getElementById('ep-modal-download');
        const modalProgressText = document.getElementById('ep-modal-progress-text');
        const modalProgressBar = document.getElementById('ep-modal-progress-bar');
        const modalStageTrack = document.getElementById('ep-modal-stage-track');
        const modalDocuments = document.getElementById('ep-modal-documents');
        const modalMissingDocs = document.getElementById('ep-modal-missing-docs');
        const modalHistory = document.getElementById('ep-modal-history');
        const modalFields = {
            epId: document.getElementById('ep-modal-ep-id'),
            documentEpId: document.getElementById('ep-modal-document-ep-id'),
            firstName: document.getElementById('ep-modal-first-name'),
            lastName: document.getElementById('ep-modal-last-name'),
            email: document.getElementById('ep-modal-email'),
            phone: document.getElementById('ep-modal-phone'),
            nationality: document.getElementById('ep-modal-nationality'),
            university: document.getElementById('ep-modal-university'),
            fieldOfStudy: document.getElementById('ep-modal-field-of-study'),
            applicationDate: document.getElementById('ep-modal-application-date'),
            opportunityTitle: document.getElementById('ep-modal-opportunity-title'),
            country: document.getElementById('ep-modal-country'),
            organization: document.getElementById('ep-modal-organization'),
            opportunityLink: document.getElementById('ep-modal-opportunity-link'),
            status: document.getElementById('ep-modal-status'),
        };

        const modalSummary = {
            opportunity: document.getElementById('ep-modal-summary-opportunity'),
            country: document.getElementById('ep-modal-summary-country'),
            organization: document.getElementById('ep-modal-summary-organization'),
            applicationDate: document.getElementById('ep-modal-summary-application-date'),
        };

        const charts = {
            country: document.getElementById('countryChart'),
            status: document.getElementById('statusChart'),
            monthly: document.getElementById('monthlyChart'),
        };

        const countryData = <?= json_encode($countryChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const statusData = <?= json_encode($statusChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const monthlyData = <?= json_encode($monthlyChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const stages = <?= json_encode($stages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        let activeModalEpId = null;
        const epRecordMap = new Map(epRecords.map((record) => [Number(record.id), record]));

        const createChart = (canvas, type, data, options = {}) => {
            if (!canvas || !window.Chart) return;
            const labels = data.map((item) => item.label);
            const values = data.map((item) => item.value);
            new Chart(canvas, {
                type,
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            '#7c8cff',
                            '#6ee7ff',
                            '#9b7bff',
                            '#60f0b0',
                            '#f59e0b',
                            '#f472b6',
                            '#38bdf8',
                            '#c084fc'
                        ],
                        borderWidth: 0
                    }]
                },
                options: Object.assign({
                    responsive: true,
                    plugins: {
                        legend: { display: type !== 'bar' },
                    }
                }, options)
            });
        };

        createChart(charts.country, 'doughnut', countryData);
        createChart(charts.status, 'pie', statusData);
        createChart(charts.monthly, 'bar', monthlyData, {
            scales: {
                x: { ticks: { color: '#aeb8d8' }, grid: { color: 'rgba(145,160,255,0.08)' } },
                y: { ticks: { color: '#aeb8d8', precision: 0 }, grid: { color: 'rgba(145,160,255,0.08)' } }
            },
            plugins: { legend: { display: false } }
        });

        const renderModalStageTrack = (statusKey) => {
            if (!modalStageTrack) return;
            const currentIndex = stages.findIndex((stage) => stage.key === statusKey);
            modalStageTrack.innerHTML = '';
            stages.forEach((stage, index) => {
                const step = document.createElement('div');
                step.className = 'ep-stage-step ' + (index <= currentIndex ? 'is-complete' : '') + (stage.key === statusKey ? ' is-current' : '');
                step.innerHTML = '<span class="ep-stage-dot">' + (index + 1) + '</span><span class="ep-stage-label">' + stage.label + '</span>';
                modalStageTrack.appendChild(step);
                if (index < stages.length - 1) {
                    const line = document.createElement('span');
                    line.className = 'ep-stage-line ' + (index < currentIndex ? 'is-complete' : '');
                    modalStageTrack.appendChild(line);
                }
            });
        };

        const normalizeList = (value) => Array.isArray(value) ? value : Object.values(value || {});

        const renderDocuments = (documents) => {
            if (!modalDocuments) return;
            modalDocuments.innerHTML = '';
            const items = normalizeList(documents);
            if (items.length === 0) {
                modalDocuments.innerHTML = '<div class="text-secondary">No documents uploaded yet.</div>';
                return;
            }

            items.forEach((doc) => {
                const item = window.document.createElement('div');
                item.className = 'ep-doc-item';
                item.innerHTML = `
                    <div>
                        <div class="fw-semibold">${doc.document_type || 'Document'}</div>
                        <div class="text-secondary small">${doc.original_name || ''}</div>
                    </div>
                    <a class="btn btn-sm btn-outline-light" href="${doc.web_path || '#'}" target="_blank" rel="noopener">Open</a>
                `;
                modalDocuments.appendChild(item);
            });
        };

        const renderMissingDocs = (missing) => {
            if (!modalMissingDocs) return;
            modalMissingDocs.innerHTML = '';
            const items = normalizeList(missing);
            if (items.length === 0) {
                modalMissingDocs.innerHTML = '<span class="badge rounded-pill text-bg-success">All required documents uploaded</span>';
                return;
            }
            items.forEach((item) => {
                const badge = document.createElement('span');
                badge.className = 'badge rounded-pill text-bg-warning text-dark';
                badge.textContent = item;
                modalMissingDocs.appendChild(badge);
            });
        };

        const renderHistory = (history) => {
            if (!modalHistory) return;
            modalHistory.innerHTML = '';
            const items = normalizeList(history);
            if (items.length === 0) {
                modalHistory.innerHTML = '<div class="text-secondary">No status updates yet.</div>';
                return;
            }
            items.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'ep-history-item';
                row.innerHTML = `
                    <div class="fw-semibold">${item.status_label || item.status || 'Applied'}</div>
                    <div class="text-secondary small">${item.created_at || ''}${item.changed_by_label ? ' • ' + item.changed_by_label : ''}</div>
                `;
                modalHistory.appendChild(row);
            });
        };

        const refreshCard = (ep) => {
            if (!ep) return;
            const card = document.querySelector('.js-ep-open[data-ep-id="' + ep.id + '"]');
            if (!card) return;
            const wrapper = card.classList.contains('ep-card-preview') ? card : card.closest('.ep-card-preview');
            if (!wrapper) return;
            const name = wrapper.querySelector('.js-ep-name');
            const status = wrapper.querySelector('.js-ep-status');
            const progressText = wrapper.querySelector('.js-ep-progress-text');
            const progressBar = wrapper.querySelector('.js-ep-progress-bar');
            if (name) name.textContent = ep.full_name || '';
            if (status) status.textContent = ep.stage_label || 'Applied';
            if (progressText) progressText.textContent = (ep.progress_percent || 0) + '%';
            if (progressBar) progressBar.style.width = (ep.progress_percent || 0) + '%';
        };

        const populateModal = (ep) => {
            if (!ep) return;
            activeModalEpId = ep.id || null;
            if (modalTitle) modalTitle.textContent = ep.full_name || 'EP window';
            if (modalSubtitle) modalSubtitle.textContent = ep.email || '';
            if (modalDownload) modalDownload.href = <?= json_encode(url_path('/ep-management/download')) ?> + '?ep_id=' + (ep.id || 0);
            if (modalProgressText) modalProgressText.textContent = (ep.progress_percent || 0) + '%';
            if (modalProgressBar) modalProgressBar.style.width = (ep.progress_percent || 0) + '%';
            renderModalStageTrack(ep.status || 'applied');
            renderDocuments(ep.documents);
            renderMissingDocs(ep.missing_documents);
            renderHistory(ep.history);

            if (modalFields.epId) modalFields.epId.value = ep.id || '';
            if (modalFields.documentEpId) modalFields.documentEpId.value = ep.id || '';
            if (modalFields.firstName) modalFields.firstName.value = ep.first_name || '';
            if (modalFields.lastName) modalFields.lastName.value = ep.last_name || '';
            if (modalFields.email) modalFields.email.value = ep.email || '';
            if (modalFields.phone) modalFields.phone.value = ep.phone || '';
            if (modalFields.nationality) modalFields.nationality.value = ep.nationality || '';
            if (modalFields.university) modalFields.university.value = ep.university || '';
            if (modalFields.fieldOfStudy) modalFields.fieldOfStudy.value = ep.field_of_study || '';
            if (modalFields.applicationDate) modalFields.applicationDate.value = ep.application_date || '';
            if (modalFields.opportunityTitle) modalFields.opportunityTitle.value = ep.opportunity_title || '';
            if (modalFields.country) modalFields.country.value = ep.country || '';
            if (modalFields.organization) modalFields.organization.value = ep.organization || '';
            if (modalFields.opportunityLink) modalFields.opportunityLink.value = ep.opportunity_link || '';
            if (modalFields.status) modalFields.status.value = ep.status || 'applied';

            if (modalSummary.opportunity) modalSummary.opportunity.textContent = ep.opportunity_title || '-';
            if (modalSummary.country) modalSummary.country.textContent = ep.country || '-';
            if (modalSummary.organization) modalSummary.organization.textContent = ep.organization || '-';
            if (modalSummary.applicationDate) modalSummary.applicationDate.textContent = ep.application_date || '-';
        };

        const openEp = async (epId) => {
            if (!epId) return;
            const cachedEp = epRecordMap.get(Number(epId));
            if (!cachedEp) {
                alert('Unable to load EP details.');
                return;
            }
            populateModal(cachedEp);
            const modal = getModal();
            if (modal) {
                modal.show();
            }
            await refreshModalEp(Number(epId));
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                if (event.target.closest('.js-ep-zip')) return;
                openEp(button.dataset.epId);
            });
            button.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openEp(button.dataset.epId);
                }
            });
        });

        document.querySelectorAll('.js-ep-zip').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        const refreshModalEp = async (epId) => {
            const response = await fetch(<?= json_encode(url_path('/ep-management/status-data')) ?> + '?ep_id=' + epId, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            if (payload.success && payload.ep) {
                epRecordMap.set(Number(payload.ep.id), payload.ep);
                populateModal(payload.ep);
                refreshCard(payload.ep);
            }
        };

        if (modalUpdateForm) {
            modalUpdateForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(modalUpdateForm);
                const response = await fetch(<?= json_encode(url_path('/ep-management/update')) ?>, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                if (payload.success && payload.ep) {
                    populateModal(payload.ep);
                    refreshCard(payload.ep);
                } else {
                    alert(payload.message || 'Unable to save changes.');
                }
            });
        }

        if (modalDocumentForm) {
            modalDocumentForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(modalDocumentForm);
                const response = await fetch(<?= json_encode(url_path('/ep-management/document')) ?>, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                if (payload.success) {
                    if (activeModalEpId) {
                        await openEp(activeModalEpId);
                    }
                } else {
                    alert(payload.message || 'Unable to upload document.');
                }
            });
        }

    })();
</script>

