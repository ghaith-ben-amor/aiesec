<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card hero-card p-4 p-md-5">
            <div class="card-body">
                <h1 class="display-6 fw-bold mb-3">Find the best AIESEC opportunity from your CV</h1>
                <p class="text-secondary mb-4">Upload a PDF CV and we will extract your skills, search opportunities, and rank the best matches.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars(url_path('/upload')) ?>" method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-12">
                        <label for="cv_pdf" class="form-label">PDF CV</label>
                        <input type="file" name="cv_pdf" id="cv_pdf" class="form-control form-control-lg" accept="application/pdf" required>
                    </div>
                    <?php $opts = $filterOptions ?? ['durations'=>[], 'countries'=>[]]; ?>
                    <div class="col-md-6">
                        <label for="duration_filter" class="form-label">Duration (filter)</label>
                        <select id="duration_filter" name="duration_filter" class="form-select">
                            <option value="">All durations</option>
                            <?php foreach ($opts['durations'] as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="country_filter" class="form-label">Country (filter)</label>
                        <select id="country_filter" name="country_filter" class="form-select">
                            <option value="">All countries</option>
                            <?php foreach ($opts['countries'] as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg px-4">Analyze CV</button>
                    </div>
                </form>

                <div class="mt-4 small text-secondary">
                    <strong>Supported:</strong> PDF only, up to <?= number_format(($config['max_upload_size'] ?? 0) / 1024 / 1024, 0) ?> MB.
                </div>
            </div>
        </div>
    </div>
</div>
