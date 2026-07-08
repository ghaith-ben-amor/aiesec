<?php
$profile = $profile ?? [];
$header = $profile['header'] ?? [];
$education = $profile['education'] ?? [];
$experience = $profile['experience'] ?? [];
$projects = $profile['projects'] ?? [];
$community = $profile['community'] ?? [];
$skills = $profile['skills'] ?? [];

$fill = static fn ($value, string $fallback = ''): string => htmlspecialchars((string) ($value ?? $fallback));
$lines = static function ($value): string {
    $value = trim((string) $value);
    return htmlspecialchars($value);
};

$defaultBulletRows = static function (array $items, int $count, string $fallback = ''): array {
    $rows = array_values($items);
    while (count($rows) < $count) {
        $rows[] = $fallback;
    }
    return array_slice($rows, 0, $count);
};

$experience = array_map(static function (array $item) use ($defaultBulletRows): array {
    $item['bullets'] = $defaultBulletRows($item['bullets'] ?? [], 3, '');
    return $item;
}, array_pad($experience, 3, ['title' => '', 'date' => '', 'bullets' => ['', '', '']]));

$projects = array_map(static function (array $item) use ($defaultBulletRows): array {
    $item['bullets'] = $defaultBulletRows($item['bullets'] ?? [], 2, '');
    return $item;
}, array_pad($projects, 6, ['title' => '', 'bullets' => ['', '']]));

$education = array_pad($education, 2, ['title' => '', 'date' => '']);
$skills = array_pad($skills, 5, ['label' => '', 'value' => '']);
$roles = $community['roles'] ?? ['', ''];
$roles = array_pad($roles, 2, '');
?>
<div class="cv-builder-shell">
    <style>
        .cv-builder-shell {
            padding: 1.25rem 0 3rem;
        }

        .builder-grid {
            display: grid;
            grid-template-columns: minmax(320px, 430px) minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        .builder-panel {
            position: sticky;
            top: 1rem;
            max-height: calc(100vh - 2rem);
            overflow: auto;
            background: rgba(10, 16, 36, 0.88);
            border: 1px solid rgba(145, 160, 255, 0.18);
            border-radius: 1.5rem;
            box-shadow: var(--shadow);
            padding: 1.25rem;
        }

        .builder-panel .section-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .builder-panel h1,
        .builder-panel h2,
        .builder-panel h3,
        .builder-panel label {
            color: #f5f7ff;
        }

        .builder-panel .form-control,
        .builder-panel .form-select {
            background: rgba(7, 12, 30, 0.88);
            color: #f5f7ff;
            border-color: rgba(145, 160, 255, 0.18);
        }

        .builder-panel .form-control::placeholder {
            color: rgba(174, 184, 216, 0.7);
        }

        .builder-panel .form-control:focus {
            background: rgba(7, 12, 30, 0.98);
            color: #f5f7ff;
        }

        .builder-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .builder-header p {
            color: var(--muted);
            margin: 0;
        }

        .cv-preview-wrap {
            display: grid;
            gap: 1rem;
        }

        .cv-page {
            background: #fff;
            color: #101010;
            font-family: 'Times New Roman', Georgia, serif;
            width: 100%;
            max-width: 8.27in;
            margin: 0 auto;
            min-height: 11.69in;
            padding: 0.52in 0.55in 0.42in;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.35);
        }

        .cv-name {
            text-align: center;
            font-size: clamp(2.2rem, 4vw, 3.3rem);
            line-height: 1.02;
            margin: 0;
            font-weight: 400;
        }

        .cv-subtitle {
            text-align: center;
            font-size: 1.2rem;
            margin: 0.2rem 0 0;
        }

        .cv-rule {
            height: 2px;
            background: #1f8f8d;
            margin: 0.4in 0 0.14in;
        }

        .cv-contact {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            font-size: 1rem;
            margin-bottom: 0.28in;
        }

        .cv-contact strong {
            font-weight: 700;
        }

        .section-title {
            text-align: center;
            text-transform: uppercase;
            font-size: 1.38rem;
            font-weight: 700;
            line-height: 1.1;
            margin: 0.16in 0 0.02in;
        }

        .section-rule {
            border-top: 1px solid #99cbc6;
            margin-bottom: 0.08in;
        }

        .cv-entry {
            margin-bottom: 0.16in;
        }

        .cv-entry-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-weight: 700;
        }

        .cv-entry-title {
            font-weight: 700;
        }

        .cv-entry-date {
            white-space: nowrap;
        }

        .cv-copy {
            font-size: 1rem;
            line-height: 1.25;
            margin: 0;
        }

        .cv-bullets {
            margin: 0.08in 0 0 0.2in;
            padding-left: 0.2in;
        }

        .cv-bullets li {
            margin-bottom: 0.08in;
        }

        .cv-skills p,
        .cv-community p {
            margin: 0 0 0.12in;
            line-height: 1.25;
        }

        .preview-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(145, 160, 255, 0.12);
        }

        .preview-toolbar .text-muted {
            color: var(--muted) !important;
        }

        .cv-page + .cv-page {
            margin-top: 0.75rem;
        }

        .form-note {
            color: var(--muted);
            font-size: 0.92rem;
        }

        @media (max-width: 1199.98px) {
            .builder-grid {
                grid-template-columns: 1fr;
            }

            .builder-panel {
                position: relative;
                top: auto;
                max-height: none;
            }
        }

        @media print {
            .no-print,
            nav,
            .builder-panel,
            .preview-toolbar {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            main.container {
                max-width: none !important;
                padding: 0 !important;
            }

            .builder-grid {
                display: block;
            }

            .cv-page {
                box-shadow: none;
                max-width: none;
                margin: 0;
                page-break-after: always;
                break-after: page;
            }
        }
    </style>

    <div class="builder-grid">
        <aside class="builder-panel no-print">
            <div class="builder-header">
                <div>
                    <span class="spark-line mb-3">CV Builder</span>
                    <h1 class="h3 fw-bold mb-2">Match the PDF template</h1>
                    <p>Fill the template fields, then print the preview to PDF.</p>
                </div>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Header</h2>
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input class="form-control" id="cv-name-input" data-preview-target="cv-name" value="<?= $fill($header['name'] ?? '') ?>" placeholder="Full name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Subtitle</label>
                    <input class="form-control" id="cv-subtitle-input" data-preview-target="cv-subtitle" value="<?= $fill($header['subtitle'] ?? '') ?>" placeholder="Subtitle line">
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input class="form-control" id="cv-phone-input" data-preview-target="cv-phone" value="<?= $fill($header['phone'] ?? '') ?>" placeholder="Phone">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input class="form-control" id="cv-email-input" data-preview-target="cv-email" value="<?= $fill($header['email'] ?? '') ?>" placeholder="Email">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Address</label>
                        <input class="form-control" id="cv-address-input" data-preview-target="cv-address" value="<?= $fill($header['address'] ?? '') ?>" placeholder="Address">
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Summary</h2>
                <textarea class="form-control" rows="5" id="cv-summary-input" data-preview-target="cv-summary" placeholder="Professional summary"><?= $fill($profile['summary'] ?? '') ?></textarea>
                <div class="form-note mt-2">This appears under the summary section on page 1.</div>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Education</h2>
                <?php foreach ($education as $index => $item): ?>
                    <div class="mb-3">
                        <label class="form-label">Education <?= $index + 1 ?> title</label>
                        <input class="form-control mb-2" data-preview-target="edu-<?= $index + 1 ?>-title" value="<?= $fill($item['title'] ?? '') ?>">
                        <input class="form-control" data-preview-target="edu-<?= $index + 1 ?>-date" value="<?= $fill($item['date'] ?? '') ?>" placeholder="Date">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Experience</h2>
                <?php foreach ($experience as $index => $item): ?>
                    <div class="mb-3">
                        <label class="form-label">Experience <?= $index + 1 ?></label>
                        <input class="form-control mb-2" data-preview-target="exp-<?= $index + 1 ?>-title" value="<?= $fill($item['title'] ?? '') ?>" placeholder="Role and company">
                        <input class="form-control mb-2" data-preview-target="exp-<?= $index + 1 ?>-date" value="<?= $fill($item['date'] ?? '') ?>" placeholder="Date">
                        <textarea class="form-control" rows="3" data-preview-list="exp-<?= $index + 1 ?>-bullets" placeholder="One bullet per line"><?= $lines(implode("\n", $item['bullets'] ?? [])) ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Projects</h2>
                <?php foreach ($projects as $index => $item): ?>
                    <div class="mb-3">
                        <label class="form-label">Project <?= $index + 1 ?></label>
                        <input class="form-control mb-2" data-preview-target="proj-<?= $index + 1 ?>-title" value="<?= $fill($item['title'] ?? '') ?>" placeholder="Project title">
                        <textarea class="form-control" rows="3" data-preview-list="proj-<?= $index + 1 ?>-bullets" placeholder="One bullet per line"><?= $lines(implode("\n", $item['bullets'] ?? [])) ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Community</h2>
                <div class="mb-3">
                    <label class="form-label">Organization</label>
                    <input class="form-control" data-preview-target="community-org" value="<?= $fill($community['organization'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role 1</label>
                    <input class="form-control mb-2" data-preview-target="community-role-1" value="<?= $fill($roles[0] ?? '') ?>">
                    <label class="form-label">Role 2</label>
                    <input class="form-control" data-preview-target="community-role-2" value="<?= $fill($roles[1] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Certifications</label>
                    <textarea class="form-control" rows="2" data-preview-target="community-certs"><?= $fill($community['certifications'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="section-card">
                <h2 class="h6 text-uppercase fw-bold mb-3">Skills</h2>
                <?php foreach ($skills as $index => $item): ?>
                    <div class="mb-3">
                        <label class="form-label">Skill category <?= $index + 1 ?></label>
                        <input class="form-control mb-2" data-preview-target="skill-<?= $index + 1 ?>-label" value="<?= $fill($item['label'] ?? '') ?>" placeholder="Category">
                        <textarea class="form-control" rows="2" data-preview-target="skill-<?= $index + 1 ?>-value" placeholder="Skills list"><?= $fill($item['value'] ?? '') ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-dark btn-lg" type="button" id="print-cv-btn">Print / Save as PDF</button>
                <a class="btn btn-outline-light btn-lg" href="<?= htmlspecialchars(url_path('/upload')) ?>">Back to Upload</a>
            </div>
        </aside>

        <section class="cv-preview-wrap">
            <div class="preview-toolbar no-print">
                <div>
                    <div class="fw-semibold">Live preview</div>
                    <div class="text-muted small">This matches the CV template and is ready for print-to-PDF.</div>
                </div>
                <div class="text-muted small">Imported from your last parsed CV when available.</div>
            </div>

            <article class="cv-page" id="cv-page-1">
                <h1 class="cv-name" id="cv-name"><?= $fill($header['name'] ?? 'Your Name') ?></h1>
                <p class="cv-subtitle" id="cv-subtitle"><?= $fill($header['subtitle'] ?? '') ?></p>
                <div class="cv-rule"></div>
                <div class="cv-contact">
                    <div><strong>Phone:</strong> <span id="cv-phone"><?= $fill($header['phone'] ?? '') ?></span></div>
                    <div style="text-align:center;"><strong>Email:</strong><br><span id="cv-email"><?= $fill($header['email'] ?? '') ?></span></div>
                    <div style="text-align:right;"><strong>Address:</strong> <span id="cv-address"><?= $fill($header['address'] ?? '') ?></span></div>
                </div>

                <h2 class="section-title">Professional Summary</h2>
                <div class="section-rule"></div>
                <p class="cv-copy" id="cv-summary"><?= $fill($profile['summary'] ?? '') ?></p>

                <h2 class="section-title">Education</h2>
                <div class="section-rule"></div>
                <?php foreach ($education as $index => $item): ?>
                    <div class="cv-entry">
                        <div class="cv-entry-head">
                            <div class="cv-entry-title" id="edu-<?= $index + 1 ?>-title"><?= $fill($item['title'] ?? '') ?></div>
                            <div class="cv-entry-date" id="edu-<?= $index + 1 ?>-date"><?= $fill($item['date'] ?? '') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <h2 class="section-title">Professional Experience</h2>
                <div class="section-rule"></div>
                <?php foreach ($experience as $index => $item): ?>
                    <div class="cv-entry">
                        <div class="cv-entry-head">
                            <div class="cv-entry-title" id="exp-<?= $index + 1 ?>-title"><?= $fill($item['title'] ?? '') ?></div>
                            <div class="cv-entry-date" id="exp-<?= $index + 1 ?>-date"><?= $fill($item['date'] ?? '') ?></div>
                        </div>
                        <ul class="cv-bullets" id="exp-<?= $index + 1 ?>-bullets">
                            <?php foreach (($item['bullets'] ?? []) as $bullet): ?>
                                <?php if (trim((string) $bullet) !== ''): ?>
                                    <li><?= htmlspecialchars((string) $bullet) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

                <h2 class="section-title">Academic & Technical Projects</h2>
                <div class="section-rule"></div>
                <?php foreach (array_slice($projects, 0, 3) as $index => $item): ?>
                    <div class="cv-entry">
                        <div class="cv-entry-title" id="proj-<?= $index + 1 ?>-title"><?= $fill($item['title'] ?? '') ?></div>
                        <ul class="cv-bullets" id="proj-<?= $index + 1 ?>-bullets">
                            <?php foreach (($item['bullets'] ?? []) as $bullet): ?>
                                <?php if (trim((string) $bullet) !== ''): ?>
                                    <li><?= htmlspecialchars((string) $bullet) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </article>

            <article class="cv-page" id="cv-page-2">
                <?php foreach (array_slice($projects, 3) as $index => $item): ?>
                    <div class="cv-entry">
                        <div class="cv-entry-title" id="proj-<?= $index + 4 ?>-title"><?= $fill($item['title'] ?? '') ?></div>
                        <ul class="cv-bullets" id="proj-<?= $index + 4 ?>-bullets">
                            <?php foreach (($item['bullets'] ?? []) as $bullet): ?>
                                <?php if (trim((string) $bullet) !== ''): ?>
                                    <li><?= htmlspecialchars((string) $bullet) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

                <h2 class="section-title">Community Involvement</h2>
                <div class="section-rule"></div>
                <div class="cv-community">
                    <p class="cv-entry-title" id="community-org"><?= $fill($community['organization'] ?? '') ?></p>
                    <ul class="cv-bullets">
                        <li id="community-role-1"><?= $fill($roles[0] ?? '') ?></li>
                        <li id="community-role-2"><?= $fill($roles[1] ?? '') ?></li>
                    </ul>
                    <p><strong>Certifications:</strong> <span id="community-certs"><?= $fill($community['certifications'] ?? '') ?></span></p>
                </div>

                <h2 class="section-title">Skills</h2>
                <div class="section-rule"></div>
                <div class="cv-skills">
                    <?php foreach ($skills as $index => $item): ?>
                        <p><strong id="skill-<?= $index + 1 ?>-label"><?= $fill($item['label'] ?? '') ?>:</strong> <span id="skill-<?= $index + 1 ?>-value"><?= $fill($item['value'] ?? '') ?></span></p>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    </div>

    <script>
        (function () {
            const textTargets = document.querySelectorAll('[data-preview-target]');
            const listTargets = document.querySelectorAll('[data-preview-list]');

            function setText(targetId, value) {
                const el = document.getElementById(targetId);
                if (!el) return;
                el.textContent = value || '';
            }

            function setList(targetId, value) {
                const el = document.getElementById(targetId);
                if (!el) return;
                const lines = (value || '')
                    .split(/\r?\n/)
                    .map(line => line.trim())
                    .filter(Boolean);
                el.innerHTML = '';
                lines.forEach((line) => {
                    const li = document.createElement('li');
                    li.textContent = line;
                    el.appendChild(li);
                });
            }

            textTargets.forEach((input) => {
                const targetId = input.getAttribute('data-preview-target');
                const sync = () => setText(targetId, input.value.trim());
                input.addEventListener('input', sync);
                sync();
            });

            listTargets.forEach((input) => {
                const targetId = input.getAttribute('data-preview-list');
                const sync = () => setList(targetId, input.value);
                input.addEventListener('input', sync);
                sync();
            });

            const printBtn = document.getElementById('print-cv-btn');
            if (printBtn) {
                printBtn.addEventListener('click', () => window.print());
            }
        })();
    </script>
</div>
